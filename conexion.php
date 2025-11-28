<?php
$conn = new mysqli('localhost', 'root', '', 'bd_unifut', 3306);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
// El "echo" que estaba aquí fue eliminado.
?>