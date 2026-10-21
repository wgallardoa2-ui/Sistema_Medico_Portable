<?php
// configuracion.php - Ajustes con Subida de Imágenes desde la PC
require_once 'config/database.php';
require_once 'includes/header.php';

$pdo = Database::connect();
$msg = '';

// Función auxiliar para procesar la subida de archivos desde la computadora
function guardarImagenSubida($fileKey, $prefix) {
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/uploads/';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $newName = $prefix . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $targetPath = $dir . $newName;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                return 'uploads/' . $newName;
            }
        }
    }
    return null;
}

// 1. Guardar Configuración de Imágenes e Institución
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $nombre_clinica = trim($_POST['nombre_clinica']);
    
    // Cargar valores actuales
    $foto_portada_login = Database::getConfig('foto_portada_login');
    $foto_banner_online = Database::getConfig('foto_banner_online');
    
    $stmtU = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
    $stmtU->execute([$_SESSION['usuario_id']]);
    $uCur = $stmtU->fetch();
    $foto_admin = $uCur['foto'] ?? '';

    // Procesar archivo subido desde PC o URL para Portada Login
    $subida_portada = guardarImagenSubida('file_portada_login', 'portada');
    if ($subida_portada) {
        $foto_portada_login = $subida_portada;
    } else if (!empty($_POST['foto_portada_login'])) {
        $foto_portada_login = trim($_POST['foto_portada_login']);
    }

    // Procesar archivo subido desde PC o URL para Banner Citas Web
    $subida_banner = guardarImagenSubida('file_banner_online', 'banner');
    if ($subida_banner) {
        $foto_banner_online = $subida_banner;
    } else if (!empty($_POST['foto_banner_online'])) {
        $foto_banner_online = trim($_POST['foto_banner_online']);
    }

    // Procesar archivo subido desde PC o URL para Foto de Perfil Médico
    $subida_admin = guardarImagenSubida('file_admin', 'user_' . $_SESSION['usuario_id']);
    if ($subida_admin) {
        $foto_admin = $subida_admin;
    } else if (isset($_POST['foto_admin']) && !empty($_POST['foto_admin'])) {
        $foto_admin = trim($_POST['foto_admin']);
    }

    // Guardar en Base de Datos
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO configuracion (clave, valor) VALUES (?, ?)");
    $stmt->execute(['nombre_clinica', $nombre_clinica]);
    $stmt->execute(['foto_portada_login', $foto_portada_login]);
    $stmt->execute(['foto_banner_online', $foto_banner_online]);

    if (!empty($foto_admin)) {
        $stmtU = $pdo->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
        $stmtU->execute([$foto_admin, $_SESSION['usuario_id']]);
        $_SESSION['usuario_foto'] = $foto_admin;
    }

    $msg = '<div class="alert alert-success p-3 rounded-3 shadow-sm alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check fa-lg me-2"></i>Ajustes e imágenes actualizados correctamente desde tu equipo.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// 2. Registrar Nuevo Usuario Médico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_user = trim($_POST['usuario']);
    $nuevo_pass = trim($_POST['password']);
    $nuevo_rol = trim($_POST['rol']);
    
    $nueva_foto = '';
    $subida_user = guardarImagenSubida('file_nuevo_usuario', 'user');
    if ($subida_user) {
        $nueva_foto = $subida_user;
    } else {
        $nueva_foto = trim($_POST['foto'] ?? '');
    }

    if (!empty($nuevo_nombre) && !empty($nuevo_user) && !empty($nuevo_pass)) {
        try {
            $pass_hash = password_hash($nuevo_pass, PASSWORD_DEFAULT);
            $stmtIns = $pdo->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, foto) VALUES (?, ?, ?, ?, ?)");
            $stmtIns->execute([$nuevo_nombre, $nuevo_user, $pass_hash, $nuevo_rol, $nueva_foto]);
            $msg = '<div class="alert alert-success p-3 rounded-3 shadow-sm"><i class="fa-solid fa-user-check fa-lg me-2"></i>Usuario médico <strong>' . htmlspecialchars($nuevo_user) . '</strong> creado con éxito.</div>';
        } catch (PDOException $e) {
            $msg = '<div class="alert alert-danger p-3 rounded-3 shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>El usuario <strong>' . htmlspecialchars($nuevo_user) . '</strong> ya existe. Elija otro nombre de usuario.</div>';
        }
    }
}

// 3. Eliminar Usuario
if (isset($_GET['eliminar_usuario'])) {
    $id_del = intval($_GET['eliminar_usuario']);
    if ($id_del != $_SESSION['usuario_id']) {
        $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmtDel->execute([$id_del]);
        $msg = '<div class="alert alert-warning p-3 rounded-3 shadow-sm"><i class="fa-solid fa-user-xmark me-2"></i>Usuario eliminado del sistema.</div>';
    }
}

$nombre_clinica = Database::getConfig('nombre_clinica') ?: 'Clínica de Especialidades Gallardo';
$foto_portada_login = Database::getConfig('foto_portada_login') ?: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?q=80&w=1000&auto=format&fit=crop';
$foto_banner_online = Database::getConfig('foto_banner_online') ?: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?q=80&w=1200&auto=format&fit=crop';

$stmtU = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
$stmtU->execute([$_SESSION['usuario_id']]);
$userNow = $stmtU->fetch();
$foto_admin = $userNow['foto'] ?? '';

$lista_usuarios = $pdo->query("SELECT id, nombre, usuario, rol, foto FROM usuarios ORDER BY id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-gears me-2"></i>Ajustes del Sistema y Usuarios</h4>
        <p class="text-muted small mb-0">Cargue imágenes desde su computadora o administre cuentas de usuario</p>
    </div>
</div>

<?= $msg ?>

<!-- Pestañas de Selección -->
<ul class="nav nav-pills mb-4 gap-2" id="pills-tab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-bold px-4 py-2" id="tab-imagenes" data-bs-toggle="pill" data-bs-target="#sec-imagenes">
            <i class="fa-solid fa-image me-2"></i> Imágenes & Portadas
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold px-4 py-2" id="tab-usuarios" data-bs-toggle="pill" data-bs-target="#sec-usuarios">
            <i class="fa-solid fa-users-gear me-2"></i> Usuarios del Sistema
        </button>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">
    
    <!-- SECCIÓN 1: PERSONALIZACIÓN DE IMÁGENES -->
    <div class="tab-pane fade show active" id="sec-imagenes">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card card-custom p-4 bg-white shadow-sm">
                    <form method="POST" action="configuracion.php" enctype="multipart/form-data">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fa-solid fa-hospital text-primary me-1"></i> Nombre de la Clínica</label>
                            <input type="text" name="nombre_clinica" class="form-control py-2 fw-semibold" value="<?= htmlspecialchars($nombre_clinica) ?>" required>
                        </div>

                        <!-- 1. Imagen Portada Login -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label class="form-label fw-bold text-primary mb-1"><i class="fa-solid fa-upload me-1"></i> Imagen de Portada del Login</label>
                            <p class="text-muted small mb-2">Seleccione una foto desde su computadora (.jpg, .png):</p>
                            <input type="file" name="file_portada_login" class="form-control mb-2" accept="image/*">
                            
                            <details class="mt-1">
                                <summary class="small text-muted cursor-pointer">O pegar enlace URL externo</summary>
                                <input type="url" name="foto_portada_login" class="form-control form-control-sm mt-2" value="<?= htmlspecialchars($foto_portada_login) ?>" placeholder="https://...">
                            </details>

                            <?php if ($foto_portada_login): ?>
                                <div class="mt-3">
                                    <small class="fw-bold d-block mb-1 text-muted">Vista previa actual:</small>
                                    <img src="<?= htmlspecialchars($foto_portada_login) ?>" class="rounded-3 shadow-sm" style="max-height: 120px; width: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 2. Banner Citas Web -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label class="form-label fw-bold text-primary mb-1"><i class="fa-solid fa-globe me-1"></i> Imagen Banner para Citas en Línea</label>
                            <p class="text-muted small mb-2">Seleccione el banner que verán los pacientes desde su equipo:</p>
                            <input type="file" name="file_banner_online" class="form-control mb-2" accept="image/*">
                            
                            <details class="mt-1">
                                <summary class="small text-muted cursor-pointer">O pegar enlace URL externo</summary>
                                <input type="url" name="foto_banner_online" class="form-control form-control-sm mt-2" value="<?= htmlspecialchars($foto_banner_online) ?>" placeholder="https://...">
                            </details>

                            <?php if ($foto_banner_online): ?>
                                <div class="mt-3">
                                    <small class="fw-bold d-block mb-1 text-muted">Vista previa actual:</small>
                                    <img src="<?= htmlspecialchars($foto_banner_online) ?>" class="rounded-3 shadow-sm" style="max-height: 120px; width: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 3. Foto Perfil Médico -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label class="form-label fw-bold text-primary mb-1"><i class="fa-solid fa-user-doctor me-1"></i> Mi Foto de Perfil Médico</label>
                            <p class="text-muted small mb-2">Suba su foto personal desde su computadora:</p>
                            <input type="file" name="file_admin" class="form-control mb-2" accept="image/*">
                            
                            <details class="mt-1">
                                <summary class="small text-muted cursor-pointer">O pegar enlace URL externo</summary>
                                <input type="url" name="foto_admin" class="form-control form-control-sm mt-2" value="<?= htmlspecialchars($foto_admin) ?>" placeholder="https://...">
                            </details>

                            <?php if ($foto_admin): ?>
                                <div class="mt-3 d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars($foto_admin) ?>" class="rounded-circle shadow-sm" style="width: 70px; height: 70px; object-fit: cover; border: 3px solid #0d6efd;">
                                    <small class="text-muted">Mostrada en la barra superior del menú</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="guardar_config" class="btn btn-primary btn-lg fw-bold px-4 py-3 rounded-3 shadow w-100">
                            <i class="fa-solid fa-cloud-arrow-up me-2"></i> Subir y Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-custom p-4 bg-white shadow-sm text-center">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-id-badge me-1"></i> Mi Perfil Médico</h6>
                    <img src="<?= htmlspecialchars($foto_admin ?: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?q=80&w=400&auto=format&fit=crop') ?>" class="rounded-circle shadow mb-3 mx-auto" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #0d6efd;">
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></h5>
                    <span class="badge bg-primary text-white mt-2 py-2 px-3">Director Médico</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: GESTIÓN DE USUARIOS -->
    <div class="tab-pane fade" id="sec-usuarios">
        <div class="row g-4">
            
            <div class="col-lg-5">
                <div class="card card-custom p-4 bg-white shadow-sm">
                    <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-user-plus me-2"></i>Registrar Nuevo Usuario</h5>

                    <form method="POST" action="configuracion.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nombre Completo *</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Dra. Sofía Ramírez" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Usuario de Acceso *</label>
                            <input type="text" name="usuario" class="form-control" placeholder="Ej: sramirez" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Contraseña *</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Rol / Cargo *</label>
                            <select name="rol" class="form-select" required>
                                <option value="Médico Especialista">Médico Especialista</option>
                                <option value="Administrador">Administrador</option>
                                <option value="Recepción / Caja">Recepción / Caja</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small">Foto de Perfil (Desde la PC)</label>
                            <input type="file" name="file_nuevo_usuario" class="form-control mb-1" accept="image/*">
                        </div>
                        <button type="submit" name="crear_usuario" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-user-check me-2"></i> Crear Usuario de Acceso
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-custom p-4 bg-white shadow-sm">
                    <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-users me-2"></i>Usuarios Registrados</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Nombre</th>
                                    <th>Rol</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_usuarios as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($u['foto'] ?: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?q=80&w=200&auto=format&fit=crop') ?>" class="rounded-circle me-2 shadow-sm" style="width: 36px; height: 36px; object-fit: cover;">
                                                <strong><code><?= htmlspecialchars($u['usuario']) ?></code></strong>
                                            </div>
                                        </td>
                                        <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($u['rol']) ?></span></td>
                                        <td>
                                            <?php if ($id_del = $u['id'] != $_SESSION['usuario_id']): ?>
                                                <a href="configuracion.php?eliminar_usuario=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar este usuario?');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-success">Sesión Actual</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>