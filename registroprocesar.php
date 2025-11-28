<?php
session_start();

$redirect_error = 'registro.php';
$redirect_success = 'login.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: $redirect_error");
    exit();
}

$_SESSION['form_data'] = $_POST;

$servername = "localhost";
$username = "root";
$password = "";
$database = "bd_unifut";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $database, $port);

if ($conn->connect_error) {
    $_SESSION['mensaje'] = "Error de conexión: " . $conn->connect_error;
    header("Location: $redirect_error");
    exit();
}

// =============================================
// 1. OBTENER DATOS DEL FORMULARIO
// =============================================
$nombre = trim($_POST["nombre_completo"]); // Usamos trim()
$fecha_nac = $_POST["fecha_nacimiento"];
$genero = $_POST["genero"];
$pais = $_POST["pais_nacimiento"];
$nacionalidad = $_POST["nacionalidad"];
$email = $_POST["email"];
$contrasena = $_POST["contrasena"];
$rol = $_POST["rol"];
$estado = "activo";


// =============================================
// 2. VALIDACIONES CRÍTICAS
// =============================================

// A. Validación de campos vacíos
if (empty($nombre) || empty($fecha_nac) || empty($genero) || empty($pais) || empty($nacionalidad) || empty($email) || empty($contrasena)) {
    $_SESSION['mensaje'] = "❌ Todos los campos son obligatorios.";
    header("Location: $redirect_error");
    exit();
}

// B. (NUEVO) Validación de Nombre Completo
$texto_regex = '/^[\p{L}\s]+$/u'; // Regex para solo letras y espacios

if (!preg_match($texto_regex, $nombre)) {
    $_SESSION['mensaje'] = "❌ El 'Nombre completo' solo puede contener letras y espacios.";
    header("Location: $redirect_error");
    exit();
}
if (strpos($nombre, ' ') === false) { // Revisa si hay al menos un espacio
    $_SESSION['mensaje'] = "❌ Debes ingresar tu nombre completo (ej: Juan Perez).";
    header("Location: $redirect_error");
    exit();
}

// C. (NUEVO) Validación de Nacionalidad (para evitar trampas de JS)
if (!preg_match($texto_regex, $nacionalidad)) {
    $_SESSION['mensaje'] = "❌ La 'Nacionalidad' no es válida.";
    header("Location: $redirect_error");
    exit();
}

// D. Validación de Formato de Correo
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje'] = "❌ El formato del correo no es válido (ej: usuario@dominio.com).";
    header("Location: $redirect_error");
    exit();
}

// Verificamos que el correo tenga el formato usuario@dominio.com
$partes = explode('@', $email);
if (count($partes) !== 2 || empty($partes[0]) || empty($partes[1])) {
    $_SESSION['mensaje'] = "❌ El correo debe tener el formato 'usuario@dominio.com'.";
    header("Location: $redirect_error");
    exit();
}

if (strtolower($partes[1]) !== 'gmail.com' && strtolower($partes[1]) !== 'hotmail.com') {
    $_SESSION['mensaje'] = "❌ El correo debe ser de dominio 'gmail.com' o 'hotmail.com'.";
    header("Location: $redirect_error");
    exit();
}

// E. Validación de Fortaleza de Contraseña
$pass_regex = '/^(?=.*[\p{Ll}])(?=.*[\p{Lu}])(?=.*\d)(?=.*[^\p{L}\p{N}\s]).{8,}$/u';
if (!preg_match($pass_regex, $contrasena)) {
    $_SESSION['mensaje'] = "❌ La contraseña no cumple los requisitos: Mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 símbolo.";
    header("Location: $redirect_error");
    exit();
}

// F. Validación de Edad
try {
    $fecha_nac_obj = new DateTime($fecha_nac);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac_obj)->y; 

    if ($edad < 12) {
        $_SESSION['mensaje'] = "❌ Debes ser mayor de 12 años para registrarte. Tienes $edad años.";
        header("Location: $redirect_error");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['mensaje'] = "❌ La fecha de nacimiento no es válida.";
    header("Location: $redirect_error");
    exit();
}

// (La validación de 'País' ya no es necesaria con un <select>)


// =============================================
// 3. VALIDAR EMAIL DUPLICADO
// =============================================
$sql = "SELECT id_usuario FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION['mensaje'] = "❌ Ya existe una cuenta con este correo.";
    $stmt->close();
    header("Location: $redirect_error");
    exit();
}
$stmt->close();


// =============================================
// 4. PROCESAR FOTO
// =============================================
$fotoNombre = "default.jpg"; 

if (!empty($_FILES["foto_perfil"]["name"])) {
    // (Tu código de procesar foto va aquí, está bien como estaba)
    $directorio = "uploads/";
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }
    $extension = strtolower(pathinfo($_FILES["foto_perfil"]["name"], PATHINFO_EXTENSION));
    $extPermitidas = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($extension, $extPermitidas)) {
        $_SESSION['mensaje'] = "❌ Solo se permiten imágenes JPG, PNG o GIF.";
        header("Location: $redirect_error");
        exit();
    }
    if ($_FILES["foto_perfil"]["size"] > 5 * 1024 * 1024) { // 5MB
        $_SESSION['mensaje'] = "❌ La imagen es demasiado grande. Máximo 5MB.";
        header("Location: $redirect_error");
        exit();
    }
    $fotoNombre = uniqid("foto_", true) . "." . $extension;
    $rutaDestino = $directorio . $fotoNombre;
    if (!move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $rutaDestino)) {
        $_SESSION['mensaje'] = "❌ Error al subir la imagen.";
        header("Location: $redirect_error");
        exit();
    }
}


// =============================================
// 5. INSERTAR USUARIO EN LA BASE DE DATOS
// =============================================
$hash = password_hash($contrasena, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios 
(nombre_completo, fecha_nacimiento, genero, pais_nacimiento, nacionalidad, email, contrasena, rol, estado, foto_perfil)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssss", 
    $nombre, $fecha_nac, $genero, $pais, $nacionalidad, $email, 
    $hash, $rol, $estado, $fotoNombre
);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "✔ Registro exitoso. Ya puedes iniciar sesión.";
    unset($_SESSION['form_data']); 
    header("Location: $redirect_success");
    exit();
} else {
    $_SESSION['mensaje'] = "❌ Error al crear usuario: " . $stmt->error;
    header("Location: $redirect_error");
    exit();
}

$stmt->close();
$conn->close();
?>