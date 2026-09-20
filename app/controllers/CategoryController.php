<?php
namespace App\Controllers;

use App\Middleware\AdminMiddleware;
use App\Models\Category;
use App\Models\InspectionItem;
use App\Models\AuditLog;

class CategoryController {
    private Category $categoryModel;
    private InspectionItem $itemModel;
    private AuditLog $auditLog;

    public function __construct() {
        AdminMiddleware::handle('categories', 'read');
        $this->categoryModel = new Category();
        $this->itemModel = new InspectionItem();
        $this->auditLog = new AuditLog();
    }

    public function index(): void {
        $categories = $this->categoryModel->all();
        view('categories.index', ['categories' => $categories]);
    }

    public function create(): void {
        AdminMiddleware::handle('categories', 'write');
        view('categories.create');
    }

    public function store(): void {
        AdminMiddleware::handle('categories', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/categories/create');
        }

        $errors = validate($_POST, [
            'name' => 'required|min:3'
        ]);

        if (!empty($errors)) {
            view('categories.create', ['errors' => $errors, 'old' => $_POST]);
            return;
        }

        $catId = $this->categoryModel->create([
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'icon' => trim($_POST['icon'] ?? 'fa-list-check')
        ]);

        if ($catId) {
            $this->auditLog->log('Created Category', 'Category', $catId, "Created category: {$_POST['name']}");
            set_flash('success', 'Category created successfully.');
            redirect('/categories');
        } else {
            set_flash('danger', 'Failed to create category.');
            redirect('/categories/create');
        }
    }

    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $category = $this->categoryModel->findById($id);
        if (!$category) {
            set_flash('danger', 'Category not found.');
            redirect('/categories');
        }
        $items = $this->itemModel->getByCategory($id);
        view('categories.edit', ['category' => $category, 'items' => $items]);
    }

    public function update(): void {
        AdminMiddleware::handle('categories', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/categories');
        }

        $id = (int)($_POST['id'] ?? 0);
        $errors = validate($_POST, [
            'name' => 'required|min:3'
        ]);

        if (!empty($errors)) {
            $category = $this->categoryModel->findById($id);
            $items = $this->itemModel->getByCategory($id);
            view('categories.edit', ['errors' => $errors, 'category' => array_merge($category ?? [], $_POST), 'items' => $items]);
            return;
        }

        $this->categoryModel->update($id, [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'icon' => trim($_POST['icon'] ?? 'fa-list-check')
        ]);

        $this->auditLog->log('Updated Category', 'Category', $id, "Updated category #{$id}");
        set_flash('success', 'Category updated successfully.');
        redirect('/categories');
    }

    public function storeItem(): void {
        AdminMiddleware::handle('categories', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF validation failed.');
            redirect('/categories');
        }
        $catId = (int)($_POST['category_id'] ?? 0);
        if (!empty($_POST['title'])) {
            $this->itemModel->create([
                'category_id' => $catId,
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description'] ?? ''),
                'is_critical' => isset($_POST['is_critical']) ? 1 : 0
            ]);
            $this->auditLog->log('Created Checklist Item', 'Category', $catId, "Added checklist item to category #{$catId}");
            set_flash('success', 'Checklist item added.');
        }
        redirect("/categories/edit?id={$catId}");
    }

    public function delete(): void {
        AdminMiddleware::handle('categories', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/categories');
        }
        $id = (int)($_POST['id'] ?? 0);
        $this->categoryModel->delete($id);
        $this->auditLog->log('Deleted Category', 'Category', $id, "Deleted category #{$id}");
        set_flash('info', 'Category deleted.');
        redirect('/categories');
    }
}
