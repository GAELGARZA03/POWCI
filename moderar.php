<?php
session_start();
include('conexion.php');

// Redirigir de vuelta a la misma página (index o mundiales)
$redirect_page = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Verificar que haya sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Verificar rol admin
$sql = "SELECT rol FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['mensaje'] = "❌ Usuario no encontrado";
    header("Location: $redirect_page");
    exit();
}
$user = $result->fetch_assoc();
if ($user['rol'] !== 'admin') {
    $_SESSION['mensaje'] = "🚫 Solo los administradores pueden moderar publicaciones.";
    header("Location: $redirect_page");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect_page");
    exit();
}

$id_publicacion = intval($_POST['id_publicacion'] ?? 0);
$accion = $_POST['accion'] ?? '';

if ($id_publicacion <= 0 || !in_array($accion, ['aprobar','rechazar'])) {
    $_SESSION['mensaje'] = "❌ Datos inválidos.";
    header("Location: $redirect_page");
    exit();
}

if ($accion === 'aprobar') {
    $sql_update = "UPDATE publicaciones SET estado_publicacion='aprobada', fecha_aprobacion=NOW() WHERE id_publicacion=?";
} else {
    $sql_update = "UPDATE publicaciones SET estado_publicacion='rechazada', fecha_aprobacion=NULL WHERE id_publicacion=?";
}

$stmt = $conn->prepare($sql_update);
$stmt->bind_param("i", $id_publicacion);
if ($stmt->execute()) {
    $_SESSION['mensaje'] = ($accion === 'aprobar') ? "✔ Publicación aprobada" : "❌ Publicación rechazada";
} else {
    $_SESSION['mensaje'] = "❌ Error al actualizar publicación: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: $redirect_page");
exit();
?>