<?php
session_start();
include('conexion.php');

$redirect_page = 'post.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Verificar que sea admin
$sql_user = "SELECT rol FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $_SESSION['id_usuario']);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

if ($user['rol'] !== 'admin') {
    $_SESSION['mensaje'] = "❌ Solo el administrador puede crear mundiales.";
    header("Location: $redirect_page");
    exit();
}

// Validar método
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: $redirect_page");
    exit();
}

// Recibir datos
$anio = $_POST['anio'];
$sede = $_POST['sede'];
$campeon = $_POST['campeon'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;

// Validar campos obligatorios
if (empty($anio) || empty($sede)) {
    $_SESSION['mensaje'] = "❌ El año y la sede son obligatorios.";
    header("Location: $redirect_page");
    exit();
}

// Evitar mundiales duplicados
$check = $conn->prepare("SELECT id_mundial FROM mundiales WHERE anio = ?");
$check->bind_param("i", $anio);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION['mensaje'] = "❌ Ya existe un mundial con ese año.";
    header("Location: $redirect_page");
    exit();
}
$check->close();

// --- PROCESAR LOGO (NUEVO) ---
$ruta_logo = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
    $directorio = "uploads/mundiales/";
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    
    $extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array($extension, $allowed)) {
        $nombre_archivo = "logo_" . $anio . "_" . time() . "." . $extension;
        $destino = $directorio . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destino)) {
            $ruta_logo = $destino;
        } else {
            $_SESSION['mensaje'] = "❌ Error al mover el logo a la carpeta.";
            header("Location: $redirect_page");
            exit();
        }
    } else {
        $_SESSION['mensaje'] = "❌ Formato de imagen no válido (solo jpg, png, gif, webp).";
        header("Location: $redirect_page");
        exit();
    }
}

// Insertar mundial (Consulta Actualizada)
$sql = "INSERT INTO mundiales (anio, sede, campeon, descripcion, logo) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $anio, $sede, $campeon, $descripcion, $ruta_logo);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "✔ Mundial creado correctamente.";
} else {
    $_SESSION['mensaje'] = "❌ Error al crear mundial: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: $redirect_page");
exit();
?>