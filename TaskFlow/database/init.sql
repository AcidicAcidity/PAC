-- ============================================
-- TaskFlow — Схема базы данных (PostgreSQL)
-- Автоматически применяется при первом запуске
-- ============================================

-- Типы ENUM
DO $$ BEGIN
    CREATE TYPE user_role AS ENUM ('admin', 'user');
EXCEPTION WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE collab_member_role AS ENUM ('admin', 'executor');
EXCEPTION WHEN duplicate_object THEN null;
END $$;

-- Портал (компания)
CREATE TABLE IF NOT EXISTS portals (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    invite_code VARCHAR(20) UNIQUE,
    owner_id INTEGER,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Иерархические роли портала
CREATE TABLE IF NOT EXISTS portal_roles (
    id SERIAL PRIMARY KEY,
    portal_id INTEGER NOT NULL REFERENCES portals(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    hierarchy_level INTEGER NOT NULL DEFAULT 10,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(portal_id, name)
);

-- Пользователи
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(500) DEFAULT '',
    role user_role NOT NULL DEFAULT 'user',
    portal_id INTEGER REFERENCES portals(id) ON DELETE CASCADE,
    portal_role_id INTEGER REFERENCES portal_roles(id) ON DELETE SET NULL,
    is_blocked BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Добавляем внешний ключ для owner_id после создания таблицы users
ALTER TABLE portals ADD CONSTRAINT fk_portals_owner
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL;

-- Верификация email
CREATE TABLE IF NOT EXISTS email_verifications (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    code VARCHAR(6) NOT NULL,
    expires TIMESTAMP NOT NULL
);

-- Коды приглашения в портал
CREATE TABLE IF NOT EXISTS portal_invitations (
    id SERIAL PRIMARY KEY,
    portal_id INTEGER NOT NULL REFERENCES portals(id) ON DELETE CASCADE,
    code VARCHAR(20) UNIQUE NOT NULL,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    max_uses INTEGER DEFAULT 1,
    used_count INTEGER DEFAULT 0,
    expires TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Коллабы (группы)
CREATE TABLE IF NOT EXISTS collabs (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT '',
    owner_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    portal_id INTEGER NOT NULL REFERENCES portals(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Участники коллаб
CREATE TABLE IF NOT EXISTS collab_members (
    collab_id INTEGER NOT NULL REFERENCES collabs(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role collab_member_role NOT NULL DEFAULT 'executor',
    joined_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (collab_id, user_id)
);

-- Воронки канбан-доски (главная + коллабы)
CREATE TABLE IF NOT EXISTS funnels (
    id SERIAL PRIMARY KEY,
    portal_id INTEGER NOT NULL REFERENCES portals(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    collab_id INTEGER REFERENCES collabs(id) ON DELETE CASCADE,
    is_main BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (portal_id, collab_id)
);

-- Колонки канбан-доски
CREATE TABLE IF NOT EXISTS board_columns (
    id SERIAL PRIMARY KEY,
    portal_id INTEGER NOT NULL REFERENCES portals(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    color VARCHAR(7) DEFAULT '#808080',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Задачи
CREATE TABLE IF NOT EXISTS tasks (
    id SERIAL PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    description TEXT DEFAULT '',
    priority VARCHAR(20) DEFAULT 'medium',
    creator_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    assignee_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    collab_id INTEGER REFERENCES collabs(id) ON DELETE SET NULL,
    column_id INTEGER REFERENCES board_columns(id) ON DELETE SET NULL,
    portal_id INTEGER NOT NULL REFERENCES portals(id) ON DELETE CASCADE,
    position INTEGER DEFAULT 0,
    is_public BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    deadline DATE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Личные сообщения
CREATE TABLE IF NOT EXISTS private_messages (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    receiver_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Сообщения коллаб
CREATE TABLE IF NOT EXISTS collab_messages (
    id SERIAL PRIMARY KEY,
    collab_id INTEGER NOT NULL REFERENCES collabs(id) ON DELETE CASCADE,
    sender_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Отзывы о портале
CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    content TEXT DEFAULT '',
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Настройки пользователя
CREATE TABLE IF NOT EXISTS user_settings (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    theme VARCHAR(50) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'ru'
);

-- Индексы для производительности
CREATE INDEX IF NOT EXISTS idx_users_portal ON users(portal_id);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_tasks_portal ON tasks(portal_id);
CREATE INDEX IF NOT EXISTS idx_tasks_column ON tasks(column_id);
CREATE INDEX IF NOT EXISTS idx_tasks_assignee ON tasks(assignee_id);
CREATE INDEX IF NOT EXISTS idx_tasks_collab ON tasks(collab_id);
CREATE INDEX IF NOT EXISTS idx_funnels_portal ON funnels(portal_id);
CREATE INDEX IF NOT EXISTS idx_funnels_collab ON funnels(collab_id);
CREATE INDEX IF NOT EXISTS idx_private_messages_users ON private_messages(sender_id, receiver_id);
CREATE INDEX IF NOT EXISTS idx_collab_messages_collab ON collab_messages(collab_id);
CREATE INDEX IF NOT EXISTS idx_reviews_approved ON reviews(is_approved) WHERE is_approved = TRUE;
CREATE INDEX IF NOT EXISTS idx_portal_invitations_code ON portal_invitations(code) WHERE is_active = TRUE;
