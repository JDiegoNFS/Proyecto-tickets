<?php
session_start();
require_once 'includes/db.php';

// Manejo del POST (procesamiento de login)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $clave = $_POST["clave"];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND clave = ?");
    $stmt->execute([$usuario, $clave]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION["usuario_id"] = $user["id"];
        $_SESSION["rol"] = $user["rol"];
        $_SESSION["usuario_nombre"] = $user["usuario"];

        // Redirección según el rol
        switch ($user["rol"]) {
            case 'admin':
                header("Location: dashboard.php");
                break;
            case 'usuario':
                header("Location: usuario/ver_tickets.php");
                break;
            case 'cliente':
                header("Location: cliente/ver_tickets.php");
                break;
        }
        exit;
    } else {
        // Guardamos el error y redireccionamos (PRG)
        $_SESSION["login_error"] = "Credenciales incorrectas";
        header("Location: index.php");
        exit;
    }
}

// Mostrar el error si existe
$error = '';
if (isset($_SESSION["login_error"])) {
    $error = $_SESSION["login_error"];
    unset($_SESSION["login_error"]);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Tickets</title>
    <link rel="stylesheet" href="css/style_index.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/global-theme-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="page-title">Sistema de Tickets</div>

    <div class="login-wrapper">
        <div class="login-container">
            <h2>Iniciar Sesión</h2>
            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="post">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="usuario" required autocomplete="username" aria-label="Usuario">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="clave" required autocomplete="current-password" aria-label="Contraseña">
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>

                <div class="options">
                    <label><input type="checkbox" name="recordar"> Recordarme</label>
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit">Entrar</button>

                <div class="register-link">
                    ¿No tienes cuenta? <a href="#">Regístrate</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.querySelector('input[name="clave"]');
            const icon = document.querySelector('.toggle-password');
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    </script>

    <!-- Sistema de Temas -->
    <script src="js/theme-manager.js"></script>

</body>
</html>
