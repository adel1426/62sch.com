<?php

function handle_progress_mark(): void {
    start_session_safe();
    $studentId = $_SESSION['student_id'] ?? null;
    if (!$studentId) send_json(['error' => 'يجب تسجيل الدخول أولاً'], 401);

    $b      = read_json_body();
    $grade  = $b['grade_key']    ?? '';
    $unit   = isset($b['unit_index'])   ? (int)$b['unit_index']   : null;
    $lesson = isset($b['lesson_index']) ? (int)$b['lesson_index'] : null;

    if (!$grade || $unit === null || $lesson === null) {
        send_json(['error' => 'بيانات ناقصة'], 400);
    }

    db()->prepare(
        "INSERT IGNORE INTO lesson_progress (user_id, grade_key, unit_index, lesson_index) VALUES (?,?,?,?)"
    )->execute([$studentId, $grade, $unit, $lesson]);

    send_json(['success' => true]);
}

function handle_progress_get(): void {
    start_session_safe();
    $studentId = $_SESSION['student_id'] ?? null;
    if (!$studentId) send_json(['completed' => [], 'videos' => [], 'total_points' => 0]);

    $grade  = $_GET['gradeKey'] ?? null;
    $sql    = "SELECT grade_key, unit_index, lesson_index FROM lesson_progress WHERE user_id = ?";
    $params = [$studentId];
    if ($grade) { $sql .= " AND grade_key = ?"; $params[] = $grade; }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $completed = array_map(
        fn($r) => $r['grade_key'] . '|' . $r['unit_index'] . '|' . $r['lesson_index'],
        $stmt->fetchAll()
    );

    $videos = [];
    try {
        $vSql    = "SELECT grade_key, unit_index, lesson_index FROM video_progress WHERE user_id = ?";
        $vParams = [$studentId];
        if ($grade) { $vSql .= " AND grade_key = ?"; $vParams[] = $grade; }
        $vStmt = db()->prepare($vSql);
        $vStmt->execute($vParams);
        $videos = array_map(
            fn($r) => $r['grade_key'] . '|' . $r['unit_index'] . '|' . $r['lesson_index'],
            $vStmt->fetchAll()
        );
    } catch (Throwable $e) {
        Logger::warn('video_progress unavailable', ['err' => $e->getMessage()]);
    }

    $pts = db()->prepare("SELECT total_points FROM users WHERE id = ?");
    $pts->execute([$studentId]);
    $row = $pts->fetch();

    send_json([
        'completed'    => $completed,
        'videos'       => $videos,
        'total_points' => $row ? (int)$row['total_points'] : 0,
    ]);
}

function handle_progress_summary(): void {
    start_session_safe();
    $studentId = $_SESSION['student_id'] ?? null;
    if (!$studentId) send_json(['error' => 'غير مسجّل'], 401);

    $stmt = db()->prepare(
        "SELECT grade_key, unit_index, lesson_index
         FROM lesson_progress WHERE user_id = ? ORDER BY completed_at DESC"
    );
    $stmt->execute([$studentId]);
    $rows = $stmt->fetchAll();

    $byGrade = [];
    foreach ($rows as $r) {
        $byGrade[$r['grade_key']][] = ['unit' => (int)$r['unit_index'], 'lesson' => (int)$r['lesson_index']];
    }

    $u = db()->prepare("SELECT name, grade_level, total_points FROM users WHERE id = ?");
    $u->execute([$studentId]);

    send_json([
        'user'     => $u->fetch(),
        'progress' => $byGrade,
        'total'    => count($rows),
    ]);
}

function handle_progress_video(): void {
    start_session_safe();
    $userId = $_SESSION['student_id'] ?? null;
    if (!$userId) send_json(['error' => 'غير مسجّل'], 401);

    $b           = read_json_body();
    $gradeKey    = $b['gradeKey']    ?? '';
    $unitIndex   = isset($b['unitIndex'])   ? (int)$b['unitIndex']   : null;
    $lessonIndex = isset($b['lessonIndex']) ? (int)$b['lessonIndex'] : null;

    if (!$gradeKey || $unitIndex === null || $lessonIndex === null) {
        send_json(['error' => 'بيانات ناقصة'], 400);
    }

    $pdo   = db();
    $check = $pdo->prepare(
        "SELECT id FROM video_progress WHERE user_id=? AND grade_key=? AND unit_index=? AND lesson_index=?"
    );
    $check->execute([$userId, $gradeKey, $unitIndex, $lessonIndex]);
    if ($check->fetch()) {
        send_json(['ok' => true, 'points_earned' => 0, 'already_watched' => true]);
    }

    $pdo->prepare(
        "INSERT IGNORE INTO video_progress (user_id, grade_key, unit_index, lesson_index) VALUES (?,?,?,?)"
    )->execute([$userId, $gradeKey, $unitIndex, $lessonIndex]);

    $points = 5;
    $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")
        ->execute([$points, $userId]);

    $totalStmt = $pdo->prepare("SELECT total_points FROM users WHERE id = ?");
    $totalStmt->execute([$userId]);
    $totalPoints = (int)$totalStmt->fetchColumn();

    send_json(['ok' => true, 'points_earned' => $points, 'total_points' => $totalPoints]);
}
