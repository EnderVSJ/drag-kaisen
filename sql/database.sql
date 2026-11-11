-- ============================================================
--  Drag & Kaisen - Base de datos de usuarios
--  Importar en phpMyAdmin: pestaña "Importar" > elegir este archivo > Continuar
-- ============================================================

CREATE DATABASE IF NOT EXISTS `drag_kaisen`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `drag_kaisen`;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario`       VARCHAR(20)  NOT NULL,   -- nombre de usuario (único, sin distinguir mayúsculas)
  `password_hash` VARCHAR(255) NOT NULL,   -- hash generado con password_hash(); nunca la contraseña real
  `creado_en`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Registro de peleas y del daño de cada golpe
--  (se puede volver a importar este archivo: no borra nada existente)
-- ============================================================

CREATE TABLE IF NOT EXISTS `peleas` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`            INT UNSIGNED NOT NULL,
  `hechicero_id`          VARCHAR(10)  NOT NULL,
  `hechicero_nombre`      VARCHAR(60)  NOT NULL,
  `hechicero_grado`       TINYINT UNSIGNED NOT NULL,
  `enemigo_id`            VARCHAR(10)  NOT NULL,
  `enemigo_nombre`        VARCHAR(60)  NOT NULL,
  `enemigo_grado`         TINYINT UNSIGNED NOT NULL,
  `hp_hechicero_inicial`  SMALLINT UNSIGNED NOT NULL,
  `hp_enemigo_inicial`    SMALLINT UNSIGNED NOT NULL,
  `hp_hechicero_final`    SMALLINT UNSIGNED NULL,
  `hp_enemigo_final`      SMALLINT UNSIGNED NULL,
  `resultado`             ENUM('en_curso','victoria','derrota','abandonada') NOT NULL DEFAULT 'en_curso',
  `turnos`                SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `dano_causado`          INT UNSIGNED NOT NULL DEFAULT 0,   -- daño total que hizo el hechicero
  `dano_recibido`         INT UNSIGNED NOT NULL DEFAULT 0,   -- daño total que recibió el hechicero
  `iniciada_en`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finalizada_en`         TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_peleas_usuario` (`usuario_id`, `iniciada_en`),
  CONSTRAINT `fk_peleas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pelea_golpes` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pelea_id`            INT UNSIGNED NOT NULL,
  `orden`               SMALLINT UNSIGNED NOT NULL,           -- 1, 2, 3... golpe dentro de la pelea
  `turno`               SMALLINT UNSIGNED NOT NULL,
  `atacante`            ENUM('hechicero','enemigo') NOT NULL,
  `atacante_nombre`     VARCHAR(60)  NOT NULL,
  `defensor_nombre`     VARCHAR(60)  NOT NULL,
  `dano`                SMALLINT UNSIGNED NOT NULL,
  `hp_defensor_restante` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_golpe_orden` (`pelea_id`, `orden`),
  CONSTRAINT `fk_golpes_pelea` FOREIGN KEY (`pelea_id`) REFERENCES `peleas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
