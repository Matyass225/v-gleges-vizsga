
USE sajat_DB_Neve;

CREATE TABLE measurements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    temperature FLOAT NOT NULL,
    humidity FLOAT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- API kulcsok táblája
CREATE TABLE IF NOT EXISTS api_keys (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  api_key       CHAR(64) NOT NULL UNIQUE,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at  TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

-- Példa API kulcs (CSERÉLD KI egy biztonságos értékre!)
-- Javasolt generálás:  openssl rand -hex 32   (64 hex karakter)
INSERT INTO api_keys (api_key, is_active)
VALUES ('12345678', 1);
