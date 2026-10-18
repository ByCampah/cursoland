<?php
require_once 'config/db.php';
include_once 'includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$mensaje_exito = '';
$mensaje_error = '';

// 1. Cargar datos actualizados del usuario logueado
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

// 2. Modificar Perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'modificar_perfil') {
    $nombre          = trim($_POST['nombre'] ?? '');
    $apellido        = trim($_POST['apellido'] ?? '');
    $dni             = trim($_POST['dni'] ?? '');
    $fechaNacimiento = $_POST['fechaNacimiento'] ?? '';
    $antecedentes    = trim($_POST['antecedentes'] ?? '');
    $intereses       = trim($_POST['intereses'] ?? '');
    $password_nueva  = $_POST['password_nueva'] ?? '';

    $fotoNombre = $usuario['fotoPerfil'];
    if (isset($_FILES['fotoPerfil']) && $_FILES['fotoPerfil']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['fotoPerfil']['name'], PATHINFO_EXTENSION);
        $fotoNombre = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['fotoPerfil']['tmp_name'], 'resources/uploads/' . $fotoNombre);
    }

    if (!empty($password_nueva)) {
        $passwordHash = password_hash($password_nueva, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, dni = ?, fechaNacimiento = ?, fotoPerfil = ?, antecedentes = ?, intereses = ?, password = ? WHERE id = ?";
        $stmt_up = $pdo->prepare($sql);
        $res = $stmt_up->execute([$nombre, $apellido, $dni, $fechaNacimiento, $fotoNombre, $antecedentes, $intereses, $passwordHash, $id_usuario]);
    } else {
        $sql = "UPDATE usuarios SET nombre = ?, apellido = ?, dni = ?, fechaNacimiento = ?, fotoPerfil = ?, antecedentes = ?, intereses = ? WHERE id = ?";
        $stmt_up = $pdo->prepare($sql);
        $res = $stmt_up->execute([$nombre, $apellido, $dni, $fechaNacimiento, $fotoNombre, $antecedentes, $intereses, $id_usuario]);
    }

    if ($res) {
        $mensaje_exito = "Perfil actualizado correctamente.";
        $stmt->execute([$id_usuario]);
        $usuario = $stmt->fetch();
        $_SESSION['nombre'] = $usuario['nombre'];
    }
}

// 3. Solicitar Plan PRO (Solo para usuarios en Plan Gratuito)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'solicitar_pro' && $usuario['tipoPlan'] === 'gratuito') {
    $motivo = "Solicitud Plan PRO";
    $mensaje_texto = trim($_POST['mensaje_comprobante'] ?? '');

    // Buscar si ya tiene una solicitud pendiente sin revisar
    $stmt_exist = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? AND leida = 0 AND motivo = 'Solicitud Plan PRO' LIMIT 1");
    $stmt_exist->execute([$id_usuario]);
    $solicitud_existente = $stmt_exist->fetch();

    $archivoNombre = '';
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
        $archivoNombre = 'comprobante_' . time() . '_' . $id_usuario . '.' . $ext;
        move_uploaded_file($_FILES['comprobante']['tmp_name'], 'resources/uploads/' . $archivoNombre);
    } elseif ($solicitud_existente) {
        preg_match('/ARCHIVO: ([^\s]+)/', $solicitud_existente['textoMensaje'], $match_f);
        $archivoNombre = $match_f[1] ?? '';
    }

    if (empty($archivoNombre)) {
        $mensaje_error = "Es obligatorio adjuntar el comprobante de pago.";
    } else {
        $mensaje_completo = "Solicitud de @" . $usuario['apodo'] . ": " . $mensaje_texto . " | ARCHIVO: " . $archivoNombre;

        if ($solicitud_existente) {
            $stmt_up_sol = $pdo->prepare("UPDATE notificaciones SET textoMensaje = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt_up_sol->execute([$mensaje_completo, $solicitud_existente['id']]);
            $mensaje_exito = "¡Tu solicitud pendiente fue actualizada correctamente!";
        } else {
            $stmt_notif = $pdo->prepare("INSERT INTO notificaciones (usuario_id, motivo, textoMensaje, leida) VALUES (?, ?, ?, 0)");
            $stmt_notif->execute([$id_usuario, $motivo, $mensaje_completo]);
            $mensaje_exito = "¡Solicitud de Plan PRO enviada al Administrador!";
        }
    }
}

// 4. Acciones del Administrador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $usuario['esAdmin'] == 1) {
    if (isset($_POST['accion']) && $_POST['accion'] === 'activar_pro_admin') {
        $id_usuario_target = $_POST['id_usuario_target'];
        $fechaVencimiento  = $_POST['fechaVencimiento'];

        $stmt_pro = $pdo->prepare("UPDATE usuarios SET tipoPlan = 'pro', fechaVencimientoPro = ? WHERE id = ?");
        if ($stmt_pro->execute([$fechaVencimiento, $id_usuario_target])) {
            // Marcar solicitudes de este usuario como leídas
            $stmt_m = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND motivo = 'Solicitud Plan PRO'");
            $stmt_m->execute([$id_usuario_target]);

            $mensaje_exito = "Plan PRO activado/actualizado correctamente.";
        }
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'dar_de_baja_pro') {
        $id_usuario_target = $_POST['id_usuario_target'];
        $stmt_baja = $pdo->prepare("UPDATE usuarios SET tipoPlan = 'gratuito', fechaVencimientoPro = NULL WHERE id = ?");
        if ($stmt_baja->execute([$id_usuario_target])) {
            $mensaje_exito = "Se dio de baja el Plan PRO al usuario.";
        }
    }
}

// Cargar si el usuario actual tiene una solicitud pendiente activa
$stmt_mi_sol = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? AND leida = 0 AND motivo = 'Solicitud Plan PRO' LIMIT 1");
$stmt_mi_sol->execute([$id_usuario]);
$mi_solicitud_pendiente = $stmt_mi_sol->fetch();

$mi_mensaje_previo = '';
if ($mi_solicitud_pendiente) {
    preg_match('/: (.*?) \| ARCHIVO:/', $mi_solicitud_pendiente['textoMensaje'], $match_msg);
    $mi_mensaje_previo = $match_msg[1] ?? '';
}
?>

<div class="row">
    <!-- Lateral Izquierdo: Datos de Perfil -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm text-center p-3">
            <img src="resources/uploads/<?php echo htmlspecialchars($usuario['fotoPerfil']); ?>" alt="Foto" class="rounded-circle img-thumbnail mx-auto d-block mb-3" style="width: 140px; height: 140px; object-fit: cover;">
            <h4><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></h4>
            <p class="text-muted">@<?php echo htmlspecialchars($usuario['apodo']); ?></p>
            <div class="mb-2">
                <?php if ($usuario['tipoPlan'] === 'pro'): ?>
                    <span class="badge bg-warning text-dark fs-6">Plan PRO</span>
                    <?php if ($usuario['fechaVencimientoPro']): ?>
                        <small class="d-block text-muted mt-1">Vence: <?php echo $usuario['fechaVencimientoPro']; ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-secondary fs-6">Plan Gratuito</span>
                <?php endif; ?>
                <?php if ($usuario['esAdmin'] == 1): ?>
                    <span class="badge bg-danger fs-6 ms-1">Administrador</span>
                <?php endif; ?>
            </div>
            <hr>
            <div class="text-start">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                <p><strong>DNI:</strong> <?php echo htmlspecialchars($usuario['dni'] ?? 'No especificado'); ?></p>
                <p><strong>Fecha Nacimiento:</strong> <?php echo htmlspecialchars($usuario['fechaNacimiento']); ?></p>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="col-md-8">
        <?php if ($mensaje_exito): ?><div class="alert alert-success"><?php echo $mensaje_exito; ?></div><?php endif; ?>
        <?php if ($mensaje_error): ?><div class="alert alert-danger"><?php echo $mensaje_error; ?></div><?php endif; ?>

        <!-- Modificar Perfil -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white"><h5 class="mb-0">Modificar Datos Personales</h5></div>
            <div class="card-body">
                <form action="perfil.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="modificar_perfil">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Apellido</label><input type="text" name="apellido" class="form-control" value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">DNI</label><input type="text" name="dni" class="form-control" value="<?php echo htmlspecialchars($usuario['dni'] ?? ''); ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Fecha Nacimiento</label><input type="date" name="fechaNacimiento" class="form-control" value="<?php echo htmlspecialchars($usuario['fechaNacimiento']); ?>" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Foto Perfil</label><input type="file" name="fotoPerfil" class="form-control" accept="image/*"></div>
                    <div class="mb-3"><label class="form-label">Antecedentes</label><textarea name="antecedentes" class="form-control" rows="2"><?php echo htmlspecialchars($usuario['antecedentes'] ?? ''); ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Intereses</label><textarea name="intereses" class="form-control" rows="2"><?php echo htmlspecialchars($usuario['intereses'] ?? ''); ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Nueva Contraseña</label><input type="password" name="password_nueva" class="form-control" placeholder="******"></div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <!-- Solicitar PRO (Solo disponible para Plan Gratuito) -->
        <?php if ($usuario['esAdmin'] == 0 && $usuario['tipoPlan'] === 'gratuito'): ?>
            <div class="card shadow-sm border-warning mb-4">
                <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                    <span>Solicitar Pase a Plan PRO</span>
                    <?php if ($mi_solicitud_pendiente): ?><span class="badge bg-primary">Solicitud en Revisión</span><?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($mi_solicitud_pendiente): ?>
                        <div class="alert alert-info">Tu solicitud está en revisión. Puedes actualizar tu mensaje o reemplazar el comprobante subido.</div>
                    <?php else: ?>
                        <p class="card-text">Pasa a Plan PRO para disfrutar de ventajas exclusivas: creación ilimitada de cursos, mayor capacidad de archivos adjuntos e inscripciones sin límite.</p>
                    <?php endif; ?>

                    <form action="perfil.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="accion" value="solicitar_pro">
                        <div class="mb-3"><label class="form-label">Mensaje para el Administrador</label><textarea name="mensaje_comprobante" class="form-control" rows="2" required><?php echo htmlspecialchars($mi_mensaje_previo); ?></textarea></div>
                        <div class="mb-3"><label class="form-label"><?php echo $mi_solicitud_pendiente ? 'Reemplazar Comprobante (Opcional)' : 'Adjuntar Comprobante (Imagen/PDF) *'; ?></label><input type="file" name="comprobante" class="form-control" <?php echo $mi_solicitud_pendiente ? '' : 'required'; ?>></div>
                        <button type="submit" class="btn btn-warning fw-bold"><?php echo $mi_solicitud_pendiente ? 'Actualizar Solicitud' : 'Enviar Comprobante PRO'; ?></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- PANELS ADMINISTRADOR -->
        <?php if ($usuario['esAdmin'] == 1): ?>
            
            <!-- Panel 1: Solicitudes de Plan PRO Pendientes -->
            <div class="card shadow-sm border-danger mb-4">
                <div class="card-header bg-danger text-white fw-bold">Panel Admin: Solicitudes de Plan PRO Pendientes</div>
                <div class="card-body">
                    <?php
                    $stmt_sol = $pdo->query("SELECT n.*, u.apodo FROM notificaciones n JOIN usuarios u ON n.usuario_id = u.id WHERE n.motivo = 'Solicitud Plan PRO' AND n.leida = 0 ORDER BY n.created_at DESC");
                    $solicitudes = $stmt_sol->fetchAll();
                    ?>
                    <?php if (empty($solicitudes)): ?>
                        <p class="text-muted mb-0">No hay solicitudes pendientes.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light"><tr><th>Mensaje y Usuario</th><th>Comprobante</th><th>Acción</th></tr></thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $sol): 
                                        preg_match('/ARCHIVO: ([^\s]+)/', $sol['textoMensaje'], $match_file);
                                        $f_name = $match_file[1] ?? null;
                                    ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted d-block"><?php echo $sol['created_at']; ?></small>
                                                <strong>@<?php echo htmlspecialchars($sol['apodo']); ?>:</strong> <?php echo htmlspecialchars($sol['textoMensaje']); ?>
                                            </td>
                                            <td class="text-center"><?php if ($f_name): ?><a href="resources/uploads/<?php echo htmlspecialchars($f_name); ?>" target="_blank" class="btn btn-sm btn-outline-info">Ver Adjunto</a><?php else: ?><span class="text-muted">Sin adjunto</span><?php endif; ?></td>
                                            <td>
                                                <form action="perfil.php" method="POST" class="d-flex gap-2">
                                                    <input type="hidden" name="accion" value="activar_pro_admin">
                                                    <input type="hidden" name="id_usuario_target" value="<?php echo $sol['usuario_id']; ?>">
                                                    <input type="date" name="fechaVencimiento" class="form-control form-control-sm" required value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                                                    <button type="submit" class="btn btn-sm btn-success text-nowrap">Aprobar PRO</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel 2: Gestión Global de Usuarios PRO Activos -->
            <div class="card shadow-sm border-dark mb-4">
                <div class="card-header bg-dark text-white fw-bold">Gestión Global de Usuarios con Plan PRO Activos</div>
                <div class="card-body">
                    <?php
                    $stmt_pro_users = $pdo->query("SELECT id, apodo, email, fechaVencimientoPro FROM usuarios WHERE tipoPlan = 'pro' AND esAdmin = 0 ORDER BY fechaVencimientoPro ASC");
                    $pro_users = $stmt_pro_users->fetchAll();
                    ?>
                    <?php if (empty($pro_users)): ?>
                        <p class="text-muted mb-0">No hay usuarios con Plan PRO activos actualmente.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light"><tr><th>Usuario</th><th>Email</th><th>Vencimiento Actual</th><th>Modificar Vencimiento</th><th>Acción</th></tr></thead>
                                <tbody>
                                    <?php foreach ($pro_users as $pu): ?>
                                        <tr>
                                            <td><strong>@<?php echo htmlspecialchars($pu['apodo']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($pu['email']); ?></td>
                                            <td><span class="badge bg-warning text-dark fs-6"><?php echo $pu['fechaVencimientoPro'] ? $pu['fechaVencimientoPro'] : 'Sin fecha'; ?></span></td>
                                            <td>
                                                <form action="perfil.php" method="POST" class="d-flex gap-2">
                                                    <input type="hidden" name="accion" value="activar_pro_admin">
                                                    <input type="hidden" name="id_usuario_target" value="<?php echo $pu['id']; ?>">
                                                    <input type="date" name="fechaVencimiento" class="form-control form-control-sm" value="<?php echo $pu['fechaVencimientoPro']; ?>" required>
                                                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">Guardar</button>
                                                </form>
                                            </td>
                                            <td>
                                                <form action="perfil.php" method="POST" onsubmit="return confirm('¿Seguro que deseas dar de baja el Plan PRO?');">
                                                    <input type="hidden" name="accion" value="dar_de_baja_pro">
                                                    <input type="hidden" name="id_usuario_target" value="<?php echo $pu['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Dar de Baja</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>