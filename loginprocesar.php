<?php
session_start();

// Verificar que los datos vienen por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$database = "bd_unifut";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $database, $port);

if ($conn->connect_error) {
    // Usamos la sesión para el mensaje de error
    $_SESSION['mensaje'] = "Error de conexión: " . $conn->connect_error;
    header("Location: login.php");
    exit();
}

$email = $_POST["email"];
$password_ingresada = $_POST["contrasena"];

// Consultar usuario
$sql = "SELECT id_usuario, nombre_completo, contrasena FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si existe
if ($result->num_rows === 1) {

    $user = $result->fetch_assoc();
    $hash = $user["contrasena"];

    // Verificar contraseña
    if (password_verify($password_ingresada, $hash)) {

        // Crear sesión
        $_SESSION["id_usuario"] = $user["id_usuario"];
        $_SESSION["nombre"] = $user["nombre_completo"];

        header("Location: index.php"); // Éxito, va al index
        exit();

    } else {
        $_SESSION['mensaje'] = "❌ Contraseña incorrecta.";
        header("Location: login.php"); // Error, regresa al login
        exit();
    }

} else {
    $_SESSION['mensaje'] = "❌ No existe una cuenta con ese correo.";
    header("Location: login.php"); // Error, regresa al login
    exit();
}

$stmt->close();
$conn->close();
?>