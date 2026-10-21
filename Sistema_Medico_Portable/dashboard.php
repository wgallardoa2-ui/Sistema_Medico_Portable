<?php
// dashboard.php - Panel Principal con Alerta de Citas Web Entrantes
require_once 'config/database.php';
require_once 'includes/header.php';

$total_pacientes = 0;
$citas_hoy = 0;
$citas_pendientes = 0;
$citas_web_pendientes = 0;
$citas_list = [];
$pacientes_list = [];
$doctores_list = [];
$msg = '';

// Procesar agendamiento rápido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agendar_cita_rapida'])) {
    $paciente_id = intval($_POST['paciente_id'] ?? 0);
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $hora = $_POST['hora'] ?? '09:00';
    $motivo = trim($_POST['motivo'] ?? '');

    if ($paciente_id > 0) {
        try {
            $pdo = Database::connect();
            $stmt = $pdo->prepare("INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo, estado, origen) VALUES (?, ?, ?, ?, ?, 'Pendiente', 'Presencial')");
            $stmt->execute([$paciente_id, $doctor_id, $fecha, $hora, $motivo]);
            $msg = '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i>Cita médica agendada correctamente.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>Error al agendar la cita.</div>';
        }
    }
}

// Cargar métricas
try {
    $pdo = Database::connect();

    $total_pacientes = $pdo->query("SELECT COUNT(*) FROM pacientes")->fetchColumn() ?: 0;
    
    $today = date('Y-m-d');
    $stHoy = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE fecha = ?");
    $stHoy->execute([$today]);
    $citas_hoy = $stHoy->fetchColumn() ?: 0;

    $citas_pendientes = $pdo->query("SELECT COUNT(*) FROM citas WHERE estado = 'Pendiente'")->fetchColumn() ?: 0;
    $citas_web_pendientes = $pdo->query("SELECT COUNT(*) FROM citas WHERE origen = 'Online' AND estado = 'Pendiente'")->fetchColumn() ?: 0;

    $stCitas = $pdo->prepare("
        SELECT c.*, p.nombre as paciente_nombre, p.documento, d.nombre as doctor_nombre, d.especialidad 
        FROM citas c 
        JOIN pacientes p ON c.paciente_id = p.id 
        LEFT JOIN doctores d ON c.doctor_id = d.id 
        WHERE c.fecha = ? 
        ORDER BY c.hora ASC
    ");
    $stCitas->execute([$today]);
    $citas_list = $stCitas->fetchAll() ?: [];

    $pacientes_list = $pdo->query("SELECT id, nombre, documento FROM pacientes ORDER BY nombre ASC")->fetchAll() ?: [];
    $doctores_list = $pdo->query("SELECT id, nombre, especialidad FROM doctores ORDER BY nombre ASC")->fetchAll() ?: [];

} catch (Exception $e) {}
?>

<!-- Tarjetas de Métricas -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3 bg-white border-start border-4 border-primary shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Total Pacientes</span>
                    <h2 class="fw-extrabold text-primary mb-0 mt-1"><?= $total_pacientes ?></h2>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3">
                    <i class="fa-solid fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3 bg-white border-start border-4 border-warning shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-bold text-uppercase">Citas para Hoy</span>
                    <h2 class="fw-extrabold text-warning mb-0 mt-1"><?= $citas_hoy ?></h2>
                </div>
                <div class="bg-warning-subtle text-warning rounded-circle p-3">
                    <i class="fa-solid fa-calendar-day fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <a href="citas.php" class="text-decoration-none">
            <div class="card card-custom p-3 bg-white border-start border-4 border-info shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Citas Pendientes Totales</span>
                        <h2 class="fw-extrabold text-info mb-0 mt-1"><?= $citas_pendientes ?></h2>
                    </div>
                    <div class="bg-info-subtle text-info rounded-circle p-3">
                        <i class="fa-solid fa-clock-rotate-left fa-2x"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<?= $msg ?>

<?php if ($citas_web_pendientes > 0): ?>
    <div class="alert alert-warning p-3 rounded-3 shadow-sm d-flex justify-content-between align-items-center mb-4 border-start border-4 border-warning">
        <div>
            <h6 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-globe fa-lg me-2 text-warning"></i>¡Tienes <?= $citas_web_pendientes ?> cita(s) reservada(s) desde la Web pendientes!</h6>
            <small class="text-muted">Revise la agenda general para confirmar la fecha, hora o asignar el médico disponible.</small>
        </div>
        <a href="citas.php?f_origen=Online&f_estado=Pendiente" class="btn btn-warning btn-sm fw-bold px-3">
            <i class="fa-solid fa-calendar-check me-1"></i> Ver Citas Web
        </a>
    </div>
<?php endif; ?>

<!-- Tabla de Citas del Día -->
<div class="card card-custom p-4 bg-white shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-calendar-check me-2"></i>Citas Programadas para Hoy (<?= date('d/m/Y') ?>)</h5>
        <div>
            <a href="citas.php" class="btn btn-outline-primary btn-sm fw-bold me-2">
                <i class="fa-solid fa-list me-1"></i> Ver Agenda General
            </a>
            <button class="btn btn-primary btn-sm fw-bold px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaCita">
                <i class="fa-solid fa-plus me-1"></i> Programar Cita
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Hora</th>
                    <th>Documento</th>
                    <th>Paciente</th>
                    <th>Especialista</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citas_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fa-solid fa-calendar-xmark fa-2x mb-2 opacity-50 d-block"></i>
                            No hay citas programadas para el día de hoy.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($citas_list as $c): ?>
                        <tr>
                            <td><strong class="text-primary"><?= htmlspecialchars($c['hora']) ?></strong></td>
                            <td><code><?= htmlspecialchars($c['documento']) ?></code></td>
                            <td><strong><?= htmlspecialchars($c['paciente_nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($c['doctor_nombre'] ?: 'Sin Asignar') ?> <br><small class="text-muted"><?= htmlspecialchars($c['especialidad'] ?: '') ?></small></td>
                            <td><?= htmlspecialchars($c['motivo']) ?></td>
                            <td>
                                <span class="badge bg-<?= $c['estado'] === 'Pendiente' ? 'warning text-dark' : ($c['estado'] === 'Confirmada / Llegó' ? 'info text-white' : 'success') ?>">
                                    <?= htmlspecialchars($c['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="historia.php?paciente_id=<?= $c['paciente_id'] ?>&cita_id=<?= $c['id'] ?>" class="btn btn-sm btn-success fw-bold">
                                    <i class="fa-solid fa-stethoscope me-1"></i> Atender
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Emergente de Nueva Cita -->
<div class="modal fade" id="modalNuevaCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="dashboard.php">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-calendar-plus me-2"></i>Programar Cita Médica</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Paciente *</label>
                        <select name="paciente_id" class="form-select" required>
                            <?php if (empty($pacientes_list)): ?>
                                <option value="">-- No hay pacientes registrados. Primero cree uno en Pacientes --</option>
                            <?php else: ?>
                                <option value="">-- Seleccionar Paciente --</option>
                                <?php foreach ($pacientes_list as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['documento']) ?>)</option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Doctor / Especialista</label>
                        <select name="doctor_id" class="form-select">
                            <option value="0">-- Seleccionar Doctor --</option>
                            <?php foreach ($doctores_list as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nombre']) ?> (<?= htmlspecialchars($d['especialidad']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fecha *</label>
                        <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Hora *</label>
                        <input type="time" name="hora" class="form-control" value="09:00" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Motivo Consulta</label>
                        <textarea name="motivo" class="form-control" rows="2" placeholder="Describa el motivo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="agendar_cita_rapida" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>