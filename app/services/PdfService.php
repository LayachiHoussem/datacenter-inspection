<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService {

    /**
     * Render the clean HTML layout for inspection reports (print & PDF generation).
     */
    public function renderHtmlReport(array $inspection, array $results, array $photos, ?array $report): string {
        // Group checklist results by category (fallback to "General" if none set)
        $groupedResults = [];
        foreach ($results as $item) {
            $catName = $item['category_name'] ?? 'General';
            if (!isset($groupedResults[$catName])) {
                $groupedResults[$catName] = [
                    'items' => [],
                ];
            }
            $groupedResults[$catName]['items'][] = $item;
        }
        ksort($groupedResults);

        // Load dynamic enterprise branding and logo
        $enterprise = \enterprise_setting();
        $enterpriseName = $enterprise['name'] ?? 'Datacenter Inspection System';
        $enterpriseSite = $enterprise['site'] ?? 'PCR Datacenter Facility';
        $enterpriseWebsite = $enterprise['website'] ?? '';
        $logoSrc = \enterprise_logo_base64();

        $refCode = \sanitize($inspection['reference_code'] ?? 'RPT-' . ($report['id'] ?? '001'));
        $score = (float)($report['score'] ?? 100.0);
        $scoreBadgeClass = $score >= 90 ? 'badge-pass' : ($score >= 70 ? 'badge-warning' : 'badge-fail');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Inspection Report - <?= $refCode ?></title>
            <style>
                @page {
                    margin: 25mm 20mm;
                    size: A4 portrait;
                }
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    color: #1e293b;
                    background: #ffffff;
                    font-size: 13px;
                    line-height: 1.5;
                    padding: 20px;
                }

                /* ===== Header Table ===== */
                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                    border-bottom: 3px solid #e75113;
                    padding-bottom: 15px;
                    margin-bottom: 25px;
                }
                .header-table td {
                    vertical-align: middle;
                }
                .logo-img {
                    max-height: 50px;
                    max-width: 200px;
                }
                .header-title-box {
                    text-align: right;
                }
                .report-main-title {
                    font-size: 20px;
                    font-weight: bold;
                    color: #0f172a;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                }
                .report-ref-code {
                    font-size: 13px;
                    color: #e75113;
                    font-weight: 600;
                    margin-top: 3px;
                }

                /* ===== Badges ===== */
                .badge {
                    display: inline-block;
                    padding: 3px 10px;
                    border-radius: 12px;
                    font-size: 11px;
                    font-weight: bold;
                    text-transform: uppercase;
                    letter-spacing: 0.3px;
                }
                .badge-pass { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
                .badge-fail { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
                .badge-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

                /* ===== Meta Info Table ===== */
                .meta-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 25px;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                }
                .meta-table td {
                    padding: 9px 14px;
                    border-bottom: 1px solid #e2e8f0;
                    border-right: 1px solid #e2e8f0;
                    font-size: 12.5px;
                    width: 50%;
                }
                .meta-table tr td:last-child {
                    border-right: none;
                }
                .meta-table tr:last-child td {
                    border-bottom: none;
                }
                .meta-table strong {
                    color:  #e75113;
                    font-weight: 600;
                    display: inline-block;
                    min-width: 130px;
                }

                /* ===== Section Title ===== */
                .section-title {
                    font-size: 16px;
                    color: #0f172a;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-left: 4px solid #e75113;
                    padding-left: 10px;
                    margin: 20px 0 12px 0;
                    font-weight: bold;
                }

                /* ===== Category block & results table ===== */
                .category-title {
                    background: #f1f5f9;
                    border-left: 4px solid #3b82f6;
                    padding: 8px 12px;
                    margin-top: 15px;
                    margin-bottom: 8px;
                    font-size: 13px;
                    font-weight: bold;
                    color: #1e293b;
                    text-transform: uppercase;
                }

                .results-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 18px;
                    background: #ffffff;
                    border: 1px solid #cbd5e1;
                }
                .results-table th {
                    background: #000000ff;
                    color: #e75113;
                    text-align: left;
                    padding: 8px 10px;
                    font-size: 11.5px;
                    text-transform: uppercase;
                    letter-spacing: 0.4px;
                    border: 1px solid #1e293b;
                }
                .results-table td {
                    padding: 8px 10px;
                    border: 1px solid #e2e8f0;
                    font-size: 12px;
                    vertical-align: top;
                }
                .results-table tr:nth-child(even) {
                    background: #f8fafc;
                }

                /* ===== Footer ===== */
                .footer {
                    margin-top: 30px;
                    border-top: 1px solid #e2e8f0;
                    padding-top: 12px;
                    text-align: center;
                    font-size: 11px;
                    color: #64748b;
                }
            </style>
        </head>
        <body>
            <!-- Header -->
            <table class="header-table">
                <tr>
                    <td style="width: 50%;">
                        <?php if (!empty($logoSrc)): ?>
                            <img src="<?= $logoSrc ?>" alt="Logo" class="logo-img">
                        <?php else: ?>
                            <div style="font-size: 20px; font-weight: bold; color: #0f172a;">
                                <?= \sanitize($enterpriseName) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="width: 50%;" class="header-title-box">
                        <div class="report-main-title">INSPECTION REPORT</div>
                        <div class="report-ref-code"><?= $refCode ?></div>
                    </td>
                </tr>
            </table>

            <!-- Meta Information -->
            <table class="meta-table">
                <tr>
                    <td><strong>Title:</strong> <?= \sanitize($inspection['title'] ?? 'Datacenter Routine Inspection') ?></td>
                    <td><strong>Inspector:</strong> <?= \sanitize($inspection['inspector_name'] ?? 'System Inspector') ?></td>
                </tr>
                <tr>
                    <td><strong>Site:</strong> <?= \sanitize($enterpriseSite) ?></td>
                    <td><strong>Equipment:</strong> <?= \sanitize($inspection['equipment_name'] ?? 'General Datacenter') ?></td>
                </tr>
                <?php 
                $startedAt = !empty($inspection['started_at']) ? $inspection['started_at'] : (!empty($inspection['created_at']) ? $inspection['created_at'] : 'N/A');
                $isCompleted = in_array(strtolower($inspection['status'] ?? ''), ['completed', 'closed', 'done']);
                $completedAt = !empty($inspection['completed_at']) 
                    ? $inspection['completed_at'] 
                    : (!empty($report['created_at']) 
                        ? $report['created_at'] 
                        : ($isCompleted && !empty($inspection['created_at']) ? $inspection['created_at'] : 'N/A'));
                ?>
                <tr>
                    <td><strong>Started At:</strong> <?= \sanitize($startedAt) ?></td>
                    <td><strong>Completed At:</strong> <?= \sanitize($completedAt) ?></td>
                </tr>
                <tr>
                    <td><strong>Compliance Score:</strong> <span class="badge <?= $scoreBadgeClass ?>"><?= number_format($score, 1) ?>%</span></td>
                    <?php 
                    $rawStatus = $inspection['status'] ?? $report['inspection_status'] ?? $report['status'] ?? 'completed';
                    $inspStatus = strtolower(trim((string)$rawStatus));
                    $inspColor = \App\Config\Constants::getStatusColor($inspStatus, 'Inspection Statuses');
                    $inspBg = \App\Config\Constants::hexToRgba($inspColor, 0.15);
                    $inspBorder = \App\Config\Constants::hexToRgba($inspColor, 0.4);
                    $displayStatus = strtoupper(str_replace('_', ' ', \sanitize($inspStatus)));
                    ?>
                    <td><strong>Status:</strong> <span class="badge" style="background: <?= $inspBg ?>; color: <?= $inspColor ?>; border: 1px solid <?= $inspBorder ?>;"><?= $displayStatus ?></span></td>
                </tr>
            </table>

           

            <!-- Checklist Results -->
            <div class="section-title">Checklist Results Breakdown</div>

            <?php if (empty($groupedResults)): ?>
                <p style="color:#64748b; padding: 10px 0;">No checklist items recorded for this inspection.</p>
            <?php else: ?>
                <?php foreach ($groupedResults as $categoryName => $group): ?>
                    <div class="category-title">
                        <?= \sanitize($categoryName) ?> (<?= count($group['items']) ?> items)
                    </div>

                    <table class="results-table">
                        <thead>
                            <tr>
                                <th style="width: 45%;">Checklist Item</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 28%;">Notes &amp; Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($group['items'] as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= \sanitize($item['item_title'] ?? '') ?></strong>
                                    <?php if (!empty($item['is_critical'])): ?>
                                        <span class="badge badge-fail" style="font-size: 9px; padding: 1px 4px; margin-left: 5px;">CRITICAL</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $st = strtolower(trim((string)($item['result_status'] ?? $item['status'] ?? 'na')));
                                    $stColor = \App\Config\Constants::getStatusColor($st, 'Checklist Results');
                                    $stBg = \App\Config\Constants::hexToRgba($stColor, 0.15);
                                    $stBorder = \App\Config\Constants::hexToRgba($stColor, 0.4);
                                    $stDisplay = strtoupper(str_replace('_', ' ', \sanitize($st)));
                                    ?>
                                    <span class="badge" style="background: <?= $stBg ?>; color: <?= $stColor ?>; border: 1px solid <?= $stBorder ?>;">
                                        <?= $stDisplay !== '' ? $stDisplay : 'N/A' ?>
                                    </span>
                                </td>
                                <td><?= !empty($item['notes']) ? \sanitize($item['notes']) : '<span style="color:#94a3b8;">-</span>' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="footer">
                <p><?= \sanitize($enterpriseName) ?> &bull; <?= \sanitize($enterpriseSite) ?><?= !empty($enterpriseWebsite) ? ' &bull; ' . \sanitize($enterpriseWebsite) : '' ?> &bull; Operational Compliance Report &bull; Generated on <?= date('Y-m-d H:i:s') ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Render HTML to binary PDF using Dompdf.
     */
    public function renderPdfBinary(string $html): ?string {
        // Ensure Dompdf autoloader is loaded
        if (!class_exists(\Dompdf\Dompdf::class)) {
            $dompdfAutoload = dirname(__DIR__, 2) . '/vendor/dompdf/autoload.inc.php';
            if (file_exists($dompdfAutoload)) {
                require_once $dompdfAutoload;
            }
        }

        if (!class_exists(\Dompdf\Dompdf::class)) {
            error_log("Dompdf library is not installed or loaded.");
            return null;
        }

        try {
            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'Helvetica');

            $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0777, true);
            }
            $options->set('fontCache', $cacheDir);
            $options->set('tempDir', $cacheDir);
            $options->set('chroot', dirname(__DIR__, 2));

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();
        } catch (\Throwable $e) {
            error_log("Dompdf Render Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate binary PDF and physically save it to a target path on disk.
     */
    public function savePdf(string $html, string $targetPath): bool {
        $binary = $this->renderPdfBinary($html);
        if ($binary === null) {
            return false;
        }

        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                error_log("Failed to create PDF storage directory: {$dir}");
                return false;
            }
        }

        $result = file_put_contents($targetPath, $binary);
        if ($result === false) {
            error_log("Failed to write PDF file to: {$targetPath}");
            return false;
        }

        return true;
    }

    /**
     * Convert HTML report to binary PDF and stream as a download, saving to disk if path provided.
     */
    public function downloadPdf(string $html, string $filename = 'inspection_report.pdf', ?string $savePath = null, bool $forceRegenerate = true): void {
        $binary = null;

        // If a saved file already exists and we are not forcing fresh generation, read it
        if (!$forceRegenerate && $savePath && file_exists($savePath) && filesize($savePath) > 0) {
            $binary = file_get_contents($savePath);
        }

        // Render fresh binary
        if ($binary === null) {
            $binary = $this->renderPdfBinary($html);
            if ($binary !== null && $savePath) {
                $dir = dirname($savePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                file_put_contents($savePath, $binary);
            }
        }

        if ($binary !== null) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($binary));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $binary;
            exit;
        }

        // Fallback message if Dompdf class is missing or failed
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><body>";
        echo "<h3>PDF Generator Error</h3>";
        echo "<p>Failed to generate PDF. Please ensure the Dompdf library is properly installed.</p>";
        echo "<a href='javascript:history.back()'>Go Back</a>";
        echo "</body></html>";
        exit;
    }

    /**
     * Generate and stream audit trail report as PDF.
     */
    public function generateAuditReport(array $logs): void {
        $enterprise = \enterprise_setting();
        $enterpriseName = $enterprise['name'] ?? 'Datacenter Inspection System';
        $enterpriseSite = $enterprise['site'] ?? 'PCR Datacenter Facility';
        $logoSrc = \enterprise_logo_base64();

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?= \sanitize($enterpriseName) ?> - System Audit Logs</title>
            <style>
                @page { margin: 15mm 10mm; size: A4 landscape; }
                body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; }
                .report-header { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 12px; }
                .logo-img { max-height: 40px; max-width: 150px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
                th { background-color: #f1f5f9; color: #334155; font-size: 11px; font-weight: 600; }
                h2 { color: #0f172a; margin-bottom: 2px; font-size: 18px; }
                .meta { color: #64748b; font-size: 11px; }
            </style>
        </head>
        <body>
            <table class="report-header" style="border:none;">
                <tr style="border:none;">
                    <td style="border:none; width:50%; vertical-align:middle;">
                        <?php if (!empty($logoSrc)): ?>
                            <img src="<?= $logoSrc ?>" alt="Logo" class="logo-img">
                        <?php else: ?>
                            <div style="font-size: 16px; font-weight: bold; color: #0f172a;">
                                <?= \sanitize($enterpriseName) ?>
                            </div>
                        <?php endif; ?>
                        <div class="meta" style="margin-top:3px;"><?= \sanitize($enterpriseSite) ?></div>
                    </td>
                    <td style="border:none; width:50%; text-align:right; vertical-align:middle;">
                        <h2>System Audit Trail Report</h2>
                        <div class="meta">Generated on: <?= date('Y-m-d H:i:s') ?></div>
                    </td>
                </tr>
            </table>
            <table>
                <thead>
                    <tr>
                        <th style="width: 140px;">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                        <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                        <td><strong><?= htmlspecialchars($log['action'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($log['entity_type'] ?? '') ?></td>
                        <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                        <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        $html = ob_get_clean();
        $this->downloadPdf($html, 'audit_logs_' . date('Ymd_His') . '.pdf');
    }
}