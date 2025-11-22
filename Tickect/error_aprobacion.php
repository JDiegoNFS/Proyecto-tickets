<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Sistema de Tickets</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
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
        
        .error-title {
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
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #5a6268, #6c757d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.3);
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        
        <h1 class="error-title">Error en la Aprobación</h1>
        
        <p class="error-message">
            <?php echo htmlspecialchars($error ?? 'Ha ocurrido un error inesperado'); ?>
        </p>
        
        <div class="actions">
            <a href="javascript:window.close();" class="btn">
                <i class="fas fa-times"></i>
                Cerrar Ventana
            </a>
        </div>
        
        <div class="footer">
            <p>Si el problema persiste, contacte al administrador del sistema.</p>
        </div>
    </div>
</body>
</html>
