<?php
session_start();
include('conexion.php');

// --- MENSAJES ---
$mensaje = '';
$tipo_mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $es_error = strpos($mensaje, '❌') !== false || strpos(strtolower($mensaje), 'error') !== false;
    $tipo_mensaje = $es_error ? 'error' : 'exito';
    unset($_SESSION['mensaje']);
}

// --- DATOS RE-POBLADOS (RECUPERAR SI HUBO ERROR) ---
$form_data = [];
if (isset($_SESSION['form_data_post'])) {
    $form_data = $_SESSION['form_data_post'];
    unset($_SESSION['form_data_post']); // Limpiar después de usar
}
// --------------------------------------------------

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener rol
$sql_user = "SELECT rol FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result_user = $stmt->get_result();

if ($result_user->num_rows === 0) {
    session_destroy();
    $_SESSION['mensaje'] = "❌ Error: Usuario no encontrado.";
    header("Location: login.php");
    exit();
}

$user_data = $result_user->fetch_assoc();
$es_admin = ($user_data['rol'] === "admin");

// ===============================
// 1. Obtener mundiales
// ===============================
$mundiales = [];
$sql_mundiales = "SELECT id_mundial, anio, sede FROM mundiales ORDER BY anio ASC";
$result_mundiales = $conn->query($sql_mundiales);
if ($result_mundiales && $result_mundiales->num_rows > 0) {
    while ($fila = $result_mundiales->fetch_assoc()) {
        $mundiales[] = $fila;
    }
}

// ===============================
// 2. Obtener categorías
// ===============================
$categorias = [];
$sql_categorias = "SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC";
$result_categorias = $conn->query($sql_categorias);
if ($result_categorias && $result_categorias->num_rows > 0) {
    while ($fila = $result_categorias->fetch_assoc()) {
        $categorias[] = $fila;
    }
}

// ===============================
// 3. Validación para usuario normal
// ===============================
$puede_publicar = true;
$mensaje_bloqueo = "";
if (!$es_admin && (empty($mundiales) || empty($categorias))) {
    $puede_publicar = false;
    $mensaje_bloqueo = "🚫 No puedes crear publicaciones aún. El administrador debe crear al menos un mundial y una categoría.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universo Futbolero - Crear publicación</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>
<body>

<h1>Universo Futbolero</h1>

<main>

    <?php if (!empty($mensaje)): ?>
        <div class="alerta <?= $tipo_mensaje ?>">
            <span class="material-symbols-outlined"><?= ($tipo_mensaje == 'exito') ? 'check_circle' : 'error' ?></span>
            <?= htmlspecialchars(str_replace(['❌','✔'], '', $mensaje)) ?>
        </div>
    <?php endif; ?>

<?php if ($es_admin): ?>
    <h2><span class="material-symbols-outlined">settings</span> Panel del Administrador</h2>

    <section>
        <h3>🌍 Crear nuevo mundial</h3>
        <form action="crearmundial.php" method="POST">
            <input type="number" name="anio" placeholder="Año" required>
            <input type="text" name="sede" placeholder="Sede" required>
            <input type="text" name="campeon" placeholder="Campeón">
            <textarea name="descripcion" placeholder="Descripción"></textarea>
            <button type="submit"><span class="material-symbols-outlined">add</span> Crear mundial</button>
        </form>
    </section>

    <section>
        <h3>📂 Crear nueva categoría</h3>
        <form action="crearcategoria.php" method="POST">
            <input type="text" name="nombre" placeholder="Nombre de categoría" required>
            <textarea name="descripcion" placeholder="Descripción"></textarea>
            <button type="submit"><span class="material-symbols-outlined">add</span> Crear categoría</button>
        </form>
    </section>

    <hr>
<?php endif; ?>

    <h2><span class="material-symbols-outlined">edit_note</span> Crear nueva publicación</h2>

    <?php if ($puede_publicar): ?>
    <form action="guardarpost.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($id_usuario) ?>">

        <div>
            <label for="id_mundial">Mundial:</label>
            <select id="id_mundial" name="id_mundial" required>
                <option value="">-- Selecciona un mundial --</option>
                <?php foreach ($mundiales as $m): ?>
                    <option value="<?= $m['id_mundial'] ?>" 
                        <?= (isset($form_data['id_mundial']) && $form_data['id_mundial'] == $m['id_mundial']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['anio'] . " - " . $m['sede']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="id_categoria">Categoría:</label>
            <select id="id_categoria" name="id_categoria" required>
                <option value="">-- Selecciona una categoría --</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id_categoria'] ?>" 
                        <?= (isset($form_data['id_categoria']) && $form_data['id_categoria'] == $c['id_categoria']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="titulo">Título:</label>
            <input type="text" id="titulo" name="titulo" 
                   value="<?= htmlspecialchars($form_data['titulo'] ?? '') ?>" required>
        </div>

        <div>
            <label for="contenido">Contenido:</label>
            <textarea id="contenido" name="contenido" required><?= htmlspecialchars($form_data['contenido'] ?? '') ?></textarea>
        </div>

        <hr style="border-color: rgba(255,255,255,0.1);">
        <p style="color: var(--verde-menta); font-size: 0.9em;">
            <span class="material-symbols-outlined" style="font-size:18px;">info</span> 
            Elige <strong>solo una</strong> de las siguientes opciones multimedia:
        </p>

        <div>
            <label for="media">Opción A: Subir Imagen o Video</label>
            <input type="file" id="media" name="media" accept="image/*,video/*">
        </div>

        <p style="text-align:center; color:white;">— O —</p>

        <div>
            <label for="link_media">Opción B: Enlace de YouTube</label>
            <input type="url" id="link_media" name="link_media" 
                   value="<?= htmlspecialchars($form_data['link_media'] ?? '') ?>"
                   placeholder="https://www.youtube.com/watch?v=xxxx">
        </div>

        <button type="submit"><span class="material-symbols-outlined">send</span> Publicar</button>
    </form>
    <?php else: ?>
        <div class="alerta error">
            <?= htmlspecialchars($mensaje_bloqueo) ?>
        </div>
    <?php endif; ?>

</main>

<footer>
    <a href="index.php"><button><span class="material-symbols-outlined">arrow_back</span> Volver al inicio</button></a>
</footer>

<script src="notificaciones.js"></script>
</body>
</html>