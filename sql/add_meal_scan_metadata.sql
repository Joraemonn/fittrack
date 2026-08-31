-- Adds scan metadata needed for meals to keep AI/manual source after reload.
-- Run once against the FitTrack database.

ALTER TABLE meals
    ADD COLUMN food_name VARCHAR(100) NULL AFTER meal_name,
    ADD COLUMN food_display VARCHAR(100) NULL AFTER food_name,
    ADD COLUMN serving_grams DECIMAL(6,1) NULL AFTER fats_g,
    ADD COLUMN source ENUM('manual', 'scanned') NOT NULL DEFAULT 'manual' AFTER serving_grams,
    ADD COLUMN ai_confidence DECIMAL(5,2) NULL AFTER source;

UPDATE meals
SET food_display = COALESCE(food_display, meal_name),
    food_name = COALESCE(food_name, LOWER(REPLACE(TRIM(meal_name), ' ', '_')))
WHERE food_display IS NULL
   OR food_name IS NULL;
