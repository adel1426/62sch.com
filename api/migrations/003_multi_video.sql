-- الهجرة 003: دعم أكثر من رابط فيديو في الدرس الواحد

-- توسيع عمود video_url ليستوعب JSON array بدلاً من رابط واحد
ALTER TABLE lesson_content MODIFY COLUMN video_url TEXT NULL;

-- إضافة رقم الفيديو داخل الدرس لتتبع مشاهدة كل فيديو على حدة (وحسابه نقاطاً)
ALTER TABLE video_progress ADD COLUMN video_index TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE video_progress DROP INDEX uniq_video;
ALTER TABLE video_progress ADD UNIQUE KEY uniq_video (user_id, grade_key, unit_index, lesson_index, video_index);
