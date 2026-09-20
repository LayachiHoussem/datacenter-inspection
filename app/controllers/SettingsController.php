<?php
namespace App\Controllers;

use App\Middleware\AdminMiddleware;
use App\Models\AuditLog;
use App\Services\PdfService;
use App\Services\MailService;
use App\Services\EnterpriseService;
use App\Config\Constants;

class SettingsController {
    private AuditLog $auditLog;
    private PdfService $pdfService;
    private MailService $mailService;
    private EnterpriseService $enterpriseService;

    public function __construct() {
        AdminMiddleware::handle('settings', 'read');
        $this->auditLog = new AuditLog();
        $this->pdfService = new PdfService();
        $this->mailService = new MailService();
        $this->enterpriseService = new EnterpriseService();
    }

    public function index(): void {
        $logs = $this->auditLog->getRecent(50);
        $config = require __DIR__ . '/../config/config.php';
        $constants = Constants::getAll();
        $groupedConstants = Constants::getGrouped();
        $categories = Constants::getCategories();
        $mailSettings = $this->mailService->getSettings();
        $enterpriseSettings = $this->enterpriseService->getSettings();

        view('settings.index', [
            'logs' => $logs,
            'config' => $config,
            'constants' => $constants,
            'groupedConstants' => $groupedConstants,
            'categories' => $categories,
            'mailSettings' => $mailSettings,
            'enterpriseSettings' => $enterpriseSettings
        ]);
    }

    public function update(): void {
        AdminMiddleware::handle('settings', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/settings');
        }

        $this->auditLog->log('Updated System Settings', 'Settings', null, "System parameters updated");
        set_flash('success', 'System settings saved successfully.');
        redirect('/settings');
    }

    /**
     * Create a new dynamic constant
     */
    public function storeConstant(): void {
        AdminMiddleware::handle('settings', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/settings');
        }

        $name = trim($_POST['name'] ?? '');
        $value = $_POST['value'] ?? null;
        $type = trim($_POST['type'] ?? 'string');
        $category = trim($_POST['category'] ?? 'Custom');
        $comment = trim($_POST['comment'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];

        if (empty($name)) {
            set_flash('danger', 'Name / Title is required.');
            redirect('/settings');
        }

        $result = Constants::add($name, $value, $category, $comment, $type, $permissions, $color);

        if ($result['success']) {
            $this->auditLog->log('Created Status/Role', 'Constants', null, "Added item [{$name}] in category [{$category}]");
            set_flash('success', $result['message']);
        } else {
            set_flash('danger', $result['message']);
        }

        redirect('/settings');
    }

    /**
     * Update an existing dynamic constant
     */
    public function updateConstant(): void {
        AdminMiddleware::handle('settings', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/settings');
        }

        $originalName = trim($_POST['original_name'] ?? '');
        $newName = trim($_POST['new_name'] ?? $originalName);
        $value = $_POST['value'] ?? null;
        $type = trim($_POST['type'] ?? 'string');
        $category = trim($_POST['category'] ?? 'Custom');
        $comment = trim($_POST['comment'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];

        if (empty($originalName)) {
            set_flash('danger', 'Original item identifier missing.');
            redirect('/settings');
        }

        $result = Constants::update($originalName, $newName, $value, $category, $comment, $type, $permissions, $color);

        if ($result['success']) {
            $this->auditLog->log('Updated Status/Role', 'Constants', null, "Modified item [{$originalName}] -> [{$newName}]");
            set_flash('success', $result['message']);
        } else {
            set_flash('danger', $result['message']);
        }

        redirect('/settings');
    }

    /**
     * Delete a dynamic constant
     */
    public function deleteConstant(): void {
        AdminMiddleware::handle('settings', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/settings');
        }

        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            set_flash('danger', 'Constant name is required.');
            redirect('/settings');
        }

        $result = Constants::delete($name);

        if ($result['success']) {
            $this->auditLog->log('Deleted Constant', 'Constants', null, "Deleted dynamic constant [{$name}]");
            set_flash('info', $result['message']);
        } else {
            set_flash('danger', $result['message']);
        }

        redirect('/settings');
    }

    /**
     * Update SMTP Mail configuration
     */
     public function updateMail(): void {
        AdminMiddleware::handle('settings', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/settings?tab=mail-pane');
        }

        $data = [
            'smtp_enabled' => isset($_POST['smtp_enabled']) && $_POST['smtp_enabled'] === '1',
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
            'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
            'smtp_user' => trim($_POST['smtp_user'] ?? ''),
            'smtp_pass' => (string)($_POST['smtp_pass'] ?? ''),
            'from_email' => trim($_POST['from_email'] ?? ''),
            'from_name' => trim($_POST['from_name'] ?? 'Datacenter Inspection System'),
            'default_recipients' => trim($_POST['default_recipients'] ?? ''),
        ];

        // If password is left blank and a previous password exists, preserve it
        if (empty($data['smtp_pass'])) {
            $existing = $this->mailService->getSettings();
            $data['smtp_pass'] = $existing['smtp_pass'] ?? '';
        }

        $success = $this->mailService->saveSettings($data);
        if ($success) {
            $this->auditLog->log('Updated SMTP Settings', 'Settings', null, "Updated SMTP host: {$data['smtp_host']}:{$data['smtp_port']} ({$data['smtp_encryption']})");
            set_flash('success', 'SMTP Mail server configuration updated successfully.');
        } else {
            set_flash('danger', 'Failed to save SMTP mail settings to storage.');
        }

        redirect('/settings?tab=mail-pane');
    }

    /**
     * Send a test email to verify SMTP connection
     */
    public function testMail(): void {
        AdminMiddleware::handle('settings', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/settings?tab=mail-pane');
        }

        $testEmail = trim($_POST['test_email'] ?? '');
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            set_flash('danger', 'Please provide a valid test email address.');
            redirect('/settings?tab=mail-pane');
        }

        $result = $this->mailService->testConnection($testEmail);
        if ($result['success']) {
            $this->auditLog->log('Tested SMTP Connection', 'Settings', null, "Sent test email to {$testEmail} (Success)");
            set_flash('success', "Test email successfully sent to {$testEmail}!");
        } else {
            $this->auditLog->log('Tested SMTP Connection Failed', 'Settings', null, "Failed test email to {$testEmail}: {$result['message']}");
            set_flash('danger', "SMTP Test Failed: " . htmlspecialchars($result['message']));
        }

        redirect('/settings?tab=mail-pane');
    }

    /**
     * Update Enterprise Profile information and optional logo upload
     */
    public function updateEnterprise(): void {
        AdminMiddleware::handle('settings', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/settings?tab=enterprise-pane');
        }

        $logoFile = isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['logo'] : null;
        $result = $this->enterpriseService->saveSettings($_POST, $logoFile);

        if ($result['success']) {
            $name = trim($_POST['name'] ?? 'Enterprise');
            $site = trim($_POST['site'] ?? '');
            $this->auditLog->log('Updated Enterprise Profile', 'Settings', null, "Updated company profile: [{$name}] site: [{$site}]");
            set_flash('success', $result['message']);
        } else {
            set_flash('danger', $result['message']);
        }

        redirect('/settings?tab=enterprise-pane');
    }

    /**
     * Reset Enterprise logo to system default
     */
    public function resetEnterpriseLogo(): void {
        AdminMiddleware::handle('settings', 'write');
        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/settings?tab=enterprise-pane');
        }

        $result = $this->enterpriseService->resetLogo();
        if ($result['success']) {
            $this->auditLog->log('Reset Enterprise Logo', 'Settings', null, "Restored default enterprise branding logo");
            set_flash('info', $result['message']);
        } else {
            set_flash('danger', $result['message']);
        }

        redirect('/settings?tab=enterprise-pane');
    }

    public function download(): void {
        $this->downloadLogs();
    }

    public function downloadLogs(): void {
        $logs = $this->auditLog->getRecent(200);
        $this->pdfService->generateAuditReport($logs);
    }
}
