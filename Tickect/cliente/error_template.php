<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Sistema de Tickets</title>
    <link rel="stylesheet" href="../css/style_cliente_responder.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="page-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-text">
                    <h1 class="titulo">⚠️ Acceso Denegado</h1>
                    <p class="subtitulo">No tienes permisos para acceder a este contenido</p>
                </div>
                <div class="header-actions">
                    <a href="ver_tickets.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Volver a Mis Tickets
                    </a>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div class="error-container">
            <div class="error-card">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="error-content">
                    <h2>Acceso Restringido</h2>
                    <p class="error-message"><?php echo htmlspecialchars($mensaje_error ?? 'Error desconocido'); ?></p>
                    <div class="error-actions">
                        <a href="ver_tickets.php" class="btn btn-primary">
                            <i class="fas fa-ticket-alt"></i> Ver Mis Tickets
                        </a>
                        <a href="crear_ticket.php" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Crear Nuevo Ticket
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .error-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 60vh;
            padding: 20px;
        }

        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            max-width: 500px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .error-icon {
            font-size: 4rem;
            color: #e74c3c;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .error-content h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .error-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #357abd, #4a90e2);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 144, 226, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }

        @media (max-width: 768px) {
            .error-card {
                padding: 30px 20px;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</body>
</html>
