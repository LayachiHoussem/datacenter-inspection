<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Inspection;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\InspectionItem;
use App\Models\InspectionResult;
use App\Models\ReportPhoto;
use App\Models\AuditLog;
use App\Services\FileUploadService;
use App\Services\ReportService;

class InspectionController {
    private Inspection $inspectionModel;
    private Category $categoryModel;
    private Equipment $equipmentModel;
    private InspectionItem $itemModel;
    private InspectionResult $resultModel;
    private ReportPhoto $photoModel;
    private AuditLog $auditLog;

    public function __construct() {
        AuthMiddleware::handle();
        $this->inspectionModel = new Inspection();
        $this->categoryModel = new Category();
        $this->equipmentModel = new Equipment();
        $this->itemModel = new InspectionItem();
        $this->resultModel = new InspectionResult();
        $this->photoModel = new ReportPhoto();
        $this->auditLog = new AuditLog();
    }

    public function index(): void {
        $inspections = $this->inspectionModel->all();
        view('inspections.index', ['inspections' => $inspections]);
    }

    public function create(): void {
        $categories = $this->categoryModel->all();
        $equipments = $this->equipmentModel->all();
        
        $db = \App\Config\Database::getInstance()->getConnection();
        $devices = [];
        if ($db) {
            $sql = "SELECT e.*, c.name as category_name, c.icon as category_icon 
                    FROM equipment e 
                    LEFT JOIN categories c ON e.category_id = c.id 
                    ORDER BY e.id ASC";
            $devices = $db->query($sql)->fetchAll();
        }

        view('inspections.create', [
            'categories' => $categories,
            'equipments' => $equipments,
            'devices' => $devices
        ]);
    }

    public function store(): void {
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF validation failed.');
            redirect('/inspections/create');
        }

        $user = auth_user();
        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            $title = 'Datacenter Inspection - ' . date('Y-m-d H:i');
        }

        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : 1;
        $equipmentId = !empty($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;
        $status = !empty($_POST['status']) ? trim($_POST['status']) : STATUS_COMPLETED;
        $overallNotes = trim($_POST['overall_notes'] ?? $_POST['notes'] ?? '');

        $inspectionId = $this->inspectionModel->create([
            'title' => $title,
            'user_id' => $user['id'],
            'category_id' => $categoryId,
            'equipment_id' => $equipmentId,
            'status' => $status,
            'notes' => $overallNotes
        ]);

        if ($inspectionId) {
            // Save item checklist results from table
            $itemResults = $_POST['items'] ?? [];
            if (!empty($itemResults)) {
                $this->resultModel->saveResults($inspectionId, $itemResults);
            }

            // Upload per-item device photos uploaded directly on table rows
            if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
                $uploadService = new FileUploadService();
                foreach ($_FILES['photos']['name'] as $itemId => $fileName) {
                    if (!empty($fileName) && isset($_FILES['photos']['error'][$itemId]) && $_FILES['photos']['error'][$itemId] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['photos']['name'][$itemId],
                            'type' => $_FILES['photos']['type'][$itemId],
                            'tmp_name' => $_FILES['photos']['tmp_name'][$itemId],
                            'error' => $_FILES['photos']['error'][$itemId],
                            'size' => $_FILES['photos']['size'][$itemId]
                        ];
                        $upResult = $uploadService->upload($file);
                        if ($upResult['success']) {
                            $caption = sanitize($_POST['items'][$itemId]['notes'] ?? 'Device Evidence Photo');
                            $this->photoModel->create([
                                'inspection_id' => $inspectionId,
                                'item_id' => $itemId,
                                'file_path' => $upResult['file_path'],
                                'caption' => !empty($caption) ? $caption : 'Device Evidence Photo'
                            ]);
                        }
                    }
                }
            }

            // Upload overall Photo Evidence if attached
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadService = $uploadService ?? new FileUploadService();
                $upResult = $uploadService->upload($_FILES['photo']);
                if ($upResult['success']) {
                    $this->photoModel->create([
                        'inspection_id' => $inspectionId,
                        'file_path' => $upResult['file_path'],
                        'caption' => sanitize($_POST['photo_caption'] ?? 'Inspection Photo Evidence')
                    ]);
                }
            }

            if ($equipmentId) {
                $this->equipmentModel->updateLastInspected($equipmentId);
            }

            // Generate report record (fast database sync; PDF rendered on-demand)
            $reportService = new ReportService();
            $reportService->generateReport($inspectionId, $user['id'], false);

            $this->auditLog->log('Completed Device Inspection Table', 'Inspection', $inspectionId, "Submitted inspection: {$title}");
            set_flash('success', 'Inspection submitted and report generated successfully!');
            redirect("/inspections/result?id={$inspectionId}");
        } else {
            set_flash('danger', 'Failed to save inspection.');
            redirect('/inspections/create');
        }
    }

    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $inspection = $this->inspectionModel->findById($id);
        if (!$inspection) {
            set_flash('danger', 'Inspection record not found.');
            redirect('/inspections');
        }

        $results = $this->resultModel->getByInspection($id);
        $photos = $this->photoModel->getByInspection($id);

        view('inspections.show', [
            'inspection' => $inspection,
            'results' => $results,
            'photos' => $photos
        ]);
    }

    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $inspection = $this->inspectionModel->findById($id);
        if (!$inspection) {
            set_flash('danger', 'Inspection not found.');
            redirect('/inspections');
        }

        // Load same equipment devices as the create form
        $db = \App\Config\Database::getInstance()->getConnection();
        $devices = [];
        if ($db) {
            $sql = "SELECT e.*, c.name as category_name, c.icon as category_icon 
                    FROM equipment e 
                    LEFT JOIN categories c ON e.category_id = c.id 
                    ORDER BY e.id ASC";
            $devices = $db->query($sql)->fetchAll();
        }

        // Load existing results keyed by equipment_id for pre-filling
        $resultsRaw = $this->resultModel->getByInspection($id);
        $results = [];
        foreach ($resultsRaw as $res) {
            $key = $res['equipment_id'] ?? $res['item_id'];
            $results[$key] = $res;
        }

        // Load existing photos keyed by item_id (equipment_id)
        $photosRaw = $this->photoModel->getByInspection($id);
        $photos = [];
        foreach ($photosRaw as $p) {
            if (!empty($p['item_id'])) {
                $photos[$p['item_id']] = $p;
            }
        }

        view('inspections.edit', [
            'inspection' => $inspection,
            'devices' => $devices,
            'results' => $results,
            'photos' => $photos
        ]);
    }

    public function updateResults(): void {
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/inspections');
        }

        $id = (int)($_POST['inspection_id'] ?? 0);
        $inspection = $this->inspectionModel->findById($id);
        if (!$inspection) {
            set_flash('danger', 'Inspection not found.');
            redirect('/inspections');
        }

        $title = trim($_POST['title'] ?? '');
        $itemResults = $_POST['items'] ?? [];
        $status = $_POST['status'] ?? STATUS_COMPLETED;
        $notes = trim($_POST['overall_notes'] ?? '');

        // Update inspection record (title, notes, status)
        $updateData = ['status' => $status, 'notes' => $notes];
        if (!empty($title)) {
            $updateData['title'] = $title;
        }
        $this->inspectionModel->update($id, $updateData);

        // Save Item Checklist Results
        $this->resultModel->saveResults($id, $itemResults);

        // Upload per-device photos from table rows
        if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
            $uploadService = new FileUploadService();
            foreach ($_FILES['photos']['name'] as $itemId => $fileName) {
                if (!empty($fileName) && isset($_FILES['photos']['error'][$itemId]) && $_FILES['photos']['error'][$itemId] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['photos']['name'][$itemId],
                        'type' => $_FILES['photos']['type'][$itemId],
                        'tmp_name' => $_FILES['photos']['tmp_name'][$itemId],
                        'error' => $_FILES['photos']['error'][$itemId],
                        'size' => $_FILES['photos']['size'][$itemId]
                    ];
                    $upResult = $uploadService->upload($file);
                    if ($upResult['success']) {
                        $caption = sanitize($_POST['items'][$itemId]['notes'] ?? 'Device Evidence Photo');
                        $this->photoModel->create([
                            'inspection_id' => $id,
                            'item_id' => $itemId,
                            'file_path' => $upResult['file_path'],
                            'caption' => !empty($caption) ? $caption : 'Device Evidence Photo'
                        ]);
                    }
                }
            }
        }

        if ($inspection['equipment_id']) {
            $this->equipmentModel->updateLastInspected($inspection['equipment_id']);
        }

        // Auto Sync Report record if completed (PDF compiled on demand when viewing/downloading)
        if ($status === STATUS_COMPLETED) {
            $user = auth_user();
            $reportService = new ReportService();
            $reportService->generateReport($id, $user['id'], false);
        }

        $this->auditLog->log('Updated Inspection Checklist', 'Inspection', $id, "Updated results for inspection #{$id}");
        set_flash('success', 'Inspection updated successfully!');
        redirect("/inspections/result?id={$id}");
    }

    public function result(): void {
        $id = (int)($_GET['id'] ?? 0);
        $inspection = $this->inspectionModel->findById($id);
        if (!$inspection) {
            set_flash('danger', 'Inspection not found.');
            redirect('/inspections');
        }

        $results = $this->resultModel->getByInspection($id);
        $photos = $this->photoModel->getByInspection($id);

        view('inspections.result', [
            'inspection' => $inspection,
            'results' => $results,
            'photos' => $photos
        ]);
    }

    public function delete(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash('danger', 'Invalid inspection ID.');
            redirect('/inspections');
            return;
        }

        $inspection = $this->inspectionModel->findById($id);
        if (!$inspection) {
            set_flash('danger', 'Inspection not found.');
            redirect('/inspections');
            return;
        }

        $deleted = $this->inspectionModel->delete($id);
        if ($deleted) {
            $this->auditLog->log('Deleted Inspection', 'Inspection', $id, "Deleted inspection: {$inspection['title']} ({$inspection['reference_code']})");
            set_flash('success', 'Inspection deleted successfully.');
        } else {
            set_flash('danger', 'Failed to delete inspection.');
        }

        redirect('/inspections');
    }

    /**
     * Dispatch inspection PDF report via email
     */
    public function sendReportEmail(): void {
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/inspections');
        }

        $id = (int)($_POST['inspection_id'] ?? 0);
        $inspection = $this->inspectionModel->findById($id);
        if (!$inspection) {
            set_flash('danger', 'Inspection not found.');
            redirect('/inspections');
        }

        $recipients = trim($_POST['recipients'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $attachPdf = isset($_POST['attach_pdf']) && $_POST['attach_pdf'] === '1';

        if (empty($recipients)) {
            set_flash('danger', 'Please enter at least one recipient email address.');
            redirect("/inspections/show?id={$id}");
        }

        $results = $this->resultModel->getByInspection($id);
        $photos = $this->photoModel->getByInspection($id);

        // Find or build report summary data
        $reportModel = new \App\Models\Report();
        $report = $reportModel->findByInspection($id);
        if (!$report) {
            $report = [
                'id' => 0,
                'summary' => $inspection['notes'] ?? 'Inspection report',
                'score' => 100.0,
                'status' => 'approved',
                'created_at' => $inspection['created_at']
            ];
        }

        $pdfBinary = null;
        if ($attachPdf) {
            $pdfService = new \App\Services\PdfService();
            $html = $pdfService->renderHtmlReport($inspection, $results, $photos, $report);
            $pdfBinary = $pdfService->renderPdfBinary($html);
        }

        $mailService = new \App\Services\MailService();
        $mailResult = $mailService->sendInspectionReportEmail($inspection, $report, $recipients, $notes, $pdfBinary);

        if ($mailResult['success']) {
            $this->auditLog->log('Emailed Inspection Report', 'Inspection', $id, "Emailed report {$inspection['reference_code']} to {$recipients}");
            set_flash('success', "Inspection report successfully sent to {$recipients}!");
        } else {
            $this->auditLog->log('Email Inspection Report Failed', 'Inspection', $id, "Failed to email report {$inspection['reference_code']}: {$mailResult['message']}");
            set_flash('danger', "Failed to send email: " . htmlspecialchars($mailResult['message']));
        }

        $returnUrl = !empty($_POST['return_url']) ? $_POST['return_url'] : "/inspections/show?id={$id}";
        redirect($returnUrl);
    }
}

