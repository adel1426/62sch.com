-- Clear all existing questions while keeping curriculum and lesson content.
DELETE FROM questions;
ALTER TABLE questions AUTO_INCREMENT = 1;
