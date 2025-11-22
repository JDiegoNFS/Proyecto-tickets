<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    // Si no hay sesión activa, redirige al login
    header("Location: ../index.php");
    exit;
}

function verificarRol($rolesPermitidos) {
    if (!isset($_SESSION['rol'])) {
        header("Location: ../login.php");
        exit();
    }

    if (is_array($rolesPermitidos)) {
        if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
            header("Location: ../index.php");
            exit();
        }
    } else {
        if ($_SESSION['rol'] !== $rolesPermitidos) {
            header("Location: ../index.php");
            exit();
        }
    }
}

