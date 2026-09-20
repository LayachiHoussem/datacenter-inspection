<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Report;
use App\Models\Inspection;
use App\Models\InspectionResult;
use App\Models\ReportPhoto;
use App\Services\PdfService;

class ReportController {
    private Report $reportModel;
    private Inspection $inspectionModel;
    private InspectionResult $resultModel;
    private ReportPhoto $photoModel;
    private PdfService $pdfService;

    public function __construct() {
        AuthMiddleware::handle();
        $this->reportModel = new Report();
        $this->inspectionModel = new Inspection();
        $this->resultModel = new InspectionResult();
        $this->photoModel = new ReportPhoto();
        $this->pdfService = new PdfService();
    }

    public function index(): void {
        $reports = $this->reportModel->all();
        view('reports.index', ['reports' => $reports]);
    }

    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $report = $this->reportModel->findById($id);
        if (!$report) {
            set_flash('danger', 'Report record not found.');
            redirect('/reports');
        }

        $inspection = $this->inspectionModel->findById($report['inspection_id']);
        $results = $this->resultModel->getByInspection($report['inspection_id']);
        $photos = $this->photoModel->getByInspection($report['inspection_id']);

        if (isset($_GET['print'])) {
            echo $this->pdfService->renderHtmlReport($inspection, $results, $photos, $report);
            exit;
        }
        if (isset($_GET['download'])) {
            $refCode = $inspection['reference_code'] ?? 'RPT-' . $id;
            $filename = 'report_' . $refCode . '.pdf';

            $storageDir = dirname(__DIR__, 2) . '/storage/reports/pdf';
            $storageFile = $storageDir . '/' . $refCode . '.pdf';

            $html = $this->pdfService->renderHtmlReport($inspection, $results, $photos, $report);

            // Render and download (saves copy to storageFile automatically)
            $this->pdfService->downloadPdf($html, $filename, $storageFile, true);
            exit;
        }

        view('reports.show', [
            'report' => $report,
            'inspection' => $inspection,
            'results' => $results,
            'photos' => $photos
        ]);
    }

    /**
     * Send inspection report PDF via email directly from reports view
     */
    public function sendReportEmail(): void {
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/reports');
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $report = $this->reportModel->findById($reportId);
        if (!$report) {
            set_flash('danger', 'Report record not found.');
            redirect('/reports');
        }

        $inspection = $this->inspectionModel->findById($report['inspection_id']);
        if (!$inspection) {
            set_flash('danger', 'Associated inspection record not found.');
            redirect('/reports');
        }

        $recipients = trim($_POST['recipients'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $attachPdf = isset($_POST['attach_pdf']) && $_POST['attach_pdf'] === '1';

        if (empty($recipients)) {
            set_flash('danger', 'Please provide at least one recipient email address.');
            redirect("/reports/show?id={$reportId}");
        }

        $results = $this->resultModel->getByInspection($report['inspection_id']);
        $photos = $this->photoModel->getByInspection($report['inspection_id']);

        $pdfBinary = null;
        if ($attachPdf) {
            $html = $this->pdfService->renderHtmlReport($inspection, $results, $photos, $report);
            $pdfBinary = $this->pdfService->renderPdfBinary($html);
        }

        $mailService = new \App\Services\MailService();
        $mailResult = $mailService->sendInspectionReportEmail($inspection, $report, $recipients, $notes, $pdfBinary);

        $auditLog = new \App\Models\AuditLog();
        if ($mailResult['success']) {
            $auditLog->log('Emailed Report', 'Report', $reportId, "Emailed report {$inspection['reference_code']} to {$recipients}");
            set_flash('success', "Report successfully dispatched to {$recipients}!");
        } else {
            $auditLog->log('Email Report Failed', 'Report', $reportId, "Failed to email report {$inspection['reference_code']}: {$mailResult['message']}");
            set_flash('danger', "Failed to dispatch email: " . htmlspecialchars($mailResult['message']));
        }

        redirect("/reports/show?id={$reportId}");
    }
}
