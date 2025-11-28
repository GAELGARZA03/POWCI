<?php
session_start();
// Si ya inició sesión, no debe volver al login
if (isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit();
}

// --- CÓDIGO NUEVO PARA MENSAJES ---
$mensaje = '';
$tipo_mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    
    // Determina si es éxito o error
    $es_error = strpos($mensaje, '❌') !== false || strpos(strtolower($mensaje), 'error') !== false;
    $tipo_mensaje = $es_error ? 'error' : 'exito';
    
    // unset para que no se repita
    unset($_SESSION['mensaje']);
}
// --- FIN CÓDIGO NUEVO ---
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>

<h2>Iniciar sesión</h2>

<?php if (!empty($mensaje)): ?>
    <div class="alerta <?= $tipo_mensaje ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>
<form action="loginprocesar.php" method="POST">
    <label>Correo electrónico:</label><br>
    <input type="email" name="email" required><br><br>

<label for="login-contrasena">Contraseña:</label><br>
    <input type="password" name="contrasena" id="login-contrasena" required><br>

    <div style="margin-top: -5px; margin-bottom: 15px;">
        <input type="checkbox" id="ver-contrasena-login" style="width: auto;">
        <label for="ver-contrasena-login" style="font-weight: normal; color: var(--gris); font-size: 14px;">
            Mostrar contraseña
        </label>
    </div>

    <button type="submit">Ingresar</button>
</form>

<br>
    <a href="index.php">
        <button>⬅ Volver al inicio</button>
    </a>

    <script src="notificaciones.js"></script>
    <script src="formulario.js"></script> </body>
</body>
</html>