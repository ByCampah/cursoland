<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursolandia</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="resources/css/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">Cursolandia</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Catálogo</a></li>
        <?php if(isset($_SESSION['usuario_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="mis_cursos.php">Mis Cursos</a></li>
          <li class="nav-item"><a class="nav-link" href="perfil.php">Mi Perfil</a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Cerrar Sesión</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Ingresar / Registrarse</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container min-vh-100">