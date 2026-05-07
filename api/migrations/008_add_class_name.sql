-- الهجرة 008: إضافة عمود الفصل الدراسي للمستخدمين
ALTER TABLE users ADD COLUMN class_name VARCHAR(20) NULL AFTER grade_level;
