-- الهجرة 007: حذف سجلات النتائج اليتيمة (أسماء ظلّت بعد حذف المستخدمين)
-- يجب تشغيله مرة واحدة فقط

-- حذف النتائج المرتبطة بمستخدمين محذوفين
DELETE ss FROM student_scores ss
LEFT JOIN users u ON u.id = ss.user_id
WHERE ss.user_id IS NOT NULL AND u.id IS NULL;

-- حذف النتائج غير المرتبطة بأي مستخدم (guest قديم)
DELETE FROM student_scores WHERE user_id IS NULL;
