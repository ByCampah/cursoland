<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$mensaje_error = '';
$mensaje_exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apodo          = trim($_POST['apodo'] ?? '');
    $nombre         = trim($_POST['nombre'] ?? '');
    $apellido       = trim($_POST['apellido'] ?? '');
    $dni            = trim($_POST['dni'] ?? '');
    $fechaNacimiento = $_POST['fechaNacimiento'] ?? '';
    $email          = trim($_POST['email'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm_pass   = $_POST['confirm_password'] ?? '';
    $antecedentes   = trim($_POST['antecedentes'] ?? '');
    $intereses      = trim($_POST['intereses'] ?? '');

    // Validaciones básicas
    if (empty($apodo) || empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($fechaNacimiento)) {
        $mensaje_error = "Por favor, completa todos los campos obligatorios.";
    } elseif ($password !== $confirm_pass) {
        $mensaje_error = "Las contraseñas no coinciden.";
    } else {
        // Verificar si el correo o el apodo ya existen
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR apodo = ?");
        $stmt_check->execute([$email, $apodo]);
        if ($stmt_check->fetch()) {
            $mensaje_error = "El correo electrónico o el apodo/usuario ya están registrados.";
        } else {
            // Manejo de la Foto de Perfil
            $fotoNombre = 'default.png';
            if (isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['fotoPerfil']['name'], PATHINFO_EXTENSION);
                $fotoNombre = time() . '_' . uniqid() . '.' . $ext;
                $destino = 'resources/uploads/' . $fotoNombre;
                move_uploaded_file($_FILES['fotoPerfil']['tmp_name'], $destino);
            }

            // Encriptar contraseña por seguridad
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insertar en la Base de Datos
            $sql = "INSERT INTO usuarios (apodo, nombre, apellido, dni, fechaNacimiento, email, password, fotoPerfil, antecedentes, intereses) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$apodo, $nombre, $apellido, $dni, $fechaNacimiento, $email, $passwordHash, $fotoNombre, $antecedentes, $intereses])) {
                $mensaje_exito = "¡Cuenta creada exitosamente! Ya puedes iniciar sesión.";
            } else {
                $mensaje_error = "Ocurrió un error al registrar la cuenta.";
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Formulario de Registro</h4>
            </div>
            <div class="card-body">
                <?php if ($mensaje_error): ?>
                    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
                <?php endif; ?>
                <?php if ($mensaje_exito): ?>
                    <div class="alert alert-success">
                        <?php echo $mensaje_exito; ?> <a href="login.php" class="alert-link">Iniciar Sesión</a>
                    </div>
                <?php else: ?>
                    <form action="registro.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre de Usuario (Apodo) *</label>
                                <input type="text" name="apodo" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo Electrónico *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellido *</label>
                                <input type="text" name="apellido" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">DNI</label>
                                <input type="text" name="dni" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Nacimiento *</label>
                                <input type="date" name="fechaNacimiento" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contraseña *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Contraseña *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Imagen de Perfil</label>
                            <input type="file" name="fotoPerfil" class="form-control" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Antecedentes</label>
                            <textarea name="antecedentes" class="form-control" rows="2" placeholder="Estudios, experiencia previa..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Intereses (Separados por coma)</label>
                            <textarea name="intereses" class="form-control" rows="2" placeholder="Programación, Diseño, Música..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Registrarse</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>