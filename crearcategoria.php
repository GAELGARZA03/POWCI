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
    $_SESSION['mensaje'] = "❌ Solo el administrador puede crear categorías.";
    header("Location: $redirect_page");
    exit();
}

// Validar método
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: $redirect_page");
    exit();
}

// Recibir datos
$nombre = trim($_POST['nombre']);
$descripcion = $_POST['descripcion'] ?? null;

// Campos obligatorios
if (empty($nombre)) {
    $_SESSION['mensaje'] = "❌ El nombre de la categoría es obligatorio.";
    header("Location: $redirect_page");
    exit();
}

// Evitar categorías duplicadas
$check = $conn->prepare("SELECT id_categoria FROM categorias WHERE nombre = ?");
$check->bind_param("s", $nombre);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION['mensaje'] = "❌ Ya existe una categoría con ese nombre.";
    header("Location: $redirect_page");
    exit();
}

// Insertar
$sql = "INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $nombre, $descripcion);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "✔ Categoría creada correctamente.";
} else {
    $_SESSION['mensaje'] = "❌ Error al crear categoría: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: $redirect_page");
exit();
?>