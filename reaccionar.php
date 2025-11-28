<?php
session_start();
include('conexion.php');

// Redirigir a la página de donde vino el usuario, o al index por defecto
$redirect_page = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}
$id_usuario = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect_page");
    exit();
}

$id_publicacion = $_POST['id_publicacion'] ?? 0;
$tipo_reaccion = $_POST['tipo_reaccion'] ?? '';

if ($id_publicacion <= 0 || !in_array($tipo_reaccion, ['like', 'dislike'])) {
    $_SESSION['mensaje'] = "❌ Error en la reacción.";
    header("Location: $redirect_page");
    exit();
}

$sql_check = "SELECT tipo FROM reacciones WHERE id_publicacion = ? AND id_usuario = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_publicacion, $id_usuario);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $reaccion_actual = $result_check->fetch_assoc();
    if ($reaccion_actual['tipo'] === $tipo_reaccion) {
        $sql_delete = "DELETE FROM reacciones WHERE id_publicacion = ? AND id_usuario = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $id_publicacion, $id_usuario);
        $stmt_delete->execute();
        $_SESSION['mensaje'] = "✔ Reacción eliminada.";
        $stmt_delete->close();
    } else {
        $sql_update = "UPDATE reacciones SET tipo = ?, fecha_reaccion = NOW() WHERE id_publicacion = ? AND id_usuario = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sii", $tipo_reaccion, $id_publicacion, $id_usuario);
        $stmt_update->execute();
        $_SESSION['mensaje'] = "✔ Reacción actualizada.";
        $stmt_update->close();
    }
} else {
    $sql_insert = "INSERT INTO reacciones (id_publicacion, id_usuario, tipo, fecha_reaccion) VALUES (?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iis", $id_publicacion, $id_usuario, $tipo_reaccion);
    $stmt_insert->execute();
    $_SESSION['mensaje'] = "✔ Reacción guardada.";
    $stmt_insert->close();
}

$stmt_check->close();
$conn->close();

header("Location: $redirect_page");
exit();
?>