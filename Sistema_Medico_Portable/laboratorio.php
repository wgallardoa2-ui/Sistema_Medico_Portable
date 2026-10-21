<?php
// laboratorio.php
require_once 'config/database.php';
require_once 'includes/header.php';

$pdo = Database::connect();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_examen'])) {
    $paciente_id = $_POST['paciente_id'];
    $tipo = trim($_POST['tipo_examen']);
    $resultado = trim($_POST['resultado']);

    $stmt = $pdo->prepare("INSERT INTO laboratorio (paciente_id, tipo_examen, resultado, estado) VALUES (?, ?, ?, 'Completado')");
    $stmt->execute([$paciente_id, $tipo, $resultado]);
    $msg = '<div class="alert alert-success">Examen de laboratorio registrado.</div>';
}

$pacientes = $pdo->query("SELECT id, nombre, documento FROM pacientes ORDER BY nombre ASC")->fetchAll();
$examenes = $pdo->query("
    SELECT l.*, p.nombre as paciente_nombre, p.documento 
    FROM laboratorio l 
    JOIN pacientes p ON l.paciente_id = p.id 
    ORDER BY l.fecha DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-vial-circle-check me-2"></i>Laboratorio Clínico</h4>
        <p class="text-muted small mb-0">Resultados de análisis clínicos e imágenes</p>
    </div>
    <button class="btn btn-gradient-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalLab">
        <i class="fa-solid fa-plus me-1"></i> Registrar Examen
    </button>
</div>

<?= $msg ?>

<div class="card card-custom p-4 bg-white shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Paciente</th>
                    <th>Tipo Examen</th>
                    <th>Resultado / Observación</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($examenes)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No existen exámenes de laboratorio registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($examenes as $ex): ?>
                        <tr>
                            <td><?= $ex['fecha'] ?></td>
                            <td><strong><?= htmlspecialchars($ex['paciente_nombre']) ?></strong> <br><small class="text-muted">Doc: <?= htmlspecialchars($ex['documento']) ?></small></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($ex['tipo_examen']) ?></span></td>
                            <td><?= htmlspecialchars($ex['resultado']) ?></td>
                            <td><span class="badge bg-success">Completado</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalLab" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="laboratorio.php">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-vial me-2"></i>Nuevo Examen Clínico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Paciente *</label>
                        <select name="paciente_id" class="form-select" required>
                            <option value="">-- Seleccione un Paciente --</option>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['documento']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Tipo de Análisis *</label>
                        <select name="tipo_examen" class="form-select" required>
                            <option value="Hemograma Completo">Hemograma Completo</option>
                            <option value="Perfil Lipídico">Perfil Lipídico</option>
                            <option value="Glucosa e Insulina">Glucosa e Insulina</option>
                            <option value="Examen General de Orina">Examen General de Orina</option>
                            <option value="Radiografía de Tórax">Radiografía de Tórax</option>
                            <option value="Ecografía Abdominal">Ecografía Abdominal</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Resultados y Valores *</label>
                        <textarea name="resultado" class="form-control" rows="3" placeholder="Ingresar parámetros obtenidos..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_examen" class="btn btn-primary fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Examen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>