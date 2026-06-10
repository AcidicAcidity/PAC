-- Миграция: состояние задачи и крайний срок
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS deadline DATE;
