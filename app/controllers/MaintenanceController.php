<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\MaintenancePlan;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\MailService;

class MaintenanceController {
    private MaintenancePlan $planModel;
    private Equipment $equipmentModel;
    private Category $categoryModel;
    private User $userModel;
    private AuditLog $auditLog;
    private MailService $mailService;

    public function __construct() {
        AuthMiddleware::handle();
        $this->planModel = new MaintenancePlan();
        $this->equipmentModel = new Equipment();
        $this->categoryModel = new Category();
        $this->userModel = new User();
        $this->auditLog = new AuditLog();
        $this->mailService = new MailService();
    }

    /**
     * Main index view: supports Table, Interactive Calendar, and Kanban Board views
     */
    public function index(): void {
        if (!can_access('maintenance')) {
            set_flash('danger', 'Access denied to Preventive Maintenance module.');
            redirect('/dashboard');
        }

        $activeView = trim($_GET['view'] ?? 'table');
        if (!in_array($activeView, ['table', 'calendar', 'kanban'])) {
            $activeView = 'table';
        }

        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'equipment_id' => $_GET['equipment_id'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'assigned_to' => $_GET['assigned_to'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $plans = $this->planModel->all($filters);
        $stats = $this->planModel->getStats();
        $equipments = $this->equipmentModel->all();
        $categories = $this->categoryModel->all();
        $users = $this->userModel->all();

        view('maintenance.index', [
            'plans' => $plans,
            'stats' => $stats,
            'equipments' => $equipments,
            'categories' => $categories,
            'users' => $users,
            'activeView' => $activeView,
            'filters' => $filters
        ]);
    }

    /**
     * Plan creation form
     */
    public function create(): void {
        if (!can_write('maintenance')) {
            set_flash('danger', 'You do not have permission to plan preventive maintenance.');
            redirect('/maintenance');
        }

        $equipments = $this->equipmentModel->all();
        $categories = $this->categoryModel->all();
        $users = $this->userModel->all();
        $selectedDate = !empty($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d');

        view('maintenance.create', [
            'equipments' => $equipments,
            'categories' => $categories,
            'users' => $users,
            'selectedDate' => $selectedDate,
            'preselectedEquipment' => (int)($_GET['equipment_id'] ?? 0)
        ]);
    }

    /**
     * Store new preventive maintenance plan
     */
    public function store(): void {
        if (!can_write('maintenance')) {
            set_flash('danger', 'You do not have permission to schedule maintenance.');
            redirect('/maintenance');
        }

        if (!csrf_verify()) {
            set_flash('danger', 'Security validation (CSRF) failed. Please try again.');
            redirect('/maintenance/create');
        }

        $user = auth_user();
        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            set_flash('danger', 'Maintenance task title is required.');
            redirect('/maintenance/create');
        }

        $scheduledDate = !empty($_POST['scheduled_date']) ? trim($_POST['scheduled_date']) : date('Y-m-d');
        $scheduledTime = !empty($_POST['scheduled_time']) ? trim($_POST['scheduled_time']) . ':00' : '09:00:00';
        $recurrenceType = !empty($_POST['recurrence_type']) ? trim($_POST['recurrence_type']) : 'one_time';
        $customVal = !empty($_POST['custom_interval_value']) ? (int)$_POST['custom_interval_value'] : null;
        $customUnit = !empty($_POST['custom_interval_unit']) ? trim($_POST['custom_interval_unit']) : null;
        $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
        $recipientEmails = trim($_POST['recipient_emails'] ?? '');
        $emailNotes = trim($_POST['email_notes'] ?? '');

        // Support multiple selected equipment units
        $equipmentIds = [];
        if (isset($_POST['equipment_ids']) && is_array($_POST['equipment_ids'])) {
            $equipmentIds = array_filter(array_map('intval', $_POST['equipment_ids']));
        } elseif (!empty($_POST['equipment_id'])) {
            $equipmentIds = [(int)$_POST['equipment_id']];
        }

        $planData = [
            'title' => $title,
            'description' => trim($_POST['description'] ?? ''),
            'checklist_scope' => trim($_POST['checklist_scope'] ?? ''),
            'equipment_id' => !empty($equipmentIds[0]) ? $equipmentIds[0] : null,
            'equipment_ids' => $equipmentIds,
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
            'created_by' => (int)($user['id'] ?? 1),
            'status' => !empty($_POST['status']) ? trim($_POST['status']) : 'scheduled',
            'priority' => !empty($_POST['priority']) ? trim($_POST['priority']) : 'medium',
            'recurrence_type' => $recurrenceType,
            'custom_interval_value' => $customVal,
            'custom_interval_unit' => $customUnit,
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'estimated_duration_minutes' => !empty($_POST['estimated_duration_minutes']) ? (int)$_POST['estimated_duration_minutes'] : 60,
            'end_date' => !empty($_POST['end_date']) ? trim($_POST['end_date']) : null,
            'notify_email' => $notifyEmail,
            'recipient_emails' => $recipientEmails,
        ];

        $planId = $this->planModel->create($planData);

        if ($planId) {
            // Handle uploaded file attachments ("joinder des fichiers")
            if (!empty($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name'])) {
                $uploadService = new \App\Services\FileUploadService('maintenance');
                $attModel = new \App\Models\MaintenanceAttachment();

                foreach ($_FILES['attachments']['name'] as $key => $name) {
                    if (empty($name)) continue;
                    if (isset($_FILES['attachments']['error'][$key]) && $_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileArr = [
                            'name' => $_FILES['attachments']['name'][$key],
                            'type' => $_FILES['attachments']['type'][$key] ?? '',
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                            'error' => $_FILES['attachments']['error'][$key],
                            'size' => $_FILES['attachments']['size'][$key],
                        ];
                        $uploadRes = $uploadService->upload($fileArr);
                        if ($uploadRes['success']) {
                            $attModel->create($planId, $uploadRes);
                        }
                    }
                }
            }

            $createdPlan = $this->planModel->findById($planId);
            $this->auditLog->log('Scheduled Maintenance Plan', 'MaintenancePlan', $planId, "Created plan {$createdPlan['plan_code']}: {$title}");

            $emailStatusMsg = '';
            if ($notifyEmail) {
                $recipients = [];

                // Add assigned user email
                if (!empty($createdPlan['assigned_email'])) {
                    $recipients[] = $createdPlan['assigned_email'];
                }

                // Add custom CC recipients
                if (!empty($recipientEmails)) {
                    $ccs = array_map('trim', explode(',', str_replace(';', ',', $recipientEmails)));
                    foreach ($ccs as $cc) {
                        if (filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                            $recipients[] = $cc;
                        }
                    }
                }

                // Default system recipients if no tech email
                if (empty($recipients)) {
                    $mailSettings = $this->mailService->getSettings();
                    if (!empty($mailSettings['default_recipients'])) {
                        $recipients[] = $mailSettings['default_recipients'];
                    }
                }

                if (!empty($recipients)) {
                    $recipients = array_unique($recipients);
                    $mailRes = $this->mailService->sendMaintenancePlanNotification($createdPlan, $recipients, 'scheduled', $emailNotes);
                    if ($mailRes['success']) {
                        $emailStatusMsg = ' Notification email sent to ' . implode(', ', $recipients) . '.';
                        $this->auditLog->log('Sent Maintenance Email', 'MaintenancePlan', $planId, "Dispatched notification to " . implode(', ', $recipients));
                    } else {
                        $emailStatusMsg = ' (Email notice could not be sent: ' . htmlspecialchars($mailRes['message']) . ')';
                    }
                }
            }

            set_flash('success', "Preventive Maintenance Plan [{$createdPlan['plan_code']}] successfully scheduled!{$emailStatusMsg}");
            $returnView = !empty($_POST['return_view']) ? $_POST['return_view'] : 'kanban';
            redirect("/maintenance?view={$returnView}");
        } else {
            set_flash('danger', 'Failed to create maintenance plan. Please check your data.');
            redirect('/maintenance/create');
        }
    }

    /**
     * Details view of a maintenance plan
     */
    public function show(): void {
        if (!can_access('maintenance')) {
            set_flash('danger', 'Access denied.');
            redirect('/dashboard');
        }

        $id = (int)($_GET['id'] ?? 0);
        $plan = $this->planModel->findById($id);

        if (!$plan) {
            set_flash('danger', 'Maintenance plan not found.');
            redirect('/maintenance');
        }

        view('maintenance.show', [
            'plan' => $plan,
            'canWrite' => can_write('maintenance')
        ]);
    }

    /**
     * Edit form
     */
    public function edit(): void {
        if (!can_write('maintenance')) {
            set_flash('danger', 'Access denied.');
            redirect('/maintenance');
        }

        $id = (int)($_GET['id'] ?? 0);
        $plan = $this->planModel->findById($id);

        if (!$plan) {
            set_flash('danger', 'Maintenance plan not found.');
            redirect('/maintenance');
        }

        $equipments = $this->equipmentModel->all();
        $categories = $this->categoryModel->all();
        $users = $this->userModel->all();

        view('maintenance.edit', [
            'plan' => $plan,
            'equipments' => $equipments,
            'categories' => $categories,
            'users' => $users
        ]);
    }

    /**
     * Update existing plan
     */
    public function update(): void {
        if (!can_write('maintenance')) {
            set_flash('danger', 'Access denied.');
            redirect('/maintenance');
        }

        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/maintenance');
        }

        $id = (int)($_POST['id'] ?? 0);
        $existing = $this->planModel->findById($id);
        if (!$existing) {
            set_flash('danger', 'Plan not found.');
            redirect('/maintenance');
        }

        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            set_flash('danger', 'Title is required.');
            redirect("/maintenance/edit?id={$id}");
        }

        $scheduledDate = !empty($_POST['scheduled_date']) ? trim($_POST['scheduled_date']) : date('Y-m-d');
        $scheduledTime = !empty($_POST['scheduled_time']) ? trim($_POST['scheduled_time']) . ':00' : '09:00:00';
        $recurrenceType = !empty($_POST['recurrence_type']) ? trim($_POST['recurrence_type']) : 'one_time';
        $customVal = !empty($_POST['custom_interval_value']) ? (int)$_POST['custom_interval_value'] : null;
        $customUnit = !empty($_POST['custom_interval_unit']) ? trim($_POST['custom_interval_unit']) : null;
        $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
        $recipientEmails = trim($_POST['recipient_emails'] ?? '');

        // Support multiple selected equipment units
        $equipmentIds = [];
        if (isset($_POST['equipment_ids']) && is_array($_POST['equipment_ids'])) {
            $equipmentIds = array_filter(array_map('intval', $_POST['equipment_ids']));
        } elseif (!empty($_POST['equipment_id'])) {
            $equipmentIds = [(int)$_POST['equipment_id']];
        }

        $data = [
            'title' => $title,
            'description' => trim($_POST['description'] ?? ''),
            'checklist_scope' => trim($_POST['checklist_scope'] ?? ''),
            'equipment_id' => !empty($equipmentIds[0]) ? $equipmentIds[0] : null,
            'equipment_ids' => $equipmentIds,
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
            'status' => !empty($_POST['status']) ? trim($_POST['status']) : $existing['status'],
            'priority' => !empty($_POST['priority']) ? trim($_POST['priority']) : 'medium',
            'recurrence_type' => $recurrenceType,
            'custom_interval_value' => $customVal,
            'custom_interval_unit' => $customUnit,
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'estimated_duration_minutes' => !empty($_POST['estimated_duration_minutes']) ? (int)$_POST['estimated_duration_minutes'] : 60,
            'end_date' => !empty($_POST['end_date']) ? trim($_POST['end_date']) : null,
            'notify_email' => $notifyEmail,
            'recipient_emails' => $recipientEmails,
        ];

        $success = $this->planModel->update($id, $data);

        if ($success) {
            // Handle newly uploaded file attachments
            if (!empty($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name'])) {
                $uploadService = new \App\Services\FileUploadService('maintenance');
                $attModel = new \App\Models\MaintenanceAttachment();

                foreach ($_FILES['attachments']['name'] as $key => $name) {
                    if (empty($name)) continue;
                    if (isset($_FILES['attachments']['error'][$key]) && $_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileArr = [
                            'name' => $_FILES['attachments']['name'][$key],
                            'type' => $_FILES['attachments']['type'][$key] ?? '',
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                            'error' => $_FILES['attachments']['error'][$key],
                            'size' => $_FILES['attachments']['size'][$key],
                        ];
                        $uploadRes = $uploadService->upload($fileArr);
                        if ($uploadRes['success']) {
                            $attModel->create($id, $uploadRes);
                        }
                    }
                }
            }

            $updated = $this->planModel->findById($id);
            $this->auditLog->log('Updated Maintenance Plan', 'MaintenancePlan', $id, "Updated plan {$updated['plan_code']}");

            if ($notifyEmail && !empty($_POST['send_update_notification'])) {
                $recipients = [];
                if (!empty($updated['assigned_email'])) {
                    $recipients[] = $updated['assigned_email'];
                }
                if (!empty($recipientEmails)) {
                    $ccs = array_map('trim', explode(',', str_replace(';', ',', $recipientEmails)));
                    foreach ($ccs as $cc) {
                        if (filter_var($cc, FILTER_VALIDATE_EMAIL)) $recipients[] = $cc;
                    }
                }
                if (!empty($recipients)) {
                    $this->mailService->sendMaintenancePlanNotification($updated, array_unique($recipients), 'updated', 'Plan specifications have been modified.');
                }
            }

            set_flash('success', "Maintenance plan [{$updated['plan_code']}] updated successfully.");
            redirect("/maintenance/show?id={$id}");
        } else {
            set_flash('danger', 'Failed to update maintenance plan.');
            redirect("/maintenance/edit?id={$id}");
        }
    }

    /**
     * Quick status update (AJAX endpoint for Kanban drag-and-drop or modal)
     */
    public function updateStatus(): void {
        if (!can_write('maintenance')) {
            json_response(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        // Support both JSON body and standard POST
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $id = (int)($input['id'] ?? 0);
        $status = strtolower(trim($input['status'] ?? ''));
        $notes = trim($input['notes'] ?? '');

        $validStatuses = ['scheduled', 'in_progress', 'completed', 'overdue', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            json_response(['success' => false, 'message' => 'Invalid status identifier.'], 400);
        }

        $plan = $this->planModel->findById($id);
        if (!$plan) {
            json_response(['success' => false, 'message' => 'Maintenance plan not found.'], 404);
        }

        if ($status === 'completed') {
            $ok = $this->planModel->markComplete($id, $notes ?: 'Completed from Kanban / quick status change.');
        } else {
            $ok = $this->planModel->updateStatus($id, $status, $notes);
        }

        if ($ok) {
            $updated = $this->planModel->findById($id);
            $this->auditLog->log('Changed Maintenance Status', 'MaintenancePlan', $id, "Status changed to {$status} for plan {$updated['plan_code']}");
            
            // If AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || !empty($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'json')) {
                json_response([
                    'success' => true,
                    'message' => "Plan status updated to " . strtoupper(str_replace('_', ' ', $status)),
                    'plan' => $updated,
                    'stats' => $this->planModel->getStats()
                ]);
            }

            set_flash('success', "Status of plan [{$updated['plan_code']}] changed to " . strtoupper(str_replace('_', ' ', $status)));
            redirect(!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/maintenance');
        }

        json_response(['success' => false, 'message' => 'Failed to update plan status.'], 500);
    }

    /**
     * Mark plan as completed with notes and advance recurring schedule
     */
    public function markComplete(): void {
        if (!can_write('maintenance')) {
            set_flash('danger', 'Access denied.');
            redirect('/maintenance');
        }

        if (!csrf_verify()) {
            set_flash('danger', 'CSRF verification failed.');
            redirect('/maintenance');
        }

        $id = (int)($_POST['id'] ?? 0);
        $notes = trim($_POST['completion_notes'] ?? '');

        $plan = $this->planModel->findById($id);
        if (!$plan) {
            set_flash('danger', 'Plan not found.');
            redirect('/maintenance');
        }

        $ok = $this->planModel->markComplete($id, $notes);
        if ($ok) {
            $updated = $this->planModel->findById($id);
            $this->auditLog->log('Completed Maintenance Plan', 'MaintenancePlan', $id, "Completed plan {$plan['plan_code']}. " . ($updated['next_due_date'] ? "Next cycle: {$updated['next_due_date']}" : ""));
            
            $msg = "Maintenance plan [{$plan['plan_code']}] marked as completed!";
            if (!empty($updated['next_due_date']) && $plan['recurrence_type'] !== 'one_time') {
                $msg .= " Next dynamic cycle scheduled for: {$updated['next_due_date']}.";
            }
            set_flash('success', $msg);
        } else {
            set_flash('danger', 'Failed to mark maintenance plan as completed.');
        }

        redirect(!empty($_POST['return_url']) ? $_POST['return_url'] : "/maintenance/show?id={$id}");
    }

    /**
     * Delete maintenance plan
     */
    public function delete(): void {
        if (!can_write('maintenance')) {
            set_flash('danger', 'Access denied.');
            redirect('/maintenance');
        }

        $id = (int)($_POST['id'] ?? 0);
        $plan = $this->planModel->findById($id);

        if ($plan) {
            $this->planModel->delete($id);
            $this->auditLog->log('Deleted Maintenance Plan', 'MaintenancePlan', $id, "Deleted plan {$plan['plan_code']}: {$plan['title']}");
            set_flash('success', "Maintenance plan [{$plan['plan_code']}] deleted successfully.");
        } else {
            set_flash('danger', 'Plan not found.');
        }

        redirect('/maintenance');
    }

    /**
     * JSON events feed for interactive Calendar view
     */
    public function calendarEvents(): void {
        if (!can_access('maintenance')) {
            json_response(['error' => 'Unauthorized'], 403);
        }

        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $events = $this->planModel->getCalendarEvents($start, $end);
        json_response($events);
    }

    /**
     * Manual email notification dispatch
     */
    public function sendNotificationEmail(): void {
        if (!can_write('maintenance')) {
            set_flash('danger', 'Access denied.');
            redirect('/maintenance');
        }

        $id = (int)($_POST['id'] ?? 0);
        $plan = $this->planModel->findById($id);

        if (!$plan) {
            set_flash('danger', 'Plan not found.');
            redirect('/maintenance');
        }

        $recipientsInput = trim($_POST['recipients'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $recipients = [];
        if (!empty($recipientsInput)) {
            $ccs = array_map('trim', explode(',', str_replace(';', ',', $recipientsInput)));
            foreach ($ccs as $cc) {
                if (filter_var($cc, FILTER_VALIDATE_EMAIL)) $recipients[] = $cc;
            }
        }
        if (empty($recipients) && !empty($plan['assigned_email'])) {
            $recipients[] = $plan['assigned_email'];
        }
        if (empty($recipients)) {
            $settings = $this->mailService->getSettings();
            if (!empty($settings['default_recipients'])) {
                $recipients[] = $settings['default_recipients'];
            }
        }

        if (empty($recipients)) {
            set_flash('danger', 'No valid recipient email address found.');
            redirect("/maintenance/show?id={$id}");
        }

        $res = $this->mailService->sendMaintenancePlanNotification($plan, array_unique($recipients), 'scheduled', $notes);
        if ($res['success']) {
            $this->auditLog->log('Emailed Maintenance Plan', 'MaintenancePlan', $id, "Emailed plan {$plan['plan_code']} to " . implode(', ', $recipients));
            set_flash('success', "Maintenance notification email sent successfully to " . implode(', ', $recipients) . "!");
        } else {
            set_flash('danger', "Failed to send email: " . htmlspecialchars($res['message']));
        }

        redirect("/maintenance/show?id={$id}");
    }

    /**
     * Delete an attached document or file from maintenance plan
     */
    public function deleteAttachment(): void {
        if (!can_write('maintenance')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            set_flash('danger', 'Unauthorized action.');
            redirect('/maintenance');
        }

        $id = (int)($_POST['attachment_id'] ?? $_POST['id'] ?? 0);
        $attModel = new \App\Models\MaintenanceAttachment();
        $att = $attModel->findById($id);

        if (!$att) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response(['success' => false, 'message' => 'Attachment file not found.'], 404);
            }
            set_flash('danger', 'Attachment file not found.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/maintenance');
        }

        $planId = (int)$att['plan_id'];
        $fileName = $att['original_name'] ?? $att['file_name'];
        $ok = $attModel->delete($id);

        if ($ok) {
            $this->auditLog->log('Deleted Maintenance Attachment', 'MaintenanceAttachment', $id, "Deleted file '{$fileName}' from plan #{$planId}");
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response(['success' => true, 'message' => "Attachment '{$fileName}' deleted successfully."]);
            }
            set_flash('success', "File attachment '{$fileName}' deleted successfully.");
        } else {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                json_response(['success' => false, 'message' => 'Failed to delete attachment.'], 500);
            }
            set_flash('danger', 'Failed to delete attachment.');
        }

        $redirectUrl = !empty($_POST['return_url']) ? $_POST['return_url'] : ($_SERVER['HTTP_REFERER'] ?? "/maintenance/show?id={$planId}");
        redirect($redirectUrl);
    }

    /**
     * Upload additional attachments to an existing maintenance plan
     */
    public function uploadAttachments(): void {
        if (!can_write('maintenance')) {
            set_flash('danger', 'Unauthorized action.');
            redirect('/maintenance');
        }

        $planId = (int)($_POST['plan_id'] ?? 0);
        $plan = $this->planModel->findById($planId);
        if (!$plan) {
            set_flash('danger', 'Maintenance plan not found.');
            redirect('/maintenance');
        }

        $count = 0;
        if (!empty($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name'])) {
            $uploadService = new \App\Services\FileUploadService('maintenance');
            $attModel = new \App\Models\MaintenanceAttachment();

            foreach ($_FILES['attachments']['name'] as $key => $name) {
                if (empty($name)) continue;
                if (isset($_FILES['attachments']['error'][$key]) && $_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileArr = [
                        'name' => $_FILES['attachments']['name'][$key],
                        'type' => $_FILES['attachments']['type'][$key] ?? '',
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                        'error' => $_FILES['attachments']['error'][$key],
                        'size' => $_FILES['attachments']['size'][$key],
                    ];
                    $res = $uploadService->upload($fileArr);
                    if ($res['success']) {
                        $attModel->create($planId, $res);
                        $count++;
                    }
                }
            }
        }

        if ($count > 0) {
            $this->auditLog->log('Uploaded Maintenance Attachments', 'MaintenancePlan', $planId, "Uploaded {$count} attachment(s) to plan {$plan['plan_code']}");
            set_flash('success', "{$count} file(s) attached successfully to plan [{$plan['plan_code']}].");
        } else {
            set_flash('warning', 'No files were uploaded or files did not meet format requirements.');
        }

        redirect(!empty($_POST['return_url']) ? $_POST['return_url'] : "/maintenance/show?id={$planId}");
    }
}
