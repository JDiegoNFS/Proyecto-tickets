-- Script para generar 30 tickets de prueba con datos variados
-- Sistema de Tickets - Datos de Prueba

-- Primero, asegurarnos de tener usuarios de prueba
INSERT INTO usuarios (usuario, nombre, clave, rol, departamento_id) VALUES
('soporte1', 'Ana García', '123', 'usuario', 1),
('soporte2', 'Carlos Ruiz', '123', 'usuario', 1),
('soporte3', 'María López', '123', 'usuario', 2),
('soporte4', 'Pedro Martínez', '123', 'usuario', 2),
('cliente1', 'Juan Pérez', '123', 'cliente', 1),
('cliente2', 'Laura Sánchez', '123', 'cliente', 2),
('cliente3', 'Roberto Torres', '123', 'cliente', 1),
('cliente4', 'Sofia Ramírez', '123', 'cliente', 2)
ON DUPLICATE KEY UPDATE usuario=usuario;

-- Generar 30 tickets de prueba con fechas variadas (últimos 6 meses)
-- Tickets de Agosto 2024
INSERT INTO tickets (categoria_id, descripcion, estado, fecha_creacion, usuario_id, departamento_id, cliente_id, fecha_inicio, fecha_cierre) VALUES
(1, 'Problema con el acceso al sistema de nómina. No puedo iniciar sesión desde esta mañana.', 'cerrado', '2024-08-05 09:15:00', 15, 1, 13, '2024-08-05 09:30:00', '2024-08-05 14:20:00'),
(1, 'Solicitud de instalación de software Office en computadora nueva del área de ventas.', 'cerrado', '2024-08-08 10:30:00', 15, 1, 9, '2024-08-08 11:00:00', '2024-08-08 16:45:00'),
(2, 'La impresora del segundo piso no está funcionando. Necesito imprimir documentos urgentes.', 'cerrado', '2024-08-12 14:20:00', 16, 2, 10, '2024-08-12 14:45:00', '2024-08-13 10:30:00'),
(1, 'Necesito permisos de administrador para instalar un programa de diseño.', 'cerrado', '2024-08-15 08:45:00', 15, 1, 13, '2024-08-15 09:00:00', '2024-08-15 11:30:00'),
(2, 'El aire acondicionado de la sala de juntas no enfría correctamente.', 'cerrado', '2024-08-20 11:00:00', 16, 2, 9, '2024-08-20 13:00:00', '2024-08-21 09:00:00'),

-- Tickets de Septiembre 2024
(1, 'Error al intentar acceder al servidor compartido. Mensaje: "Acceso denegado".', 'cerrado', '2024-09-02 09:00:00', 15, 1, 13, '2024-09-02 09:30:00', '2024-09-02 15:00:00'),
(1, 'Solicitud de creación de usuario para nuevo empleado del departamento de ventas.', 'cerrado', '2024-09-05 10:15:00', 15, 1, 9, '2024-09-05 10:30:00', '2024-09-05 12:00:00'),
(2, 'Fuga de agua en el baño del tercer piso. Requiere atención inmediata.', 'cerrado', '2024-09-10 13:30:00', 16, 2, 10, '2024-09-10 14:00:00', '2024-09-11 08:00:00'),
(1, 'Mi correo electrónico no está recibiendo mensajes desde ayer.', 'cerrado', '2024-09-15 08:30:00', 15, 1, 13, '2024-09-15 09:00:00', '2024-09-15 16:30:00'),
(2, 'Solicitud de mantenimiento preventivo para equipos de cómputo del área administrativa.', 'cerrado', '2024-09-20 11:45:00', 16, 2, 9, '2024-09-21 08:00:00', '2024-09-22 17:00:00'),

-- Tickets de Octubre 2024
(1, 'Problema con VPN. No puedo conectarme desde casa para trabajar remotamente.', 'cerrado', '2024-10-03 07:45:00', 15, 1, 13, '2024-10-03 08:30:00', '2024-10-03 12:00:00'),
(1, 'Solicitud de aumento de espacio en disco en el servidor. Estoy al 95% de capacidad.', 'cerrado', '2024-10-07 09:30:00', 15, 1, 9, '2024-10-07 10:00:00', '2024-10-08 14:00:00'),
(2, 'Las luces del estacionamiento no están funcionando. Representa un riesgo de seguridad.', 'cerrado', '2024-10-12 16:00:00', 16, 2, 10, '2024-10-13 08:00:00', '2024-10-13 11:00:00'),
(1, 'Necesito recuperar archivos borrados accidentalmente de la carpeta compartida.', 'cerrado', '2024-10-18 10:00:00', 15, 1, 13, '2024-10-18 10:30:00', '2024-10-18 15:45:00'),
(2, 'Solicitud de limpieza profunda en oficinas. Hay mucho polvo acumulado.', 'cerrado', '2024-10-25 14:30:00', 16, 2, 9, '2024-10-26 07:00:00', '2024-10-26 12:00:00'),

-- Tickets de Noviembre 2024 (algunos cerrados, algunos en proceso, algunos pendientes)
(1, 'El sistema de punto de venta está muy lento. Afecta la atención a clientes.', 'cerrado', '2024-11-02 09:00:00', 15, 1, 13, '2024-11-02 09:30:00', '2024-11-02 17:00:00'),
(1, 'Solicitud de instalación de antivirus en computadoras nuevas del almacén.', 'cerrado', '2024-11-05 10:30:00', 15, 1, 9, '2024-11-05 11:00:00', '2024-11-05 16:00:00'),
(2, 'El elevador hace ruidos extraños. Necesita revisión técnica urgente.', 'cerrado', '2024-11-08 08:15:00', 16, 2, 10, '2024-11-08 09:00:00', '2024-11-09 14:00:00'),
(1, 'No puedo acceder a la base de datos de clientes. Error de conexión.', 'en_proceso', '2024-11-12 11:00:00', 15, 1, 13, '2024-11-12 11:30:00', NULL),
(2, 'Solicitud de cambio de cerraduras en la puerta principal por seguridad.', 'en_proceso', '2024-11-15 13:00:00', 16, 2, 9, '2024-11-15 14:00:00', NULL),

-- Tickets de Noviembre 2024 (recientes - pendientes y en proceso)
(1, 'Mi computadora se reinicia sola constantemente. No puedo trabajar.', 'en_proceso', '2024-11-20 09:30:00', 15, 1, 13, '2024-11-20 10:00:00', NULL),
(1, 'Solicitud de configuración de impresora en red para el área de contabilidad.', 'en_proceso', '2024-11-22 10:00:00', 15, 1, 9, '2024-11-22 10:30:00', NULL),
(2, 'El sistema de alarma no está funcionando correctamente. Se activa sin razón.', 'pendiente', '2024-11-25 14:00:00', NULL, 2, 10, NULL, NULL),
(1, 'Necesito acceso al sistema de reportes financieros para el cierre mensual.', 'pendiente', '2024-11-26 08:00:00', NULL, 1, 13, NULL, NULL),
(2, 'Solicitud de fumigación en las oficinas. Hay presencia de insectos.', 'pendiente', '2024-11-27 11:00:00', NULL, 2, 9, NULL, NULL),

-- Tickets muy recientes (últimos días)
(1, 'El servidor de archivos no responde. No puedo acceder a documentos importantes.', 'pendiente', '2024-11-28 09:00:00', NULL, 1, 13, NULL, NULL),
(1, 'Solicitud de actualización de software de contabilidad a la última versión.', 'pendiente', '2024-11-28 14:30:00', NULL, 1, 9, NULL, NULL),
(2, 'La puerta del baño del primer piso está atascada y no cierra bien.', 'pendiente', '2024-11-29 10:15:00', NULL, 2, 10, NULL, NULL),
(1, 'Mi teclado no funciona. Necesito reemplazo urgente.', 'abierto', '2024-11-29 15:00:00', 15, 1, 13, NULL, NULL),
(2, 'Solicitud de instalación de dispensador de agua en el área de descanso.', 'abierto', '2024-11-30 08:30:00', 16, 2, 9, NULL, NULL);

-- Mensaje de confirmación
SELECT 'Se han insertado 30 tickets de prueba exitosamente' as Resultado;
SELECT 
    estado,
    COUNT(*) as cantidad,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tickets), 1), '%') as porcentaje
FROM tickets 
GROUP BY estado
ORDER BY cantidad DESC;
