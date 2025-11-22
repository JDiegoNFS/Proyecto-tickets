<?php
echo "<h1>🚀 Proyecto Sistema de Tickets</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Servidor:</strong> " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p><strong>Puerto:</strong> " . $_SERVER['SERVER_PORT'] . "</p>";
echo "<p><strong>Ruta del proyecto:</strong> " . __DIR__ . "</p>";

echo "<h2>🔗 Enlaces de acceso:</h2>";
echo "<ul>";
echo "<li><a href='Tickect/index.php' target='_blank'>🏠 Página principal del sistema</a></li>";
echo "<li><a href='Tickect/dashboard.php' target='_blank'>📊 Dashboard (requiere login)</a></li>";
echo "</ul>";

echo "<h2>👥 Usuarios de prueba:</h2>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Usuario</th><th>Contraseña</th><th>Rol</th></tr>";
echo "<tr><td>admin</td><td>123</td><td>Administrador</td></tr>";
echo "<tr><td>Jorge</td><td>123</td><td>Usuario (Comercial)</td></tr>";
echo "<tr><td>jefe_norte_1</td><td>123</td><td>Cliente (Jefe Tienda)</td></tr>";
echo "</table>";

echo "<p><em>Todos los usuarios usan la contraseña: <strong>123</strong></em></p>";
?>