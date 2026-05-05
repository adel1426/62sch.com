<?php

function handle_score_submit(): void {
    start_session_safe();
    ensure_student_scores_user_id_column();

    $b      = read_json_body();
    $grade  = $b['gradeKey']    ?? '';
    $unit   = $b['unitIndex']   ?? null;
    $lesson = $b['lessonIndex'] ?? null;
    $score  = $b['score']       ?? null;
    $total  = $b['total']       ?? null;

    $studentId = $_SESSION['student_id'] ?? null;
    $name = $studentId
        ? ($_SESSION['student_name'] ?? '')
        : mb_substr(trim((string)($b['studentName'] ?? '')), 0, 100);

    if ($name === '' || !$grade || $unit === null || $lesson === null || $score === null || $total === null) {
        send_json(['error' => 'بيانات ناقصة'], 400);
    }

    $score  = max(0, (int)$score);
    $total  = max(1, (int)$total);
    $unit   = (int)$unit;
    $lesson = (int)$lesson;

    $pdo = db();
    $pdo->beginTransaction();

    try {
        if ($studentId) {
            $pdo->prepare(
                "UPDATE student_scores SET user_id = ?
                 WHERE user_id IS NULL AND student_name = ?
                   AND grade_key = ? AND unit_index = ? AND lesson_index = ?"
            )->execute([$studentId, $name, $grade, $unit, $lesson]);

            $prevStmt = $pdo->prepare(
                "SELECT score FROM student_scores
                 WHERE user_id = ? AND grade_key = ? AND unit_index = ? AND lesson_index = ?
                 LIMIT 1 FOR UPDATE"
            );
            $prevStmt->execute([$studentId, $grade, $unit, $lesson]);
        } else {
            $prevStmt = $pdo->prepare(
                "SELECT score FROM student_scores
                 WHERE user_id IS NULL AND student_name = ?
                   AND grade_key = ? AND unit_index = ? AND lesson_index = ?
                 LIMIT 1 FOR UPDATE"
            );
            $prevStmt->execute([$name, $grade, $unit, $lesson]);
        }

        $previousBest = (int)($prevStmt->fetchColumn() ?: 0);
        $bestScore    = max($previousBest, $score);

        if ($studentId) {
            $pdo->prepare(
                "INSERT INTO student_scores (user_id, student_name, grade_key, unit_index, lesson_index, score, total)
                 VALUES (?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                   student_name = VALUES(student_name),
                   total = VALUES(total),
                   created_at = IF(VALUES(score) > score, NOW(), created_at),
                   score = GREATEST(score, VALUES(score))"
            )->execute([$studentId, $name, $grade, $unit, $lesson, $score, $total]);
        } else {
            $existing = $pdo->prepare(
                "SELECT id FROM student_scores
                 WHERE user_id IS NULL AND student_name = ?
                   AND grade_key = ? AND unit_index = ? AND lesson_index = ?
                 LIMIT 1"
            );
            $existing->execute([$name, $grade, $unit, $lesson]);
            $scoreId = $existing->fetchColumn();

            if ($scoreId) {
                $pdo->prepare(
                    "UPDATE student_scores
                     SET total = ?, created_at = IF(? > score, NOW(), created_at), score = GREATEST(score, ?)
                     WHERE id = ?"
                )->execute([$total, $score, $score, $scoreId]);
            } else {
                $pdo->prepare(
                    "INSERT INTO student_scores (user_id, student_name, grade_key, unit_index, lesson_index, score, total)
                     VALUES (NULL,?,?,?,?,?,?)"
                )->execute([$name, $grade, $unit, $lesson, $score, $total]);
            }
        }

        $pointsEarned = 0;
        $totalPoints  = null;

        if ($studentId) {
            $pointsEarned = max(0, $score - $previousBest) * 10;
            if ($pointsEarned > 0) {
                $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")
                    ->execute([$pointsEarned, $studentId]);
            }
            $pdo->prepare(
                "INSERT IGNORE INTO lesson_progress (user_id, grade_key, unit_index, lesson_index) VALUES (?,?,?,?)"
            )->execute([$studentId, $grade, $unit, $lesson]);

            $pts = $pdo->prepare("SELECT total_points FROM users WHERE id = ?");
            $pts->execute([$studentId]);
            $totalPoints = (int)$pts->fetchColumn();
        }

        $pdo->commit();
        send_json([
            'success'       => true,
            'points_earned' => $pointsEarned,
            'previous_best' => $previousBest,
            'best_score'    => $bestScore,
            'score_saved'   => $score >= $previousBest,
            'total_points'  => $totalPoints,
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handle_leaderboard(): void {
    ensure_student_scores_user_id_column();

    $grade = $_GET['gradeKey'] ?? '';
    if (!$grade) send_json(['error' => 'gradeKey مطلوب'], 400);

    $stmt = db()->prepare(
        "SELECT
           COALESCE(u.name, ss.student_name) AS student_name,
           COALESCE(u.username, '') AS username,
           SUM(ss.score) AS total_score,
           SUM(ss.total) AS total_possible,
           COUNT(*) AS lessons_count,
           ROUND(SUM(ss.score) / NULLIF(SUM(ss.total),0) * 100, 1) AS pct
         FROM student_scores ss
         LEFT JOIN users u ON u.id = ss.user_id
         WHERE ss.grade_key = ?
         GROUP BY COALESCE(CAST(ss.user_id AS CHAR), CONCAT('guest:', ss.student_name)),
                  COALESCE(u.name, ss.student_name),
                  COALESCE(u.username, '')
         ORDER BY total_score DESC, pct DESC
         LIMIT 5"
    );
    $stmt->execute([$grade]);
    send_json($stmt->fetchAll());
}
