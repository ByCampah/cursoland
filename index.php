<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="p-5 mb-4 bg-white rounded-3 shadow-sm text-center">
    <div class="container-fluid py-3">
        <h1 class="display-5 fw-bold">Bienvenido a Cursolandia</h1>
        <p class="col-md-8 fs-4 mx-auto text-muted">
            La plataforma interactiva para crear, compartir y realizar cursos en línea.
        </p>
        <?php if (!isset($_SESSION['usuario_id'])): ?>
            <a href="login.php" class="btn btn-primary btn-lg me-2">Iniciar Sesión</a>
            <a href="registro.php" class="btn btn-outline-secondary btn-lg">Registrarse</a>
        <?php else: ?>
            <p class="lead text-success fw-bold">¡Hola, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</p>
            <a href="index.php" class="btn btn-primary btn-lg me-2">Ver Catálogo de Cursos</a>
            <a href="perfil.php" class="btn btn-outline-dark btn-lg">Ir a Mi Perfil</a>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>