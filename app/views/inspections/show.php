<?php
view('layouts.header', ['title' => 'Inspection Details']);
view('layouts.sidebar');
view('layouts.navbar', ['title' => 'Inspection Details: ' . sanitize($inspection['reference_code'])]);
?>

<div class="card">
    <div class="card-header">
        <div>
            <?= status_badge($inspection['status'], 'Inspection Statuses') ?>
            <h2 style="color:var(--text-primary); margin-top:8px; font-size:1.25rem; font-weight:700;"><?= sanitize($inspection['title']) ?></h2>
            <p style="color:var(--text-secondary); font-size:0.85rem; margin-top:4px;">Reference: <span class="code-text" style="color:var(--primary); font-weight:600;"><?= sanitize($inspection['reference_code']) ?></span></p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" onclick="openEmailReportModal(event)"><i class="fa-solid fa-paper-plane"></i> Send by Mail</button>
            <a href="/inspections/edit?id=<?= $inspection['id'] ?>" class="btn btn-secondary"><i class="fa-solid fa-pen-to-square"></i> Edit Inspection</a>
            <?php if ($inspection['status'] === 'completed'): ?>
                <a href="/inspections/result?id=<?= $inspection['id'] ?>" class="btn btn-success"><i class="fa-solid fa-chart-pie"></i> View Compliance Report</a>
            <?php endif; ?>
            <a href="/inspections" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:20px; margin-bottom:24px; padding:16px; background:#f8fafc; border-radius:var(--radius-md); border:1px solid var(--border-color);">
        <div>
            <div style="font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; font-weight:600;">Inspector</div>
            <div style="font-weight:600; color:var(--text-primary); margin-top:4px;"><?= sanitize($inspection['inspector_name']) ?></div>
            <div style="font-size:0.8rem; color:var(--text-secondary);"><?= sanitize($inspection['inspector_email']) ?></div>
        </div>
        <div>
            <div style="font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; font-weight:600;">Category</div>
            <div style="font-weight:600; color:var(--text-primary); margin-top:4px;"><?= sanitize($inspection['category_name']) ?></div>
        </div>
        <div>
            <div style="font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; font-weight:600;">Equipment Unit</div>
            <div style="font-weight:600; color:var(--text-primary); margin-top:4px;"><?= sanitize($inspection['equipment_name'] ?? 'General Datacenter') ?></div>
            <?php if (!empty($inspection['serial_number'])): ?>
                <div style="font-size:0.8rem; color:var(--primary);" class="code-text">SN: <?= sanitize($inspection['serial_number']) ?></div>
            <?php endif; ?>
        </div>
        <div>
            <div style="font-size:0.75rem; color:var(--text-secondary); text-transform:uppercase; font-weight:600;">Timeline</div>
            <div style="font-size:0.82rem; color:var(--text-primary); margin-top:4px;">
                <span style="color:var(--text-secondary); font-size:0.75rem;">Started:</span> <?= sanitize($inspection['started_at'] ?? 'N/A') ?>
            </div>
            <div style="font-size:0.82rem; color:var(--text-primary); margin-top:2px;">
                <span style="color:var(--text-secondary); font-size:0.75rem;">Completed:</span> <?= sanitize($inspection['completed_at'] ?? ($inspection['status'] === 'completed' ? ($inspection['created_at'] ?? 'N/A') : 'In Progress')) ?>
            </div>
        </div>
    </div>

    <h3 style="color:var(--text-primary); font-size:1.05rem; font-weight:600; margin-bottom:16px;"><i class="fa-solid fa-list-check" style="color:var(--primary);"></i> Checklist Item Breakdown</h3>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="color:var(--primary)">Checklist Item</th>
                    <th style="color:var(--primary)">Result Status</th>
                    <th style="color:var(--primary)">Notes / Remarks</th>
                    <th style="color:var(--primary)">Device Photo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="5" style="text-align:center; color:var(--text-secondary);">No checklist items evaluated yet.</td></tr>
                <?php else: ?>
                    <?php 
                    // Map photos by item_id
                    $itemPhotos = [];
                    foreach ($photos as $p) {
                        if (!empty($p['item_id'])) {
                            $itemPhotos[$p['item_id']] = $p['file_path'];
                        }
                    }
                    ?>
                    <?php foreach ($results as $res): ?>
                    <tr>
                        <td>
                            <strong><?= sanitize($res['item_title']) ?></strong>
                            <div style="font-size:0.8rem; color:var(--text-secondary);"><?= sanitize($res['item_description']) ?></div>
                        </td>
                        <td>
                            <?= status_badge($res['result_status'], 'Checklist Results') ?>
                        </td>
                        <td><?= sanitize($res['notes'] ?? '-') ?></td>
                        <td>
                            <?php if (isset($itemPhotos[$res['item_id']])): ?>
                                <a href="/<?= sanitize($itemPhotos[$res['item_id']]) ?>" target="_blank">
                                    <img src="/<?= sanitize($itemPhotos[$res['item_id']]) ?>" alt="Evidence" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);">No Photo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($photos)): ?>
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-camera" style="color:var(--color-success);"></i> Photo Evidence</div>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:16px;">
        <?php foreach ($photos as $photo): ?>
            <div style="background:#ffffff; border:1px solid var(--border-color); border-radius:8px; overflow:hidden;">
                <img src="/<?= sanitize($photo['file_path']) ?>" alt="Photo Evidence" style="width:100%; height:160px; object-fit:cover;">
                <div style="padding:10px; font-size:0.82rem; color:var(--text-secondary);"><?= sanitize($photo['caption'] ?? 'Inspection Photo') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$mailService = new \App\Services\MailService();
$mailSettings = $mailService->getSettings();
$defaultRecipients = !empty($inspection['inspector_email']) ? $inspection['inspector_email'] : ($mailSettings['default_recipients'] ?? '');
?>
<!-- ========================================================================= -->
<!-- MODAL: SEND REPORT BY EMAIL -->
<!-- ========================================================================= -->
<div id="emailReportModal" class="modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15, 23, 42, 0.7); backdrop-filter:blur(4px); z-index:99999; align-items:center; justify-content:center;">
    <div class="modal-dialog" style="max-width:540px; width:92%; margin:auto; background:#ffffff !important; border-radius:var(--radius-lg); overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); border:1px solid #cbd5e1; z-index:100000;">
        <div class="modal-content" style="background:#ffffff !important;">
            <div class="modal-header" style="background:#ffffff !important; padding:18px 24px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div class="modal-title" style="font-size:1.05rem; font-weight:700; color:var(--text-primary);">
                    <i class="fa-solid fa-paper-plane" style="color:var(--primary);"></i> Send Inspection Report by Email
                </div>
                <button type="button" class="modal-close-btn" onclick="closeEmailReportModal()" style="background:none; border:none; font-size:1.4rem; color:var(--text-secondary); cursor:pointer;">&times;</button>
            </div>
            <form action="/inspections/email" method="POST" onsubmit="return handleEmailSend(this, 'btnSendShowEmail')">
                <?= csrf_field() ?>
                <input type="hidden" name="inspection_id" value="<?= $inspection['id'] ?>">
                <input type="hidden" name="return_url" value="/inspections/show?id=<?= $inspection['id'] ?>">

                <div class="modal-body" style="background:#ffffff !important; padding:24px; display:flex; flex-direction:column; gap:16px;">
                    <div style="background:#f1f5f9; border:1px solid var(--border-color); border-radius:6px; padding:12px 16px; font-size:0.85rem; color:var(--text-secondary);">
                        Inspection: <strong style="color:var(--text-primary);"><?= sanitize($inspection['title']) ?></strong> &bull; Code: <span class="code-text" style="color:var(--primary); font-weight:600;"><?= sanitize($inspection['reference_code']) ?></span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight:600; font-size:0.86rem; color:var(--text-primary);">Recipient Email(s) <span style="color:var(--color-danger);">*</span></label>
                        <input type="text" name="recipients" class="form-control" placeholder="client@example.com, manager@datacenter.local" value="<?= sanitize($defaultRecipients) ?>" style="background:#ffffff; color:var(--text-primary); border:1px solid #cbd5e1;" required>
                        <small style="font-size:0.75rem; color:var(--text-secondary); margin-top:4px;">Separate multiple recipient emails with commas.</small>
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
                    <button type="submit" id="btnSendShowEmail" class="btn btn-primary">
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
