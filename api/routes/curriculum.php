<?php

function handle_curriculum_get(): void {
    $gradeKey = $_GET['gradeKey'] ?? '';
    if (!in_array($gradeKey, ['first', 'second'])) {
        send_json(['units' => []]);
    }

    $pdo    = db();
    $uStmt  = $pdo->prepare(
        "SELECT id, unit_index, title, emoji FROM curriculum_units WHERE grade_key=? ORDER BY unit_index"
    );
    $uStmt->execute([$gradeKey]);
    $units  = $uStmt->fetchAll(PDO::FETCH_ASSOC);

    $lStmt  = $pdo->prepare(
        "SELECT id, unit_index, lesson_index, title, COALESCE(emoji, '📘') AS emoji
         FROM curriculum_lessons WHERE grade_key=? ORDER BY unit_index, lesson_index"
    );
    $lStmt->execute([$gradeKey]);
    $allLessons = $lStmt->fetchAll(PDO::FETCH_ASSOC);

    $lessonsByUnit = [];
    foreach ($allLessons as $l) {
        $lessonsByUnit[(int)$l['unit_index']][] = [
            'id'           => (int)$l['id'],
            'lesson_index' => (int)$l['lesson_index'],
            'title'        => $l['title'],
            'emoji'        => $l['emoji'] ?? '📘',
        ];
    }

    $result = [];
    foreach ($units as $u) {
        $ui       = (int)$u['unit_index'];
        $result[] = [
            'id'         => (int)$u['id'],
            'unit_index' => $ui,
            'title'      => $u['title'],
            'emoji'      => $u['emoji'],
            'lessons'    => $lessonsByUnit[$ui] ?? [],
        ];
    }
    send_json(['units' => $result]);
}

function handle_curriculum_unit_upsert(): void {
    require_admin();
    $b        = read_json_body();
    $gradeKey = $b['grade_key'] ?? '';
    $title    = trim($b['title'] ?? '');
    $emoji    = trim($b['emoji'] ?? '📚');

    if (!$title || !in_array($gradeKey, ['first', 'second'])) {
        send_json(['error' => 'بيانات غير صالحة'], 400);
    }

    $pdo = db();
    if (isset($b['unit_index'])) {
        $unitIndex = (int)$b['unit_index'];
    } else {
        $maxStmt = $pdo->prepare("SELECT COALESCE(MAX(unit_index),-1) FROM curriculum_units WHERE grade_key=?");
        $maxStmt->execute([$gradeKey]);
        $maxDb     = (int)$maxStmt->fetchColumn();
        $hardcoded = (int)($b['hardcoded_count'] ?? 0);
        $unitIndex = max($maxDb + 1, $hardcoded);
    }

    $pdo->prepare(
        "INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE title=VALUES(title), emoji=VALUES(emoji)"
    )->execute([$gradeKey, $unitIndex, $title, $emoji]);

    send_json(['ok' => true, 'unit_index' => $unitIndex]);
}

function handle_curriculum_lesson_upsert(): void {
    require_admin();
    $b           = read_json_body();
    $gradeKey    = $b['grade_key']  ?? '';
    $unitIndex   = isset($b['unit_index']) ? (int)$b['unit_index'] : null;
    $title       = trim($b['title'] ?? '');
    $emoji       = trim($b['emoji'] ?? '📘');

    if (!$title || !in_array($gradeKey, ['first', 'second']) || $unitIndex === null) {
        send_json(['error' => 'بيانات غير صالحة'], 400);
    }

    $pdo = db();
    if (isset($b['lesson_index'])) {
        $lessonIndex = (int)$b['lesson_index'];
    } else {
        $maxRow = $pdo->prepare(
            "SELECT COALESCE(MAX(lesson_index),-1) FROM curriculum_lessons WHERE grade_key=? AND unit_index=?"
        );
        $maxRow->execute([$gradeKey, $unitIndex]);
        $maxDb       = (int)$maxRow->fetchColumn();
        $hardcoded   = (int)($b['hardcoded_count'] ?? 0);
        $lessonIndex = max($maxDb + 1, $hardcoded);
    }

    $pdo->prepare(
        "INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title, emoji) VALUES (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE title=VALUES(title), emoji=VALUES(emoji)"
    )->execute([$gradeKey, $unitIndex, $lessonIndex, $title, $emoji]);

    send_json(['ok' => true, 'lesson_index' => $lessonIndex]);
}
