<?php
session_start();
include('conexion.php');

// 1. Inicializar array de vistas si no existe
if (!isset($_SESSION['viewed_posts'])) {
    $_SESSION['viewed_posts'] = [];
}

// 2. Obtener datos del usuario actual
// Si no hay sesión, esto será 0
$id_usuario_sesion = $_SESSION['id_usuario'] ?? 0; 
$es_admin = false;

if ($id_usuario_sesion > 0) {
    $sql_user = "SELECT rol FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql_user);
    $stmt->bind_param("i", $id_usuario_sesion);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $es_admin = ($row['rol'] === 'admin');
    }
    $stmt->close();
}

// 3. Recibir Filtros (AJAX)
$busqueda = $_POST['busqueda'] ?? '';
$orden = $_POST['orden'] ?? 'cronologico';

// 4. Construir la Consulta SQL Dinámica
$sql_base = "SELECT p.id_publicacion, p.titulo, p.id_categoria, c.nombre AS categoria, p.contenido,
               p.media_path, p.tipo_contenido, p.fecha_publicacion, p.fecha_aprobacion, p.estado_publicacion,
               u.nombre_completo AS autor, u.foto_perfil, 
               CONCAT(m.sede, ' ', m.anio) AS nombre_mundial, m.sede, m.anio,
               COALESCE(r.total_likes, 0) AS total_likes,
               COALESCE(r.total_dislikes, 0) AS total_dislikes,
               COALESCE(v.total_vistas, 0) AS total_vistas,
               COALESCE(comm.total_comentarios, 0) AS total_comentarios,
               r_user.tipo AS reaccion_usuario
        FROM publicaciones p
        INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN mundiales m ON p.id_mundial = m.id_mundial
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
        LEFT JOIN (
            SELECT id_publicacion, COUNT(*) AS total_comentarios
            FROM comentarios GROUP BY id_publicacion
        ) comm ON p.id_publicacion = comm.id_publicacion
        LEFT JOIN reacciones r_user ON p.id_publicacion = r_user.id_publicacion AND r_user.id_usuario = $id_usuario_sesion
        WHERE 1=1 "; 

if (!$es_admin) {
    $sql_base .= " AND p.estado_publicacion = 'aprobada' ";
}

$params = [];
$types = "";

if (!empty($busqueda)) {
    $termino = "%" . $busqueda . "%";
    $sql_base .= " AND (p.titulo LIKE ? OR c.nombre LIKE ? OR m.anio LIKE ? OR m.sede LIKE ? OR u.nombre_completo LIKE ?) ";
    array_push($params, $termino, $termino, $termino, $termino, $termino);
    $types .= "sssss";
}

switch ($orden) {
    case 'mundial_cronologico':
        $sql_base .= " ORDER BY m.anio ASC, p.fecha_publicacion DESC";
        break;
    case 'sede':
        $sql_base .= " ORDER BY m.sede ASC, p.fecha_publicacion DESC";
        break;
    case 'mas_likes':
        $sql_base .= " ORDER BY total_likes DESC, p.fecha_publicacion DESC";
        break;
    case 'mas_comentarios':
        $sql_base .= " ORDER BY total_comentarios DESC, p.fecha_publicacion DESC";
        break;
    case 'cronologico':
    default:
        $sql_base .= " ORDER BY p.fecha_publicacion DESC";
        break;
}

$stmt = $conn->prepare($sql_base);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// 5. Generar HTML
if ($resultado && $resultado->num_rows > 0):
    while ($post = $resultado->fetch_assoc()):
        
        // =====================================================
        // CORRECCIÓN: Registrar vista manejando usuario NULL
        // =====================================================
        $id_post_actual = $post['id_publicacion'];
        
        if (!in_array($id_post_actual, $_SESSION['viewed_posts'])) {
            
            // Si es 0 (no logueado), ponemos NULL para que MySQL no se queje
            $id_usuario_para_bd = ($id_usuario_sesion > 0) ? $id_usuario_sesion : null;

            $sql_vista = "INSERT INTO vistas (id_publicacion, id_usuario, fecha_vista) VALUES (?, ?, NOW())";
            $stmt_vista = $conn->prepare($sql_vista);
            
            // 'i' para id_publicacion, 'i' para id_usuario (acepta null)
            $stmt_vista->bind_param("ii", $id_post_actual, $id_usuario_para_bd);
            
            $stmt_vista->execute();
            $stmt_vista->close();
            
            $_SESSION['viewed_posts'][] = $id_post_actual;
            $post['total_vistas']++; 
        }
        // =====================================================
        
        $reaccion_actual_usuario = $post['reaccion_usuario'];
?>
    <section style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        
        <div class="autor-info" style="display: flex; align-items: center; margin-bottom: 15px;">
            <img src="uploads/<?= htmlspecialchars($post['foto_perfil'] ?? 'default.jpg') ?>" alt="Foto" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px; object-fit: cover;">
            <h3 style="margin: 0; color: var(--blanco-suave);"><?= htmlspecialchars($post["autor"]) ?></h3>
        </div>

        <h2 style="display:flex; align-items:center;">
            <span class="material-symbols-outlined" style="margin-right:10px; color:var(--verde-menta);">sports_soccer</span> 
            <?= htmlspecialchars($post["titulo"]) ?>
        </h2>
        
        <p style="color: var(--gris); font-size: 0.9em;">
            <strong>Mundial:</strong> <?= htmlspecialchars($post["nombre_mundial"] ?? 'No especificado') ?> | 
            <strong>Categoría:</strong> <?= htmlspecialchars($post["categoria"] ?? 'General') ?>
            <?php if ($es_admin): ?> | <strong>Estado:</strong> <?= htmlspecialchars($post["estado_publicacion"]) ?> <?php endif; ?>
        </p>

        <p><?= nl2br(htmlspecialchars($post["contenido"])) ?></p>

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

        <div class="estadisticas" style="color: var(--gris); font-size: 0.9em; margin-top: 15px; padding-top: 10px; border-top: 1px solid var(--verde-suave); display: flex; justify-content: space-around; align-items: center;">
            <?php if ($id_usuario_sesion > 0): ?>
                <form action="reaccionar.php" method="POST" class="form-reaccion">
                    <input type="hidden" name="id_publicacion" value="<?= $id_post_actual ?>">
                    <button type="submit" name="tipo_reaccion" value="like" class="btn-reaccion <?= ($reaccion_actual_usuario === 'like') ? 'reaccion-activa' : '' ?>">
                        <span class="material-symbols-outlined">thumb_up</span> <?= $post["total_likes"] ?>
                    </button>
                </form>
                <form action="reaccionar.php" method="POST" class="form-reaccion">
                    <input type="hidden" name="id_publicacion" value="<?= $id_post_actual ?>">
                    <button type="submit" name="tipo_reaccion" value="dislike" class="btn-reaccion <?= ($reaccion_actual_usuario === 'dislike') ? 'reaccion-activa' : '' ?>">
                        <span class="material-symbols-outlined">thumb_down</span> <?= $post["total_dislikes"] ?>
                    </button>
                </form>
            <?php else: ?>
                <span><span class="material-symbols-outlined">thumb_up</span> <?= $post["total_likes"] ?> Likes</span> 
                <span><span class="material-symbols-outlined">thumb_down</span> <?= $post["total_dislikes"] ?> Dislikes</span> 
            <?php endif; ?>
            <span><span class="material-symbols-outlined">visibility</span> <?= $post["total_vistas"] ?></span>
            <span><span class="material-symbols-outlined">chat</span> <?= $post["total_comentarios"] ?></span>
        </div>
        
        <p style="color: var(--gris); font-size: 0.8em; text-align: right; margin-top: 10px;">
            Publicado: <?= htmlspecialchars($post["fecha_publicacion"]) ?>
        </p>

        <hr style="opacity: 0.3;">
        <h4 style="display:flex; align-items:center;"><span class="material-symbols-outlined">forum</span> Comentarios:</h4>
        <div class="lista-comentarios" style="margin-bottom: 15px; max-height: 200px; overflow-y: auto; padding: 5px;">
            <?php
            // (Tu SQL de comentarios sigue igual)
            $sql_com = "SELECT c.id_comentario, c.contenido, c.fecha_comentario, u.nombre_completo, u.foto_perfil 
                        FROM comentarios c JOIN usuarios u ON c.id_usuario = u.id_usuario
                        WHERE c.id_publicacion = ? ORDER BY c.fecha_comentario ASC";
            $stmt_com = $conn->prepare($sql_com);
            $stmt_com->bind_param("i", $id_post_actual);
            $stmt_com->execute();
            $coms = $stmt_com->get_result();
            if ($coms->num_rows > 0):
                while ($c = $coms->fetch_assoc()):
            ?>
                <div class="comentario" style="display: flex; align-items: flex-start; border-bottom: 1px solid var(--verde-suave); padding-bottom: 5px; margin-bottom: 5px;">
                    <img src="uploads/<?= htmlspecialchars($c['foto_perfil'] ?? 'default.jpg') ?>" style="width: 35px; height: 35px; border-radius: 50%; margin-right: 10px; object-fit: cover;">
                    <div style="flex-grow: 1;">
                        <strong><?= htmlspecialchars($c['nombre_completo']) ?>:</strong>
                        <p style="margin: 0; color: var(--blanco-suave);"><?= nl2br(htmlspecialchars($c['contenido'])) ?></p>
                        <small style="color: var(--gris);"><?= $c['fecha_comentario'] ?></small>
                    </div>
                    <?php if ($es_admin): ?>
                        <form action="eliminarcomentario.php" method="POST" onsubmit="return confirm('¿Borrar?');">
                            <input type="hidden" name="id_comentario" value="<?= $c['id_comentario'] ?>">
                            <button type="submit" class="btn-borrar-comentario" title="Eliminar">
                                <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; else: echo "<p style='color:var(--gris);'>Sin comentarios.</p>"; endif; $stmt_com->close(); ?>
        </div>

        <?php if ($id_usuario_sesion > 0): ?>
            <form action="guardarcomentario.php" method="POST" class="form-comentario">
                <input type="hidden" name="id_publicacion" value="<?= $post['id_publicacion'] ?>">
                <textarea name="contenido" placeholder="Escribe un comentario..." required style="width: 100%; min-height: 40px; background: transparent; color: var(--blanco-suave); border: 1px solid var(--verde-menta); border-radius: 5px;"></textarea>
                <button type="submit" style="margin-top: 5px;"><span class="material-symbols-outlined">send</span> Comentar</button>
            </form>
        <?php else: ?>
            <p><a href="login.php" style="color: var(--verde-menta);">Inicia sesión</a> para comentar.</p>
        <?php endif; ?>

        <?php if ($es_admin && $post['estado_publicacion']=='pendiente'): ?>
            <form action="moderar.php" method="POST" style="margin-top:10px; border-top: 1px solid var(--verde-suave); padding-top: 10px;">
                <input type="hidden" name="id_publicacion" value="<?= $post['id_publicacion'] ?>">
                <button type="submit" name="accion" value="aprobar"><span class="material-symbols-outlined">check</span> Aprobar</button>
                <button type="submit" name="accion" value="rechazar"><span class="material-symbols-outlined">close</span> Rechazar</button>
            </form>
        <?php endif; ?>
    </section>
<?php
    endwhile;
else:
    echo "<p style='text-align:center; margin-top:20px;'>No se encontraron publicaciones con esos filtros.</p>";
endif;
$conn->close();
?>