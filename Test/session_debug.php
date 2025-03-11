<?php
// Initialize session
session_start();

// Display current session information
echo "<h2>Información de la sesión actual:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Display session configuration
echo "<h2>Configuración de PHP para sesiones:</h2>";
echo "<p>session.save_path: " . ini_get('session.save_path') . "</p>";
echo "<p>session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "</p>";
echo "<p>session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "</p>";
echo "<p>session.cookie_domain: " . ini_get('session.cookie_domain') . "</p>";
echo "<p>session.cookie_path: " . ini_get('session.cookie_path') . "</p>";

// Display session storage information
echo "<h2>Información sobre el almacenamiento de sesiones:</h2>";
echo "<p>Current session ID: " . session_id() . "</p>";
echo "<p>Current session status: ";
$status = session_status();
if ($status === PHP_SESSION_DISABLED) {
    echo "DISABLED";
} else if ($status === PHP_SESSION_NONE) {
    echo "NONE";
} else if ($status === PHP_SESSION_ACTIVE) {
    echo "ACTIVE";
}
echo "</p>";

// Link for manual testing
echo "<h2>Acciones:</h2>";
echo "<p><a href='index.php'>Volver a la página de inicio de sesión</a></p>";
echo "<p><a href='logout.php'>Cerrar sesión</a></p>";
?>