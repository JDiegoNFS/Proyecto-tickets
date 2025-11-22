-- Tabla para configurar destinatarios de escalamiento por tipo de solicitud
CREATE TABLE IF NOT EXISTS `escalamiento_destinatarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_solicitud` varchar(100) NOT NULL,
  `email_destinatario` varchar(255) NOT NULL,
  `nombre_destinatario` varchar(255) NOT NULL,
  `cargo_destinatario` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tipo_email` (`tipo_solicitud`, `email_destinatario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla para registrar escalamientos realizados
CREATE TABLE IF NOT EXISTS `escalamientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_solicitud` varchar(100) NOT NULL,
  `email_destinatario` varchar(255) NOT NULL,
  `nombre_destinatario` varchar(255) NOT NULL,
  `cargo_destinatario` varchar(255) NOT NULL,
  `asunto` varchar(500) NOT NULL,
  `mensaje_personalizado` text,
  `estado` enum('enviado','pendiente','error','aprobado','rechazado') DEFAULT 'pendiente',
  `token_aprobacion` varchar(255) NOT NULL,
  `fecha_escalamiento` timestamp DEFAULT CURRENT_TIMESTAMP,
  `fecha_respuesta` timestamp NULL DEFAULT NULL,
  `respuesta_destinatario` text,
  `ip_respuesta` varchar(45) NULL,
  `user_agent_respuesta` text NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_aprobacion` (`token_aprobacion`),
  KEY `ticket_id` (`ticket_id`),
  KEY `usuario_id` (`usuario_id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar configuraciones por defecto para diferentes tipos de solicitud
INSERT INTO `escalamiento_destinatarios` (`tipo_solicitud`, `email_destinatario`, `nombre_destinatario`, `cargo_destinatario`) VALUES
('cambio_horario', 'director.operaciones@empresa.com', 'Director de Operaciones', 'Director Regional'),
('cambio_horario', 'gerente.general@empresa.com', 'Gerente General', 'Gerente General'),
('incidencia_critica', 'director.operaciones@empresa.com', 'Director de Operaciones', 'Director Regional'),
('incidencia_critica', 'gerente.general@empresa.com', 'Gerente General', 'Gerente General'),
('aprobacion_especial', 'director.operaciones@empresa.com', 'Director de Operaciones', 'Director Regional'),
('aprobacion_especial', 'gerente.general@empresa.com', 'Gerente General', 'Gerente General');

-- Agregar campo para tipo de solicitud en tickets (opcional)
ALTER TABLE `tickets` ADD COLUMN `tipo_solicitud` varchar(100) DEFAULT NULL AFTER `estado`;
