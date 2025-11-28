<?php
session_start();
include('conexion.php');

$redirect_page = 'perfil.php';

// Verificar sesión activa
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Si no viene desde POST, redirigir
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: $redirect_page");
    exit();
}

// =============================================
// 1. RECIBIR DATOS
// =============================================
$nombre = trim($_POST['nombre']);
$fecha = $_POST['fecha'];
$genero = $_POST['genero'];
$pais = trim($_POST['pais']);
$nacionalidad = trim($_POST['nacionalidad']);
$correo = trim($_POST['correo']);
$password = $_POST['password']; // Puede estar vacío

// =============================================
// 2. VALIDACIONES (Igual que en Registro)
// =============================================

// A. Campos obligatorios (Excepto contraseña y foto)
if (empty($nombre) || empty($fecha) || empty($genero) || empty($pais) || empty($nacionalidad) || empty($correo)) {
    $_SESSION['mensaje'] = "❌ Todos los campos son obligatorios (excepto contraseña y foto).";
    header("Location: $redirect_page");
    exit();
}

// B. Validación de Nombre Completo (Letras, espacios y al menos 2 palabras)
$texto_regex = '/^[\p{L}\s]+$/u';
if (!preg_match($texto_regex, $nombre)) {
    $_SESSION['mensaje'] = "❌ El nombre solo puede contener letras y espacios.";
    header("Location: $redirect_page");
    exit();
}
if (strpos($nombre, ' ') === false) {
    $_SESSION['mensaje'] = "❌ Debes ingresar tu nombre completo (Nombre y Apellido).";
    header("Location: $redirect_page");
    exit();
}

// C. Validación de País y Nacionalidad
if (!preg_match($texto_regex, $pais)) {
    $_SESSION['mensaje'] = "❌ El país solo puede contener letras y espacios.";
    header("Location: $redirect_page");
    exit();
}
if (!preg_match($texto_regex, $nacionalidad)) {
    $_SESSION['mensaje'] = "❌ La nacionalidad solo puede contener letras y espacios.";
    header("Location: $redirect_page");
    exit();
}

// D. Validación de Email
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje'] = "❌ Formato de correo inválido.";
    header("Location: $redirect_page");
    exit();
}

// E. Validación de Edad (> 12 años)
try {
    $fecha_nac_obj = new DateTime($fecha);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac_obj)->y;
    if ($edad < 12) {
        $_SESSION['mensaje'] = "❌ Debes ser mayor de 12 años (tienes $edad).";
        header("Location: $redirect_page");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['mensaje'] = "❌ Fecha de nacimiento inválida.";
    header("Location: $redirect_page");
    exit();
}

// =============================================
// 3. GESTIÓN DE CONTRASEÑA
// =============================================
// Obtenemos la contraseña actual de la base de datos
$sql_get_pass = "SELECT contrasena FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_get_pass);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario_actual = $result->fetch_assoc();

$pass_final_hash = $usuario_actual['contrasena']; // Por defecto, usamos la vieja

// Si el usuario escribió algo en la contraseña, la validamos y actualizamos
if (!empty($password)) {
    // Validación de Fortaleza (La misma del registro)
    $pass_regex = '/^(?=.*[\p{Ll}])(?=.*[\p{Lu}])(?=.*\d)(?=.*[^\p{L}\p{N}\s]).{8,}$/u';
    if (!preg_match($pass_regex, $password)) {
        $_SESSION['mensaje'] = "❌ La nueva contraseña no cumple los requisitos: Mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 símbolo.";
        header("Location: $redirect_page");
        exit();
    }
    
    // Si pasa la validación, la encriptamos
    $pass_final_hash = password_hash($password, PASSWORD_DEFAULT);
}

// =============================================
// 4. GESTIÓN DE FOTO DE PERFIL
// =============================================
$nombre_foto = null;
if (!empty($_FILES['foto']['name'])) {
    $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $extPermitidas = ["jpg", "jpeg", "png", "gif"];

    if (!in_array($extension, $extPermitidas)) {
        $_SESSION['mensaje'] = "❌ Solo se permiten imágenes JPG, PNG o GIF.";
        header("Location: $redirect_page");
        exit();
    }
    
    // Generar nombre único
    $nombre_foto = "perfil_" . $id_usuario . "_" . time() . "." . $extension;
    $ruta_destino = "uploads/" . $nombre_foto;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
        $_SESSION['mensaje'] = "❌ Error al subir la foto.";
        header("Location: $redirect_page");
        exit();
    }
}

// =============================================
// 5. ACTUALIZAR BASE DE DATOS
// =============================================
if ($nombre_foto) {
    // Si subió foto, actualizamos todo INCLUYENDO la foto
    $sql = "UPDATE usuarios SET 
                nombre_completo = ?, 
                fecha_nacimiento = ?, 
                genero = ?, 
                pais_nacimiento = ?, 
                nacionalidad = ?, 
                email = ?, 
                contrasena = ?, 
                foto_perfil = ?
            WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", 
        $nombre, $fecha, $genero, $pais, $nacionalidad, $correo, 
        $pass_final_hash, $nombre_foto, $id_usuario
    );
} else {
    // Si NO subió foto, actualizamos todo EXCEPTO la foto
    $sql = "UPDATE usuarios SET 
                nombre_completo = ?, 
                fecha_nacimiento = ?, 
                genero = ?, 
                pais_nacimiento = ?, 
                nacionalidad = ?, 
                email = ?, 
                contrasena = ?
            WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", 
        $nombre, $fecha, $genero, $pais, $nacionalidad, $correo, 
        $pass_final_hash, $id_usuario
    );
}

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "✔ Datos actualizados correctamente.";
} else {
    $_SESSION['mensaje'] = "❌ Error al actualizar datos: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: $redirect_page");
exit();
?>