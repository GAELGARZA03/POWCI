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

// Insertar mundial
$sql = "INSERT INTO mundiales (anio, sede, campeon, descripcion) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $anio, $sede, $campeon, $descripcion);

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