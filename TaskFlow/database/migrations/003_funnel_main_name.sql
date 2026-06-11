-- Переименование главной воронки: «Главная» / битая кодировка → «Общая»
UPDATE funnels
SET name = 'Общая'
WHERE is_main = TRUE
  AND (name = 'Главная' OR name LIKE '%?%');
