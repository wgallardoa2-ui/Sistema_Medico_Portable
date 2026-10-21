<?php
// historia.php
require_once 'config/database.php';
require_once 'includes/header.php';

$pdo = Database::connect();
$paciente_id = $_GET['paciente_id'] ?? null;
$cita_id = $_GET['cita_id'] ?? null;
$msg = '';

if (!$paciente_id) {
    header("Location: pacientes.php");
    exit();
}

$stmtP = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
$stmtP->execute([$paciente_id]);
$paciente = $stmtP->fetch();

if (!$paciente) {
    die("Paciente no encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_consulta'])) {
    $sintomas = trim($_POST['sintomas']);
    $diagnostico = trim($_POST['diagnostico']);
    $tratamiento = trim($_POST['tratamiento']);

    $stmt = $pdo->prepare("INSERT INTO historias_clinicas (cita_id, paciente_id, sintomas, diagnostico, tratamiento) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$cita_id, $paciente_id, $sintomas, $diagnostico, $tratamiento]);

    if ($cita_id) {
        $updateCita = $pdo->prepare("UPDATE citas SET estado = 'Atendido' WHERE id = ?");
        $updateCita->execute([$cita_id]);
    }

    $msg = '<div class="alert alert-success"><i class="fa-solid fa-check-circle me-1"></i> Consulta guardada en la historia clínica del paciente.</div>';
}

$stmtH = $pdo->prepare("SELECT * FROM historias_clinicas WHERE paciente_id = ? ORDER BY fecha DESC");
$stmtH->execute([$paciente_id]);
$consultasAnteriores = $stmtH->fetchAll();
?>

<div class="mb-4">
    <a href="pacientes.php" class="text-decoration-none text-muted"><i class="fa-solid fa-arrow-left me-1"></i> Volver a Pacientes</a>
    <h4 class="fw-bold mt-2"><i class="fa-solid fa-file-medical text-primary me-2"></i>Expediente Médico</h4>
</div>

<?= $msg ?>

<div class="card card-custom p-4 bg-white mb-4">
    <div class="row">
        <div class="col-md-3"><strong>Nombre:</strong> <br><?= htmlspecialchars($paciente['nombre']) ?></div>
        <div class="col-md-3"><strong>Documento:</strong> <br><?= htmlspecialchars($paciente['documento']) ?></div>
        <div class="col-md-2"><strong>Sangre:</strong> <br><span class="badge bg-info text-dark"><?= htmlspecialchars($paciente['sangre'] ?: 'N/R') ?></span></div>
        <div class="col-md-4"><strong>Alergias:</strong> <br><span class="text-danger"><?= htmlspecialchars($paciente['alergias'] ?: 'Ninguna') ?></span></div>
    </div>
</div>

<div class="card card-custom p-4 bg-white mb-4">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-notes-medical text-success me-2"></i>Registrar Nueva Consulta / Atender</h5>
    <form method="POST">
        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label fw-bold">Síntomas presentados</label>
                <textarea name="sintomas" class="form-control" rows="2" placeholder="Descripción breve de los síntomas..." required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Diagnóstico Médico *</label>
                <textarea name="diagnostico" class="form-control" rows="3" placeholder="Diagnóstico clínico..." required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Tratamiento / Receta Médica *</label>
                <textarea name="tratamiento" class="form-control" rows="3" placeholder="Medicamentos y dosis..." required></textarea>
            </div>
            <div class="col-12 text-end">
                <button type="submit" name="guardar_consulta" class="btn btn-success px-4 fw-bold">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar en Historia Clínica
                </button>
            </div>
        </div>
    </form>
</div>

<div class="card card-custom p-4 bg-white">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-secondary"></i>Historial de Consultas Médicas</h5>
    <?php if (empty($consultasAnteriores)): ?>
        <p class="text-muted mb-0">El paciente no tiene registros de consultas previas.</p>
    <?php else: ?>
        <div class="accordion" id="accordionHistoria">
            <?php foreach ($consultasAnteriores as $idx => $h): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $idx !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#c<?= $h['id'] ?>">
                            <i class="fa-solid fa-calendar-day me-2 text-primary"></i> Consulta realizada el: <?= $h['fecha'] ?>
                        </button>
                    </h2>
                    <div id="c<?= $h['id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#accordionHistoria">
                        <div class="accordion-body">
                            <p><strong>Síntomas:</strong> <?= htmlspecialchars($h['sintomas']) ?></p>
                            <p><strong>Diagnóstico:</strong> <?= htmlspecialchars($h['diagnostico']) ?></p>
                            <p class="mb-0"><strong>Tratamiento / Receta:</strong> <span class="bg-light d-block p-2 rounded text-dark mt-1"><?= nl2br(htmlspecialchars($h['tratamiento'])) ?></span></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>