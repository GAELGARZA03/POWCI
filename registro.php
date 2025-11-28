<?php
session_start();

if (isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit();
}

$mensaje = '';
$tipo_mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $es_error = strpos($mensaje, '❌') !== false || strpos(strtolower($mensaje), 'error') !== false;
    $tipo_mensaje = $es_error ? 'error' : 'exito';
    unset($_SESSION['mensaje']);
}

$form_data = []; 
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']); 
}

$admin_existe = false;
$conexion = new mysqli("localhost", "root", "", "bd_unifut", 3306);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
$sql_admin = "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin'";
$resultado = $conexion->query($sql_admin);
$row = $resultado->fetch_assoc();
if ($row["total"] > 0) {
    $admin_existe = true;
}
$conexion->close();

// --- Helpers para re-poblar selects ---
$genero_seleccionado = $form_data['genero'] ?? '';
$pais_seleccionado = $form_data['pais_nacimiento'] ?? '';

// --- Lista de Países (Expandible) ---
$paises = [
    "España", "Estados Unidos", "México", "Argentina", "Bolivia", "Chile", 
    "Colombia", "Costa Rica", "Cuba", "Ecuador", "El Salvador", "Guatemala", 
    "Honduras", "Nicaragua", "Panamá", "Paraguay", "Perú", "Puerto Rico", 
    "República Dominicana", "Uruguay", "Venezuela"
];
sort($paises); // Los ordenamos alfabéticamente
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de usuario</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>

<h2>Crear cuenta</h2>

<?php if (!empty($mensaje)): ?>
    <div class="alerta <?= $tipo_mensaje ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<form action="registroprocesar.php" method="POST" enctype="multipart/form-data">

    <label>Nombre completo:</label><br>
    <input type="text" name="nombre_completo" value="<?= htmlspecialchars($form_data['nombre_completo'] ?? '') ?>" required><br><br>

    <label>Fecha de nacimiento:</label><br>
    <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($form_data['fecha_nacimiento'] ?? '') ?>" required><br><br>

    <label>Foto de perfil:</label><br>
    <input type="file" name="foto_perfil" accept="image/*"><br><br>

    <label>Género:</label><br>
    <select name="genero" required>
        <option value="">-- Selecciona --</option>
        <option value="Masculino" <?= ($genero_seleccionado == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
        <option value="Femenino" <?= ($genero_seleccionado == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
        <option value="Prefiero no decir" <?= ($genero_seleccionado == 'Prefiero no decir') ? 'selected' : '' ?>>Prefiero no decir</option>
    </select><br><br>

    <label for="pais_nacimiento">País de nacimiento:</label><br>
    <select name="pais_nacimiento" id="pais_nacimiento" required>
        <option value="">-- Selecciona un país --</option>
        <?php foreach ($paises as $pais): ?>
            <option value="<?= $pais ?>" <?= ($pais_seleccionado == $pais) ? 'selected' : '' ?>>
                <?= $pais ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label for="nacionalidad">Nacionalidad:</label><br>
    <input type="text" name="nacionalidad" id="nacionalidad" 
           value="<?= htmlspecialchars($form_data['nacionalidad'] ?? '') ?>" 
           readonly required 
           placeholder="Se llenará automáticamente"
           style="background-color: #333; color: #ccc;"> <br><br>

    <label>Correo electrónico:</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required><br><br>

    <label for="registro-contrasena">Contraseña:</label><br>
    <input type="password" name="contrasena" id="registro-contrasena" required><br>

    <div style="margin-top: -5px; margin-bottom: 15px;">
        <input type="checkbox" id="ver-contrasena" style="width: auto;">
        <label for="ver-contrasena" style="font-weight: normal; color: var(--gris); font-size: 14px;">
            Mostrar contraseña
        </label>
    </div>

    <?php if (!$admin_existe): ?>
        <label>Rol del usuario:</label><br>
        <select name="rol" required>
            <option value="admin">Administrador</option>
            <option value="usuario">Usuario</option>
        </select><br><br>
    <?php else: ?>
        <input type="hidden" name="rol" value="usuario">
    <?php endif; ?>

    <button type="submit">Registrarse</button>
</form>

<br>
    <a href="index.php">
        <button>⬅ Volver al inicio</button>
    </a>

    <script src="notificaciones.js"></script>
    <script src="formulario.js"></script>
</body>
</html>