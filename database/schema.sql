-- Seshat: avalúos. Importar en MySQL/MariaDB (utf8mb4).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS visita_solicitudes;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS avaluo_estado_historial;
DROP TABLE IF EXISTS cotizaciones;
DROP TABLE IF EXISTS avaluos;
DROP TABLE IF EXISTS usuarios;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    telefono VARCHAR(40) DEFAULT NULL,
    rol ENUM('cliente','valuador') NOT NULL DEFAULT 'cliente',
    google_access_token TEXT DEFAULT NULL,
    google_refresh_token TEXT DEFAULT NULL,
    google_token_expires_at DATETIME DEFAULT NULL,
    google_calendar_id VARCHAR(255) DEFAULT 'primary',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avaluos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    valuador_id INT UNSIGNED DEFAULT NULL,
    codigo_seguimiento VARCHAR(32) DEFAULT NULL UNIQUE,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    direccion_inmueble VARCHAR(500) NOT NULL,
    tipo_inmueble VARCHAR(120) DEFAULT NULL,
    estado ENUM(
        'solicitado',
        'cotizado',
        'espera_visita',
        'en_proceso',
        'finalizado',
        'cancelado'
    ) NOT NULL DEFAULT 'solicitado',
    estado_notas TEXT,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_avaluos_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_avaluos_valuador FOREIGN KEY (valuador_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cotizaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    avaluo_id INT UNSIGNED NOT NULL,
    valuador_id INT UNSIGNED NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    moneda VARCHAR(8) NOT NULL DEFAULT 'USD',
    detalle TEXT,
    estado ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cot_avaluo FOREIGN KEY (avaluo_id) REFERENCES avaluos(id) ON DELETE CASCADE,
    CONSTRAINT fk_cot_valuador FOREIGN KEY (valuador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_cot_avaluo (avaluo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avaluo_estado_historial (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    avaluo_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(32) NOT NULL,
    estado_nuevo VARCHAR(32) NOT NULL,
    nota TEXT,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hist_avaluo FOREIGN KEY (avaluo_id) REFERENCES avaluos(id) ON DELETE CASCADE,
    CONSTRAINT fk_hist_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_hist_avaluo (avaluo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notificaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(64) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    cuerpo TEXT,
    datos_json JSON DEFAULT NULL,
    leida_en DATETIME DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_notif_user (usuario_id, leida_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visita_solicitudes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    avaluo_id INT UNSIGNED NOT NULL,
    cliente_id INT UNSIGNED NOT NULL,
    inicio_propuesto DATETIME NOT NULL,
    fin_propuesto DATETIME NOT NULL,
    mensaje VARCHAR(500) DEFAULT NULL,
    estado ENUM('pendiente','confirmada','rechazada') NOT NULL DEFAULT 'pendiente',
    respuesta_valuador TEXT,
    evento_google_id VARCHAR(255) DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vis_avaluo FOREIGN KEY (avaluo_id) REFERENCES avaluos(id) ON DELETE CASCADE,
    CONSTRAINT fk_vis_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_vis_avaluo (avaluo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
