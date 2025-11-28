<?php
session_start();
include('middleware.php'); 
include('conexion.php'); 

// Mensajes
$mensaje = '';
$tipo_mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $es_error = strpos($mensaje, '❌') !== false || strpos(strtolower($mensaje), 'error') !== false || strpos($mensaje, '🚫') !== false;
    $tipo_mensaje = $es_error ? 'error' : 'exito';
    unset($_SESSION['mensaje']);
}

// Datos de sesión y Verificación de Rol
$nombre_usuario = $_SESSION['nombre'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? null;
$es_admin = false;

if ($id_usuario) {
    $sql_user = "SELECT rol FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql_user);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $es_admin = ($row['rol'] === 'admin');
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Universo Futbolero</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>
<div class="container">
    <h1>Universo Futbolero</h1>

    <?php if (!empty($mensaje)): ?>
        <div class="alerta <?= $tipo_mensaje ?>">
            <span class="material-symbols-outlined">
                <?= ($tipo_mensaje == 'exito') ? 'check_circle' : 'error' ?>
            </span>
            <?= htmlspecialchars(str_replace(['✔','❌','🚫'], '', $mensaje)) ?>
        </div>
    <?php endif; ?>

    <?php if (!$id_usuario): ?>
        <a href="login.php"><button><span class="material-symbols-outlined">login</span> Iniciar sesión</button></a>
        <a href="registro.php"><button><span class="material-symbols-outlined">person_add</span> Registrarse</button></a>
        <a href="mundiales.php"><button><span class="material-symbols-outlined">public</span> Ver por Mundial</button></a>

    <?php else: ?>
        <a href="perfil.php"><button><span class="material-symbols-outlined">person</span> Mi Perfil</button></a>
        <a href="post.php"><button><span class="material-symbols-outlined">add_circle</span> Crear publicación</button></a>
        <a href="mundiales.php"><button><span class="material-symbols-outlined">public</span> Ver por Mundial</button></a>
        <a href="logout.php"><button><span class="material-symbols-outlined">logout</span> Cerrar sesión</button></a>
        <p style="display: flex; align-items: center; gap: 5px;">
            Bienvenido, <strong><?= htmlspecialchars($nombre_usuario) ?></strong>
        </p>
    <?php endif; ?>

    <hr>

    <div class="filtros-container">
        <div>
            <label for="busqueda-ajax"><span class="material-symbols-outlined">search</span> Buscar:</label>
            <input type="text" id="busqueda-ajax" placeholder="Título, País, Año, Usuario...">
        </div>
        
        <div>
            <label for="orden-ajax"><span class="material-symbols-outlined">sort</span> Ordenar por:</label>
            <select id="orden-ajax">
                <option value="cronologico">Más recientes (Default)</option>
                <option value="mundial_cronologico">Cronología de Mundiales</option>
                <option value="sede">Sede (A-Z)</option>
                <option value="mas_likes">Más Likes</option>
                <option value="mas_comentarios">Más Comentarios</option>
            </select>
        </div>
    </div>

    <?php 
    // Definimos la clase CSS según el rol
    $clase_layout = $es_admin ? 'layout-admin' : 'layout-usuario';
    ?>

    <div class="contenido-principal <?= $clase_layout ?>">
        
        <div id="contenedor-publicaciones">
            <p style="text-align:center; color: var(--verde-pastel);">
                <span class="material-symbols-outlined spin">sync</span> Cargando publicaciones...
            </p>
        </div>

        <?php if (!$es_admin): ?>
        <aside class="sidebar-api">
            <div id="contenedor-noticias">
                <p style="text-align:center; color:var(--gris);">
                    <span class="material-symbols-outlined spin">rss_feed</span> Cargando noticias en vivo...
                </p>
            </div>
        </aside>
        <?php endif; ?>

    </div>

</div> <script>
document.addEventListener('DOMContentLoaded', () => {
    
    // --- PARTE 1: PUBLICACIONES ---
    const inputBusqueda = document.getElementById('busqueda-ajax');
    const selectOrden = document.getElementById('orden-ajax');
    const contenedorPubs = document.getElementById('contenedor-publicaciones');

    function cargarPublicaciones() {
        const busqueda = inputBusqueda.value;
        const orden = selectOrden.value;

        const formData = new FormData();
        formData.append('busqueda', busqueda);
        formData.append('orden', orden);

        fetch('obtener_publicaciones.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            contenedorPubs.innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            contenedorPubs.innerHTML = "<p>Error al cargar publicaciones.</p>";
        });
    }

    // --- PARTE 2: NOTICIAS (API) ---
    function cargarNoticias() {
        const contenedorNoticias = document.getElementById('contenedor-noticias');
        
        // Solo intentamos cargar si el contenedor existe (es decir, si NO es admin)
        if (contenedorNoticias) {
            fetch('obtener_noticias.php')
            .then(response => response.text())
            .then(data => {
                contenedorNoticias.innerHTML = data;
            })
            .catch(error => {
                console.error('Error API:', error);
                contenedorNoticias.innerHTML = "<p>Error API.</p>";
            });
        }
    }

    // Eventos Iniciales
    cargarPublicaciones();
    cargarNoticias();

    // Eventos de Filtros
    let timeout = null;
    inputBusqueda.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(cargarPublicaciones, 300); 
    });

    selectOrden.addEventListener('change', cargarPublicaciones);
});
</script>

<script src="notificaciones.js"></script>
<script src="formulario.js"></script>
</body>
</html>