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

// Datos de sesión
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

// Recibir ID de mundial seleccionado (GET)
$id_mundial_seleccionado = isset($_GET['id']) ? intval($_GET['id']) : 0;
$info_mundial = null;

// Obtener lista de mundiales para el Select
$lista_mundiales = [];
$sql_lista = "SELECT id_mundial, anio, sede FROM mundiales ORDER BY anio ASC";
$res_lista = $conn->query($sql_lista);
if ($res_lista) {
    while($row = $res_lista->fetch_assoc()) {
        $lista_mundiales[] = $row;
    }
}

// Si hay un mundial seleccionado, obtener su info completa
if ($id_mundial_seleccionado > 0) {
    $sql_info = "SELECT * FROM mundiales WHERE id_mundial = ?";
    $stmt_info = $conn->prepare($sql_info);
    $stmt_info->bind_param("i", $id_mundial_seleccionado);
    $stmt_info->execute();
    $res_info = $stmt_info->get_result();
    if ($res_info->num_rows > 0) {
        $info_mundial = $res_info->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mundiales - Universo Futbolero</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>
<div class="container">
    <h1><span class="material-symbols-outlined" style="font-size:35px;">public</span> Zona de Mundiales</h1>

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
        <a href="index.php"><button><span class="material-symbols-outlined">home</span> Inicio</button></a>
    <?php else: ?>
        <a href="perfil.php"><button><span class="material-symbols-outlined">person</span> Mi Perfil</button></a>
        <a href="post.php"><button><span class="material-symbols-outlined">add_circle</span> Crear publicación</button></a>
        <a href="index.php"><button><span class="material-symbols-outlined">home</span> Inicio</button></a>
        <a href="logout.php"><button><span class="material-symbols-outlined">logout</span> Cerrar sesión</button></a>
    <?php endif; ?>

    <hr>

    <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 15px; margin-bottom: 20px; border: 1px solid var(--verde-menta);">
        <label for="select-mundial" style="color: var(--verde-pastel); font-weight: bold; font-size: 1.1em;">
            <span class="material-symbols-outlined">flag</span> Selecciona un Mundial:
        </label>
        <select id="select-mundial" onchange="cambiarMundial(this.value)" style="width: 100%; max-width: 400px; padding: 10px; margin-top: 10px; font-size: 16px;">
            <option value="">-- Elige una edición --</option>
            <?php foreach ($lista_mundiales as $m): ?>
                <option value="<?= $m['id_mundial'] ?>" <?= ($id_mundial_seleccionado == $m['id_mundial']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['anio'] . " - " . $m['sede']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($info_mundial): ?>
        <section style="text-align: center; border: 2px solid var(--verde-menta); background: rgba(0,0,0,0.3);">
            <?php if (!empty($info_mundial['logo'])): ?>
                <img src="<?= htmlspecialchars($info_mundial['logo']) ?>" alt="Logo Mundial" style="max-width: 200px; width: 100%; margin: 0 auto 15px; display: block; filter: drop-shadow(0 0 10px rgba(255,255,255,0.3));">
            <?php endif; ?>
            
            <h2 style="font-size: 2.5em; margin: 0; text-transform: uppercase; letter-spacing: 2px;">
                <?= htmlspecialchars($info_mundial['sede'] . " " . $info_mundial['anio']) ?>
            </h2>
            
            <?php if (!empty($info_mundial['campeon'])): ?>
                <h3 style="color: #FFD700; text-shadow: 1px 1px 2px black;">
                    <span class="material-symbols-outlined">emoji_events</span> Campeón: <?= htmlspecialchars($info_mundial['campeon']) ?>
                </h3>
            <?php endif; ?>

            <?php if (!empty($info_mundial['descripcion'])): ?>
                <p style="font-style: italic; color: var(--blanco-suave); margin-top: 10px;">
                    <?= nl2br(htmlspecialchars($info_mundial['descripcion'])) ?>
                </p>
            <?php endif; ?>
        </section>

        <div class="filtros-container" style="justify-content: center;">
            <div style="width: 100%; max-width: 600px;">
                <label for="busqueda-mundial"><span class="material-symbols-outlined">search</span> Buscar en este mundial:</label>
                <input type="text" id="busqueda-mundial" placeholder="Buscar goles, jugadores, partidos..." style="width: 100%;">
            </div>
        </div>

        <div id="contenedor-publicaciones-mundial">
            <p style="text-align:center; color: var(--verde-pastel);">
                <span class="material-symbols-outlined spin">sync</span> Cargando publicaciones de este mundial...
            </p>
        </div>

    <?php elseif ($id_mundial_seleccionado > 0): ?>
        <p style="text-align: center; color: var(--gris);">No se encontró información del mundial seleccionado.</p>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: var(--gris);">
            <span class="material-symbols-outlined" style="font-size: 60px; opacity: 0.5;">travel_explore</span>
            <p>Selecciona un mundial arriba para ver su historia y publicaciones.</p>
        </div>
    <?php endif; ?>

</div>

<script>
    // Función para recargar la página al seleccionar un mundial
    function cambiarMundial(id) {
        if (id) {
            window.location.href = "mundiales.php?id=" + id;
        }
    }

    // Cargar publicaciones si hay un mundial seleccionado
    document.addEventListener('DOMContentLoaded', () => {
        const idMundial = "<?= $id_mundial_seleccionado ?>";
        const contenedor = document.getElementById('contenedor-publicaciones-mundial');
        const inputBusqueda = document.getElementById('busqueda-mundial');

        if (idMundial > 0 && contenedor) {
            
            function cargarPublicacionesMundial() {
                const busqueda = inputBusqueda ? inputBusqueda.value : '';
                
                const formData = new FormData();
                formData.append('id_mundial', idMundial); // Filtro clave
                formData.append('busqueda', busqueda);
                formData.append('orden', 'cronologico'); // Orden por defecto

                fetch('obtener_publicaciones.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    contenedor.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    contenedor.innerHTML = "<p>Error al cargar publicaciones.</p>";
                });
            }

            // Carga inicial
            cargarPublicacionesMundial();

            // Evento de búsqueda en tiempo real
            let timeout = null;
            if (inputBusqueda) {
                inputBusqueda.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(cargarPublicacionesMundial, 300); 
                });
            }
        }
    });
</script>

<script src="notificaciones.js"></script>
</body>
</html>