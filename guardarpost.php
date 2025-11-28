<?php
session_start();
include('conexion.php');

$redirect_error = 'post.php';
$redirect_success = 'index.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect_error");
    exit();
}

// === PASO 1: Guardar los datos del formulario para re-poblar en caso de error ===
$_SESSION['form_data_post'] = $_POST;

// Recibir datos del formulario
$id_mundial = $_POST['id_mundial'] ?? null;
$id_categoria = $_POST['id_categoria'] ?? null;
$titulo = trim($_POST['titulo'] ?? '');
$contenido = trim($_POST['contenido'] ?? '');
$link_media = trim($_POST['link_media'] ?? '');
$media_path = '';
$tipo_contenido = 'texto'; // default

// ===============================
// (NUEVO) VALIDACIÓN: Solo uno a la vez
// ===============================
$tiene_archivo = (isset($_FILES['media']) && $_FILES['media']['error'] === 0 && !empty($_FILES['media']['name']));
$tiene_link = !empty($link_media);

if ($tiene_archivo && $tiene_link) {
    $_SESSION['mensaje'] = "❌ Solo puedes agregar UNA opción multimedia: sube un archivo O pega un enlace, pero no ambos.";
    header("Location: $redirect_error");
    exit();
}

// Validar campos obligatorios
if (empty($id_mundial) || empty($id_categoria) || empty($titulo) || empty($contenido)) {
    $_SESSION['mensaje'] = "❌ Faltan datos obligatorios (Mundial, Categoría, Título y Contenido).";
    header("Location: $redirect_error");
    exit();
}

// ===============================
// 1. Manejo de archivo multimedia
// ===============================
if ($tiene_archivo) {
    $carpeta = "uploads_posts/";
    if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);

    $nombre_temp = $_FILES['media']['tmp_name'];
    $nombre_archivo = $_FILES['media']['name'];
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

    // Determinar tipo
    $mime = mime_content_type($nombre_temp);
    if (strstr($mime, "image")) {
        $tipo_contenido = 'imagen';
    } elseif (strstr($mime, "video")) {
        $tipo_contenido = 'video';
    } else {
        $_SESSION['mensaje'] = "❌ Solo se permiten imágenes o videos.";
        header("Location: $redirect_error");
        exit();
    }

    $nuevo_nombre = "post_" . time() . "_" . rand(1000,9999) . "." . $extension;
    $ruta_destino = $carpeta . $nuevo_nombre;

    if (!move_uploaded_file($nombre_temp, $ruta_destino)) {
        $_SESSION['mensaje'] = "❌ Error al subir el archivo multimedia.";
        header("Location: $redirect_error");
        exit();
    }

    $media_path = $ruta_destino;
}

// ===============================
// 2. Manejo de link de video
// ===============================
if ($tiene_link) {
    // Validar que sea un enlace YouTube
    if (filter_var($link_media, FILTER_VALIDATE_URL)) {
        $tipo_contenido = 'video_link'; 
        $media_path = $link_media;
    } else {
        $_SESSION['mensaje'] = "❌ El enlace de video no es válido.";
        header("Location: $redirect_error");
        exit();
    }
}

// ===============================
// 3. Insertar publicación
// ===============================
$sql = "INSERT INTO publicaciones
(id_usuario, id_mundial, id_categoria, titulo, contenido, tipo_contenido, media_path, fecha_publicacion)
VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iiissss",
    $id_usuario,
    $id_mundial,
    $id_categoria,
    $titulo,
    $contenido,
    $tipo_contenido,
    $media_path
);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "✔ Publicación creada con éxito. Pendiente de aprobación.";
    
    // IMPORTANTE: Borrar los datos del formulario de la sesión si todo salió bien
    unset($_SESSION['form_data_post']);
    
    header("Location: $redirect_success");
} else {
    $_SESSION['mensaje'] = "❌ Error al guardar la publicación: " . $conn->error;
    header("Location: $redirect_error");
}

$stmt->close();
$conn->close();
exit();
?>