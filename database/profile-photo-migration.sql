-- Execute une seule fois sur la base Vendo existante.
ALTER TABLE users ADD COLUMN telephone VARCHAR(30) NULL, ADD COLUMN photo_url VARCHAR(2048) NULL;
