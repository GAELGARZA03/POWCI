CREATE DATABASE bd_unifut;
USE bd_unifut;

CREATE TABLE usuarios (
id_usuario INT AUTO_INCREMENT PRIMARY KEY,
nombre_completo VARCHAR(100) NOT NULL,
fecha_nacimiento DATE NOT NULL,
foto_perfil VARCHAR(255) DEFAULT 'default.jpg',
genero ENUM('Masculino', 'Femenino', 'Prefiero no decir') NOT NULL,
pais_nacimiento VARCHAR(100) NOT NULL,
nacionalidad VARCHAR(100) NOT NULL,
email VARCHAR(100) UNIQUE NOT NULL,
contrasena VARCHAR(255) NOT NULL,
fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE usuarios
ADD COLUMN rol ENUM('admin', 'usuario') DEFAULT 'usuario' AFTER contrasena,
ADD COLUMN estado ENUM('activo', 'inactivo') DEFAULT 'activo' AFTER rol;

CREATE TABLE mundiales (
id_mundial INT AUTO_INCREMENT PRIMARY KEY,
anio YEAR NOT NULL,
sede VARCHAR(100) NOT NULL,
campeon VARCHAR(100),
descripcion TEXT,
Fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE publicaciones (
id_publicacion INT AUTO_INCREMENT PRIMARY KEY,
id_usuario INT NOT NULL,
id_mundial INT NULL,
categoria VARCHAR(100) DEFAULT 'General',
titulo VARCHAR(255) NOT NULL,
mundial VARCHAR(100),
contenido TEXT,
tipo_contenido ENUM('texto', 'imagen', 'video') DEFAULT 'texto',
fecha_publicacion DATETIME DEFAULT CURRENT_TIMESTAMP,
fecha_aprobacion DATETIME NULL,
estado_publicacion ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',

FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
ON DELETE CASCADE
ON UPDATE CASCADE,
FOREIGN KEY (id_mundial) REFERENCES mundiales(id_mundial)
ON DELETE SET NULL
ON UPDATE CASCADE 
);

ALTER TABLE publicaciones 
MODIFY tipo_contenido ENUM('texto', 'imagen', 'video', 'link') DEFAULT 'texto';

ALTER TABLE publicaciones 
ADD COLUMN media_path VARCHAR(255) AFTER tipo_contenido;

ALTER TABLE publicaciones
MODIFY media_path VARCHAR(500) NULL;


ALTER TABLE publicaciones
ADD COLUMN id_categoria INT NULL AFTER id_mundial;

ALTER TABLE publicaciones
ADD CONSTRAINT fk_categoria_publicacion
FOREIGN KEY (id_categoria)
REFERENCES categorias(id_categoria)
ON DELETE SET NULL
ON UPDATE CASCADE;

ALTER TABLE publicaciones DROP COLUMN categoria;

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE comentarios (
id_comentario INT AUTO_INCREMENT PRIMARY KEY,
id_publicacion INT NOT NULL,
id_usuario INT NOT NULL,
contenido TEXT NOT NULL,
fecha_comentario DATETIME DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id_publicacion)
ON DELETE CASCADE
ON UPDATE CASCADE,
FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
ON DELETE CASCADE
ON UPDATE CASCADE
);

CREATE TABLE reacciones (
id_reaccion INT AUTO_INCREMENT PRIMARY KEY,
id_publicacion INT NOT NULL,
id_usuario INT NOT NULL,
tipo ENUM('like', 'dislike') NOT NULL,
fecha_reaccion DATETIME DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id_publicacion)
ON DELETE CASCADE
ON UPDATE CASCADE,
FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
ON DELETE CASCADE
ON UPDATE CASCADE,

UNIQUE (id_publicacion, id_usuario)
);

CREATE TABLE vistas (
id_vista INT AUTO_INCREMENT PRIMARY KEY,
id_publicacion INT NOT NULL,
id_usuario INT NULL,
fecha_vista DATETIME DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id_publicacion)
ON DELETE CASCADE
ON UPDATE CASCADE,
FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) 
ON DELETE SET NULL
ON UPDATE CASCADE
);

/*COMANDOS PARA VER SI SE INSERTAN BIEN LOS DATOS*/
select * from usuarios;
select * from mundiales;
select * from publicaciones;
select * from categorias;

/*COMNADOS PARA VACIAR TABLAS*/
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE vistas;
TRUNCATE TABLE reacciones;
TRUNCATE TABLE comentarios;
TRUNCATE TABLE publicaciones;
TRUNCATE TABLE mundiales;
TRUNCATE TABLE usuarios;
TRUNCATE TABLE categorias;

SET FOREIGN_KEY_CHECKS = 1;


