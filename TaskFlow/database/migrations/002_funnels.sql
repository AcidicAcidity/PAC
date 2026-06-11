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

CREATE INDEX IF NOT EXISTS idx_funnels_portal ON funnels(portal_id);
CREATE INDEX IF NOT EXISTS idx_funnels_collab ON funnels(collab_id);

-- Общая воронка для существующих порталов
INSERT INTO funnels (portal_id, name, is_main)
SELECT p.id, 'Общая', TRUE
FROM portals p
WHERE NOT EXISTS (
    SELECT 1 FROM funnels f WHERE f.portal_id = p.id AND f.is_main = TRUE
);

-- Воронки для существующих коллабов
INSERT INTO funnels (portal_id, name, collab_id, is_main)
SELECT c.portal_id, c.name, c.id, FALSE
FROM collabs c
WHERE NOT EXISTS (
    SELECT 1 FROM funnels f WHERE f.collab_id = c.id
);

-- Роль «Директор» для существующих администраторов портала
UPDATE users u
SET portal_role_id = pr.id
FROM portal_roles pr
WHERE u.portal_id = pr.portal_id
  AND pr.name = 'Директор'
  AND u.role = 'admin'
  AND u.portal_role_id IS NULL;
