CREATE TABLE portals (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    invite_code VARCHAR(20) UNIQUE NOT NULL,
    owner_id INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url TEXT,
    role VARCHAR(20) DEFAULT 'user', -- 'user' / 'admin'
    portal_id INTEGER REFERENCES portals(id),
    portal_role_id INTEGER REFERENCES portal_roles(id),
    is_blocked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE portal_roles (
    id SERIAL PRIMARY KEY,
    portal_id INTEGER REFERENCES portals(id) ON DELETE CASCADE,
    name VARCHAR(50) NOT NULL,
    hierarchy_level INT NOT NULL,  -- 1 - высший (менеджер), 2 - супервайзер, 3 - техспец и т.д.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE email_verifications (
    user_id INTEGER REFERENCES users(id) PRIMARY KEY,
    code VARCHAR(6) NOT NULL,
    expires TIMESTAMP NOT NULL
);

CREATE TABLE portal_invitations (
    id SERIAL PRIMARY KEY,
    portal_id INTEGER REFERENCES portals(id),
    code VARCHAR(20) UNIQUE NOT NULL,
    created_by INTEGER REFERENCES users(id),
    max_uses INTEGER DEFAULT 1,
    used_count INTEGER DEFAULT 0,
    expires TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE collabs (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    owner_id INTEGER REFERENCES users(id),
    portal_id INTEGER REFERENCES portals(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TYPE collab_role AS ENUM ('admin', 'executor');

CREATE TABLE collab_members (
    collab_id INTEGER REFERENCES collabs(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    role collab_role DEFAULT 'executor',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (collab_id, user_id)
);

CREATE TABLE board_columns (
    id SERIAL PRIMARY KEY,
    portal_id INTEGER REFERENCES portals(id),
    title VARCHAR(100) NOT NULL,
    position INT NOT NULL,
    color VARCHAR(7) DEFAULT '#808080',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority INTEGER DEFAULT 0,
    creator_id INTEGER REFERENCES users(id),
    assignee_id INTEGER REFERENCES users(id),
    collab_id INTEGER REFERENCES collabs(id) ON DELETE SET NULL,
    column_id INTEGER REFERENCES board_columns(id),
    portal_id INTEGER REFERENCES portals(id),
    position INTEGER DEFAULT 0,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE private_messages (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER REFERENCES users(id),
    receiver_id INTEGER REFERENCES users(id),
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE collab_messages (
    id SERIAL PRIMARY KEY,
    collab_id INTEGER REFERENCES collabs(id) ON DELETE CASCADE,
    sender_id INTEGER REFERENCES users(id),
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    content TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_settings (
    user_id INTEGER REFERENCES users(id) PRIMARY KEY,
    theme VARCHAR(50) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'ru',
    vim_mode BOOLEAN DEFAULT FALSE
);

-- Индексы
CREATE INDEX idx_users_portal ON users(portal_id);
CREATE INDEX idx_tasks_portal_column ON tasks(portal_id, column_id);
CREATE INDEX idx_messages_sender_receiver ON private_messages(sender_id, receiver_id);
CREATE INDEX idx_collab_messages_collab ON collab_messages(collab_id);
