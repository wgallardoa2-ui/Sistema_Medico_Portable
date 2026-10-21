<?php
// citas.php - Módulo de Gestión General de Citas Médicas
require_once 'config/database.php';
require_once 'includes/header.php';

$pdo = Database::connect();
$msg = '';

// 1. Cambiar Estado Rápido (ej. "Llegó el Paciente / Confirmado", "Cancelado", "Atendido")
if (isset($_GET['cambiar_estado']) && isset($_GET['id'])) {
    $id_cita = intval($_GET['id']);
    $nuevo_estado = trim($_GET['cambiar_estado']);
    $estados_validos = ['Pendiente', 'Confirmada / Llegó', 'Atendida', 'Cancelada'];

    if (in_array($nuevo_estado, $estados_validos)) {
        try {
            $stmtState = $pdo->prepare("UPDATE citas SET estado = ? WHERE id = ?");
            $stmtState->execute([$nuevo_estado, $id_cita]);
            $msg = '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i>Estado de la cita actualizado a: <strong>' . htmlspecialchars($nuevo_estado) . '</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>Error al actualizar estado.</div>';
        }
    }
}

// 2. Modificar/Reagendar Cita (Cambiar Fecha, Hora, Doctor o Motivo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_cita'])) {
    $cita_id = intval($_POST['cita_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $motivo = trim($_POST['motivo']);
    $estado = $_POST['estado'];

    try {
        $stmtUp = $pdo->prepare("UPDATE citas SET doctor_id = ?, fecha = ?, hora = ?, motivo = ?, estado = ? WHERE id = ?");
        $stmtUp->execute([$doctor_id, $fecha, $hora, $motivo, $estado, $cita_id]);
        $msg = '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fa-solid fa-calendar-pen me-2"></i>Cita médica reagendada y actualizada con éxito.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>Error al modificar la cita.</div>';
    }
}

// 3. Crear Nueva Cita Interna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cita'])) {
    $paciente_id = intval($_POST['paciente_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $motivo = trim($_POST['motivo']);

    if ($paciente_id > 0) {
        try {
            $stmtIns = $pdo->prepare("INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo, estado, origen) VALUES (?, ?, ?, ?, ?, 'Pendiente', 'Presencial')");
            $stmtIns->execute([$paciente_id, $doctor_id, $fecha, $hora, $motivo]);
            $msg = '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i>Cita agendada correctamente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>Error al agendar cita.</div>';
        }
    }
}

// Filtros de Búsqueda
$filtro_doctor = intval($_GET['f_doctor'] ?? 0);
$filtro_estado = trim($_GET['f_estado'] ?? '');
$filtro_origen = trim($_GET['f_origen'] ?? '');
$filtro_fecha  = trim($_GET['f_fecha'] ?? '');

$sql = "SELECT c.*, p.nombre as paciente_nombre, p.documento, p.telefono as paciente_tel, d.nombre as doctor_nombre, d.especialidad 
        FROM citas c 
        JOIN pacientes p ON c.paciente_id = p.id 
        LEFT JOIN doctores d ON c.doctor_id = d.id 
        WHERE 1=1";
$params = [];

if ($filtro_doctor > 0) {
    $sql .= " AND c.doctor_id = ?";
    $params[] = $filtro_doctor;
}
if (!empty($filtro_estado)) {
    $sql .= " AND c.estado = ?";
    $params[] = $filtro_estado;
}
if (!empty($filtro_origen)) {
    $sql .= " AND c.origen = ?";
    $params[] = $filtro_origen;
}
if (!empty($filtro_fecha)) {
    $sql .= " AND c.fecha = ?";
    $params[] = $filtro_fecha;
}

$sql .= " ORDER BY c.fecha DESC, c.hora ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas_list = $stmt->fetchAll();

// Listas de datos para filtros y modales
$pacientes_list = $pdo->query("SELECT id, nombre, documento FROM pacientes ORDER BY nombre ASC")->fetchAll();
$doctores_list = $pdo->query("SELECT id, nombre, especialidad FROM doctores ORDER BY nombre ASC")->fetchAll();

// Métricas rápidas de la agenda
$cnt_total = count($citas_list);
$cnt_web = $pdo->query("SELECT COUNT(*) FROM citas WHERE origen = 'Online' AND estado = 'Pendiente'")->fetchColumn() ?: 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-primary mb-0"><i class="fa-solid fa-calendar-days me-2"></i>Agenda General y Control de Citas</h4>
        <p class="text-muted small mb-0">Administre citas recibidas vía Web o Presenciales, confirme asistencia y asigne médicos</p>
    </div>
    <button class="btn btn-primary fw-bold px-4 py-2 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaCita">
        <i class="fa-solid fa-plus me-1"></i> Agendar Cita
    </button>
</div>

<?= $msg ?>

<?php if ($cnt_web > 0): ?>
    <div class="alert alert-warning p-3 rounded-3 shadow-sm d-flex justify-content-between align-items-center mb-4 border-start border-4 border-warning">
        <div>
            <h6 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-globe fa-lg me-2 text-warning"></i>¡Atención! Hay <?= $cnt_web ?> solicitud(es) de Citas Web pendientes por revisar.</h6>
            <small class="text-muted">Revise la lista abajo para asignar médico, confirmar la asistencia o reagendar la hora.</small>
        </div>
        <a href="citas.php?f_origen=Online&f_estado=Pendiente" class="btn btn-warning btn-sm fw-bold px-3">
            Ver Solicitudes Web
        </a>
    </div>
<?php endif; ?>

<!-- Barra de Filtros -->
<div class="card card-custom p-3 bg-white shadow-sm mb-4">
    <form method="GET" action="citas.php" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted">Filtrar por Médico</label>
            <select name="f_doctor" class="form-select form-select-sm">
                <option value="0">-- Todos los Médicos --</option>
                <?php foreach ($doctores_list as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filtro_doctor == $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nombre']) ?> (<?= htmlspecialchars($d['especialidad']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted">Estado</label>
            <select name="f_estado" class="form-select form-select-sm">
                <option value="">-- Todos --</option>
                <option value="Pendiente" <?= $filtro_estado === 'Pendiente' ? 'selected' : '' ?>>Pendientes</option>
                <option value="Confirmada / Llegó" <?= $filtro_estado === 'Confirmada / Llegó' ? 'selected' : '' ?>>Confirmada / Llegó</option>
                <option value="Atendida" <?= $filtro_estado === 'Atendida' ? 'selected' : '' ?>>Atendidas</option>
                <option value="Cancelada" <?= $filtro_estado === 'Cancelada' ? 'selected' : '' ?>>Canceladas</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-bold small text-muted">Origen</label>
            <select name="f_origen" class="form-select form-select-sm">
                <option value="">-- Todos --</option>
                <option value="Online" <?= $filtro_origen === 'Online' ? 'selected' : '' ?>>Citas Web</option>
                <option value="Presencial" <?= $filtro_origen === 'Presencial' ? 'selected' : '' ?>>Presenciales</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted">Fecha Específica</label>
            <input type="date" name="f_fecha" class="form-control form-control-sm" value="<?= htmlspecialchars($filtro_fecha) ?>">
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm fw-bold w-100">
                <i class="fa-solid fa-filter me-1"></i> Filtrar
            </button>
            <a href="citas.php" class="btn btn-outline-secondary btn-sm" title="Limpiar Filtros">
                <i class="fa-solid fa-arrows-rotate"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tabla de Control de Citas -->
<div class="card card-custom p-4 bg-white shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha & Hora</th>
                    <th>Origen</th>
                    <th>Paciente</th>
                    <th>Médico Asignado</th>
                    <th>Motivo Consulta</th>
                    <th>Estado Paciente</th>
                    <th class="text-end">Acciones / Control</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citas_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fa-solid fa-calendar-xmark fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                            No se encontraron citas registradas con los filtros seleccionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($citas_list as $c): ?>
                        <tr>
                            <td>
                                <strong class="d-block text-primary"><?= date('d/m/Y', strtotime($c['fecha'])) ?></strong>
                                <span class="badge bg-light text-dark border"><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($c['hora']) ?></span>
                            </td>
                            <td>
                                <?php if ($c['origen'] === 'Online'): ?>
                                    <span class="badge bg-info text-dark shadow-sm"><i class="fa-solid fa-globe me-1"></i> Cita Web</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fa-solid fa-user-check me-1"></i> Presencial</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($c['paciente_nombre']) ?></strong>
                                <small class="d-block text-muted">Doc: <?= htmlspecialchars($c['documento']) ?> | Tel: <?= htmlspecialchars($c['paciente_tel'] ?: 'N/A') ?></small>
                            </td>
                            <td>
                                <?php if (!empty($c['doctor_nombre'])): ?>
                                    <span class="fw-bold text-dark"><i class="fa-solid fa-user-doctor text-primary me-1"></i><?= htmlspecialchars($c['doctor_nombre']) ?></span>
                                    <small class="d-block text-muted"><?= htmlspecialchars($c['especialidad']) ?></small>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Sin Asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small"><?= htmlspecialchars($c['motivo'] ?: 'Consulta General') ?></span>
                            </td>
                            <td>
                                <?php 
                                    $stClass = 'bg-warning text-dark';
                                    if ($c['estado'] === 'Confirmada / Llegó') $stClass = 'bg-info text-white fw-bold';
                                    if ($c['estado'] === 'Atendida') $stClass = 'bg-success text-white';
                                    if ($c['estado'] === 'Cancelada') $stClass = 'bg-danger text-white';
                                ?>
                                <span class="badge <?= $stClass ?> px-2 py-1">
                                    <?= htmlspecialchars($c['estado']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">
                                        Estado / Cambio
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><a class="dropdown-menu-item dropdown-item text-info fw-bold" href="citas.php?id=<?= $c['id'] ?>&cambiar_estado=Confirmada / Llegó"><i class="fa-solid fa-person-walking-arrow-right me-2"></i> Paciente Llegó / Confirmar</a></li>
                                        <li><a class="dropdown-menu-item dropdown-item text-success fw-bold" href="citas.php?id=<?= $c['id'] ?>&cambiar_estado=Atendida"><i class="fa-solid fa-check-double me-2"></i> Marcar como Atendida</a></li>
                                        <li><a class="dropdown-menu-item dropdown-item text-danger fw-bold" href="citas.php?id=<?= $c['id'] ?>&cambiar_estado=Cancelada"><i class="fa-solid fa-ban me-2"></i> Cancelar Cita</a></li>
                                    </ul>
                                </div>

                                <!-- Botón Editar / Reagendar -->
                                <button class="btn btn-sm btn-light border ms-1" data-bs-toggle="modal" data-bs-target="#modalEditarCita<?= $c['id'] ?>" title="Reagendar / Modificar">
                                    <i class="fa-solid fa-pen-to-square text-primary"></i>
                                </button>

                                <!-- Botón Atender -->
                                <a href="historia.php?paciente_id=<?= $c['paciente_id'] ?>&cita_id=<?= $c['id'] ?>" class="btn btn-sm btn-success fw-bold ms-1" title="Atender en Consulta">
                                    <i class="fa-solid fa-stethoscope"></i>
                                </a>

                                <!-- MODAL DE EDICIÓN Y REAGENDAMIENTO DE CITA -->
                                <div class="modal fade text-start" id="modalEditarCita<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow-lg rounded-4">
                                            <form method="POST" action="citas.php">
                                                <input type="hidden" name="cita_id" value="<?= $c['id'] ?>">
                                                <div class="modal-header bg-primary text-white rounded-top-4">
                                                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-calendar-pen me-2"></i> Reagendar / Asignar Cita</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body row g-3">
                                                    <div class="col-12 bg-light p-3 rounded-3">
                                                        <strong class="d-block text-primary">Paciente: <?= htmlspecialchars($c['paciente_nombre']) ?></strong>
                                                        <small class="text-muted">Origen: <?= $c['origen'] === 'Online' ? 'Reserva en Línea (Web)' : 'Atención Presencial' ?></small>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold small">Médico Asignado *</label>
                                                        <select name="doctor_id" class="form-select" required>
                                                            <option value="0">-- Seleccionar Médico --</option>
                                                            <?php foreach ($doctores_list as $d): ?>
                                                                <option value="<?= $d['id'] ?>" <?= $c['doctor_id'] == $d['id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($d['nombre']) ?> (<?= htmlspecialchars($d['especialidad']) ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold small">Fecha Atención *</label>
                                                        <input type="date" name="fecha" class="form-control" value="<?= $c['fecha'] ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-bold small">Hora *</label>
                                                        <input type="time" name="hora" class="form-control" value="<?= $c['hora'] ?>" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold small">Estado de la Cita</label>
                                                        <select name="estado" class="form-select">
                                                            <option value="Pendiente" <?= $c['estado'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                            <option value="Confirmada / Llegó" <?= $c['estado'] === 'Confirmada / Llegó' ? 'selected' : '' ?>>Confirmada / Llegó el Paciente</option>
                                                            <option value="Atendida" <?= $c['estado'] === 'Atendida' ? 'selected' : '' ?>>Atendida</option>
                                                            <option value="Cancelada" <?= $c['estado'] === 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-bold small">Motivo Consulta</label>
                                                        <textarea name="motivo" class="form-control" rows="2"><?= htmlspecialchars($c['motivo']) ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="modificar_cita" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cambios</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CREAR NUEVA CITA PRESENCIAL -->
<div class="modal fade" id="modalNuevaCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="citas.php">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-calendar-plus me-2"></i> Agendar Nueva Cita</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small">Paciente *</label>
                        <select name="paciente_id" class="form-select" required>
                            <option value="">-- Seleccionar Paciente --</option>
                            <?php foreach ($pacientes_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['documento']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Médico Asignado</label>
                        <select name="doctor_id" class="form-select">
                            <option value="0">-- Seleccionar Médico --</option>
                            <?php foreach ($doctores_list as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nombre']) ?> (<?= htmlspecialchars($d['especialidad']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Fecha *</label>
                        <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Hora *</label>
                        <input type="time" name="hora" class="form-control" value="09:00" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Motivo Consulta</label>
                        <textarea name="motivo" class="form-control" rows="2" placeholder="Describa el motivo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_cita" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Agendar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>