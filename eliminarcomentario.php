<?php
session_start();
include('conexion.php');

// 1. Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// 2. Verificar que sea ADMIN (Seguridad Crítica)
$id_usuario = $_SESSION['id_usuario'];
$sql_rol = "SELECT rol FROM usuarios WHERE id_usuario = ?";
$stmt_rol = $conn->prepare($sql_rol);
$stmt_rol->bind_param("i", $id_usuario);
$stmt_rol->execute();
$res_rol = $stmt_rol->get_result();
$fila_rol = $res_rol->fetch_assoc();

if ($fila_rol['rol'] !== 'admin') {
    $_SESSION['mensaje'] = "🚫 No tienes permiso para eliminar comentarios.";
    header("Location: index.php");
    exit();
}

// 3. Verificar método POST y datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_comentario'])) {
    
    $id_comentario = $_POST['id_comentario'];

    // 4. Eliminar el comentario
    $sql = "DELETE FROM comentarios WHERE id_comentario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_comentario);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "✔ Comentario eliminado correctamente.";
    } else {
        $_SESSION['mensaje'] = "❌ Error al eliminar: " . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
header("Location: index.php"); // Regresar al inicio
exit();
?>