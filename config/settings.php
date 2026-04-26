<?php
// config/settings.php – Load, get, and update system settings

require_once __DIR__ . '/db.php';

/**
 * Load all settings as associative array
 */
function load_settings(): array
{
    $defaults = [
        'barangay_name'    => 'Barangay Connect',
        'municipality'     => '',
        'contact'          => '',
        'email'            => '',
        'clearance_fee'    => '50.00',
        'maintenance_mode' => '0',
    ];

    try {
        $pdo = get_db();
        $stmt = $pdo->query("SELECT SettingKey, SettingValue FROM SystemSettings");
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[$row['SettingKey']] = $row['SettingValue'];
        }
        return array_merge($defaults, $rows);
    } catch (PDOException $e) {
        // Table may not exist yet – return defaults
        error_log('load_settings() error: ' . $e->getMessage());
        return $defaults;
    }
}

/**
 * Get a single setting value by key
 */
function get_setting($key, $default = null)
{
    static $settings = null;
    if ($settings === null) {
        $settings = load_settings();
    }
    return $settings[$key] ?? $default;
}

/**
 * Update (or insert) a setting
 */
function update_setting($key, $value)
{
    $pdo = get_db();
    $stmt = $pdo->prepare("REPLACE INTO SystemSettings (SettingKey, SettingValue) VALUES (?, ?)");
    return $stmt->execute([$key, $value]);
}