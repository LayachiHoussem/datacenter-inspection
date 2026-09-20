<?php
view('layouts.header', ['title' => 'Inspection Result']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Inspection Compliance Result']);

$total = count($results);
$checklistMeta = \App\Config\Constants::getStatusOptionsWithMeta('Checklist Results');

$statusCounts = [];
foreach ($results as $r) {
    $st = strtolower(trim($r['result_status'] ?? 'pass'));
    $statusCounts[$st] = ($statusCounts[$st] ?? 0) + 1;
}

$passed = $statusCounts['pass'] ?? 0;
$warnings = $statusCounts['warning'] ?? 0;
$score = $total > 0 ? round((($passed * 100) + ($warnings * 50)) / $total, 1) : 100;
?>

<div class="card" style="text-align: center; padding: 40px 24px;">
    <div style="width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; background: var(--color-success-bg); color: var(--color-success); border: 2px solid var(--color-success);">
        <i class="fa-solid fa-award"></i>
    </div>

    <h2 style="color: var(--text-primary); font-size: 1.6rem; font-weight: 700;">Inspection Evaluation Completed</h2>
    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 4px;">Reference Code: <span class="code-text" style="color: var(--primary); font-weight:600;"><?= sanitize($inspection['reference_code']) ?></span></p>

    <div style="display: flex; justify-content: center; gap: 16px; margin: 28px 0; flex-wrap: wrap;">
        <!-- Compliance Score Card -->
        <div style="background: #f8fafc; padding: 16px 24px; border-radius: var(--radius-md); border: 1px solid var(--border-color); min-width: 140px;">
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--color-success);"><?= $score ?>%</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight:600; margin-top:2px;">Compliance Score</div>
        </div>

        <?php 
        // Render cards for each active or evaluated checklist status
        $renderedStatuses = [];
        // First show any status with counts > 0
        foreach ($statusCounts as $stKey => $count) {
            $meta = $checklistMeta[$stKey] ?? null;
            $label = $meta['label'] ?? ucwords(str_replace('_', ' ', $stKey));
            $color = $meta['color'] ?? \App\Config\Constants::getStatusColor($stKey, 'Checklist Results');
            $renderedStatuses[$stKey] = true;
        ?>
            <div style="background: #f8fafc; padding: 16px 24px; border-radius: var(--radius-md); border: 1px solid <?= $color ?>44; min-width: 140px; box-shadow: 0 1px 3px <?= $color ?>15;">
                <div style="font-size: 1.8rem; font-weight: 700; color: <?= $color ?>;"><?= $count ?></div>
                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight:600; margin-top:2px; display:flex; align-items:center; justify-content:center; gap:5px;">
                    <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:<?= $color ?>;"></span>
                    <?= sanitize($label) ?>
                </div>
            </div>
        <?php } ?>

        <?php 
        // Then show core statuses with 0 if not yet rendered
        foreach ($checklistMeta as $stKey => $meta) {
            if (!isset($renderedStatuses[$stKey])) {
                $count = 0;
                $label = $meta['label'];
                $color = $meta['color'];
            ?>
                <div style="background: #f8fafc; padding: 16px 24px; border-radius: var(--radius-md); border: 1px solid var(--border-color); min-width: 140px; opacity: 0.65;">
                    <div style="font-size: 1.8rem; font-weight: 700; color: <?= $color ?>;"><?= $count ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; font-weight:600; margin-top:2px; display:flex; align-items:center; justify-content:center; gap:5px;">
                        <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:<?= $color ?>;"></span>
                        <?= sanitize($label) ?>
                    </div>
                </div>
            <?php }
        } ?>
    </div>

    <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
        <button type="button" class="btn btn-primary" onclick="openEmailReportModal(event)"><i class="fa-solid fa-paper-plane"></i> Email Compliance Report</button>
        <a href="/reports" class="btn btn-secondary"><i class="fa-solid fa-file-contract"></i> View Audit Reports</a>
        <a href="/inspections/show?id=<?= $inspection['id'] ?>" class="btn btn-secondary"><i class="fa-solid fa-eye"></i> View Full Inspection Record</a>
    </div>
</div>

<?php
$mailService = new \App\Services\MailService();
$mailSettings = $mailService->getSettings();
$defaultRecipients = !empty($inspection['inspector_email']) ? $inspection['inspector_email'] : ($mailSettings['default_recipients'] ?? '');
?>
<!-- ========================================================================= -->
<!-- MODAL: SEND REPORT BY EMAIL -->
<!-- ========================================================================= -->
<div id="emailReportModal" class="modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15, 23, 42, 0.7); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center;">
    <div class="modal-dialog" style="max-width:540px; width:92%; margin:auto; background:#ffffff !important; border-radius:var(--radius-lg); overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); border:1px solid #cbd5e1; text-align:left; z-index:100000;">
        <div class="modal-content" style="background:#ffffff !important;">
            <div class="modal-header" style="background:#ffffff !important; padding:18px 24px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div class="modal-title" style="font-size:1.05rem; font-weight:700; color:var(--text-primary);">
                    <i class="fa-solid fa-paper-plane" style="color:var(--primary);"></i> Send Compliance Report by Email
                </div>
                <button type="button" class="modal-close-btn" onclick="closeEmailReportModal()" style="background:none; border:none; font-size:1.4rem; color:var(--text-secondary); cursor:pointer;">&times;</button>
            </div>
            <form action="/inspections/email" method="POST" onsubmit="return handleEmailSend(this, 'btnSendResultEmail')">
                <?= csrf_field() ?>
                <input type="hidden" name="inspection_id" value="<?= $inspection['id'] ?>">
                <input type="hidden" name="return_url" value="/inspections/result?id=<?= $inspection['id'] ?>">

                <div class="modal-body" style="background:#ffffff !important; padding:24px; display:flex; flex-direction:column; gap:16px;">
                    <div style="background:#f1f5f9; border:1px solid var(--border-color); border-radius:6px; padding:12px 16px; font-size:0.85rem; color:var(--text-secondary);">
                        Report: <strong style="color:var(--text-primary);"><?= sanitize($inspection['title']) ?></strong> &bull; Score: <strong style="color:var(--color-success);"><?= $score ?>%</strong>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600; font-size:0.86rem; color:var(--text-primary);">Recipient Email(s) <span style="color:var(--color-danger);">*</span></label>
                        <input type="text" name="recipients" class="form-control" placeholder="client@example.com, manager@datacenter.local" value="<?= sanitize($defaultRecipients) ?>" style="background:#ffffff; color:var(--text-primary); border:1px solid #cbd5e1;" required>
                        <small style="font-size:0.75rem; color:var(--text-secondary); margin-top:4px;">Separate multiple emails with commas.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600; font-size:0.86rem; color:var(--text-primary);">Custom Message / Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add an optional message to accompany the attached PDF report..." style="background:#ffffff; color:var(--text-primary); border:1px solid #cbd5e1;"></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.86rem; font-weight:600; color:var(--text-primary);">
                            <input type="checkbox" name="attach_pdf" value="1" checked style="accent-color:var(--primary); width:16px; height:16px;">
                            Attach Official PDF Compliance Report
                        </label>
                    </div>
                </div>

                <div class="modal-footer" style="background:#f8fafc !important; padding:16px 24px; border-top:1px solid var(--border-color); display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" class="btn btn-secondary" onclick="closeEmailReportModal()">Cancel</button>
                    <button type="submit" id="btnSendResultEmail" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEmailReportModal(e) {
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const m = document.getElementById('emailReportModal');
    if (m) {
        m.style.setProperty('display', 'flex', 'important');
    }
}
function closeEmailReportModal() {
    const m = document.getElementById('emailReportModal');
    if (m) {
        m.style.setProperty('display', 'none', 'important');
    }
}
function handleEmailSend(form, btnId) {
    const btn = document.getElementById(btnId);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending Email...';
    }
    return true;
}
window.addEventListener('click', (e) => {
    const m = document.getElementById('emailReportModal');
    if (e.target === m) closeEmailReportModal();
});
</script>

<?php view('layouts.footer'); ?>
