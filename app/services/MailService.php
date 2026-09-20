<?php
namespace App\Services;

class MailService {
    private static ?string $storageFile = null;
    private array $config = [];

    public function __construct() {
        if (self::$storageFile === null) {
            self::$storageFile = dirname(__DIR__, 2) . '/storage/mail_settings.json';
        }
        $this->loadSettings();
    }

    /**
     * Default SMTP Mail Configuration
     */
    private function defaultSettings(): array {
        return [
            // SMTP Settings
            'smtp_enabled' => true,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls', // tls, ssl, none
            'smtp_user' => '',
            'smtp_pass' => '',
            'from_email' => 'inspections@datacenter.local',
            'from_name' => 'Datacenter Inspection System',
            'default_recipients' => 'admin@datacenter.local'
        ];
    }

    /**
     * Load settings from storage/mail_settings.json
     */
    public function loadSettings(): array {
        $defaults = $this->defaultSettings();
        if (file_exists(self::$storageFile)) {
            $json = file_get_contents(self::$storageFile);
            $data = json_decode($json, true);
            if (is_array($data)) {
                $this->config = array_merge($defaults, $data);
                return $this->config;
            }
        }
        $this->config = $defaults;
        return $this->config;
    }

    /**
     * Save settings to storage/mail_settings.json
     */
    public function saveSettings(array $data): bool {
        $current = $this->loadSettings();
        $updated = array_merge($current, $data);

        // Type sanitization
        $updated['smtp_enabled'] = !empty($updated['smtp_enabled']);
        $updated['smtp_port'] = (int)($updated['smtp_port'] ?? 587);

        // Remove any legacy snmp fields
        unset($updated['snmp_enabled'], $updated['snmp_host'], $updated['snmp_port'], $updated['snmp_community'], $updated['snmp_version'], $updated['snmp_trap_on_fail']);

        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $result = file_put_contents(self::$storageFile, json_encode($updated, JSON_PRETTY_PRINT));
        if ($result !== false) {
            $this->config = $updated;
            return true;
        }
        return false;
    }

    public function getSettings(): array {
        if (empty($this->config)) {
            $this->loadSettings();
        }
        return $this->config;
    }

    /**
     * Send email using direct pure PHP SMTP socket connection (RFC 5321)
     * Supports STARTTLS, SSL, AUTH LOGIN, and multipart attachments.
     *
     * @param string|array $to Single email or array of emails
     * @param string $subject
     * @param string $htmlBody
     * @param array $attachments Array of ['name' => 'filename.pdf', 'content' => binary/string, 'type' => 'application/pdf']
     * @return array ['success' => bool, 'message' => string, 'log' => string]
     */
    public function send(string|array $to, string $subject, string $htmlBody, array $attachments = []): array {
        $settings = $this->getSettings();

        if (empty($settings['smtp_enabled'])) {
            return [
                'success' => false,
                'message' => 'SMTP mail service is currently disabled in System Settings.',
                'log' => ''
            ];
        }

        $host = trim($settings['smtp_host'] ?? '');
        $port = (int)($settings['smtp_port'] ?? 587);
        $encryption = strtolower(trim($settings['smtp_encryption'] ?? 'tls'));
        $username = trim($settings['smtp_user'] ?? '');
        $password = (string)($settings['smtp_pass'] ?? '');
        $fromEmail = trim($settings['from_email'] ?? 'noreply@datacenter.local');
        $fromName = trim($settings['from_name'] ?? '');
        if (empty($fromName) || $fromName === 'Datacenter Inspection System') {
            $fromName = function_exists('enterprise_name') ? enterprise_name() : 'Datacenter Inspection System';
        }

        if (empty($host)) {
            return ['success' => false, 'message' => 'SMTP Host is not configured in Mail Settings.', 'log' => ''];
        }

        $recipients = is_array($to) ? $to : array_map('trim', explode(',', $to));
        $validRecipients = [];
        foreach ($recipients as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validRecipients[] = $email;
            }
        }

        if (empty($validRecipients)) {
            return ['success' => false, 'message' => 'No valid recipient email address provided.', 'log' => ''];
        }

        // Connection prefix for SSL
        $socketHost = ($encryption === 'ssl') ? "ssl://{$host}" : $host;
        $timeout = 15;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $log = [];
        $socket = @stream_socket_client("{$socketHost}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            return [
                'success' => false,
                'message' => "Connection to SMTP server failed ({$errno}): {$errstr}",
                'log' => implode("\n", $log)
            ];
        }

        stream_set_timeout($socket, $timeout);

        $readResponse = function() use ($socket, &$log): string {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            $log[] = "S: " . trim($response);
            return $response;
        };

        $sendCommand = function(string $cmd) use ($socket, &$log): void {
            $masked = $cmd;
            if (str_starts_with($cmd, 'AUTH LOGIN') === false && strlen($cmd) > 20 && !str_contains($cmd, ' ')) {
                // mask potential password
                $masked = '***';
            }
            $log[] = "C: " . trim($masked);
            fwrite($socket, $cmd . "\r\n");
        };

        // 1. Initial Greeting
        $resp = $readResponse();
        if (!str_starts_with($resp, '220')) {
            fclose($socket);
            return ['success' => false, 'message' => 'Invalid greeting from SMTP server: ' . $resp, 'log' => implode("\n", $log)];
        }

        // 2. EHLO
        $clientDomain = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $sendCommand("EHLO {$clientDomain}");
        $resp = $readResponse();

        // 3. STARTTLS Upgrade if requested
        if ($encryption === 'tls') {
            $sendCommand("STARTTLS");
            $resp = $readResponse();
            if (!str_starts_with($resp, '220')) {
                fclose($socket);
                return ['success' => false, 'message' => 'STARTTLS failed: ' . $resp, 'log' => implode("\n", $log)];
            }

            $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                fclose($socket);
                return ['success' => false, 'message' => 'TLS encryption handshake failed.', 'log' => implode("\n", $log)];
            }

            // Re-send EHLO over TLS
            $sendCommand("EHLO {$clientDomain}");
            $resp = $readResponse();
        }

        // 4. Authentication if credentials provided
        if (!empty($username) && !empty($password)) {
            $sendCommand("AUTH LOGIN");
            $resp = $readResponse();
            if (!str_starts_with($resp, '334')) {
                fclose($socket);
                return ['success' => false, 'message' => 'AUTH LOGIN failed: ' . $resp, 'log' => implode("\n", $log)];
            }

            $sendCommand(base64_encode($username));
            $resp = $readResponse();
            if (!str_starts_with($resp, '334')) {
                fclose($socket);
                return ['success' => false, 'message' => 'Username rejected by SMTP server: ' . $resp, 'log' => implode("\n", $log)];
            }

            $sendCommand(base64_encode($password));
            $resp = $readResponse();
            if (!str_starts_with($resp, '235')) {
                fclose($socket);
                return ['success' => false, 'message' => 'Password authentication failed: ' . $resp, 'log' => implode("\n", $log)];
            }
        }

        // 5. MAIL FROM
        $sendCommand("MAIL FROM:<{$fromEmail}>");
        $resp = $readResponse();
        if (!str_starts_with($resp, '250')) {
            fclose($socket);
            return ['success' => false, 'message' => 'Sender address rejected: ' . $resp, 'log' => implode("\n", $log)];
        }

        // 6. RCPT TO for each recipient
        foreach ($validRecipients as $rcpt) {
            $sendCommand("RCPT TO:<{$rcpt}>");
            $resp = $readResponse();
            if (!str_starts_with($resp, '250') && !str_starts_with($resp, '251')) {
                fclose($socket);
                return ['success' => false, 'message' => "Recipient {$rcpt} rejected: " . $resp, 'log' => implode("\n", $log)];
            }
        }

        // 7. DATA
        $sendCommand("DATA");
        $resp = $readResponse();
        if (!str_starts_with($resp, '354')) {
            fclose($socket);
            return ['success' => false, 'message' => 'DATA command rejected: ' . $resp, 'log' => implode("\n", $log)];
        }

        // Build MIME message
        $boundaryMixed = '=_mix_' . md5(uniqid(microtime(), true));
        $boundaryAlt   = '=_alt_' . md5(uniqid(microtime(), true));

        $headers = [];
        $headers[] = "Date: " . date('r');
        $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
        $headers[] = "To: " . implode(', ', $validRecipients);
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "MIME-Version: 1.0";

        $body = "";
        if (!empty($attachments)) {
            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundaryMixed}\"";
            
            $body .= "--{$boundaryMixed}\r\n";
            $body .= "Content-Type: multipart/alternative; boundary=\"{$boundaryAlt}\"\r\n\r\n";
            
            // Plaintext fallback
            $plainText = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));
            $body .= "--{$boundaryAlt}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($plainText)) . "\r\n";

            // HTML content
            $body .= "--{$boundaryAlt}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $body .= "--{$boundaryAlt}--\r\n\r\n";

            // Attachments
            foreach ($attachments as $att) {
                $filename = $att['name'] ?? 'attachment.pdf';
                $mimeType = $att['type'] ?? 'application/pdf';
                $content  = $att['content'] ?? '';

                $body .= "--{$boundaryMixed}\r\n";
                $body .= "Content-Type: {$mimeType}; name=\"{$filename}\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
                $body .= chunk_split(base64_encode($content)) . "\r\n";
            }

            $body .= "--{$boundaryMixed}--\r\n";
        } else {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: base64";
            $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        }

        $fullPayload = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        fwrite($socket, $fullPayload . "\r\n");

        $resp = $readResponse();
        $sendCommand("QUIT");
        fclose($socket);

        if (str_starts_with($resp, '250')) {
            return [
                'success' => true,
                'message' => 'Email sent successfully via SMTP.',
                'log' => implode("\n", $log)
            ];
        }

        return [
            'success' => false,
            'message' => 'SMTP server response after sending data: ' . $resp,
            'log' => implode("\n", $log)
        ];
    }

    /**
     * Send test email to verify SMTP configuration
     */
    public function testConnection(string $testEmail): array {
        $subject = "Datacenter Inspection System - Test SMTP Connection";
        $html = "
        <div style='font-family:Arial,sans-serif; max-width:600px; margin:0 auto; padding:20px; border:1px solid #e2e8f0; border-radius:8px;'>
            <h2 style='color:#0ea5e9;'>Datacenter Inspection System</h2>
            <p style='color:#334155; font-size:15px;'>Congratulations! Your SMTP Mail Server connection was tested and is functioning perfectly.</p>
            <hr style='border:none; border-top:1px solid #e2e8f0; margin:16px 0;'>
            <table style='font-size:13px; color:#64748b; line-height:1.6;'>
                <tr><td><strong>Timestamp:</strong></td><td>" . date('Y-m-d H:i:s') . "</td></tr>
                <tr><td><strong>Server Host:</strong></td><td>" . htmlspecialchars($this->config['smtp_host'] ?? '') . "</td></tr>
                <tr><td><strong>Port:</strong></td><td>" . htmlspecialchars($this->config['smtp_port'] ?? '') . "</td></tr>
                <tr><td><strong>Encryption:</strong></td><td>" . strtoupper(htmlspecialchars($this->config['smtp_encryption'] ?? '')) . "</td></tr>
            </table>
        </div>";

        return $this->send($testEmail, $subject, $html);
    }

    /**
     * Send Inspection Report by Email with attached PDF
     */
    public function sendInspectionReportEmail(
        array $inspection,
        array $report,
        string|array $recipients,
        ?string $customNotes = '',
        ?string $pdfBinary = null
    ): array {
        $refCode = $inspection['reference_code'] ?? ('INSP-' . $inspection['id']);
        $score = number_format((float)($report['score'] ?? 100), 1);
        $status = strtoupper($inspection['status'] ?? 'COMPLETED');

        $entName = function_exists('enterprise_name') ? enterprise_name() : 'Datacenter Inspection System';
        $entSite = function_exists('enterprise_site') ? enterprise_site() : 'PCR Datacenter Facility';
        $entWebsite = function_exists('enterprise_setting') ? (string)enterprise_setting('website', '') : '';

        $subject = "{$entName} - Inspection Report - {$refCode} [{$status} - Score: {$score}%]";

        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 680px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
            <div style='background: #0f172a; padding: 24px; color: #ffffff;'>
                <h1 style='margin: 0; font-size: 20px; font-weight: 700; color: #38bdf8;'>" . htmlspecialchars($entName) . "</h1>
                <p style='margin: 4px 0 0; font-size: 13px; color: #94a3b8;'>" . htmlspecialchars($entSite) . " &bull; Official Compliance &amp; Evaluation Report</p>
            </div>
            
            <div style='padding: 24px; color: #1e293b; font-size: 14px; line-height: 1.6;'>
                <p>Hello,</p>
                <p>An inspection evaluation report for <strong>" . htmlspecialchars($entSite) . "</strong> has been finalized and dispatched for your review.</p>
                
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin: 20px 0;'>
                    <table style='width: 100%; font-size: 13px; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b; width: 140px;'><strong>Inspection Title:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'><strong>" . htmlspecialchars($inspection['title']) . "</strong></td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Reference Code:</strong></td>
                            <td style='padding: 6px 0;'><span style='background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-weight: 600;'>" . htmlspecialchars($refCode) . "</span></td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Facility / Site:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'><strong>" . htmlspecialchars($entSite) . "</strong></td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Facility Category:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'>" . htmlspecialchars($inspection['category_name'] ?? 'General Datacenter') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Equipment Unit:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'>" . htmlspecialchars($inspection['equipment_name'] ?? 'All Facility Racks') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Compliance Score:</strong></td>
                            <td style='padding: 6px 0;'><span style='color: " . ($score >= 80 ? '#16a34a' : '#dc2626') . "; font-size: 16px; font-weight: 700;'>" . $score . "%</span></td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Inspector:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'>" . htmlspecialchars($inspection['inspector_name'] ?? 'Staff') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Date:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'>" . date('F j, Y - H:i') . "</td>
                        </tr>
                    </table>
                </div>";

        if (!empty($customNotes)) {
            $htmlBody .= "
                <div style='background: #f1f5f9; border-left: 4px solid #0284c7; padding: 12px 16px; margin: 16px 0;'>
                    <strong style='color: #0f172a; font-size: 12px; text-transform: uppercase;'>Inspector Notes / Comments:</strong>
                    <p style='margin: 4px 0 0; color: #334155; font-size: 13px;'>" . nl2br(htmlspecialchars($customNotes)) . "</p>
                </div>";
        }

        $htmlBody .= "
                <p style='color: #64748b; font-size: 13px; margin-top: 20px;'>The official PDF inspection compliance report is attached to this email.</p>
            </div>
            
            <div style='background: #f1f5f9; padding: 16px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                " . htmlspecialchars($entName) . " &bull; " . htmlspecialchars($entSite) . (!empty($entWebsite) ? " &bull; " . htmlspecialchars($entWebsite) : "") . " &bull; Confidential &bull; Generated Automatically
            </div>
        </div>";

        $attachments = [];
        if ($pdfBinary !== null && strlen($pdfBinary) > 0) {
            $attachments[] = [
                'name' => "Inspection_Report_{$refCode}.pdf",
                'content' => $pdfBinary,
                'type' => 'application/pdf'
            ];
        }

        return $this->send($recipients, $subject, $htmlBody, $attachments);
    }

    /**
     * Send Password Reset Email with branded template
     *
     * @param string $recipientEmail
     * @param string $resetUrl
     * @param string $userName
     * @return array ['success' => bool, 'message' => string, 'log' => string]
     */
    public function sendPasswordResetEmail(string $recipientEmail, string $resetUrl, string $userName = 'User'): array {
        $entName = function_exists('enterprise_name') ? enterprise_name() : 'Datacenter Inspection System';
        $entSite = function_exists('enterprise_site') ? enterprise_site() : 'Datacenter Facility';
        $entWebsite = function_exists('enterprise_setting') ? (string)enterprise_setting('website', '') : '';

        $subject = "{$entName} - Password Reset Request";

        $htmlBody = "
        <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
            <div style='background: #0f172a; padding: 28px 24px; text-align: center; border-bottom: 3px solid #e75113;'>
                <h1 style='margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;'>" . htmlspecialchars($entName) . "</h1>
                <p style='margin: 6px 0 0; font-size: 13px; color: #94a3b8; font-weight: 500;'>" . htmlspecialchars($entSite) . " &bull; Security &amp; Access Portal</p>
            </div>
            
            <div style='padding: 32px 28px; color: #1e293b; font-size: 15px; line-height: 1.6;'>
                <p style='margin-top: 0; font-size: 16px;'>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p>We received a request to reset the password for your account associated with <strong>" . htmlspecialchars($recipientEmail) . "</strong>.</p>
                <p>To choose a new password and regain access to your account, click the button below:</p>
                
                <div style='text-align: center; margin: 32px 0;'>
                    <a href='" . htmlspecialchars($resetUrl) . "' style='display: inline-block; background: #e75113; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(231, 81, 19, 0.35);'>Reset My Password</a>
                </div>
                
                <div style='background: #f8fafc; border-left: 4px solid #e75113; border-radius: 4px; padding: 14px 18px; margin: 24px 0; font-size: 13px; color: #475569;'>
                    <strong>Security Notice:</strong>
                    <ul style='margin: 6px 0 0; padding-left: 18px;'>
                        <li>This password reset link will expire in <strong>60 minutes</strong>.</li>
                        <li>If you did not request this password reset, please disregard this email. Your password will remain unchanged.</li>
                    </ul>
                </div>
                
                <p style='color: #64748b; font-size: 12px; margin-top: 24px; word-break: break-all;'>
                    If the button doesn't work, copy and paste this link into your browser:<br>
                    <a href='" . htmlspecialchars($resetUrl) . "' style='color: #0284c7; text-decoration: underline;'>" . htmlspecialchars($resetUrl) . "</a>
                </p>
            </div>
            
            <div style='background: #f1f5f9; padding: 18px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                " . htmlspecialchars($entName) . " &bull; " . htmlspecialchars($entSite) . (!empty($entWebsite) ? " &bull; " . htmlspecialchars($entWebsite) : "") . "<br>
                This is an automated security notification. Please do not reply directly to this email.
            </div>
        </div>";

        return $this->send($recipientEmail, $subject, $htmlBody);
    }

    /**
     * Send Preventive Maintenance Plan Notification Email
     *
     * @param array $plan
     * @param string|array $recipients
     * @param string $action ('scheduled', 'updated', 'rescheduled', 'completed')
     * @param string|null $customNotes
     * @return array
     */
    public function sendMaintenancePlanNotification(
        array $plan,
        string|array $recipients,
        string $action = 'scheduled',
        ?string $customNotes = ''
    ): array {
        $planCode = $plan['plan_code'] ?? ('PMP-' . ($plan['id'] ?? '0'));
        $title = $plan['title'] ?? 'Preventive Maintenance Task';
        $priority = strtolower($plan['priority'] ?? 'medium');
        $status = strtoupper($plan['status'] ?? 'SCHEDULED');
        $recurrence = $plan['recurrence_label'] ?? ucfirst(str_replace('_', ' ', $plan['recurrence_type'] ?? 'One-Time'));
        $scheduledDateTime = ($plan['scheduled_date'] ?? date('Y-m-d')) . ' at ' . substr($plan['scheduled_time'] ?? '09:00', 0, 5);

        $entName = function_exists('enterprise_name') ? enterprise_name() : 'Datacenter Inspection System';
        $entSite = function_exists('enterprise_site') ? enterprise_site() : 'PCR Datacenter Facility';
        $entWebsite = function_exists('enterprise_setting') ? (string)enterprise_setting('website', '') : '';
        $appUrl = function_exists('url') ? rtrim(url(), '/') : '';
        $planUrl = !empty($plan['id']) ? "{$appUrl}/maintenance/show?id={$plan['id']}" : "{$appUrl}/maintenance";

        $priorityColors = [
            'critical' => ['bg' => '#fee2e2', 'text' => '#b91c1c', 'border' => '#f87171'],
            'high'     => ['bg' => '#ffedd5', 'text' => '#c2410c', 'border' => '#fb923c'],
            'medium'   => ['bg' => '#e0f2fe', 'text' => '#0369a1', 'border' => '#38bdf8'],
            'low'      => ['bg' => '#f1f5f9', 'text' => '#475569', 'border' => '#94a3b8'],
        ];
        $pStyle = $priorityColors[$priority] ?? $priorityColors['medium'];

        $actionLabel = match ($action) {
            'updated' => 'Maintenance Plan Updated',
            'rescheduled' => 'Maintenance Plan Rescheduled',
            'completed' => 'Maintenance Plan Completed',
            default => 'Preventive Maintenance Scheduled'
        };

        $subject = "[{$entName}] {$actionLabel}: {$title} [{$planCode}]";

        $htmlBody = "
        <div style='font-family: Arial, -apple-system, BlinkMacSystemFont, sans-serif; max-width: 680px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 26px 30px; color: #ffffff;'>
                <div style='display: flex; justify-content: space-between; align-items: center;'>
                    <div>
                        <h1 style='margin: 0; font-size: 21px; font-weight: 700; color: #38bdf8; letter-spacing: -0.3px;'>" . htmlspecialchars($entName) . "</h1>
                        <p style='margin: 4px 0 0; font-size: 13px; color: #94a3b8;'>" . htmlspecialchars($entSite) . " &bull; Preventive Maintenance Operations</p>
                    </div>
                </div>
            </div>

            <!-- Notification Banner -->
            <div style='background: #f0fdf4; border-bottom: 1px solid #bbf7d0; padding: 14px 30px;'>
                <span style='display: inline-block; background: #16a34a; color: #ffffff; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 4px; margin-right: 8px;'>Notification</span>
                <strong style='color: #166534; font-size: 14px;'>" . htmlspecialchars($actionLabel) . "</strong>
            </div>

            <!-- Content Area -->
            <div style='padding: 28px 30px; color: #1e293b; font-size: 14px; line-height: 1.6;'>
                <p style='margin-top: 0;'>Hello,</p>
                <p>A preventive maintenance operation has been scheduled / updated in the <strong>" . htmlspecialchars($entSite) . "</strong> facilities system. Please review the schedule and task specifications below:</p>

                <!-- Core Details Box -->
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0;'>
                    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;'>
                        <span style='font-family: monospace; font-size: 13px; font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 3px 8px; border-radius: 4px;'>" . htmlspecialchars($planCode) . "</span>
                        <span style='background: {$pStyle['bg']}; color: {$pStyle['text']}; border: 1px solid {$pStyle['border']}; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 4px;'>Priority: " . strtoupper($priority) . "</span>
                    </div>

                    <h2 style='margin: 0 0 16px; font-size: 17px; color: #0f172a; font-weight: 700;'>" . htmlspecialchars($title) . "</h2>

                    <table style='width: 100%; font-size: 13px; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b; width: 160px;'><strong>Target Equipment:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'><strong>" . htmlspecialchars($plan['equipment_name'] ?? 'General Datacenter Facility') . "</strong>" . (!empty($plan['serial_number']) ? " (S/N: " . htmlspecialchars($plan['serial_number']) . ")" : "") . "</td>
                        </tr>
                        " . (!empty($plan['room_location']) ? "
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Facility Location:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'>" . htmlspecialchars($plan['room_location']) . "</td>
                        </tr>" : "") . "
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Facility Domain:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'>" . htmlspecialchars($plan['category_name'] ?? 'General Infrastructure') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Scheduled Date & Time:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a; font-weight: 600;'><span style='color: #0284c7;'>" . htmlspecialchars($scheduledDateTime) . "</span>" . (!empty($plan['estimated_duration_minutes']) ? " (Est. " . (int)$plan['estimated_duration_minutes'] . " mins)" : "") . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Recurrence / Period:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'><span style='background: #f1f5f9; border: 1px solid #cbd5e1; padding: 2px 7px; border-radius: 4px; font-weight: 600;'>&#8635; " . htmlspecialchars($recurrence) . "</span></td>
                        </tr>
                        " . (!empty($plan['next_due_date']) ? "
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Next Cycle Due:</strong></td>
                            <td style='padding: 6px 0; color: #475569;'>" . htmlspecialchars($plan['next_due_date']) . "</td>
                        </tr>" : "") . "
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Assigned Personnel:</strong></td>
                            <td style='padding: 6px 0; color: #0f172a;'><strong>" . htmlspecialchars($plan['assigned_name'] ?? 'Unassigned') . "</strong>" . (!empty($plan['assigned_email']) ? " &lt;" . htmlspecialchars($plan['assigned_email']) . "&gt;" : "") . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 6px 0; color: #64748b;'><strong>Status:</strong></td>
                            <td style='padding: 6px 0;'><span style='font-weight: 700; color: #0369a1;'>" . htmlspecialchars($status) . "</span></td>
                        </tr>
                    </table>
                </div>";

        if (!empty($plan['checklist_scope'])) {
            $htmlBody .= "
                <div style='background: #fdfdfd; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px; margin: 18px 0;'>
                    <strong style='color: #0f172a; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;'>Checklist & Scope of Work:</strong>
                    <div style='margin-top: 8px; color: #334155; font-size: 13px; line-height: 1.7; white-space: pre-line;'>" . htmlspecialchars($plan['checklist_scope']) . "</div>
                </div>";
        }

        if (!empty($customNotes)) {
            $htmlBody .= "
                <div style='background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 16px 0; border-radius: 0 4px 4px 0;'>
                    <strong style='color: #1e3a8a; font-size: 12px; text-transform: uppercase;'>Planning Notes / Instructions:</strong>
                    <p style='margin: 4px 0 0; color: #1e40af; font-size: 13px;'>" . nl2br(htmlspecialchars($customNotes)) . "</p>
                </div>";
        }

        $htmlBody .= "
                <!-- Call to action button -->
                <div style='text-align: center; margin: 30px 0 10px;'>
                    <a href='" . htmlspecialchars($planUrl) . "' style='background: #e75113; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block; box-shadow: 0 2px 6px rgba(231,81,19,0.3);'>
                        Open Maintenance Plan in System &rarr;
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div style='background: #f8fafc; padding: 18px 30px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                " . htmlspecialchars($entName) . " &bull; " . htmlspecialchars($entSite) . (!empty($entWebsite) ? " &bull; " . htmlspecialchars($entWebsite) : "") . "<br>
                This automated maintenance notification was generated by the Datacenter Operations Management System.
            </div>
        </div>";

        return $this->send($recipients, $subject, $htmlBody);
    }
}

