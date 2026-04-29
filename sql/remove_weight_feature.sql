USE fittrack;

DROP TABLE IF EXISTS weight_logs;

ALTER TABLE users
DROP COLUMN IF EXISTS current_weight_kg;
