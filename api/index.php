<?php
/**
 * موجّه API الرئيسي
 * يستقبل جميع طلبات /api/* ويوزّعها على ملفات routes المناسبة
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/questions.php';
require_once __DIR__ . '/routes/scores.php';
require_once __DIR__ . '/routes/progress.php';
require_once __DIR__ . '/routes/lessons.php';
require_once __DIR__ . '/routes/curriculum.php';
require_once __DIR__ . '/routes/admin.php';
require_once __DIR__ . '/routes/upload.php';

Logger::rotate();

// ── CORS: نسمح فقط لنفس الأصل (same-origin) ──
$allowedOrigin = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($requestOrigin !== '') {
    if ($requestOrigin === $allowedOrigin) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    }
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Max-Age: 86400');
}

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = trim(preg_replace('#^/api/?#', '', $uri), '/');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    switch (true) {

        // ── الصحة ──
        case ($path === 'health' || $path === 'healthz') && $method === 'GET':
            send_json(['status' => 'ok', 'time' => date('c')]);

        // ── المصادقة ──
        case $path === 'auth/login'        && $method === 'POST': handle_login();
        case $path === 'auth/logout'       && $method === 'POST': handle_logout();
        case $path === 'auth/me'           && $method === 'GET':  handle_me();
        case $path === 'auth/register'     && $method === 'POST': handle_register();
        case $path === 'auth/student-login'&& $method === 'POST': handle_student_login();
        case $path === 'auth/csrf'         && $method === 'GET':  handle_csrf_token();

        // ── الأسئلة ──
        case $path === 'questions/counts'  && $method === 'GET':  handle_questions_counts();
        case $path === 'questions/bulk'    && $method === 'POST': handle_questions_bulk();
        case $path === 'questions/random'  && $method === 'GET':  handle_questions_random();
        case $path === 'questions'         && $method === 'GET':  handle_questions_list();
        case $path === 'questions'         && $method === 'POST': handle_question_create();
        case preg_match('#^questions/(\d+)$#', $path, $m) && $method === 'PUT':
            handle_question_update((int)$m[1]);
        case preg_match('#^questions/(\d+)$#', $path, $m) && $method === 'DELETE':
            handle_question_delete((int)$m[1]);

        // ── النتائج ──
        case $path === 'scores'            && $method === 'POST': handle_score_submit();
        case $path === 'scores/leaderboard'&& $method === 'GET':  handle_leaderboard();

        // ── التقدم ──
        case $path === 'progress'          && $method === 'POST': handle_progress_mark();
        case $path === 'progress'          && $method === 'GET':  handle_progress_get();
        case $path === 'progress/summary'  && $method === 'GET':  handle_progress_summary();
        case $path === 'progress/video'    && $method === 'POST': handle_progress_video();

        // ── محتوى الدروس ──
        case $path === 'lessons/content'   && $method === 'GET':  handle_lesson_content_get();
        case $path === 'lessons/content'   && $method === 'PUT':  handle_lesson_content_put();

        // ── المنهج الدراسي ──
        case $path === 'curriculum'        && $method === 'GET':  handle_curriculum_get();
        case $path === 'curriculum/units'  && $method === 'PUT':  handle_curriculum_unit_upsert();
        case $path === 'curriculum/lessons'&& $method === 'PUT':  handle_curriculum_lesson_upsert();

        // ── إدارة ──
        case $path === 'admin/stats'       && $method === 'GET':  handle_admin_stats();
        case preg_match('#^admin/students/(\d+)/progress$#', $path, $m) && $method === 'GET':
            handle_admin_student_progress((int)$m[1]);
        case preg_match('#^admin/students/(\d+)$#', $path, $m) && $method === 'DELETE':
            handle_admin_student_delete((int)$m[1]);

        // ── رفع الصور ──
        case $path === 'upload'            && $method === 'POST': handle_upload();

        default:
            Logger::warn('Route not found', ['method' => $method, 'path' => $path]);
            send_json(['error' => 'Route not found: ' . $method . ' /' . $path], 404);
    }
} catch (Throwable $e) {
    Logger::exception($e);
    send_json(['error' => 'Database error'], 500);
}
