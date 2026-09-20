<?php
namespace App\Services;

class EnterpriseService {
    private static ?string $storageFile = null;
    private static ?array $cachedConfig = null;
    private array $config = [];

    public function __construct() {
        if (self::$storageFile === null) {
            self::$storageFile = dirname(__DIR__, 2) . '/storage/enterprise_settings.json';
        }
        $this->loadSettings();
    }

    /**
     * Default enterprise profile configuration
     */
    public function defaultSettings(): array {
        return [
            'name' => 'Datacenter Inspection System',
            'site' => 'Datacenter Facility',
            'website' => 'https://datacenter.local',
            'logo_url' => '/assets/images/logo.png',
            'logo_path' => 'public/assets/images/logo.png',
            'is_custom_logo' => false,
            'tagline' => 'Datacenter Infrastructure & Maintenance Portal',
            'email' => 'admin@datacenter.local',
            'phone' => '+1 (555) 019-2834',
            'address' => 'Technology Park, Datacenter Facility 1',
            'updated_at' => null
        ];
    }

    /**
     * Load settings from storage/enterprise_settings.json
     */
    public function loadSettings(): array {
        if (self::$cachedConfig !== null) {
            $this->config = self::$cachedConfig;
            return $this->config;
        }

        $defaults = $this->defaultSettings();
        if (file_exists(self::$storageFile)) {
            $json = @file_get_contents(self::$storageFile);
            if ($json !== false) {
                $data = json_decode($json, true);
                if (is_array($data)) {
                    $this->config = array_merge($defaults, $data);
                    self::$cachedConfig = $this->config;
                    return $this->config;
                }
            }
        }

        $this->config = $defaults;
        self::$cachedConfig = $this->config;
        return $this->config;
    }

    /**
     * Get all enterprise settings
     */
    public function getSettings(): array {
        return $this->config;
    }

    /**
     * Get a specific setting value
     */
    public function get(string $key, mixed $default = null): mixed {
        return $this->config[$key] ?? $default;
    }

    /**
     * Save updated settings and optionally process a new logo file upload
     *
     * @param array $data Input form fields
     * @param array|null $file $_FILES['logo'] structure if uploaded
     * @return array ['success' => bool, 'message' => string]
     */
    public function saveSettings(array $data, ?array $file = null): array {
        $current = $this->loadSettings();

        $name = trim($data['name'] ?? '');
        $site = trim($data['site'] ?? '');
        $website = trim($data['website'] ?? '');
        $tagline = trim($data['tagline'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');

        if (empty($name)) {
            return ['success' => false, 'message' => 'Enterprise / Company Name is required.'];
        }

        $current['name'] = $name;
        $current['site'] = !empty($site) ? $site : 'Datacenter Facility';
        $current['website'] = $website;
        $current['tagline'] = $tagline;
        $current['email'] = $email;
        $current['phone'] = $phone;
        $current['address'] = $address;
        $current['updated_at'] = date('Y-m-d H:i:s');

        // Handle logo file upload if provided
        if ($file && isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->handleLogoUpload($file, $current);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            $current = $uploadResult['config'];
        }

        $baseDir = dirname(self::$storageFile);
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0777, true);
        }

        $saved = @file_put_contents(self::$storageFile, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($saved === false) {
            return ['success' => false, 'message' => 'Failed to persist enterprise settings to storage file.'];
        }

        $this->config = $current;
        self::$cachedConfig = $current;
        return ['success' => true, 'message' => 'Enterprise profile updated successfully.'];
    }

    /**
     * Upload and store new company logo
     */
    private function handleLogoUpload(array $file, array $current): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error code: ' . $file['error']];
        }

        // Max 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Uploaded logo exceeds the 5MB size limit.'];
        }

        $allowedMimes = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg'
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowedMimes[$mime])) {
            return ['success' => false, 'message' => 'Invalid image format. Allowed formats: PNG, JPG, SVG, WebP.'];
        }

        $ext = $allowedMimes[$mime];
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/branding';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        // Generate clean file name
        $fileName = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Failed to move uploaded logo to public branding directory.'];
        }

        // Delete old custom logo if one existed
        if (!empty($current['is_custom_logo']) && !empty($current['logo_path'])) {
            $oldFullPath = dirname(__DIR__, 2) . '/' . ltrim($current['logo_path'], '/\\');
            if (file_exists($oldFullPath) && !str_contains($oldFullPath, 'assets/images/logo.png')) {
                @unlink($oldFullPath);
            }
        }

        $current['logo_url'] = '/uploads/branding/' . $fileName;
        $current['logo_path'] = 'public/uploads/branding/' . $fileName;
        $current['is_custom_logo'] = true;
        $this->clearLogoCache();

        return ['success' => true, 'config' => $current];
    }

    /**
     * Reset logo to default system logo
     */
    public function resetLogo(): array {
        $current = $this->loadSettings();

        if (!empty($current['is_custom_logo']) && !empty($current['logo_path'])) {
            $oldFullPath = dirname(__DIR__, 2) . '/' . ltrim($current['logo_path'], '/\\');
            if (file_exists($oldFullPath) && !str_contains($oldFullPath, 'assets/images/logo.png')) {
                @unlink($oldFullPath);
            }
        }

        $defaults = $this->defaultSettings();
        $current['logo_url'] = $defaults['logo_url'];
        $current['logo_path'] = $defaults['logo_path'];
        @file_put_contents(self::$storageFile, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->config = $current;
        self::$cachedConfig = $current;
        $this->clearLogoCache();

        return ['success' => true, 'message' => 'Logo reset to system default successfully.'];
    }

    /**
     * Absolute disk path of the current logo
     */
    public function getLogoDiskPath(): ?string {
        $relPath = $this->config['logo_path'] ?? 'public/assets/images/logo.png';
        $fullPath = dirname(__DIR__, 2) . '/' . ltrim($relPath, '/\\');
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        // Fallback to assets/images/logo.png
        $fallback = dirname(__DIR__, 2) . '/public/assets/images/logo.png';
        if (file_exists($fallback)) {
            return $fallback;
        }

        return null;
    }

    /**
     * Clear cached logo thumbnails (called on logo change/reset)
     */
    public function clearLogoCache(): void {
        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/logo_thumb_*');
            if ($files) {
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
        }
    }

    /**
     * Base64 data URI of the logo (for Dompdf or inline images)
     * Automatically downscales high-res logos (e.g. 3000x2000) and caches a lightweight thumbnail
     * to avoid Dompdf hanging/slowdowns while keeping sharp report headers.
     */
    public function getLogoBase64(int $maxWidth = 400, int $maxHeight = 160): string {
        $diskPath = $this->getLogoDiskPath();
        if (!$diskPath || !file_exists($diskPath)) {
            return '';
        }

        $ext = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
        if ($ext === 'svg' || !extension_loaded('gd')) {
            $data = @file_get_contents($diskPath);
            if ($data !== false && strlen($data) > 0) {
                $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/png';
                return "data:{$mime};base64," . base64_encode($data);
            }
            return '';
        }

        $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $thumbFile = $cacheDir . '/logo_thumb_' . md5($diskPath) . "_{$maxWidth}x{$maxHeight}.png";
        if (file_exists($thumbFile) && filemtime($thumbFile) >= filemtime($diskPath)) {
            $cachedData = @file_get_contents($thumbFile);
            if ($cachedData !== false && strlen($cachedData) > 0) {
                return 'data:image/png;base64,' . base64_encode($cachedData);
            }
        }

        // Check image dimensions
        $imgInfo = @getimagesize($diskPath);
        if (!$imgInfo) {
            $data = @file_get_contents($diskPath);
            return $data ? 'data:image/png;base64,' . base64_encode($data) : '';
        }

        $origW = (int)$imgInfo[0];
        $origH = (int)$imgInfo[1];

        // If image is already reasonably sized, use directly
        if ($origW <= $maxWidth && $origH <= $maxHeight) {
            $data = @file_get_contents($diskPath);
            $mime = $imgInfo['mime'] ?? 'image/png';
            return "data:{$mime};base64," . base64_encode($data);
        }

        // Calculate aspect ratio downscaling
        $ratio = min($maxWidth / $origW, $maxHeight / $origH);
        $targetW = max(1, (int)round($origW * $ratio));
        $targetH = max(1, (int)round($origH * $ratio));

        $srcImg = match ($ext) {
            'png' => @imagecreatefrompng($diskPath),
            'jpg', 'jpeg' => @imagecreatefromjpeg($diskPath),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($diskPath) : null,
            default => null
        };

        if (!$srcImg) {
            $data = @file_get_contents($diskPath);
            return $data ? 'data:image/png;base64,' . base64_encode($data) : '';
        }

        $thumb = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $targetW, $targetH, $transparent);
        imagecopyresampled($thumb, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

        ob_start();
        imagepng($thumb, null, 6);
        $thumbData = ob_get_clean();

        @file_put_contents($thumbFile, $thumbData);

        imagedestroy($srcImg);
        imagedestroy($thumb);

        return 'data:image/png;base64,' . base64_encode($thumbData);
    }

    /**
     * Public web URL of the logo for HTML views
     */
    public function getLogoUrl(): string {
        return $this->config['logo_url'] ?? '/assets/images/logo.png';
    }
}
