<?php
session_start();

// Eliminar todas las variables de sesión
session_unset();

// Destruir la sesión completamente
session_destroy();

// Redirigir al usuario al inicio
header("Location: index.php");
exit();
?>