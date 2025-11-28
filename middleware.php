<?php
// --- CONFIGURACIÓN DEL MIDDLEWARE ---
$MODO_MANTENIMIENTO = false; // Cambia a true para bloquear el sitio
$IPS_PERMITIDAS = ['::1', '127.0.0.1']; // Tu IP local para que tú sí puedas entrar
// $IPS_PERMITIDAS = []; // <--- PON ESTO PARA PROBAR
// 1. Lógica de Mantenimiento
function verificarMantenimiento($estado, $ips_permitidas) {
    // Si está en mantenimiento Y la IP del usuario no está en la lista blanca
    if ($estado && !in_array($_SERVER['REMOTE_ADDR'], $ips_permitidas)) {
        // Si hay sesión de admin activa, lo dejamos pasar (Opcional)
        if (isset($_SESSION['id_usuario'])) {
            // Aquí necesitaríamos conectar a BD para verificar rol, 
            // pero por seguridad, el mantenimiento suele ser corte total.
            // Para este ejemplo, cortamos total.
        }
        
        // HTML de Mantenimiento
        die('
        <div style="text-align:center; padding:50px; font-family:sans-serif; background:#0d2818; color:white; height:100vh;">
            <h1 style="color:#71f79f; font-size:50px;">⚠️ En Mantenimiento</h1>
            <p>Universo Futbolero está recibiendo mejoras.</p>
            <p>Volveremos en unos minutos.</p>
        </div>
        ');
    }
}

// 2. Lógica de Firewall (Anti-XSS Básico)
function limpiarEntrada($datos) {
    if (is_array($datos)) {
        foreach ($datos as $key => $value) {
            $datos[$key] = limpiarEntrada($value);
        }
    } else {
        // Elimina etiquetas HTML y caracteres peligrosos
        $datos = htmlspecialchars($datos, ENT_QUOTES, 'UTF-8');
    }
    return $datos;
}

// --- EJECUCIÓN DEL MIDDLEWARE ---
verificarMantenimiento($MODO_MANTENIMIENTO, $IPS_PERMITIDAS);

// Aplicar limpieza automática a todos los datos entrantes
if (!empty($_POST)) {
    $_POST = limpiarEntrada($_POST);
}
if (!empty($_GET)) {
    $_GET = limpiarEntrada($_GET);
}
?>