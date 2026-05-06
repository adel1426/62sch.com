-- حذف المنهج القديم لثاني متوسط
DELETE FROM questions          WHERE grade_key = 'second';
DELETE FROM curriculum_lessons WHERE grade_key = 'second';
DELETE FROM curriculum_units   WHERE grade_key = 'second';

-- الفصل الأول: الأعداد النسبية (8 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 0, 'الأعداد النسبية', '🔢');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 0, 0, 'الأعداد النسبية'),
  ('second', 0, 1, 'مقارنة الأعداد النسبية وترتيبها'),
  ('second', 0, 2, 'جمع الأعداد النسبية وطرحها'),
  ('second', 0, 3, 'ضرب الأعداد النسبية وقسمتها'),
  ('second', 0, 4, 'حل مسائل الأعداد النسبية'),
  ('second', 0, 5, 'العمليات المركبة على الأعداد النسبية'),
  ('second', 0, 6, 'التعابير الجبرية والأعداد النسبية'),
  ('second', 0, 7, 'مراجعة الفصل الأول');

-- الفصل الثاني: الأعداد الحقيقية ونظرية فيثاغورس (6 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 1, 'الأعداد الحقيقية ونظرية فيثاغورس', '📐');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 1, 0, 'الجذور التربيعية'),
  ('second', 1, 1, 'تقدير الجذور التربيعية'),
  ('second', 1, 2, 'الأعداد الحقيقية'),
  ('second', 1, 3, 'نظرية فيثاغورس'),
  ('second', 1, 4, 'عكس نظرية فيثاغورس'),
  ('second', 1, 5, 'تطبيقات على نظرية فيثاغورس');

-- الفصل الثالث: التناسب والتشابه (7 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 2, 'التناسب والتشابه', '📏');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 2, 0, 'معدلات التغيير'),
  ('second', 2, 1, 'النسبة والتناسب'),
  ('second', 2, 2, 'حل مسائل التناسب'),
  ('second', 2, 3, 'معدل التغيير الثابت'),
  ('second', 2, 4, 'الأشكال المتشابهة'),
  ('second', 2, 5, 'نظريات التشابه في المثلثات'),
  ('second', 2, 6, 'نسب الأطوال والمساحات');

-- الفصل الرابع: النسبة المئوية (4 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 3, 'النسبة المئوية', '💯');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 3, 0, 'النسبة المئوية'),
  ('second', 3, 1, 'تطبيقات النسبة المئوية'),
  ('second', 3, 2, 'نسبة التغيير'),
  ('second', 3, 3, 'الربح والخسارة والخصم والضريبة');

-- الفصل الخامس: الهندسة والاستدلال المكاني (7 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 4, 'الهندسة والاستدلال المكاني', '🔺');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 4, 0, 'الزوايا المتكونة من مستقيمين متوازيين وقاطع'),
  ('second', 4, 1, 'زوايا المثلث'),
  ('second', 4, 2, 'أنواع المثلثات'),
  ('second', 4, 3, 'متطابقة المثلثات'),
  ('second', 4, 4, 'الرباعيات وأنواعها'),
  ('second', 4, 5, 'التحولات الهندسية (الانسحاب والانعكاس)'),
  ('second', 4, 6, 'التحولات الهندسية (الدوران والتشابه)');

-- الفصل السادس: الإحصاء (7 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 5, 'الإحصاء', '📊');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 5, 0, 'أساليب جمع البيانات'),
  ('second', 5, 1, 'تنظيم البيانات وعرضها'),
  ('second', 5, 2, 'مقاييس النزعة المركزية'),
  ('second', 5, 3, 'مقاييس التشتت'),
  ('second', 5, 4, 'المخطط الصندوقي'),
  ('second', 5, 5, 'مخططات التشتت والارتباط'),
  ('second', 5, 6, 'الاستنتاج الإحصائي');

-- الفصل السابع: الاحتمالات (4 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 6, 'الاحتمالات', '🎲');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 6, 0, 'الاحتمال التجريبي والنظري'),
  ('second', 6, 1, 'احتمال الحادثة وحادثتها المتممة'),
  ('second', 6, 2, 'احتمال الحوادث المستقلة'),
  ('second', 6, 3, 'احتمال الحوادث المتنافية');

-- الفصل الثامن: القياس (6 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 7, 'القياس (المساحة والحجم)', '📦');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 7, 0, 'المساحة الجانبية والكلية للمنشور'),
  ('second', 7, 1, 'حجم المنشور'),
  ('second', 7, 2, 'المساحة الجانبية والكلية للاسطوانة'),
  ('second', 7, 3, 'حجم الاسطوانة'),
  ('second', 7, 4, 'الهرم والمخروط (المساحة)'),
  ('second', 7, 5, 'الهرم والمخروط (الحجم)');

-- الفصل التاسع: الجبر - المعادلات والمتباينات (6 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 8, 'الجبر (المعادلات والمتباينات)', '⚖️');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 8, 0, 'التعابير الجبرية'),
  ('second', 8, 1, 'حل المعادلات الخطية ذات المتغير الواحد'),
  ('second', 8, 2, 'حل المعادلات ذات الخطوتين'),
  ('second', 8, 3, 'المتباينات وحلها'),
  ('second', 8, 4, 'رسم حل المتباينات على خط الأعداد'),
  ('second', 8, 5, 'تطبيقات المعادلات والمتباينات');

-- الفصل العاشر: الجبر - الدوال الخطية (5 دروس)
INSERT INTO curriculum_units (grade_key, unit_index, title, emoji) VALUES ('second', 9, 'الجبر (الدوال الخطية)', '📈');
INSERT INTO curriculum_lessons (grade_key, unit_index, lesson_index, title) VALUES
  ('second', 9, 0, 'الدوال والعلاقات'),
  ('second', 9, 1, 'الميل'),
  ('second', 9, 2, 'الدالة الخطية'),
  ('second', 9, 3, 'كتابة معادلة الدالة الخطية'),
  ('second', 9, 4, 'المعادلات المتزامنة (حل المنظومات)');
