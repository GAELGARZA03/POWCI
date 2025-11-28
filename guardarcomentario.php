<?php
session_start();
include('conexion.php');

// Definimos a dónde redirigir (normalmente de vuelta al index)
$redirect_page = 'index.php'; 

// 1. Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// 2. Verificar que los datos vengan por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect_page");
    exit();
}

// 3. Obtener y validar datos
$id_publicacion = $_POST['id_publicacion'] ?? 0;
$contenido = trim($_POST['contenido'] ?? '');

// Validación simple
if ($id_publicacion <= 0 || empty($contenido)) {
    $_SESSION['mensaje'] = "❌ No puedes enviar un comentario vacío.";
    header("Location: $redirect_page");
    exit();
}

// 4. Insertar en la Base de Datos
$sql = "INSERT INTO comentarios (id_publicacion, id_usuario, contenido, fecha_comentario) 
        VALUES (?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $id_publicacion, $id_usuario, $contenido);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "✔ Comentario publicado con éxito.";
} else {
    $_SESSION['mensaje'] = "❌ Error al guardar el comentario: " . $conn->error;
}

$stmt->close();
$conn->close();

// 5. Redirigir de vuelta
header("Location: $redirect_page");
exit();
?>