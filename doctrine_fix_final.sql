-- Fix Engine (MyISAM to InnoDB)
ALTER TABLE favori ENGINE=InnoDB;
ALTER TABLE programme_recommande ENGINE=InnoDB;
ALTER TABLE promo_code ENGINE=InnoDB;
ALTER TABLE restaurant ENGINE=InnoDB;
ALTER TABLE ticket ENGINE=InnoDB;

-- Fix Timezone (Align with PHP Europe/Berlin)
SET GLOBAL time_zone = '+01:00';
SET time_zone = '+01:00';

-- Fix SQL Mode
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Optimize InnoDB
SET GLOBAL innodb_buffer_pool_size = 134217728;

-- Add/Update audit columns for BlameableTrait/TimestampableTrait
-- We use user ID 18 as default for existing records (discovered via query)

-- MENU
ALTER TABLE menu ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL;
ALTER TABLE menu ADD COLUMN IF NOT EXISTS updated_by_id INT DEFAULT NULL;
UPDATE menu SET created_by_id = 18 WHERE created_by_id IS NULL;
ALTER TABLE menu MODIFY created_by_id INT NOT NULL;

-- RESERVATION_MAQUILLAGE
ALTER TABLE reservation_maquillage ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL;
ALTER TABLE reservation_maquillage ADD COLUMN IF NOT EXISTS updated_by_id INT DEFAULT NULL;
ALTER TABLE reservation_maquillage ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL;
UPDATE reservation_maquillage SET created_by_id = 18 WHERE created_by_id IS NULL;
ALTER TABLE reservation_maquillage MODIFY created_by_id INT NOT NULL;

-- FAVORI
ALTER TABLE favori ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL;
ALTER TABLE favori ADD COLUMN IF NOT EXISTS updated_by_id INT DEFAULT NULL;
ALTER TABLE favori ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL;
UPDATE favori SET created_by_id = 18 WHERE created_by_id IS NULL;
ALTER TABLE favori MODIFY created_by_id INT NOT NULL;

-- TRANSACTION
-- Note: table name is payment_transaction in mapping
ALTER TABLE payment_transaction ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL;
ALTER TABLE payment_transaction ADD COLUMN IF NOT EXISTS updated_by_id INT DEFAULT NULL;
ALTER TABLE payment_transaction ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL;
UPDATE payment_transaction SET created_by_id = 18 WHERE created_by_id IS NULL;
ALTER TABLE payment_transaction MODIFY created_by_id INT NOT NULL;

-- FACE_DATA
ALTER TABLE face_data ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL;
ALTER TABLE face_data ADD COLUMN IF NOT EXISTS updated_by_id INT DEFAULT NULL;
ALTER TABLE face_data ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL;
UPDATE face_data SET created_by_id = 18 WHERE created_by_id IS NULL;
ALTER TABLE face_data MODIFY created_by_id INT NOT NULL;

-- SPONSOR_FEEDBACK
ALTER TABLE sponsor_feedback ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL;
ALTER TABLE sponsor_feedback ADD COLUMN IF NOT EXISTS updated_by_id INT DEFAULT NULL;
ALTER TABLE sponsor_feedback ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL;
UPDATE sponsor_feedback SET created_by_id = 18 WHERE created_by_id IS NULL;
ALTER TABLE sponsor_feedback MODIFY created_by_id INT NOT NULL;

-- LOGIN_ATTEMPTS (Embeddable Refactoring)
-- If it was a string column, it should stay the same if columnPrefix is false.
-- But we ensure it exists and is VARCHAR.
ALTER TABLE login_attempts MODIFY email VARCHAR(255) NOT NULL;

-- ABONNEMENT (Enum type check)
ALTER TABLE abonnement MODIFY restriction_type VARCHAR(100) DEFAULT NULL;
