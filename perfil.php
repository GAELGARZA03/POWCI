<?php
session_start();

// --- CÓDIGO NUEVO PARA MENSAJES ---
$mensaje = '';
$tipo_mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $es_error = strpos($mensaje, '❌') !== false || strpos(strtolower($mensaje), 'error') !== false || strpos($mensaje, '🚫') !== false;
    $tipo_mensaje = $es_error ? 'error' : 'exito';
    unset($_SESSION['mensaje']);
}
// --- FIN CÓDIGO NUEVO ---

// Incluir la conexión a la base de datos
include('conexion.php');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Consultar datos del usuario desde la base de datos
$sql = "SELECT nombre_completo, fecha_nacimiento, foto_perfil, genero, pais_nacimiento, nacionalidad, email, contrasena
        FROM usuarios
        WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
} else {
    session_destroy();
    $_SESSION['mensaje'] = "❌ Error: Usuario no encontrado.";
    header("Location: login.php");
    exit();
}

// --- LISTA DE PAÍSES (Igual que en Registro) ---
$paises = [
    "Argentina", "Bolivia", "Chile", "Colombia", "Costa Rica", "Cuba", 
    "Ecuador", "El Salvador", "España", "Estados Unidos", "Guatemala", 
    "Honduras", "México", "Nicaragua", "Panamá", "Paraguay", "Perú", 
    "Puerto Rico", "República Dominicana", "Uruguay", "Venezuela"
];
sort($paises);

// ===================================
// ===== CONSULTA SQL (PUBLICACIONES) =====
// ===================================
$sql_publicaciones = "SELECT p.id_publicacion, p.titulo, p.contenido, p.tipo_contenido, p.fecha_publicacion, p.estado_publicacion,
                             COALESCE(r.total_likes, 0) AS total_likes,
                             COALESCE(r.total_dislikes, 0) AS total_dislikes,
                             COALESCE(v.total_vistas, 0) AS total_vistas
                      FROM publicaciones p
                      LEFT JOIN (
                          SELECT id_publicacion, 
                                 SUM(CASE WHEN tipo = 'like' THEN 1 ELSE 0 END) AS total_likes,
                                 SUM(CASE WHEN tipo = 'dislike' THEN 1 ELSE 0 END) AS total_dislikes
                          FROM reacciones GROUP BY id_publicacion
                      ) r ON p.id_publicacion = r.id_publicacion
                      LEFT JOIN (
                          SELECT id_publicacion, COUNT(*) AS total_vistas 
                          FROM vistas GROUP BY id_publicacion
                      ) v ON p.id_publicacion = v.id_publicacion
                      WHERE p.id_usuario = ?
                      ORDER BY p.fecha_publicacion DESC";

$stmt_pub = $conn->prepare($sql_publicaciones);
$stmt_pub->bind_param("i", $id_usuario);
$stmt_pub->execute();
$publicaciones = $stmt_pub->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Universo Futbolero</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>

    <h1>
        <span class="material-symbols-outlined" style="font-size: 35px; vertical-align: middle;">account_circle</span> 
        Perfil de Usuario
    </h1>

    <?php if (!empty($mensaje)): ?>
        <div class="alerta <?= $tipo_mensaje ?>">
            <span class="material-symbols-outlined">
                <?= ($tipo_mensaje == 'exito') ? 'check_circle' : 'error' ?>
            </span>
            <?= htmlspecialchars(str_replace(['✔','❌','🚫'], '', $mensaje)) ?>
        </div>
    <?php endif; ?>

    <section>
        <h2><span class="material-symbols-outlined">badge</span> Datos personales</h2>
        <form action="modificardatos.php" method="post" enctype="multipart/form-data">
            <label for="nombre">Nombre completo:</label><br>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required><br><br>

            <label for="fecha">Fecha de nacimiento:</label><br>
            <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($usuario['fecha_nacimiento']) ?>" required><br><br>

            <label for="foto">Foto de perfil:</label><br>
            <?php if (!empty($usuario['foto_perfil']) && $usuario['foto_perfil'] != 'default.jpg'): ?>
                <img src="uploads/<?= htmlspecialchars($usuario['foto_perfil']) ?>" width="100" style="border-radius: 10px; margin-bottom: 10px;"><br>
            <?php elseif (!empty($usuario['foto_perfil'])): ?>
                <img src="uploads/<?= htmlspecialchars($usuario['foto_perfil']) ?>" width="100" style="border-radius: 10px; margin-bottom: 10px;"><br>
            <?php endif; ?>
            <input type="file" id="foto" name="foto" accept="image/*"><br><br>

            <label for="genero">Género:</label><br>
            <select id="genero" name="genero" required>
                <option value="Masculino" <?= ($usuario['genero'] == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                <option value="Femenino" <?= ($usuario['genero'] == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                <option value="Prefiero no decir" <?= ($usuario['genero'] == 'Prefiero no decir') ? 'selected' : '' ?>>Prefiero no decir</option>
            </select><br><br>

            <label for="pais_nacimiento">País de nacimiento:</label><br>
            <select name="pais" id="pais_nacimiento" required>
                <option value="">-- Selecciona un país --</option>
                <?php foreach ($paises as $pais): ?>
                    <option value="<?= $pais ?>" <?= ($usuario['pais_nacimiento'] == $pais) ? 'selected' : '' ?>>
                        <?= $pais ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="nacionalidad">Nacionalidad:</label><br>
            <input type="text" id="nacionalidad" name="nacionalidad" 
                   value="<?= htmlspecialchars($usuario['nacionalidad']) ?>" 
                   readonly required
                   style="background-color: rgba(255,255,255,0.1); color: #ccc; cursor: not-allowed;"
            ><br><br>

            <label for="correo">Correo electrónico:</label><br>
            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($usuario['email']) ?>" required><br><br>

            <label for="password">Contraseña (dejar en blanco para no cambiar):</label><br>
            <input type="password" id="password" name="password" placeholder="********"><br><br>

            <button type="submit">
                <span class="material-symbols-outlined">save</span> Guardar cambios
            </button>
        </form>
    </section>

    <hr>

    <section>
        <h2><span class="material-symbols-outlined">history_edu</span> Mis publicaciones</h2>

        <?php if ($publicaciones->num_rows > 0): ?>
            <?php while ($post = $publicaciones->fetch_assoc()): ?>
                <div>
                    <h3>
                        <?= htmlspecialchars($post['titulo']) ?> 
                        <span style="font-size: 0.7em; color: var(--gris); font-weight: normal;">
                            (<?= htmlspecialchars($post['estado_publicacion']) ?>)
                        </span>
                    </h3>
                    <p><?= nl2br(htmlspecialchars($post['contenido'])) ?></p>
                    <?php if (!empty($post['media_path'])): 
            $media = htmlspecialchars($post['media_path']);
            
            if ($post['tipo_contenido'] === 'imagen'): ?>
                <img src='<?= $media ?>' width='100%' style='max-width: 500px; display: block; margin: 15px auto; border-radius: 10px;'>
            
            <?php elseif ($post['tipo_contenido'] === 'video'): ?>
                <video src='<?= $media ?>' width='100%' style='max-width: 500px; display: block; margin: 15px auto; border-radius: 10px;' controls></video>
            
            <?php elseif ($post['tipo_contenido'] === 'video_link'): 
                // Convertir URL normal de YouTube a Embed
                $video_url = $media;
                if (strpos($video_url, 'watch?v=') !== false) {
                    $video_url = str_replace("watch?v=", "embed/", $video_url);
                    // Limpiar parámetros extra (como &t=...)
                    $parts = explode('&', $video_url);
                    $video_url = $parts[0];
                } elseif (strpos($video_url, 'youtu.be/') !== false) {
                    $video_url = str_replace("youtu.be/", "www.youtube.com/embed/", $video_url);
                }
            ?>
                <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; margin: 15px auto; border-radius: 10px;">
                    <iframe src="<?= $video_url ?>" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen>
                    </iframe>
                </div>
            <?php endif; 
        endif; ?>
                    <div style="color: var(--gris); font-weight: bold; display: flex; gap: 15px; align-items: center; margin-top: 10px;">
                        <span style="display: flex; align-items: center;">
                            <span class="material-symbols-outlined" style="font-size: 18px; margin-right: 5px;">thumb_up</span> <?= $post['total_likes'] ?>
                        </span>
                        <span style="display: flex; align-items: center;">
                            <span class="material-symbols-outlined" style="font-size: 18px; margin-right: 5px;">thumb_down</span> <?= $post['total_dislikes'] ?>
                        </span>
                        <span style="display: flex; align-items: center;">
                            <span class="material-symbols-outlined" style="font-size: 18px; margin-right: 5px;">visibility</span> <?= $post['total_vistas'] ?>
                        </span>
                    </div>

                    <h4 style="color: var(--verde-menta); display: flex; align-items: center; margin-top: 15px;">
                        <span class="material-symbols-outlined">chat</span> Comentarios:
                    </h4>
                    <ul>
                        <?php
                        $sql_com = "SELECT c.contenido, u.nombre_completo 
                                    FROM comentarios c
                                    INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
                                    WHERE c.id_publicacion = ?";
                        $stmt_com = $conn->prepare($sql_com);
                        $stmt_com->bind_param("i", $post['id_publicacion']);
                        $stmt_com->execute();
                        $comentarios = $stmt_com->get_result();

                        if ($comentarios->num_rows > 0):
                            while ($com = $comentarios->fetch_assoc()):
                        ?>
                                <li><strong><?= htmlspecialchars($com['nombre_completo']) ?>:</strong> <?= htmlspecialchars($com['contenido']) ?></li>
                        <?php
                            endwhile;
                        else:
                            echo "<li style='color: var(--gris); list-style: none;'>Sin comentarios aún.</li>";
                        endif;
                        $stmt_com->close(); 
                        ?>
                    </ul>
                </div>
                <hr style="opacity: 0.3;">
            <?php endwhile; ?>
        <?php else: ?>
            <p>No tienes publicaciones aún.</p>
        <?php endif; ?>
    </section>

    <form action="logout.php" method="post">
        <button type="submit" style="background-color: #d32f2f; color: white;">
            <span class="material-symbols-outlined">logout</span> Cerrar sesión
        </button>
    </form>

    <br>
    <a href="index.php">
        <button><span class="material-symbols-outlined">arrow_back</span> Volver al inicio</button>
    </a>

    <script src="notificaciones.js"></script>
    <script src="formulario.js"></script>
</body>
</html>