<?php
// doctores.php
require_once 'config/database.php';
require_once 'includes/header.php';

$pdo = Database::connect();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_doctor'])) {
    $nombre = trim($_POST['nombre']);
    $especialidad = trim($_POST['especialidad']);
    $tel = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $exequatur = trim($_POST['exequatur']);

    $stmt = $pdo->prepare("INSERT INTO doctores (nombre, especialidad, telefono, email, exequatur) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $especialidad, $tel, $email, $exequatur]);
    $msg = '<div class="alert alert-success"><i class="fa-solid fa-circle-check me-1"></i> Doctor registrado correctamente.</div>';
}

$doctores = $pdo->query("SELECT * FROM doctores ORDER BY especialidad ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-user-doctor me-2"></i>Cuerpo Médico Especializado</h4>
        <p class="text-muted small mb-0">Directorio de especialistas de la clínica</p>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalDoctor">
        <i class="fa-solid fa-user-plus me-1"></i> Registrar Doctor
    </button>
</div>

<?= $msg ?>

<div class="row g-4">
    <?php foreach ($doctores as $doc): ?>
        <div class="col-md-6 col-lg-3">
            <div class="card card-custom p-3 bg-white h-100 text-center border-top border-4 border-primary shadow-sm">
                <div class="mb-3">
                    <span class="bg-light text-primary rounded-circle p-3 d-inline-flex">
                        <i class="fa-solid fa-user-md fa-2x"></i>
                    </span>
                </div>
                <h6 class="fw-bold mb-1"><?= htmlspecialchars($doc['nombre']) ?></h6>
                <span class="badge bg-primary text-white mb-2"><?= htmlspecialchars($doc['especialidad']) ?></span>
                <p class="small text-muted mb-1"><i class="fa-solid fa-id-card me-1"></i> Cod: <?= htmlspecialchars($doc['exequatur'] ?: 'N/R') ?></p>
                <p class="small text-muted mb-0"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($doc['telefono'] ?: 'Sin Teléfono') ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="modalDoctor" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="doctores.php">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-user-md me-2"></i>Registrar Nuevo Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Nombre Completo *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Dr. Fernando Torres" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Especialidad *</label>
                        <select name="especialidad" class="form-select" required>
                            <option value="Medicina General">Medicina General</option>
                            <option value="Pediatría">Pediatría</option>
                            <option value="Cardiología">Cardiología</option>
                            <option value="Traumatología">Traumatología</option>
                            <option value="Ginecología">Ginecología</option>
                            <option value="Dermatología">Dermatología</option>
                            <option value="Oftalmología">Oftalmología</option>
                            <option value="Neurología">Neurología</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Código / Exequatur</label>
                        <input type="text" name="exequatur" class="form-control" placeholder="Ej: MED-999">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_doctor" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>