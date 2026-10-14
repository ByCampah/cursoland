<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Guardar datos en la Sesión
            $_SESSION['usuario_id']  = $usuario['id'];
            $_SESSION['apodo']       = $usuario['apodo'];
            $_SESSION['nombre']      = $usuario['nombre'];
            $_SESSION['tipoPlan']    = $usuario['tipoPlan'];
            $_SESSION['esAdmin']     = $usuario['esAdmin'];

            header('Location: index.php');
            exit;
        } else {
            $mensaje_error = "Correo o contraseña incorrectos.";
        }
    } else {
        $mensaje_error = "Por favor, completa todos los campos.";
    }
}
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white text-center">
                <h4 class="mb-0">Iniciar Sesión</h4>
            </div>
            <div class="card-body">
                <?php if ($mensaje_error): ?>
                    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-1"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></p>
                    <p class="mb-0">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>