-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-09-2025 a las 13:03:05
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_tickets`
--

DELIMITER $$
--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `obtener_usuarios_visibles` (`usuario_id` INT) RETURNS TEXT CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC READS SQL DATA BEGIN
        DECLARE usuario_jerarquia VARCHAR(50);
        DECLARE usuario_departamento INT;
        DECLARE resultado TEXT DEFAULT '';
        
        -- Obtener jerarquía y departamento del usuario
        SELECT jerarquia, departamento_id INTO usuario_jerarquia, usuario_departamento
        FROM usuarios WHERE id = usuario_id;
        
        -- Si es jefe_tienda (nivel 1), solo puede ver sus propios tickets
        IF usuario_jerarquia = 'jefe_tienda' THEN
            SET resultado = CAST(usuario_id AS CHAR);
        -- Si es asistente_tienda, puede ver jefe, asistente, sub_gerente y gerente
        ELSEIF usuario_jerarquia = 'asistente_tienda' THEN
            SELECT GROUP_CONCAT(id) INTO resultado
            FROM usuarios 
            WHERE departamento_id = usuario_departamento 
            AND rol = 'cliente' 
            AND jerarquia IN ('jefe_tienda', 'asistente_tienda', 'sub_gerente_tienda', 'gerente_tienda');
        -- Si es sub_gerente_tienda, puede ver jefe, sub_gerente y gerente
        ELSEIF usuario_jerarquia = 'sub_gerente_tienda' THEN
            SELECT GROUP_CONCAT(id) INTO resultado
            FROM usuarios 
            WHERE departamento_id = usuario_departamento 
            AND rol = 'cliente' 
            AND jerarquia IN ('jefe_tienda', 'sub_gerente_tienda', 'gerente_tienda');
        -- Si es gerente_tienda (nivel 4), puede ver todos los tickets de su departamento
        ELSEIF usuario_jerarquia = 'gerente_tienda' THEN
            SELECT GROUP_CONCAT(id) INTO resultado
            FROM usuarios 
            WHERE departamento_id = usuario_departamento 
            AND rol = 'cliente' 
            AND jerarquia IN ('jefe_tienda', 'asistente_tienda', 'sub_gerente_tienda', 'gerente_tienda');
        END IF;
        
        RETURN IFNULL(resultado, CAST(usuario_id AS CHAR));
    END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos`
--

CREATE TABLE `archivos` (
  `id` int(11) NOT NULL,
  `respuesta_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_tickets`
--

CREATE TABLE `archivos_tickets` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `tipo_archivo` varchar(50) NOT NULL,
  `tamaño_archivo` int(11) NOT NULL,
  `fecha_subida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

CREATE TABLE `asignaciones` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#4a90e2',
  `departamento_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `color`, `departamento_id`, `fecha_creacion`) VALUES
(1, 'Problema Comercial', 'Problema comerial donde se agregara muchas cosas, por ejemplo problemas de comercial', '#db4614', 1, '2025-09-03 01:11:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('usuario','cliente') NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `departamentos`
--

INSERT INTO `departamentos` (`id`, `nombre`, `tipo`) VALUES
(1, 'Comercial', 'usuario'),
(2, 'Sistemas', 'usuario'),
(3, 'Operaciones', 'usuario'),
(4, 'Logística', 'usuario'),
(5, 'Ceres Plazavea HP', 'cliente'),
(6, 'Tiendas Norte', 'cliente'),
(7, 'Tiendas Sur', 'cliente'),
(8, 'Tiendas Centro', 'cliente'),
(9, 'Tiendas Este', 'cliente'),
(10, 'RRHH', 'usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `escalamientos`
--

CREATE TABLE `escalamientos` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_solicitud` varchar(100) NOT NULL,
  `email_destinatario` varchar(255) NOT NULL,
  `nombre_destinatario` varchar(255) NOT NULL,
  `cargo_destinatario` varchar(255) NOT NULL,
  `asunto` varchar(500) NOT NULL,
  `mensaje_personalizado` text DEFAULT NULL,
  `estado` enum('enviado','pendiente','error','aprobado','rechazado') DEFAULT 'pendiente',
  `token_aprobacion` varchar(255) NOT NULL,
  `fecha_escalamiento` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_respuesta` timestamp NULL DEFAULT NULL,
  `respuesta_destinatario` text DEFAULT NULL,
  `ip_respuesta` varchar(45) DEFAULT NULL,
  `user_agent_respuesta` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `escalamiento_destinatarios`
--

CREATE TABLE `escalamiento_destinatarios` (
  `id` int(11) NOT NULL,
  `tipo_solicitud` varchar(100) NOT NULL,
  `email_destinatario` varchar(255) NOT NULL,
  `nombre_destinatario` varchar(255) NOT NULL,
  `cargo_destinatario` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `escalamiento_destinatarios`
--

INSERT INTO `escalamiento_destinatarios` (`id`, `tipo_solicitud`, `email_destinatario`, `nombre_destinatario`, `cargo_destinatario`, `activo`, `fecha_creacion`) VALUES
(1, 'cambio_horario', 'director.operaciones@empresa.com', 'Director de Operaciones', 'Director Regional', 1, '2025-09-04 03:48:25'),
(2, 'cambio_horario', 'gerente.general@empresa.com', 'Gerente General', 'Gerente General', 1, '2025-09-04 03:48:25'),
(3, 'incidencia_critica', 'director.operaciones@empresa.com', 'Director de Operaciones', 'Director Regional', 1, '2025-09-04 03:48:25'),
(4, 'incidencia_critica', 'gerente.general@empresa.com', 'Gerente General', 'Gerente General', 1, '2025-09-04 03:48:25'),
(5, 'aprobacion_especial', 'director.operaciones@empresa.com', 'Director de Operaciones', 'Director Regional', 1, '2025-09-04 03:48:25'),
(6, 'aprobacion_especial', 'gerente.general@empresa.com', 'Gerente General', 'Gerente General', 1, '2025-09-04 03:48:25'),
(7, 'solicitud_gerencial', 'director.operaciones@empresa.com', 'Director de Operaciones', 'Director Regional', 1, '2025-09-04 03:48:25'),
(8, 'solicitud_gerencial', 'gerente.general@empresa.com', 'Gerente General', 'Gerente General', 1, '2025-09-04 03:48:25'),
(9, 'escalamiento_general', 'director.operaciones@empresa.com', 'Director de Operaciones', 'Director Regional', 1, '2025-09-04 03:48:25'),
(10, 'escalamiento_general', 'gerente.general@empresa.com', 'Gerente General', 'Gerente General', 1, '2025-09-04 03:48:25'),
(11, 'cambio_horario', 'jorge.pariona@plazavea.pe', 'Jorge Enrique', 'Direcror General', 1, '2025-09-04 03:49:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_tickets`
--

CREATE TABLE `historial_tickets` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `rol` varchar(20) DEFAULT NULL,
  `accion` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_tickets`
--

INSERT INTO `historial_tickets` (`id`, `ticket_id`, `usuario_id`, `rol`, `accion`, `fecha`) VALUES
(84, 34, 13, 'cliente', 'Ticket creado por el Cliente', '2025-09-04 00:40:47'),
(85, 34, 14, 'usuario', 'Ticket tomado por el Usuario', '2025-09-04 00:41:00'),
(86, 34, 13, 'cliente', 'Cliente respondió el ticket', '2025-09-04 00:54:31'),
(87, 34, 14, 'usuario', 'Usuario respondió el ticket', '2025-09-04 00:55:00'),
(88, 34, 13, 'cliente', 'Cliente respondió el ticket', '2025-09-04 01:07:19'),
(89, 35, 9, 'cliente', 'Ticket creado por el Cliente', '2025-09-04 01:25:38'),
(90, 36, 9, 'cliente', 'Ticket creado por el Cliente', '2025-09-04 01:28:09'),
(91, 37, 10, 'cliente', 'Ticket creado por el Cliente', '2025-09-04 02:54:20'),
(92, 34, 14, 'usuario', 'Ticket reasignado de \'Jorge\' a \'brus\'', '2025-09-04 03:37:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jerarquias`
--

CREATE TABLE `jerarquias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `nivel` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `jerarquias`
--

INSERT INTO `jerarquias` (`id`, `nombre`, `nivel`, `descripcion`) VALUES
(1, 'jefe_tienda', 1, 'Jefe de Tienda - Nivel más bajo'),
(2, 'gerente_tienda', 4, 'Gerente de Tienda - Nivel más alto'),
(3, 'sub_gerente_tienda', 3, 'Sub Gerente de Tienda'),
(4, 'asistente_tienda', 2, 'Asistente de Tienda');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas`
--

CREATE TABLE `respuestas` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha_respuesta` datetime NOT NULL DEFAULT current_timestamp(),
  `rol` enum('cliente','usuario') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `respuestas`
--

INSERT INTO `respuestas` (`id`, `ticket_id`, `usuario_id`, `mensaje`, `fecha_respuesta`, `rol`) VALUES
(56, 34, 13, 'Hola explicame', '2025-09-03 19:54:31', 'cliente'),
(57, 34, 14, 'hola dime', '2025-09-03 19:55:00', 'usuario'),
(58, 34, 13, 'hola como esta', '2025-09-03 20:07:19', 'cliente'),
(59, 34, 14, '???? **REASIGNACIÓN DE TICKET**\n\nEste ticket ha sido reasignado de **Jorge** a **brus**.\n\nEl nuevo responsable del ticket es: **brus**', '2025-09-03 22:37:59', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `imagenes_pegadas` text DEFAULT NULL,
  `estado` enum('pendiente','abierto','en_proceso','cerrado') DEFAULT 'pendiente',
  `tipo_solicitud` varchar(100) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  `departamento_id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tickets`
--

INSERT INTO `tickets` (`id`, `categoria_id`, `descripcion`, `imagenes_pegadas`, `estado`, `tipo_solicitud`, `fecha_creacion`, `usuario_id`, `departamento_id`, `cliente_id`, `fecha_inicio`, `fecha_cierre`) VALUES
(34, 1, 'hola ayudame con este problema', '', 'en_proceso', NULL, '2025-09-04 00:40:47', 15, 1, 13, '2025-09-03 19:41:00', NULL),
(35, 1, 'Hola Ayudame con lo solicitado', '', 'pendiente', NULL, '2025-09-04 01:25:38', NULL, 1, 9, NULL, NULL),
(36, 1, 'Hola ayudame con la creacion del siguiente usuario', '', 'pendiente', NULL, '2025-09-04 01:28:09', NULL, 1, 9, NULL, NULL),
(37, 1, 'Hola creame este ticket gerente', '', 'pendiente', NULL, '2025-09-04 02:54:20', NULL, 1, 10, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `clave` varchar(100) NOT NULL,
  `rol` enum('admin','usuario','cliente') NOT NULL,
  `departamento_id` int(11) DEFAULT NULL,
  `jerarquia` enum('jefe_tienda','gerente_tienda','sub_gerente_tienda','asistente_tienda') DEFAULT NULL,
  `superior_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `nombre`, `clave`, `rol`, `departamento_id`, `jerarquia`, `superior_id`) VALUES
(1, 'admin', 'Administrador del Sistema', '123', 'admin', NULL, NULL, NULL),
(9, 'jefe_norte_1', 'Carlos Mendoza', '123', 'cliente', 6, 'jefe_tienda', 12),
(10, 'gerente_norte_1', 'Ana García', '123', 'cliente', 6, 'gerente_tienda', 10),
(11, 'sub_gerente_norte_1', 'Luis Rodríguez', '123', 'cliente', 6, 'sub_gerente_tienda', 10),
(12, 'asistente_norte_1', 'María López', '123', 'cliente', 6, 'asistente_tienda', 11),
(13, 'jefe_norte_2', 'Elizabeth Quispe', '123', 'cliente', 6, 'jefe_tienda', 12),
(14, 'Jorge', 'Jorge Pariona', '123', 'usuario', 1, NULL, NULL),
(15, 'brus', 'Brus Chen', '123', 'usuario', 1, NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `respuesta_id` (`respuesta_id`);

--
-- Indices de la tabla `archivos_tickets`
--
ALTER TABLE `archivos_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `departamento_id` (`departamento_id`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `idx_departamentos_tipo` (`tipo`);

--
-- Indices de la tabla `escalamientos`
--
ALTER TABLE `escalamientos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_aprobacion` (`token_aprobacion`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `escalamiento_destinatarios`
--
ALTER TABLE `escalamiento_destinatarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tipo_email` (`tipo_solicitud`,`email_destinatario`);

--
-- Indices de la tabla `historial_tickets`
--
ALTER TABLE `historial_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `jerarquias`
--
ALTER TABLE `jerarquias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `respuestas`
--
ALTER TABLE `respuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `fk_departamento_ticket` (`departamento_id`),
  ADD KEY `fk_cliente` (`cliente_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_departamento_usuario` (`departamento_id`),
  ADD KEY `idx_usuarios_jerarquia` (`jerarquia`,`departamento_id`),
  ADD KEY `idx_usuarios_superior` (`superior_id`),
  ADD KEY `fk_jerarquia` (`jerarquia`),
  ADD KEY `fk_superior` (`superior_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos`
--
ALTER TABLE `archivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `archivos_tickets`
--
ALTER TABLE `archivos_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `escalamientos`
--
ALTER TABLE `escalamientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `escalamiento_destinatarios`
--
ALTER TABLE `escalamiento_destinatarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `historial_tickets`
--
ALTER TABLE `historial_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de la tabla `jerarquias`
--
ALTER TABLE `jerarquias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `respuestas`
--
ALTER TABLE `respuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD CONSTRAINT `archivos_ibfk_1` FOREIGN KEY (`respuesta_id`) REFERENCES `respuestas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `archivos_tickets`
--
ALTER TABLE `archivos_tickets`
  ADD CONSTRAINT `fk_archivos_tickets_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `asignaciones_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  ADD CONSTRAINT `asignaciones_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `escalamientos`
--
ALTER TABLE `escalamientos`
  ADD CONSTRAINT `escalamientos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escalamientos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_tickets`
--
ALTER TABLE `historial_tickets`
  ADD CONSTRAINT `historial_tickets_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  ADD CONSTRAINT `historial_tickets_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `respuestas`
--
ALTER TABLE `respuestas`
  ADD CONSTRAINT `respuestas_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `respuestas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_departamento_ticket` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`),
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_departamento_usuario` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`),
  ADD CONSTRAINT `fk_usuario_superior` FOREIGN KEY (`superior_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
