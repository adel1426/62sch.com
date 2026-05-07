<?php

function handle_admin_stats(): void {
    require_admin();
    ensure_student_scores_user_id_column();

    $pdo = db();

    $students_count    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_completions = (int)$pdo->query("SELECT COUNT(*) FROM lesson_progress")->fetchColumn();
    $total_points      = (int)$pdo->query("SELECT COALESCE(SUM(total_points),0) FROM users")->fetchColumn();

    $total_videos = 0;
    try { $total_videos = (int)$pdo->query("SELECT COUNT(*) FROM video_progress")->fetchColumn(); } catch (Throwable $e) {
        Logger::warn('video_progress table missing or inaccessible', ['err' => $e->getMessage()]);
    }

    $stmt     = $pdo->query(
        "SELECT u.id, u.name, u.username, u.grade_level, u.class_name, u.total_points, u.created_at,
                COUNT(DISTINCT lp.id) AS lessons_done
         FROM users u
         LEFT JOIN lesson_progress lp ON lp.user_id = u.id
         GROUP BY u.id, u.name, u.username, u.grade_level, u.class_name, u.total_points, u.created_at
         ORDER BY u.grade_level ASC, u.class_name ASC, u.total_points DESC, u.name ASC"
    );
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $vCounts = [];
    try {
        $vStmt = $pdo->query("SELECT user_id, COUNT(DISTINCT grade_key,unit_index,lesson_index) AS vc FROM video_progress GROUP BY user_id");
        while ($row = $vStmt->fetch()) $vCounts[(int)$row['user_id']] = (int)$row['vc'];
    } catch (Throwable $e) {
        Logger::warn('Could not fetch video counts', ['err' => $e->getMessage()]);
    }

    $scoreStats = [];
    try {
        $scoreStmt = $pdo->query(
            "SELECT user_id,
                    ROUND(SUM(score) / NULLIF(SUM(total), 0) * 100, 1) AS avg_score,
                    MAX(created_at) AS last_score_at
             FROM student_scores WHERE user_id IS NOT NULL GROUP BY user_id"
        );
        while ($row = $scoreStmt->fetch()) {
            $scoreStats[(int)$row['user_id']] = [
                'avg_score'     => $row['avg_score'] !== null ? (float)$row['avg_score'] : null,
                'last_score_at' => $row['last_score_at'],
            ];
        }
    } catch (Throwable $e) {
        Logger::warn('Could not fetch score stats', ['err' => $e->getMessage()]);
    }

    $lastLessons = [];
    try {
        $lpStmt = $pdo->query("SELECT user_id, MAX(completed_at) AS last_lesson_at FROM lesson_progress GROUP BY user_id");
        while ($row = $lpStmt->fetch()) $lastLessons[(int)$row['user_id']] = $row['last_lesson_at'];
    } catch (Throwable $e) {
        Logger::warn('Could not fetch last lesson dates', ['err' => $e->getMessage()]);
    }

    $lastVideos = [];
    try {
        $vpStmt = $pdo->query("SELECT user_id, MAX(watched_at) AS last_video_at FROM video_progress GROUP BY user_id");
        while ($row = $vpStmt->fetch()) $lastVideos[(int)$row['user_id']] = $row['last_video_at'];
    } catch (Throwable $e) {
        Logger::warn('Could not fetch last video dates', ['err' => $e->getMessage()]);
    }

    foreach ($students as &$s) {
        $id                = (int)$s['id'];
        $s['id']           = $id;
        $s['total_points'] = (int)$s['total_points'];
        $s['lessons_done'] = (int)$s['lessons_done'];
        $s['class_name']   = $s['class_name'] ?? null;
        $s['videos_done']  = $vCounts[$id] ?? 0;
        $s['avg_score']    = $scoreStats[$id]['avg_score'] ?? null;

        $activityDates     = array_filter([
            $s['created_at']                       ?? null,
            $lastLessons[$id]                      ?? null,
            $lastVideos[$id]                       ?? null,
            $scoreStats[$id]['last_score_at']      ?? null,
        ]);
        $s['last_activity'] = $activityDates ? max($activityDates) : null;
    }
    unset($s);

    $topStmt    = $pdo->query(
        "SELECT grade_key, unit_index, lesson_index, COUNT(*) AS cnt
         FROM lesson_progress GROUP BY grade_key, unit_index, lesson_index
         ORDER BY cnt DESC LIMIT 5"
    );
    $top_lessons = $topStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($top_lessons as &$l) {
        $l['cnt']          = (int)$l['cnt'];
        $l['unit_index']   = (int)$l['unit_index'];
        $l['lesson_index'] = (int)$l['lesson_index'];
    }

    $lowStmt    = $pdo->query(
        "SELECT grade_key, unit_index, lesson_index, COUNT(*) AS cnt
         FROM lesson_progress GROUP BY grade_key, unit_index, lesson_index
         HAVING cnt <= 2 ORDER BY cnt ASC LIMIT 5"
    );
    $low_lessons = $lowStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($low_lessons as &$l) {
        $l['cnt']          = (int)$l['cnt'];
        $l['unit_index']   = (int)$l['unit_index'];
        $l['lesson_index'] = (int)$l['lesson_index'];
    }

    send_json([
        'students_count'    => $students_count,
        'total_completions' => $total_completions,
        'total_videos'      => $total_videos,
        'total_points'      => $total_points,
        'students'          => $students,
        'top_lessons'       => $top_lessons,
        'low_lessons'       => $low_lessons,
    ]);
}

function handle_admin_student_progress(int $userId): void {
    require_admin();
    $pdo  = db();

    $stmt = $pdo->prepare(
        "SELECT grade_key, unit_index, lesson_index FROM lesson_progress WHERE user_id = ?"
    );
    $stmt->execute([$userId]);
    $completed = array_map(
        fn($r) => $r['grade_key'] . '|' . $r['unit_index'] . '|' . $r['lesson_index'],
        $stmt->fetchAll()
    );

    $videos = [];
    try {
        $vStmt = $pdo->prepare(
            "SELECT DISTINCT grade_key, unit_index, lesson_index FROM video_progress WHERE user_id = ?"
        );
        $vStmt->execute([$userId]);
        $videos = array_map(
            fn($r) => $r['grade_key'] . '|' . $r['unit_index'] . '|' . $r['lesson_index'],
            $vStmt->fetchAll()
        );
    } catch (Throwable $e) {
        Logger::warn('Could not fetch student video progress', ['user_id' => $userId, 'err' => $e->getMessage()]);
    }

    send_json(['completed' => $completed, 'videos' => $videos]);
}

function handle_admin_student_delete(int $userId): void {
    require_admin();
    if (!$userId) send_json(['error' => 'معرّف غير صالح'], 400);
    $pdo = db();
    $pdo->prepare("DELETE FROM student_scores WHERE user_id = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
    Logger::info('Admin deleted student', ['student_id' => $userId]);
    send_json(['ok' => true]);
}

function handle_admin_reset_scores(string $grade): void {
    require_admin();
    if (!in_array($grade, ['first', 'second'])) {
        send_json(['error' => 'مرحلة غير صالحة'], 400);
    }
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE grade_level = ?");
    $stmt->execute([$grade]);
    $ids  = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($ids)) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM lesson_progress WHERE user_id IN ($ph)")->execute($ids);
        $pdo->prepare("DELETE FROM video_progress  WHERE user_id IN ($ph)")->execute($ids);
        $pdo->prepare("DELETE FROM student_scores  WHERE user_id IN ($ph)")->execute($ids);
        $pdo->prepare("UPDATE users SET total_points = 0 WHERE id IN ($ph)")->execute($ids);
    }
    $pdo->prepare("DELETE FROM student_scores WHERE grade_key = ?")->execute([$grade]);

    Logger::info('Admin reset scores', ['grade' => $grade, 'users' => count($ids)]);
    send_json(['ok' => true, 'affected' => count($ids)]);
}

function handle_admin_reset_students(string $grade): void {
    require_admin();
    if (!in_array($grade, ['first', 'second'])) {
        send_json(['error' => 'مرحلة غير صالحة'], 400);
    }
    $pdo = db();
    // حذف النتائج أولاً (بما فيها الأسماء المخزّنة)
    $pdo->prepare("DELETE FROM student_scores WHERE grade_key = ?")->execute([$grade]);
    // ثم حذف المستخدمين (lesson_progress و video_progress تُحذف تلقائياً بـ CASCADE)
    $stmt = $pdo->prepare("DELETE FROM users WHERE grade_level = ?");
    $stmt->execute([$grade]);
    $affected = $stmt->rowCount();

    Logger::info('Admin reset students', ['grade' => $grade, 'deleted' => $affected]);
    send_json(['ok' => true, 'affected' => $affected]);
}

function handle_admin_reset_curriculum(string $grade): void {
    require_admin();
    if (!in_array($grade, ['first', 'second'])) {
        send_json(['error' => 'مرحلة غير صالحة'], 400);
    }
    $pdo = db();
    // حذف الأسئلة والدروس والوحدات للمرحلة (الجداول مرتبطة بـ grade_key)
    try { $pdo->prepare("DELETE FROM questions WHERE grade_key = ?")->execute([$grade]); } catch (Throwable $e) {}
    try { $pdo->prepare("DELETE FROM curriculum_lessons WHERE grade_key = ?")->execute([$grade]); } catch (Throwable $e) {}
    try { $pdo->prepare("DELETE FROM curriculum_units  WHERE grade_key = ?")->execute([$grade]); } catch (Throwable $e) {}

    Logger::info('Admin reset curriculum', ['grade' => $grade]);
    send_json(['ok' => true]);
}
