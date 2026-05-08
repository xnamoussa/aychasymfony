-- Correction des moteurs de tables (MyISAM -> InnoDB)
ALTER TABLE favori ENGINE=InnoDB;
ALTER TABLE programme_recommande ENGINE=InnoDB;
ALTER TABLE promo_code ENGINE=InnoDB;
ALTER TABLE restaurant ENGINE=InnoDB;
ALTER TABLE ticket ENGINE=InnoDB;

-- Correction de la collation de la base de données
ALTER DATABASE event_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Note : Pour les timezones et le buffer pool, vous devez modifier votre fichier de configuration MySQL (my.cnf ou my.ini) :
-- [mysqld]
-- default-time-zone = '+01:00' (ou le nom de votre zone)
-- innodb_buffer_pool_size = 512M
-- sql_mode = "STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"
-- innodb_flush_log_at_trx_commit = 2 (pour le dev)
