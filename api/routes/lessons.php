<?php

function handle_lesson_content_get(): void {
    $g = $_GET['gradeKey']    ?? '';
    $u = $_GET['unitIndex']   ?? null;
    $l = $_GET['lessonIndex'] ?? null;

    if (!$g || $u === null || $l === null) {
        send_json(['content' => null, 'video_url' => null]);
    }

    $stmt = db()->prepare(
        "SELECT content, video_url, updated_at
         FROM lesson_content WHERE grade_key=? AND unit_index=? AND lesson_index=?"
    );
    $stmt->execute([$g, (int)$u, (int)$l]);
    $row = $stmt->fetch();

    if (!$row) send_json(['content' => null, 'video_url' => null]);
    send_json(['content' => $row['content'], 'video_url' => $row['video_url'], 'updated_at' => $row['updated_at']]);
}

function handle_lesson_content_put(): void {
    require_admin();
    $b   = read_json_body();
    $g   = $b['grade_key']    ?? '';
    $u   = $b['unit_index']   ?? null;
    $l   = $b['lesson_index'] ?? null;
    $c   = $b['content']      ?? '';
    $vid = $b['video_url']    ?? null;

    if (!$g || $u === null || $l === null) {
        send_json(['error' => 'حقول مطلوبة مفقودة'], 400);
    }

    db()->prepare(
        "INSERT INTO lesson_content (grade_key, unit_index, lesson_index, content, video_url)
         VALUES (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE content=VALUES(content), video_url=VALUES(video_url), updated_at=NOW()"
    )->execute([$g, (int)$u, (int)$l, $c ?: '', $vid]);

    Logger::info('Lesson content updated', ['grade' => $g, 'unit' => $u, 'lesson' => $l]);
    send_json(['success' => true]);
}
