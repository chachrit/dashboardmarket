<?php
/**
 * Database Configuration - Updated for MySQL only
 * แก้ไขค่าเหล่านี้ให้เหมาะสมกับสิ่งแวดล้อมของคุณ
 */

// เช็คว่าอยู่ใน localhost หรือ production
$is_localhost = (
    (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) ||
    (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'])) ||
    (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']))
);

if ($is_localhost) {
    // === การตั้งค่าสำหรับ localhost ===
    // หากคุณมี MySQL บน localhost ให้ใช้การตั้งค่านี้
    putenv('DM_DB_SERVER=localhost');
    putenv('DM_DB_NAME=realtime_marketplace');
    putenv('DM_DB_USER=root');
    putenv('DM_DB_PASS=');  // รหัสผ่าน MySQL บน localhost (หากมี)
    
} else {
    // === การตั้งค่าสำหรับ cPanel/Production ===
    // แก้ไขค่าเหล่านี้ให้ตรงกับ cPanel ของคุณ
    putenv('DM_DB_SERVER=localhost');  // มักจะเป็น localhost ใน cPanel
    putenv('DM_DB_NAME=zcwuapsz_realtime_marketplace');  // ชื่อฐานข้อมูลใน cPanel (มี prefix)
    putenv('DM_DB_USER=zcwuapsz');  // username ใน cPanel (มี prefix)
    putenv('DM_DB_PASS=Journal@25');  // รหัสผ่านฐานข้อมูลใน cPanel
}

// Legacy configuration for backward compatibility
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/data/dashboardmarket.sqlite');
}
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', $is_localhost ? 'development' : 'production');
}
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', $is_localhost);
}
if (!defined('TIMEZONE')) {
    define('TIMEZONE', 'Asia/Bangkok');
}

// Security settings
if (!defined('SECURE_COOKIES')) {
    define('SECURE_COOKIES', true);
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 3600); // 1 hour
}

// Set timezone
if (!function_exists('date_default_timezone_set') || date_default_timezone_get() !== TIMEZONE) {
    date_default_timezone_set(TIMEZONE);
}

// Error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
