<?php
/**
 * Setup Script for Dashboard Market
 * Run this once after uploading files to initialize the application
 */

// Prevent direct access on production
if (file_exists('config.php')) {
    die('Setup already completed. Delete this file for security.');
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Dashboard Market</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .progress { background: #e9ecef; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .complete { text-align: center; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Dashboard Market Setup</h1>
        
        <?php
        $step = $_GET['step'] ?? 1;
        
        if ($step == 1) {
            // Step 1: Environment Setup
            ?>
            <div class="step">
                <h3>ขั้นตอนที่ 1: ตรวจสอบระบบและสร้างไดเรกทอรี</h3>
                <?php
                $setup_results = [];
                
                // Create directories
                $directories = ['data', 'logs', 'uploads'];
                foreach ($directories as $dir) {
                    if (!is_dir($dir)) {
                        if (@mkdir($dir, 0755, true)) {
                            $setup_results[] = "✓ สร้างโฟลเดอร์ $dir สำเร็จ";
                        } else {
                            $setup_results[] = "✗ ไม่สามารถสร้างโฟลเดอร์ $dir ได้";
                        }
                    } else {
                        $setup_results[] = "✓ โฟลเดอร์ $dir มีอยู่แล้ว";
                    }
                }
                
                // Set permissions
                foreach ($directories as $dir) {
                    if (is_dir($dir)) {
                        @chmod($dir, 0755);
                    }
                }
                
                // Create .htaccess if not exists
                if (!file_exists('.htaccess')) {
                    $htaccess_content = "RewriteEngine On\n";
                    $htaccess_content .= "# Security headers\n";
                    $htaccess_content .= "Header always set X-Content-Type-Options nosniff\n";
                    $htaccess_content .= "Header always set X-Frame-Options DENY\n";
                    $htaccess_content .= "Header always set X-XSS-Protection \"1; mode=block\"\n\n";
                    $htaccess_content .= "# Prevent access to sensitive files\n";
                    $htaccess_content .= "<Files \"*.sqlite\">\n    Deny from all\n</Files>\n";
                    $htaccess_content .= "<Files \"config.php\">\n    Deny from all\n</Files>\n";
                    
                    if (@file_put_contents('.htaccess', $htaccess_content)) {
                        $setup_results[] = "✓ สร้างไฟล์ .htaccess สำเร็จ";
                    } else {
                        $setup_results[] = "⚠ ไม่สามารถสร้างไฟล์ .htaccess ได้";
                    }
                }
                
                foreach ($setup_results as $result) {
                    echo "<div class='success'>$result</div>";
                }
                ?>
                <a href="?step=2"><button>ดำเนินการต่อ</button></a>
            </div>
            <?php
        } elseif ($step == 2) {
            // Step 2: Configuration
            ?>
            <div class="step">
                <h3>ขั้นตอนที่ 2: ตั้งค่าการกำหนดค่าระบบ</h3>
                
                <?php if ($_POST): ?>
                    <?php
                    // Process configuration
                    $config_content = "<?php\n";
                    $config_content .= "// Dashboard Market Configuration\n";
                    $config_content .= "// Generated by setup script on " . date('Y-m-d H:i:s') . "\n\n";
                    $config_content .= "define('DB_PATH', __DIR__ . '/data/dashboardmarket.sqlite');\n";
                    $config_content .= "define('ENVIRONMENT', '" . ($_POST['environment'] ?? 'production') . "');\n";
                    $config_content .= "define('DEBUG_MODE', " . (($_POST['debug'] ?? 'off') === 'on' ? 'true' : 'false') . ");\n";
                    $config_content .= "define('TIMEZONE', '" . ($_POST['timezone'] ?? 'Asia/Bangkok') . "');\n\n";
                    $config_content .= "// Security settings\n";
                    $config_content .= "define('SECURE_COOKIES', " . (($_POST['secure_cookies'] ?? 'on') === 'on' ? 'true' : 'false') . ");\n";
                    $config_content .= "define('SESSION_LIFETIME', 3600); // 1 hour\n\n";
                    $config_content .= "// Set timezone\n";
                    $config_content .= "date_default_timezone_set(TIMEZONE);\n\n";
                    $config_content .= "// Error reporting\n";
                    $config_content .= "if (ENVIRONMENT === 'development') {\n";
                    $config_content .= "    error_reporting(E_ALL);\n";
                    $config_content .= "    ini_set('display_errors', 1);\n";
                    $config_content .= "} else {\n";
                    $config_content .= "    error_reporting(0);\n";
                    $config_content .= "    ini_set('display_errors', 0);\n";
                    $config_content .= "}\n";
                    
                    if (@file_put_contents('config.php', $config_content)) {
                        echo "<div class='success'>✓ สร้างไฟล์ config.php สำเร็จ</div>";
                        echo "<a href='?step=3'><button>ดำเนินการต่อ</button></a>";
                    } else {
                        echo "<div class='error'>✗ ไม่สามารถสร้างไฟล์ config.php ได้</div>";
                    }
                    ?>
                <?php else: ?>
                    <form method="post">
                        <div class="form-group">
                            <label>Environment:</label>
                            <select name="environment">
                                <option value="production">Production</option>
                                <option value="development">Development</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Timezone:</label>
                            <select name="timezone">
                                <option value="Asia/Bangkok">Asia/Bangkok</option>
                                <option value="UTC">UTC</option>
                                <option value="Asia/Singapore">Asia/Singapore</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="debug" value="on"> เปิดใช้ Debug Mode
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="secure_cookies" value="on" checked> ใช้ Secure Cookies
                            </label>
                        </div>
                        
                        <button type="submit">สร้างการกำหนดค่า</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php
        } elseif ($step == 3) {
            // Step 3: Database Setup
            ?>
            <div class="step">
                <h3>ขั้นตอนที่ 3: ติดตั้งฐานข้อมูล</h3>
                <?php
                if (file_exists('config.php')) {
                    require_once 'config.php';
                    require_once 'db.php';
                    
                    try {
                        // Initialize database
                        $pdo = new PDO('sqlite:' . DB_PATH);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                        // Create tables
                        $sql = "CREATE TABLE IF NOT EXISTS api_settings (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            platform VARCHAR(50) NOT NULL,
                            partner_id VARCHAR(255),
                            partner_key TEXT,
                            access_token TEXT,
                            refresh_token TEXT,
                            shop_id VARCHAR(255),
                            is_sandbox BOOLEAN DEFAULT 1,
                            is_active BOOLEAN DEFAULT 1,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        )";
                        
                        $pdo->exec($sql);
                        
                        echo "<div class='success'>✓ ติดตั้งฐานข้อมูลสำเร็จ</div>";
                        echo "<div class='success'>✓ สร้างตาราง api_settings สำเร็จ</div>";
                        
                        // Insert default data
                        $platforms = ['shopee', 'lazada', 'tiktok'];
                        foreach ($platforms as $platform) {
                            $check_sql = "SELECT COUNT(*) FROM api_settings WHERE platform = ?";
                            $stmt = $pdo->prepare($check_sql);
                            $stmt->execute([$platform]);
                            
                            if ($stmt->fetchColumn() == 0) {
                                $insert_sql = "INSERT INTO api_settings (platform, is_sandbox, is_active) VALUES (?, 1, 0)";
                                $stmt = $pdo->prepare($insert_sql);
                                $stmt->execute([$platform]);
                                echo "<div class='success'>✓ เพิ่มการตั้งค่าเริ่มต้นสำหรับ $platform</div>";
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='error'>✗ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
                    }
                    
                    echo "<a href='?step=4'><button>ดำเนินการต่อ</button></a>";
                } else {
                    echo "<div class='error'>✗ ไม่พบไฟล์ config.php กรุณากลับไปขั้นตอนที่ 2</div>";
                }
                ?>
            </div>
            <?php
        } elseif ($step == 4) {
            // Step 4: Complete
            ?>
            <div class="complete">
                <h2>🎉 การติดตั้งเสร็จสิ้น!</h2>
                
                <div class="success">
                    <strong>Dashboard Market พร้อมใช้งานแล้ว</strong><br>
                    ระบบได้รับการตั้งค่าและติดตั้งเรียบร้อยแล้ว
                </div>
                
                <div class="progress">
                    <h4>ขั้นตอนถัดไป:</h4>
                    <ol>
                        <li><strong>ลบไฟล์ setup.php นี้เพื่อความปลอดภัย</strong></li>
                        <li>เข้าสู่ <a href="settings.php">หน้าตั้งค่า</a> เพื่อกำหนด API credentials</li>
                        <li>ทดสอบการเชื่อมต่อกับแต่ละ platform</li>
                        <li>เริ่มใช้งาน <a href="index.php">Dashboard</a></li>
                    </ol>
                </div>
                
                <div class="form-group">
                    <a href="index.php"><button>เข้าสู่ Dashboard</button></a>
                    <a href="settings.php"><button style="background: #28a745;">ตั้งค่า API</button></a>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 4px; color: #856404;">
                    <strong>⚠️ คำแนะนำด้านความปลอดภัย:</strong><br>
                    • ลบไฟล์ setup.php และ check_system.php ออกจาก server<br>
                    • ตรวจสอบให้แน่ใจว่าโฟลเดอร์ data/ ไม่สามารถเข้าถึงจาก web ได้<br>
                    • อัปเดต API credentials ใน settings.php<br>
                    • สำรองข้อมูลฐานข้อมูลเป็นประจำ
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
