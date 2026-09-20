<?php
namespace App\Services;

class FileUploadService {
    private string $uploadDir;
    private string $subPath;
    private array $allowedTypes;
    private int $maxSize;

    public function __construct(?string $subDir = null, ?array $customAllowedTypes = null, ?int $customMaxSize = null) {
        $config = require __DIR__ . '/../config/config.php';

        if ($subDir === 'maintenance') {
            $this->subPath = 'uploads/maintenance/';
            $this->uploadDir = dirname(__DIR__, 2) . '/public/' . $this->subPath;
            $this->allowedTypes = $customAllowedTypes ?: [
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
                'application/pdf',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain', 'text/csv',
                'application/zip', 'application/x-zip-compressed'
            ];
            $this->maxSize = $customMaxSize ?: (25 * 1024 * 1024); // 25MB
        } else {
            $this->subPath = 'uploads/inspections/';
            $this->uploadDir = $config['upload']['path'];
            $this->allowedTypes = $customAllowedTypes ?: $config['upload']['allowed_types'];
            $this->maxSize = $customMaxSize ?: $config['upload']['max_size'];
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function upload(array $file): array {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Invalid parameters.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
        }

        if ($file['size'] > $this->maxSize) {
            $maxMb = round($this->maxSize / (1024 * 1024));
            return ['success' => false, 'error' => "Exceeded filesize limit (max {$maxMb}MB)."];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        // Check if mime type is allowed or fallback by extension if finfo is generic octet-stream
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip'];

        if (!in_array($mimeType, $this->allowedTypes) && !in_array($extension, $allowedExtensions)) {
            return ['success' => false, 'error' => 'Invalid file format. Allowed: PDF, Word, Excel, CSV, Images, TXT, ZIP.'];
        }

        $filename = sprintf('%s_%s.%s', date('Ymd_His'), bin2hex(random_bytes(8)), $extension);
        $targetPath = $this->uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file.'];
        }

        return [
            'success' => true,
            'file_name' => $filename,
            'original_name' => $file['name'] ?? $filename,
            'file_path' => $this->subPath . $filename,
            'file_size' => (int)($file['size'] ?? 0),
            'file_type' => $mimeType
        ];
    }

    /**
     * Delete an uploaded file from disk safely
     */
    public static function deleteFile(string $relativePath): bool {
        $clean = ltrim($relativePath, '/\\');
        $fullPath = dirname(__DIR__, 2) . '/public/' . $clean;
        if (file_exists($fullPath) && is_file($fullPath)) {
            return @unlink($fullPath);
        }
        return false;
    }
}
