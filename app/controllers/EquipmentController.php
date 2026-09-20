<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\AuditLog;

class EquipmentController {
    private Equipment $equipmentModel;
    private Category $categoryModel;
    private AuditLog $auditLog;

    public function __construct() {
        AuthMiddleware::handle();
        $this->equipmentModel = new Equipment();
        $this->categoryModel = new Category();
        $this->auditLog = new AuditLog();
    }

    public function index(): void {
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
        $searchName = isset($_GET['name']) ? trim((string)$_GET['name']) : '';

        $filters = [];
        if ($categoryId !== null) {
            $filters['category_id'] = $categoryId;
        }
        if ($searchName !== '') {
            $filters['name'] = $searchName;
        }

        $equipment = $this->equipmentModel->filter($filters);
        $categories = $this->categoryModel->all();

        view('equipment.index', [
            'equipmentList' => $equipment,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
            'searchName' => $searchName
        ]);
    }

    public function create(): void {
        $categories = $this->categoryModel->all();
        view('equipment.create', ['categories' => $categories]);
    }

    public function store(): void {
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/equipment/create');
        }

        $errors = validate($_POST, [
            'name' => 'required|min:3',
            'serial_number' => 'required',
            'category_id' => 'required',
            'room_location' => 'required'
        ]);

        $serialNumber = trim($_POST['serial_number'] ?? '');
        $existingEquipment = $this->equipmentModel->findBySerialNumber($serialNumber);
        if ($existingEquipment) {
            $errors['serial_number'] = ["Duplicate Error: Serial number '{$serialNumber}' is already registered for equipment '{$existingEquipment['name']}'."];
        }

        if (!empty($errors)) {
            $categories = $this->categoryModel->all();
            set_flash('danger', !empty($errors['serial_number']) ? $errors['serial_number'][0] : 'Please correct the highlighted form errors.');
            view('equipment.create', [
                'errors' => $errors,
                'old' => $_POST,
                'categories' => $categories
            ]);
            return;
        }

        $eqId = $this->equipmentModel->create([
            'name' => trim($_POST['name']),
            'serial_number' => $serialNumber,
            'category_id' => (int)$_POST['category_id'],
            'room_location' => trim($_POST['room_location']),
            'status' => $_POST['status'] ?? EQUIPMENT_ACTIVE
        ]);

        if ($eqId) {
            $this->auditLog->log('Created Equipment', 'Equipment', $eqId, "Added equipment: {$_POST['name']}");
            set_flash('success', 'Equipment unit registered successfully.');
            redirect('/equipment');
        } else {
            set_flash('danger', 'Failed to register equipment unit. Please check your data and try again.');
            redirect('/equipment/create');
        }
    }

    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $equipment = $this->equipmentModel->findById($id);
        if (!$equipment) {
            set_flash('danger', 'Equipment unit not found.');
            redirect('/equipment');
        }
        $categories = $this->categoryModel->all();
        view('equipment.edit', [
            'equipment' => $equipment,
            'categories' => $categories
        ]);
    }

    public function update(): void {
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/equipment');
        }

        $id = (int)($_POST['id'] ?? 0);
        $errors = validate($_POST, [
            'name' => 'required|min:3',
            'serial_number' => 'required',
            'category_id' => 'required',
            'room_location' => 'required'
        ]);

        $serialNumber = trim($_POST['serial_number'] ?? '');
        $existingEquipment = $this->equipmentModel->findBySerialNumber($serialNumber, $id);
        if ($existingEquipment) {
            $errors['serial_number'] = ["Duplicate Error: Serial number '{$serialNumber}' is already in use by equipment '{$existingEquipment['name']}'."];
        }

        if (!empty($errors)) {
            $equipment = $this->equipmentModel->findById($id);
            $categories = $this->categoryModel->all();
            set_flash('danger', !empty($errors['serial_number']) ? $errors['serial_number'][0] : 'Please correct the highlighted form errors.');
            view('equipment.edit', [
                'errors' => $errors,
                'equipment' => array_merge($equipment ?? [], $_POST),
                'categories' => $categories
            ]);
            return;
        }

        $updated = $this->equipmentModel->update($id, [
            'name' => trim($_POST['name']),
            'serial_number' => $serialNumber,
            'category_id' => (int)$_POST['category_id'],
            'room_location' => trim($_POST['room_location']),
            'status' => $_POST['status'] ?? EQUIPMENT_ACTIVE
        ]);

        if ($updated) {
            $this->auditLog->log('Updated Equipment', 'Equipment', $id, "Updated equipment #{$id}");
            set_flash('success', 'Equipment record updated successfully.');
            redirect('/equipment');
        } else {
            set_flash('danger', 'Failed to update equipment record. Please check the values and try again.');
            redirect('/equipment/edit?id=' . $id);
        }
    }

    public function delete(): void {
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/equipment');
        }
        $id = (int)($_POST['id'] ?? 0);
        $this->equipmentModel->delete($id);
        $this->auditLog->log('Deleted Equipment', 'Equipment', $id, "Deleted equipment #{$id}");
        set_flash('info', 'Equipment record deleted.');
        redirect('/equipment');
    }
}
