<?php
/**
 * أداة هجرات قاعدة البيانات
 *
 * تشغيل من CLI:
 *   php api/migrate.php
 *
 * تشغيل من المتصفح (للمدير فقط بعد تسجيل الدخول):
 *   GET /api/migrate   (يجب إضافة المسار في .htaccess إذا أردت ذلك)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    // في المتصفح: حماية بسيطة بكلمة مرور الإدارة
    require_once __DIR__ . '/helpers.php';
    require_admin();
    header('Content-Type: text/plain; charset=utf-8');
}

function migrate_log(string $msg): void {
    global $isCli;
    if ($isCli) {
        echo $msg . PHP_EOL;
    } else {
        echo $msg . "\n";
        flush();
    }
}

try {
    $pdo = db();

    // إنشاء جدول تتبع الهجرات إن لم يكن موجوداً
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(200) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // جلب الهجرات المطبّقة مسبقاً
    $applied = $pdo->query("SELECT filename FROM migrations ORDER BY filename")
                   ->fetchAll(PDO::FETCH_COLUMN);
    $applied = array_flip($applied);

    // قراءة ملفات الهجرات بالترتيب
    $dir   = __DIR__ . '/migrations';
    $files = glob($dir . '/*.sql');
    sort($files);

    $ran = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (isset($applied[$name])) {
            migrate_log("  [تم مسبقاً] $name");
            continue;
        }

        migrate_log("  [تطبيق] $name ...");
        $sql = file_get_contents($file);

        // تقسيم على نهايات الجمل
        $statements = array_filter(
            array_map('trim', preg_split('/;\s*(\n|$)/u', $sql)),
            fn($s) => $s !== ''
        );

        $pdo->beginTransaction();
        try {
            foreach ($statements as $stmt) {
                $pdo->exec($stmt);
            }
            $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)")->execute([$name]);
            $pdo->commit();
            Logger::info("Migration applied: $name");
            migrate_log("  [نجح] $name");
            $ran++;
        } catch (Throwable $e) {
            $pdo->rollBack();
            Logger::error("Migration failed: $name", ['error' => $e->getMessage()]);
            migrate_log("  [خطأ] $name: " . $e->getMessage());
            if ($isCli) exit(1);
            http_response_code(500);
            return;
        }
    }

    if ($ran === 0) {
        migrate_log("لا توجد هجرات جديدة للتطبيق.");
    } else {
        migrate_log("تم تطبيق $ran هجرة بنجاح.");
    }

} catch (Throwable $e) {
    migrate_log("خطأ: " . $e->getMessage());
    if ($isCli) exit(1);
}
