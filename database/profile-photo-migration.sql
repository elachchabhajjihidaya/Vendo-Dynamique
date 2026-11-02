-- Select the Vendo database before running this migration. Safe to rerun.
SET @vendo_schema = DATABASE();
SET @vendo_sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@vendo_schema AND table_name='users' AND column_name='telephone')=0,'ALTER TABLE users ADD COLUMN telephone VARCHAR(30) NULL','SELECT 1');
PREPARE vendo_stmt FROM @vendo_sql;
EXECUTE vendo_stmt;
DEALLOCATE PREPARE vendo_stmt;
SET @vendo_sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@vendo_schema AND table_name='users' AND column_name='photo_url')=0,'ALTER TABLE users ADD COLUMN photo_url VARCHAR(2048) NULL','SELECT 1');
PREPARE vendo_stmt FROM @vendo_sql;
EXECUTE vendo_stmt;
DEALLOCATE PREPARE vendo_stmt;