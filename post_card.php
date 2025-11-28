<?php
/**
 * Este archivo genera el HTML para una tarjeta de publicación.
 *
 * Espera que una variable asociativa llamada $post esté disponible
 * con todos los datos necesarios de la publicación.
 *
 * Variables esperadas en $post:
 * - id_publicacion
 * - titulo
 * - contenido
 * - nombre_completo (del autor)
 * - foto_perfil (del autor)
 * - fecha_publicacion_relativa
 * - total_likes
 * - total_comentarios
 * - es_admin (booleano para mostrar botones de admin)
 */
?>
<div class="publicacion" id="post-<?= htmlspecialchars($post['id_publicacion']) ?>">
    <div class="publicacion-header">
        <img src="uploads/perfiles/<?= htmlspecialchars($post['foto_perfil']) ?>" alt="Foto de perfil" class="autor-foto">
        <div class="autor-info">
            <span class="autor-nombre"><?= htmlspecialchars($post['nombre_completo']) ?></span>
            <span class="fecha-publicacion"><?= htmlspecialchars($post['fecha_publicacion_relativa']) ?></span>
        </div>
    </div>
    <div class="publicacion-body">
        <h2><?= htmlspecialchars($post['titulo']) ?></h2>
        <p><?= nl2br(htmlspecialchars($post['contenido'])) ?></p>
    </div>
    <div class="publicacion-footer">
        <span class="likes"><span class="material-symbols-outlined">thumb_up</span> <?= $post['total_likes'] ?></span>
        <span class="comentarios"><span class="material-symbols-outlined">comment</span> <?= $post['total_comentarios'] ?></span>
        <?php if ($es_admin): ?>
            <button class="btn-eliminar" data-id="<?= $post['id_publicacion'] ?>"><span class="material-symbols-outlined">delete</span> Eliminar</button>
        <?php endif; ?>
    </div>
</div>