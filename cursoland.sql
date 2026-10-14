CREATE DATABASE IF NOT EXISTS `cursoland` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cursoland`;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `apodo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `dni` VARCHAR(20) DEFAULT NULL,
  `fechaNacimiento` DATE NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `fotoPerfil` VARCHAR(255) DEFAULT 'default.png',
  `antecedentes` TEXT DEFAULT NULL,
  `intereses` TEXT DEFAULT NULL,
  `tipoPlan` ENUM('gratuito', 'pro') DEFAULT 'gratuito',
  `esAdmin` TINYINT(1) DEFAULT 0,
  `fechaVencimientoPro` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Cursos
CREATE TABLE IF NOT EXISTS `cursos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `docente_id` INT NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `etiquetas` VARCHAR(255) DEFAULT NULL,
  `costo` DECIMAL(10,2) DEFAULT 0.00,
  `cupo` INT DEFAULT NULL,
  `fechaInicio` DATE NOT NULL,
  `fechaFin` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`docente_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Tabla de Inscripciones
CREATE TABLE IF NOT EXISTS `inscripciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `curso_id` INT NOT NULL,
  `estado` ENUM('pendiente', 'confirmada') DEFAULT 'confirmada',
  `fechaInscripcion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Tabla de Materiales
CREATE TABLE IF NOT EXISTS `materiales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `curso_id` INT NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `formato` VARCHAR(20) NOT NULL,
  `tamanoMB` DECIMAL(6,2) NOT NULL,
  `rutaArchivo` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Tabla de Preguntas (Foro)
CREATE TABLE IF NOT EXISTS `preguntas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `curso_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `texto` TEXT NOT NULL,
  `estado` ENUM('abierta', 'cerrada') DEFAULT 'abierta',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`curso_id`) REFERENCES `cursos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Tabla de Respuestas (Foro)
CREATE TABLE IF NOT EXISTS `respuestas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pregunta_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `texto` TEXT NOT NULL,
  `esDestacada` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`pregunta_id`) REFERENCES `preguntas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Tabla de Notificaciones
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `motivo` VARCHAR(100) NOT NULL,
  `textoMensaje` TEXT NOT NULL,
  `leida` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Usuario Administrador de prueba (Password: admin123)
INSERT INTO `usuarios` (`apodo`, `nombre`, `apellido`, `email`, `password`, `esAdmin`, `tipoPlan`, `fechaNacimiento`) 
VALUES ('admin', 'Administrador', 'General', 'admin@cursoland.com', '$2y$10$eE9C68r/bQOaXvR6S2aB8O7U73q9M1hA5gq5xO3.Y6a8xK1L0Q3KC', 1, 'pro', '1990-01-01')
ON DUPLICATE KEY UPDATE `id`=`id`;