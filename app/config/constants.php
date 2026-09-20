<?php
namespace App\Config;

/**
 * Datacenter Inspection System - Dynamic Constants, Colors & Role Permissions Manager
 */
class Constants {
    private static ?string $storageFile = null;
    private static array $constants = [];
    private static bool $initialized = false;

    /**
     * Standard platform navigation modules for role permissions
     */
    public static array $platformModules = [
        'dashboard' => ['label' => 'Dashboard Overview', 'icon' => 'fa-chart-line', 'description' => 'View system metrics, inspection stats and charts'],
        'maintenance' => ['label' => 'Preventive Maintenance', 'icon' => 'fa-calendar-check', 'description' => 'Schedule, track and visualize preventive maintenance plans'],
        'inspections' => ['label' => 'Inspections & Audits', 'icon' => 'fa-clipboard-check', 'description' => 'Conduct, evaluate and finalize inspections'],
        'reports' => ['label' => 'Reports & Analytics', 'icon' => 'fa-file-contract', 'description' => 'View, score and export PDF reports'],
        'equipment' => ['label' => 'Equipment Racks', 'icon' => 'fa-hard-drive', 'description' => 'Browse and update datacenter equipment units'],
        'categories' => ['label' => 'Categories & Checklists', 'icon' => 'fa-layer-group', 'description' => 'Manage facility domains and checklist items'],
        'users' => ['label' => 'User Accounts Management', 'icon' => 'fa-users', 'description' => 'Manage staff accounts and credentials'],
        'settings' => ['label' => 'System Settings & Config', 'icon' => 'fa-gears', 'description' => 'Manage platform parameters, statuses and audit logs']
    ];

    /**
     * Default core system constants definition with Read/Write role permissions and theme colors
     */
    private static array $defaults = [
        'STATUS_PENDING' => [
            'value' => 'pending',
            'type' => 'string',
            'category' => 'Inspection Statuses',
            'comment' => 'Inspection pending initiation',
            'color' => '#3b82f6',
            'is_core' => true
        ],
        'STATUS_IN_PROGRESS' => [
            'value' => 'in_progress',
            'type' => 'string',
            'category' => 'Inspection Statuses',
            'comment' => 'Inspection currently active',
            'color' => '#f6b100',
            'is_core' => true
        ],
        'STATUS_COMPLETED' => [
            'value' => 'completed',
            'type' => 'string',
            'category' => 'Inspection Statuses',
            'comment' => 'Inspection finalized and submitted',
            'color' => '#03c95a',
            'is_core' => true
        ],
        'STATUS_CANCELLED' => [
            'value' => 'cancelled',
            'type' => 'string',
            'category' => 'Inspection Statuses',
            'comment' => 'Inspection voided or cancelled',
            'color' => '#ef4444',
            'is_core' => true
        ],
        'RESULT_PASS' => [
            'value' => 'pass',
            'type' => 'string',
            'category' => 'Checklist Results',
            'comment' => 'Checklist item met compliant criteria',
            'color' => '#03c95a',
            'is_core' => true
        ],
        'RESULT_FAIL' => [
            'value' => 'fail',
            'type' => 'string',
            'category' => 'Checklist Results',
            'comment' => 'Checklist item failed inspection checks',
            'color' => '#ef4444',
            'is_core' => true
        ],
        'RESULT_WARNING' => [
            'value' => 'warning',
            'type' => 'string',
            'category' => 'Checklist Results',
            'comment' => 'Checklist item requires advisory attention',
            'color' => '#f6b100',
            'is_core' => true
        ],
        'RESULT_NA' => [
            'value' => 'na',
            'type' => 'string',
            'category' => 'Checklist Results',
            'comment' => 'Checklist item not applicable to equipment',
            'color' => '#64748b',
            'is_core' => true
        ],
        'EQUIPMENT_ACTIVE' => [
            'value' => 'active',
            'type' => 'string',
            'category' => 'Equipment Statuses',
            'comment' => 'Equipment in live production service',
            'color' => '#03c95a',
            'is_core' => true
        ],
        'EQUIPMENT_MAINTENANCE' => [
            'value' => 'maintenance',
            'type' => 'string',
            'category' => 'Equipment Statuses',
            'comment' => 'Equipment undergoing scheduled servicing',
            'color' => '#f6b100',
            'is_core' => true
        ],
        'EQUIPMENT_DECOMMISSIONED' => [
            'value' => 'decommissioned',
            'type' => 'string',
            'category' => 'Equipment Statuses',
            'comment' => 'Equipment retired from service',
            'color' => '#ef4444',
            'is_core' => true
        ],
        'ROLE_ADMIN' => [
            'value' => 'admin',
            'type' => 'string',
            'category' => 'User Roles',
            'comment' => 'Administrator with full platform read and write access',
            'color' => '#03c95a',
            'permissions' => [
                'dashboard' => 'write',
                'maintenance' => 'write',
                'inspections' => 'write',
                'reports' => 'write',
                'equipment' => 'write',
                'categories' => 'write',
                'users' => 'write',
                'settings' => 'write'
            ],
            'is_core' => true
        ],
        'ROLE_INSPECTOR' => [
            'value' => 'inspector',
            'type' => 'string',
            'category' => 'User Roles',
            'comment' => 'Field inspector for creating and conducting audits',
            'color' => '#3b82f6',
            'permissions' => [
                'dashboard' => 'read',
                'maintenance' => 'write',
                'inspections' => 'write',
                'reports' => 'read',
                'equipment' => 'read',
                'categories' => 'read',
                'users' => 'none',
                'settings' => 'none'
            ],
            'is_core' => true
        ],
        'ROLE_MANAGER' => [
            'value' => 'manager',
            'type' => 'string',
            'category' => 'User Roles',
            'comment' => 'Facility manager overseeing reports and equipment',
            'color' => '#f97316',
            'permissions' => [
                'dashboard' => 'read',
                'maintenance' => 'write',
                'inspections' => 'read',
                'reports' => 'write',
                'equipment' => 'write',
                'categories' => 'write',
                'users' => 'read',
                'settings' => 'none'
            ],
            'is_core' => true
        ]
    ];

    /**
     * Categories metadata definitions with icons, descriptions and prefixes
     */
    private static array $categoryMeta = [
        'User Roles' => [
            'icon' => 'fa-users-gear',
            'prefix' => 'ROLE_',
            'title' => 'User Roles & Permissions',
            'description' => 'Account roles and granular Read / Write permissions for each platform module'
        ],
        'Inspection Statuses' => [
            'icon' => 'fa-clipboard-list',
            'prefix' => 'STATUS_',
            'title' => 'Inspection Statuses',
            'description' => 'Lifecycle statuses for inspection workflows (e.g. pending, in_progress, completed)'
        ],
        'Checklist Results' => [
            'icon' => 'fa-square-check',
            'prefix' => 'RESULT_',
            'title' => 'Checklist Result Statuses',
            'description' => 'Evaluation ratings for checklist items (e.g. good/pass, warning, fail, na)'
        ],
        'Equipment Statuses' => [
            'icon' => 'fa-server',
            'prefix' => 'EQUIPMENT_',
            'title' => 'Equipment Operational Statuses',
            'description' => 'Condition and operational states for datacenter equipment and racks'
        ]
    ];

    private static function initPaths(): void {
        if (self::$storageFile === null) {
            self::$storageFile = __DIR__ . '/../../storage/constants_registry.json';
        }
    }

    public static function init(): void {
        if (self::$initialized) {
            return;
        }

        self::initPaths();
        self::load();

        $systemFallbacks = [
            'APP_NAME' => 'Datacenter Inspection',
            'APP_VERSION' => '1.0.0',
            'STATUS_PENDING' => 'pending',
            'STATUS_IN_PROGRESS' => 'in_progress',
            'STATUS_COMPLETED' => 'completed',
            'STATUS_CANCELLED' => 'cancelled',
            'RESULT_PASS' => 'pass',
            'RESULT_FAIL' => 'fail',
            'RESULT_WARNING' => 'warning',
            'RESULT_NA' => 'na',
            'EQUIPMENT_ACTIVE' => 'active',
            'EQUIPMENT_MAINTENANCE' => 'maintenance',
            'EQUIPMENT_DECOMMISSIONED' => 'decommissioned',
            'ROLE_ADMIN' => 'admin',
            'ROLE_INSPECTOR' => 'inspector',
            'ROLE_MANAGER' => 'manager',
            'SEVERITY_LOW' => 'low',
            'SEVERITY_MEDIUM' => 'medium',
            'SEVERITY_HIGH' => 'high',
            'SEVERITY_CRITICAL' => 'critical'
        ];

        foreach ($systemFallbacks as $k => $v) {
            if (!defined($k)) {
                define($k, $v);
            }
        }

        foreach (self::$constants as $name => $item) {
            if (!defined($name)) {
                define($name, $item['value']);
            }
        }

        self::$initialized = true;
    }

    public static function load(): array {
        self::initPaths();

        if (file_exists(self::$storageFile)) {
            $json = file_get_contents(self::$storageFile);
            $data = json_decode($json, true);
            if (is_array($data)) {
                // Normalize legacy indexed permission arrays if any
                foreach ($data as $k => &$item) {
                    if (isset($item['permissions']) && is_array($item['permissions'])) {
                        if (isset($item['permissions'][0]) && is_string($item['permissions'][0])) {
                            $normalized = [];
                            foreach ($item['permissions'] as $p) {
                                $normalized[$p] = 'write';
                            }
                            $item['permissions'] = $normalized;
                        }
                    }
                }
                self::$constants = $data;
                return self::$constants;
            }
        }

        self::$constants = self::$defaults;
        self::saveRegistry();
        return self::$constants;
    }

    public static function getAll(): array {
        if (!self::$initialized) {
            self::init();
        }
        return self::$constants;
    }

    public static function getGrouped(): array {
        $constants = self::getAll();
        $grouped = [];

        foreach (self::$categoryMeta as $catKey => $meta) {
            $grouped[$catKey] = [
                'meta' => $meta,
                'items' => []
            ];
        }

        foreach ($constants as $name => $item) {
            $cat = $item['category'] ?? '';
            if (isset($grouped[$cat])) {
                $grouped[$cat]['items'][$name] = $item;
            }
        }

        return $grouped;
    }

    public static function getCategories(): array {
        return array_keys(self::$categoryMeta);
    }

    /**
     * Get all active user roles as [value => Friendly Label]
     */
    public static function getRoleOptions(): array {
        $roles = [];
        $all = self::getAll();
        foreach ($all as $name => $item) {
            if (($item['category'] ?? '') === 'User Roles' || str_starts_with($name, 'ROLE_')) {
                $val = (string)$item['value'];
                $clean = preg_replace('/^ROLE_/', '', $name);
                $label = ucwords(strtolower(str_replace('_', ' ', $clean)));
                $roles[$val] = $label;
            }
        }
        if (empty($roles)) {
            $roles = [
                'admin' => 'Administrator',
                'inspector' => 'Inspector',
                'manager' => 'Facility Manager'
            ];
        }
        return $roles;
    }

    /**
     * Get status options for any domain category as [value => Friendly Label]
     */
    public static function getStatusOptions(string $category): array {
        $options = [];
        $all = self::getAll();
        foreach ($all as $name => $item) {
            if (($item['category'] ?? '') === $category) {
                $val = (string)$item['value'];
                $clean = preg_replace('/^(STATUS_|RESULT_|EQUIPMENT_|SEVERITY_|ROLE_|APP_)/', '', $name);
                $label = ucwords(strtolower(str_replace('_', ' ', $clean)));
                $options[$val] = $label;
            }
        }
        return $options;
    }

    /**
     * Get status options with complete metadata (label, name, color, icon)
     */
    public static function getStatusOptionsWithMeta(string $category): array {
        $options = [];
        $all = self::getAll();
        foreach ($all as $name => $item) {
            if (($item['category'] ?? '') === $category) {
                $val = (string)$item['value'];
                $clean = preg_replace('/^(STATUS_|RESULT_|EQUIPMENT_|SEVERITY_|ROLE_|APP_)/', '', $name);
                $label = ucwords(strtolower(str_replace('_', ' ', $clean)));
                $color = self::getStatusColor($val, $category);
                $options[$val] = [
                    'label' => $label,
                    'name' => $name,
                    'color' => $color,
                    'is_core' => !empty($item['is_core']),
                    'comment' => $item['comment'] ?? ''
                ];
            }
        }
        return $options;
    }

    /**
     * Convert Hex color string to rgba() format
     */
    public static function hexToRgba(string $hex, float $alpha = 1.0): string {
        $clean = ltrim(trim($hex), '#');
        if (strlen($clean) === 3) {
            $r = hexdec(str_repeat(substr($clean, 0, 1), 2));
            $g = hexdec(str_repeat(substr($clean, 1, 1), 2));
            $b = hexdec(str_repeat(substr($clean, 2, 1), 2));
        } elseif (strlen($clean) >= 6) {
            $r = hexdec(substr($clean, 0, 2));
            $g = hexdec(substr($clean, 2, 2));
            $b = hexdec(substr($clean, 4, 2));
        } else {
            return "rgba(139, 92, 246, {$alpha})";
        }
        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    /**
     * Get theme color for any status or checklist result
     */
    public static function getStatusColor(string $statusValue, string $category = ''): string {
        $val = strtolower(trim($statusValue));
        $all = self::getAll();

        // 1. If category provided, match in that category first
        if (!empty($category)) {
            foreach ($all as $name => $item) {
                if (($item['category'] ?? '') === $category && strtolower((string)($item['value'] ?? '')) === $val) {
                    if (!empty($item['color'])) {
                        return $item['color'];
                    }
                }
            }
        }

        // 2. Exact match on value across all categories
        foreach ($all as $name => $item) {
            if (strtolower((string)($item['value'] ?? '')) === $val) {
                if (!empty($item['color'])) {
                    return $item['color'];
                }
            }
        }

        // 3. Fallback standard colors by keyword
        return match ($val) {
            'pass', 'good', 'active', 'completed', 'admin' => '#03c95a',
            'warning', 'maintenance', 'in_progress', 'manager' => '#f6b100',
            'fail', 'critical', 'decommissioned', 'cancelled', 'overdue' => '#ef4444',
            'pending', 'info', 'inspector', 'scheduled' => '#3b82f6',
            'na', 'none', 'viewer' => '#64748b',
            default => '#8b5cf6' // default vibrant violet for custom statuses
        };
    }

    /**
     * Generate dynamic CSS styles for all registered constants across statuses and checklist results
     */
    public static function getDynamicStyles(): string {
        if (!self::$initialized) {
            self::init();
        }

        $all = self::getAll();
        $css = "/* Dynamic Status & Checklist Color Styles */\n";

        foreach ($all as $name => $item) {
            $val = strtolower(trim((string)($item['value'] ?? '')));
            if (empty($val)) continue;

            $cat = $item['category'] ?? '';
            $color = !empty($item['color']) ? $item['color'] : self::getStatusColor($val, $cat);
            $bg = self::hexToRgba($color, 0.14);
            $bgHover = self::hexToRgba($color, 0.08);
            $border = self::hexToRgba($color, 0.38);
            $glow = self::hexToRgba($color, 0.28);

            // Badge styling
            $css .= ".badge-{$val}, .badge.badge-{$val} {\n";
            $css .= "    background: {$bg} !important;\n";
            $css .= "    color: {$color} !important;\n";
            $css .= "    border: 1px solid {$border} !important;\n";
            $css .= "}\n";

            // Inspection sheet checklist radio group styling
            $css .= ".status-check-group input[value=\"{$val}\"]:checked + label {\n";
            $css .= "    background: {$bg} !important;\n";
            $css .= "    border-color: {$color} !important;\n";
            $css .= "    color: {$color} !important;\n";
            $css .= "    box-shadow: 0 0 0 1px {$glow} !important;\n";
            $css .= "}\n";
            $css .= ".status-check-group input[value=\"{$val}\"] + label:hover {\n";
            $css .= "    background: {$bgHover};\n";
            $css .= "    border-color: {$border};\n";
            $css .= "}\n";
            $css .= ".status-dot-{$val} {\n";
            $css .= "    background-color: {$color} !important;\n";
            $css .= "}\n";
        }

        return $css;
    }

    /**
     * Check if a role has access permission ('read' or 'write') to a platform module
     */
    public static function roleHasPermission(string $roleValue, string $module, string $level = 'read'): bool {
        if ($roleValue === 'admin' || $roleValue === 'ROLE_ADMIN') {
            return true;
        }

        $all = self::getAll();
        foreach ($all as $name => $item) {
            if (($item['category'] ?? '') === 'User Roles' && (string)($item['value'] ?? '') === $roleValue) {
                $permissions = $item['permissions'] ?? [];
                
                if (isset($permissions[0]) && is_string($permissions[0])) {
                    if (in_array($module, $permissions, true)) {
                        return true;
                    }
                    return false;
                }

                $permLevel = $permissions[$module] ?? ($module === 'maintenance' ? 'write' : 'none');
                if ($level === 'write') {
                    return $permLevel === 'write';
                }
                return ($permLevel === 'read' || $permLevel === 'write');
            }
        }

        if ($roleValue === 'inspector' || $roleValue === 'manager') {
            if ($level === 'write') {
                return in_array($module, ['maintenance', 'inspections', 'reports', 'equipment'], true);
            }
            return in_array($module, ['dashboard', 'maintenance', 'inspections', 'reports', 'equipment'], true);
        }

        return false;
    }

    /**
     * Get structured permissions map [module => 'none'|'read'|'write'] for a role
     */
    public static function getRolePermissions(string $roleValue): array {
        $result = [];
        foreach (array_keys(self::$platformModules) as $mod) {
            $result[$mod] = 'none';
        }

        if ($roleValue === 'admin') {
            foreach ($result as $mod => $v) {
                $result[$mod] = 'write';
            }
            return $result;
        }

        $all = self::getAll();
        foreach ($all as $name => $item) {
            if (($item['category'] ?? '') === 'User Roles' && (string)($item['value'] ?? '') === $roleValue) {
                $perms = $item['permissions'] ?? [];
                if (isset($perms[0]) && is_string($perms[0])) {
                    foreach ($perms as $p) {
                        $result[$p] = 'write';
                    }
                } elseif (is_array($perms)) {
                    foreach ($perms as $mod => $lvl) {
                        $result[$mod] = $lvl;
                    }
                }
                return $result;
            }
        }

        return $result;
    }

    public static function get(string $name, $default = null) {
        $name = strtoupper(trim($name));
        $all = self::getAll();
        return $all[$name]['value'] ?? $default;
    }

    public static function has(string $name): bool {
        $name = strtoupper(trim($name));
        $all = self::getAll();
        return isset($all[$name]);
    }

    /**
     * Add a new status or role with structured permissions and color
     */
    public static function add(string $titleOrName, ?string $value = null, string $category = 'Inspection Statuses', string $comment = '', string $type = 'string', array $permissions = [], ?string $color = null): array {
        self::initPaths();
        self::load();

        $title = trim($titleOrName);
        if (empty($title)) {
            return ['success' => false, 'message' => 'Name/Title is required.'];
        }

        $prefix = self::$categoryMeta[$category]['prefix'] ?? '';
        
        if (preg_match('/^[A-Z_][A-Z0-9_]*$/', $title)) {
            $name = $title;
        } else {
            $cleanUpper = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', trim($title)));
            $cleanUpper = trim($cleanUpper, '_');
            $name = $prefix . $cleanUpper;
        }

        if (empty($value)) {
            $cleanSlug = strtolower(preg_replace('/^(STATUS_|RESULT_|EQUIPMENT_|ROLE_)/', '', $name));
            $cleanSlug = preg_replace('/[^a-z0-9]+/', '_', $cleanSlug);
            $value = trim($cleanSlug, '_');
        }

        if (isset(self::$constants[$name])) {
            return [
                'success' => false,
                'message' => "Item '{$title}' ({$name}) already exists. Use edit to update it."
            ];
        }

        $castValue = self::castValue($value, $type);
        $finalColor = !empty($color) ? trim($color) : self::getStatusColor($castValue, $category);

        $record = [
            'value' => $castValue,
            'type' => $type,
            'category' => !empty($category) ? trim($category) : 'Inspection Statuses',
            'comment' => trim($comment),
            'color' => $finalColor,
            'is_core' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($category === 'User Roles') {
            $record['permissions'] = !empty($permissions) ? $permissions : [
                'dashboard' => 'read',
                'inspections' => 'write',
                'reports' => 'read',
                'equipment' => 'read',
                'categories' => 'none',
                'users' => 'none',
                'settings' => 'none'
            ];
        }

        self::$constants[$name] = $record;
        self::saveRegistry();

        if (!defined($name)) {
            define($name, $castValue);
        }

        return ['success' => true, 'message' => "Created '{$title}' successfully."];
    }

    /**
     * Update an existing status or role with structured permissions and color
     */
    public static function update(string $originalName, string $titleOrName, ?string $value = null, string $category = 'Inspection Statuses', string $comment = '', string $type = 'string', array $permissions = [], ?string $color = null): array {
        self::initPaths();
        self::load();

        $originalName = strtoupper(trim($originalName));
        if (!isset(self::$constants[$originalName])) {
            return ['success' => false, 'message' => "Item '{$originalName}' not found."];
        }

        $title = trim($titleOrName);
        $prefix = self::$categoryMeta[$category]['prefix'] ?? '';

        if (preg_match('/^[A-Z_][A-Z0-9_]*$/', $title)) {
            $newName = $title;
        } else {
            $cleanUpper = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', trim($title)));
            $cleanUpper = trim($cleanUpper, '_');
            $newName = $prefix . $cleanUpper;
        }

        if (empty($value)) {
            $cleanSlug = strtolower(preg_replace('/^(STATUS_|RESULT_|EQUIPMENT_|ROLE_)/', '', $newName));
            $value = trim(preg_replace('/[^a-z0-9]+/', '_', $cleanSlug), '_');
        }

        if ($originalName !== $newName && isset(self::$constants[$newName])) {
            return ['success' => false, 'message' => "Target identifier '{$newName}' already exists."];
        }

        $isCore = self::$constants[$originalName]['is_core'] ?? false;
        $castValue = self::castValue($value, $type);
        $finalColor = !empty($color) ? trim($color) : (self::$constants[$originalName]['color'] ?? self::getStatusColor($castValue, $category));

        $record = [
            'value' => $castValue,
            'type' => $type,
            'category' => !empty($category) ? trim($category) : (self::$constants[$originalName]['category'] ?? 'Inspection Statuses'),
            'comment' => trim($comment),
            'color' => $finalColor,
            'is_core' => $isCore,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($category === 'User Roles') {
            $record['permissions'] = !empty($permissions) ? $permissions : (self::$constants[$originalName]['permissions'] ?? [
                'dashboard' => 'read',
                'inspections' => 'write',
                'reports' => 'read',
                'equipment' => 'read',
                'categories' => 'none',
                'users' => 'none',
                'settings' => 'none'
            ]);
        }

        if ($originalName !== $newName) {
            unset(self::$constants[$originalName]);
        }

        self::$constants[$newName] = $record;
        self::saveRegistry();

        return ['success' => true, 'message' => "Updated '{$title}' successfully."];
    }

    public static function delete(string $name): array {
        self::initPaths();
        self::load();

        $name = strtoupper(trim($name));
        if (!isset(self::$constants[$name])) {
            return ['success' => false, 'message' => "Item '{$name}' not found."];
        }

        unset(self::$constants[$name]);
        self::saveRegistry();

        return ['success' => true, 'message' => "Item '{$name}' deleted successfully."];
    }

    private static function castValue($value, string $type) {
        switch ($type) {
            case 'integer':
            case 'int':
                return (int) $value;
            case 'boolean':
            case 'bool':
                if (is_bool($value)) return $value;
                $v = strtolower(trim((string)$value));
                return ($v === '1' || $v === 'true' || $v === 'yes' || $v === 'on');
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
            default:
                return (string) $value;
        }
    }

    private static function saveRegistry(): void {
        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(self::$storageFile, json_encode(self::$constants, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

\App\Config\Constants::init();
