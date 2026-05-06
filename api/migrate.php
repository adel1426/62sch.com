<?php
/**
 * Database migration runner.
 *
 * CLI:
 *   php api/migrate.php
 *   php api/migrate.php --file=005_second_grade_curriculum_with_lesson_emojis.sql
 *
 * Browser, after admin login:
 *   GET /api/migrate
 *   GET /api/migrate?file=005_second_grade_curriculum_with_lesson_emojis.sql
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    require_once __DIR__ . '/helpers.php';
    require_admin();
    header('Content-Type: text/plain; charset=utf-8');
}

function migrate_log(string $msg): void {
    global $isCli;
    echo $msg . ($isCli ? PHP_EOL : "\n");
    if (!$isCli) flush();
}

function migration_statements(string $sql): array {
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    return array_values(array_filter(
        array_map('trim', preg_split('/;\s*(\r?\n|$)/u', $sql)),
        fn($s) => $s !== ''
    ));
}

function run_migration_statement(PDO $pdo, string $stmt): void {
    $result = $pdo->query($stmt);
    if ($result instanceof PDOStatement) {
        do {
            $result->fetchAll();
        } while ($result->nextRowset());
        $result->closeCursor();
    }
}

try {
    $pdo = db();

    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(200) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $applied = $pdo->query("SELECT filename FROM migrations ORDER BY filename")
                   ->fetchAll(PDO::FETCH_COLUMN);
    $applied = array_flip($applied);

    $only = $_GET['file'] ?? null;
    if ($isCli) {
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if (str_starts_with($arg, '--file=')) {
                $only = substr($arg, 7);
            }
        }
    }

    if ($only !== null && !preg_match('/^[A-Za-z0-9_.-]+\.sql$/', $only)) {
        migrate_log('خطأ: اسم ملف الهجرة غير صالح');
        if ($isCli) exit(1);
        http_response_code(400);
        return;
    }

    $dir = __DIR__ . '/migrations';
    $files = $only ? [$dir . '/' . $only] : glob($dir . '/*.sql');
    sort($files);

    $ran = 0;
    foreach ($files as $file) {
        if (!is_file($file)) {
            migrate_log('خطأ: ملف الهجرة غير موجود: ' . basename($file));
            if ($isCli) exit(1);
            http_response_code(404);
            return;
        }

        $name = basename($file);
        if (!$only && str_contains($name, 'clear_all_content')) {
            migrate_log("  [تخطي ملف خطير] $name");
            continue;
        }

        if (isset($applied[$name])) {
            migrate_log("  [تم مسبقًا] $name");
            continue;
        }

        migrate_log("  [تطبيق] $name ...");
        $statements = migration_statements(file_get_contents($file));

        try {
            foreach ($statements as $stmt) {
                run_migration_statement($pdo, $stmt);
            }
            $pdo->prepare("INSERT IGNORE INTO migrations (filename) VALUES (?)")->execute([$name]);
            Logger::info("Migration applied: $name");
            migrate_log("  [نجح] $name");
            $ran++;
        } catch (Throwable $e) {
            Logger::error("Migration failed: $name", ['error' => $e->getMessage()]);
            migrate_log("  [خطأ] $name: " . $e->getMessage());
            if ($isCli) exit(1);
            http_response_code(500);
            return;
        }
    }

    migrate_log($ran === 0
        ? 'لا توجد هجرات جديدة للتطبيق.'
        : "تم تطبيق $ran هجرة بنجاح."
    );
} catch (Throwable $e) {
    migrate_log('خطأ: ' . $e->getMessage());
    if ($isCli) exit(1);
}
