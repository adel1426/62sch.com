<?php

function handle_upload(): void {
    require_admin();

    if (empty($_FILES['image'])) {
        send_json(['error' => 'لم يُرسَل أي ملف'], 400);
    }

    $file = $_FILES['image'];

    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'الملف أكبر من الحد المسموح في الخادم',
        UPLOAD_ERR_FORM_SIZE  => 'الملف أكبر من الحد المسموح في الفورم',
        UPLOAD_ERR_PARTIAL    => 'رُفع الملف جزئياً فقط',
        UPLOAD_ERR_NO_FILE    => 'لم يُختر أي ملف',
        UPLOAD_ERR_NO_TMP_DIR => 'مجلد temp غير موجود',
        UPLOAD_ERR_CANT_WRITE => 'فشل الكتابة على القرص',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        send_json(['error' => $uploadErrors[$file['error']] ?? 'خطأ في الرفع'], 400);
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        send_json(['error' => 'حجم الصورة يتجاوز 5MB'], 413);
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        send_json(['error' => 'الملف ليس صورة صالحة'], 415);
    }

    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $mime = $info['mime'];
    if (!isset($mimeToExt[$mime])) {
        send_json(['error' => 'نوع غير مدعوم — يُسمح بـ JPG, PNG, GIF, WebP'], 415);
    }

    $uploadDir = dirname(__DIR__, 2) . '/uploads/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        send_json(['error' => 'تعذّر إنشاء مجلد الصور على الخادم'], 500);
    }

    $ext      = $mimeToExt[$mime];
    $filename = 'q_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        send_json(['error' => 'فشل نقل الصورة — تحقق من صلاحيات مجلد uploads/'], 500);
    }

    Logger::info('Image uploaded', ['file' => $filename]);
    send_json(['url' => '/uploads/' . $filename]);
}
