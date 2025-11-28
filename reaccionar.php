<?php
session_start();
include('conexion.php');

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

// 3. Obtener datos
$id_publicacion = $_POST['id_publicacion'] ?? 0;
$tipo_reaccion = $_POST['tipo_reaccion'] ?? ''; // 'like' o 'dislike'

// 4. Validar
if ($id_publicacion <= 0 || !in_array($tipo_reaccion, ['like', 'dislike'])) {
    $_SESSION['mensaje'] = "❌ Error en la reacción.";
    header("Location: $redirect_page");
    exit();
}

// 5. Lógica de Reacción (La parte clave)

// Primero, vemos si el usuario ya reaccionó a este post
$sql_check = "SELECT tipo FROM reacciones WHERE id_publicacion = ? AND id_usuario = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_publicacion, $id_usuario);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // --- El usuario YA había reaccionado ---
    $reaccion_actual = $result_check->fetch_assoc();

    if ($reaccion_actual['tipo'] === $tipo_reaccion) {
        // Caso A: Hizo clic en el mismo botón (ej: ya dio like y vuelve a dar like)
        // ACCIÓN: Eliminar la reacción (Toggle off)
        $sql_delete = "DELETE FROM reacciones WHERE id_publicacion = ? AND id_usuario = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $id_publicacion, $id_usuario);
        $stmt_delete->execute();
        $_SESSION['mensaje'] = "✔ Reacción eliminada.";
        $stmt_delete->close();

    } else {
        // Caso B: Hizo clic en el botón opuesto (ej: tenía like y dio dislike)
        // ACCIÓN: Actualizar la reacción (Switch)
        $sql_update = "UPDATE reacciones SET tipo = ?, fecha_reaccion = NOW() WHERE id_publicacion = ? AND id_usuario = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sii", $tipo_reaccion, $id_publicacion, $id_usuario);
        $stmt_update->execute();
        $_SESSION['mensaje'] = "✔ Reacción actualizada.";
        $stmt_update->close();
    }

} else {
    // --- El usuario NO había reaccionado ---
    // Caso C: Es una nueva reacción
    // ACCIÓN: Insertar la reacción
    $sql_insert = "INSERT INTO reacciones (id_publicacion, id_usuario, tipo, fecha_reaccion) VALUES (?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iis", $id_publicacion, $id_usuario, $tipo_reaccion);
    $stmt_insert->execute();
    $_SESSION['mensaje'] = "✔ Reacción guardada.";
    $stmt_insert->close();
}

$stmt_check->close();
$conn->close();

// 6. Redirigir de vuelta
header("Location: $redirect_page");
exit();
?>