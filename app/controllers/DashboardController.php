<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Inspection;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\Report;
use App\Models\AuditLog;

class DashboardController {
    private Inspection $inspectionModel;
    private Equipment $equipmentModel;
    private Category $categoryModel;
    private Report $reportModel;
    private AuditLog $auditLog;

    public function __construct() {
        AuthMiddleware::handle();
        $this->inspectionModel = new Inspection();
        $this->equipmentModel = new Equipment();
        $this->categoryModel = new Category();
        $this->reportModel = new Report();
        $this->auditLog = new AuditLog();
    }

    public function index(): void {
        $inspections = $this->inspectionModel->all();
        $equipments = $this->equipmentModel->all();
        $categories = $this->categoryModel->all();
        $reports = $this->reportModel->all();
        $recentLogs = $this->auditLog->getRecent(6);

        $totalInspections = count($inspections);
        $completedInspections = count(array_filter($inspections, fn($i) => $i['status'] === STATUS_COMPLETED));
        $pendingInspections = count(array_filter($inspections, fn($i) => $i['status'] === STATUS_PENDING || $i['status'] === STATUS_IN_PROGRESS));
        $totalEquipment = count($equipments);
        $activeEquipment = count(array_filter($equipments, fn($e) => $e['status'] === EQUIPMENT_ACTIVE));

        // Average score calculation
        $scores = array_map(fn($r) => (float)$r['score'], $reports);
        $avgScore = !empty($scores) ? round(array_sum($scores) / count($scores), 1) : 100.0;

        view('dashboard.index', [
            'totalInspections' => $totalInspections,
            'completedInspections' => $completedInspections,
            'pendingInspections' => $pendingInspections,
            'totalEquipment' => $totalEquipment,
            'activeEquipment' => $activeEquipment,
            'avgScore' => $avgScore,
            'recentInspections' => array_slice($inspections, 0, 5),
            'categories' => $categories,
            'recentLogs' => $recentLogs
        ]);
    }
}
