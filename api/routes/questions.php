<?php

function cast_question(array $row): array {
    $row['id']             = (int)$row['id'];
    $row['unit_index']     = (int)$row['unit_index'];
    $row['lesson_index']   = (int)$row['lesson_index'];
    $row['correct_answer'] = (int)$row['correct_answer'];
    return $row;
}

function handle_questions_counts(): void {
    $grade = $_GET['gradeKey'] ?? '';
    if (!$grade) send_json(['error' => 'gradeKey مطلوب'], 400);

    $stmt = db()->prepare(
        "SELECT unit_index, lesson_index, COUNT(*) AS count
         FROM questions WHERE grade_key = ?
         GROUP BY unit_index, lesson_index"
    );
    $stmt->execute([$grade]);
    $counts = [];
    foreach ($stmt->fetchAll() as $r) {
        $counts[$r['unit_index'] . '|' . $r['lesson_index']] = (int)$r['count'];
    }
    send_json($counts);
}

function handle_questions_list(): void {
    $where  = [];
    $params = [];
    if (isset($_GET['gradeKey'])) {
        $where[]  = 'grade_key = ?';
        $params[] = $_GET['gradeKey'];
    }
    if (isset($_GET['unitIndex'])) {
        $where[]  = 'unit_index = ?';
        $params[] = (int)$_GET['unitIndex'];
    }
    if (isset($_GET['lessonIndex'])) {
        $where[]  = 'lesson_index = ?';
        $params[] = (int)$_GET['lessonIndex'];
    }

    $sql = 'SELECT * FROM questions';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY created_at ASC, id ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r = cast_question($r);
    send_json($rows);
}

function handle_questions_random(): void {
    $grade  = $_GET['gradeKey'] ?? '';
    $unit   = isset($_GET['unitIndex']) ? (int)$_GET['unitIndex'] : null;
    $count  = min((int)($_GET['count'] ?? 2), 20);
    if (!$grade) send_json(['error' => 'gradeKey مطلوب'], 400);

    $sql    = "SELECT * FROM questions WHERE grade_key = ?";
    $params = [$grade];
    if ($unit !== null) { $sql .= " AND unit_index = ?"; $params[] = $unit; }
    $sql .= " ORDER BY RAND() LIMIT ?";
    $params[] = $count;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r = cast_question($r);
    send_json($rows);
}

function handle_question_create(): void {
    require_admin();
    $b        = read_json_body();
    $required = ['grade_key','unit_index','lesson_index','question_text','option_a','option_b','option_c','option_d','correct_answer'];
    foreach ($required as $f) {
        if (!isset($b[$f]) || $b[$f] === '') {
            send_json(['error' => 'حقول مطلوبة مفقودة'], 400);
        }
    }

    try {
        $stmt = db()->prepare(
            "INSERT INTO questions
             (grade_key, unit_index, lesson_index, question_text, question_hash,
              option_a, option_b, option_c, option_d, correct_answer, explanation, image_url)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $b['grade_key'], (int)$b['unit_index'], (int)$b['lesson_index'],
            $b['question_text'], hash('sha256', $b['question_text']),
            $b['option_a'], $b['option_b'], $b['option_c'], $b['option_d'],
            (int)$b['correct_answer'], $b['explanation'] ?? null, $b['image_url'] ?? null,
        ]);
        $id = (int)db()->lastInsertId();

        $rowStmt = db()->prepare("SELECT * FROM questions WHERE id=?");
        $rowStmt->execute([$id]);
        $row = $rowStmt->fetch();
        send_json(cast_question($row), 201);
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            send_json(['error' => 'السؤال موجود مسبقاً في هذا الدرس'], 409);
        }
        throw $e;
    }
}

function handle_question_update(int $id): void {
    require_admin();
    $b    = read_json_body();
    $stmt = db()->prepare(
        "UPDATE questions
         SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?,
             correct_answer=?, explanation=?, image_url=?
         WHERE id=?"
    );
    $stmt->execute([
        $b['question_text'] ?? '', $b['option_a'] ?? '', $b['option_b'] ?? '',
        $b['option_c'] ?? '', $b['option_d'] ?? '',
        (int)($b['correct_answer'] ?? 0), $b['explanation'] ?? null, $b['image_url'] ?? null,
        $id,
    ]);

    if ($stmt->rowCount() === 0) {
        $checkStmt = db()->prepare("SELECT id FROM questions WHERE id=?");
        $checkStmt->execute([$id]);
        if (!$checkStmt->fetch()) send_json(['error' => 'السؤال غير موجود'], 404);
    }

    $rowStmt = db()->prepare("SELECT * FROM questions WHERE id=?");
    $rowStmt->execute([$id]);
    send_json(cast_question($rowStmt->fetch()));
}

function handle_question_delete(int $id): void {
    require_admin();
    $stmt = db()->prepare("DELETE FROM questions WHERE id=?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) send_json(['error' => 'السؤال غير موجود'], 404);
    send_json(['success' => true]);
}

function handle_questions_bulk(): void {
    require_admin();
    $b         = read_json_body();
    $grade     = $b['grade_key'] ?? '';
    $unit      = isset($b['unit_index'])   ? (int)$b['unit_index']   : null;
    $lesson    = isset($b['lesson_index']) ? (int)$b['lesson_index'] : null;
    $questions = $b['questions'] ?? null;

    if (!$grade || $unit === null || $lesson === null || !is_array($questions) || empty($questions)) {
        send_json(['error' => 'حقول مطلوبة مفقودة'], 400);
    }

    $inserted = 0; $skipped = 0; $errors = [];
    $stmt = db()->prepare(
        "INSERT INTO questions
         (grade_key, unit_index, lesson_index, question_text, question_hash,
          option_a, option_b, option_c, option_d, correct_answer)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    );

    foreach ($questions as $i => $q) {
        if (!isset($q['question_text'], $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['correct_answer'])) {
            $errors[] = ['row' => $i + 2, 'error' => 'بيانات ناقصة'];
            continue;
        }
        try {
            $stmt->execute([
                $grade, $unit, $lesson,
                $q['question_text'], hash('sha256', $q['question_text']),
                $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'],
                (int)$q['correct_answer'],
            ]);
            $inserted++;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $skipped++;
            } else {
                Logger::error('BULK INSERT ROW ' . ($i + 2), ['msg' => $e->getMessage()]);
                $errors[] = ['row' => $i + 2, 'error' => 'Database error'];
            }
        }
    }

    Logger::info('Bulk import done', ['inserted' => $inserted, 'skipped' => $skipped]);
    send_json(['inserted' => $inserted, 'skipped' => $skipped, 'errors' => $errors]);
}
