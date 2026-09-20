<?php
namespace App\Services;

use App\Models\Inspection;
use App\Models\InspectionResult;
use App\Models\ReportPhoto;
use App\Models\Report;

class ReportService {
    private Inspection $inspectionModel;
    private InspectionResult $resultModel;
    private Report $reportModel;
    private ReportPhoto $photoModel;
    private PdfService $pdfService;

    public function __construct() {
        $this->inspectionModel = new Inspection();
        $this->resultModel = new InspectionResult();
        $this->reportModel = new Report();
        $this->photoModel = new ReportPhoto();
        $this->pdfService = new PdfService();
    }

    public function generateReport(int $inspectionId, int $generatedBy, bool $generatePdf = false): array {
        $inspection = $this->inspectionModel->findById($inspectionId);
        if (!$inspection) {
            return ['success' => false, 'error' => 'Inspection not found.'];
        }

        $results = $this->resultModel->getByInspection($inspectionId);
        $totalItems = count($results);

        if ($totalItems === 0) {
            $score = 100.00;
        } else {
            $passed = 0;
            $warnings = 0;
            $failed = 0;

            foreach ($results as $res) {
                if ($res['result_status'] === RESULT_PASS) {
                    $passed++;
                } elseif ($res['result_status'] === RESULT_WARNING) {
                    $warnings++;
                } elseif ($res['result_status'] === RESULT_FAIL) {
                    $failed++;
                }
            }

            // Simple score formula: (Pass*100 + Warning*50) / Total
            $score = round((($passed * 100) + ($warnings * 50)) / $totalItems, 2);
        }

        $summary = sprintf(
            "Inspection [%s] evaluation complete. Score: %.2f%%. Status: %s. Notes: %s",
            $inspection['reference_code'],
            $score,
            strtoupper($inspection['status']),
            $inspection['notes'] ?? 'None'
        );

        $relativePath = "reports/pdf/{$inspection['reference_code']}.pdf";
        $existingReport = $this->reportModel->findByInspection($inspectionId);

        if ($existingReport) {
            $this->reportModel->update((int)$existingReport['id'], [
                'generated_by' => $generatedBy,
                'summary' => $summary,
                'score' => $score,
                'file_path' => $relativePath
            ]);
            $reportId = (int)$existingReport['id'];
        } else {
            $reportId = $this->reportModel->create([
                'inspection_id' => $inspectionId,
                'generated_by' => $generatedBy,
                'summary' => $summary,
                'score' => $score,
                'file_path' => $relativePath
            ]);
        }

        $saved = false;
        // Compile binary PDF to disk only if explicitly requested (e.g. download or offline archiving)
        if ($generatePdf) {
            $photos = $this->photoModel->getByInspection($inspectionId);
            $reportData = [
                'id' => $reportId,
                'inspection_id' => $inspectionId,
                'generated_by' => $generatedBy,
                'summary' => $summary,
                'score' => $score,
                'file_path' => $relativePath,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $html = $this->pdfService->renderHtmlReport($inspection, $results, $photos, $reportData);
            $storageDir = dirname(__DIR__, 2) . '/storage/reports/pdf';
            $storageFile = $storageDir . '/' . $inspection['reference_code'] . '.pdf';
            $saved = $this->pdfService->savePdf($html, $storageFile);
        }

        return [
            'success' => true,
            'report_id' => $reportId,
            'score' => $score,
            'summary' => $summary,
            'file_path' => $relativePath,
            'file_saved' => $saved
        ];
    }

    /**
     * Helper to explicitly compile and store the PDF file on demand.
     */
    public function generatePdfForInspection(int $inspectionId): ?string {
        $inspection = $this->inspectionModel->findById($inspectionId);
        if (!$inspection) return null;

        $results = $this->resultModel->getByInspection($inspectionId);
        $photos = $this->photoModel->getByInspection($inspectionId);
        $report = $this->reportModel->findByInspection($inspectionId);

        $html = $this->pdfService->renderHtmlReport($inspection, $results, $photos, $report);

        $storageDir = dirname(__DIR__, 2) . '/storage/reports/pdf';
        $storageFile = $storageDir . '/' . $inspection['reference_code'] . '.pdf';
        $saved = $this->pdfService->savePdf($html, $storageFile);

        return $saved ? $storageFile : null;
    }
}

