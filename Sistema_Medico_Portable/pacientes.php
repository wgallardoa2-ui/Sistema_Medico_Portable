<?php
// pacientes.php
require_once 'config/database.php';
require_once 'includes/header.php';

$pdo = Database::connect();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_paciente'])) {
    $doc = trim($_POST['documento']);
    $nombre = trim($_POST['nombre']);
    $tel = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $nacimiento = $_POST['fecha_nacimiento'];
    $sangre = $_POST['sangre'];
    $alergias = trim($_POST['alergias']);

    try {
        $stmt = $pdo->prepare("INSERT INTO pacientes (documento, nombre, telefono, email, fecha_nacimiento, sangre, alergias) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$doc, $nombre, $tel, $email, $nacimiento, $sangre, $alergias]);
        $msg = '<div class="alert alert-success">Paciente registrado con éxito.</div>';
    } catch (PDOException $e) {
        $msg = '<div class="alert alert-danger">Error: El número de documento ya se encuentra registrado.</div>';
    }
}

$stmt = $pdo->query("SELECT * FROM pacientes ORDER BY id DESC");
$pacientes = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-user-injured text-primary me-2"></i>Gestión de Pacientes</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPaciente">
        <i class="fa-solid fa-user-plus me-1"></i> Nuevo Paciente
    </button>
</div>

<?= $msg ?>

<div class="card card-custom p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Teléfono</th>
                    <th>Tipo Sangre</th>
                    <th>Alergias</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pacientes)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No existen pacientes registrados en el sistema.</td></tr>
                <?php else: ?>
                    <?php foreach ($pacientes as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['documento']) ?></strong></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['telefono'] ?: 'N/A') ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($p['sangre'] ?: 'N/R') ?></span></td>
                            <td><small class="text-danger"><?= htmlspecialchars($p['alergias'] ?: 'Ninguna') ?></small></td>
                            <td>
                                <a href="historia.php?paciente_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-folder-open me-1"></i> Expediente
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalPaciente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="pacientes.php">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Registrar Nuevo Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Documento Identificación *</label>
                        <input type="text" name="documento" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Sangre</label>
                        <select name="sangre" class="form-select">
                            <option value="">Seleccione</option>
                            <option value="O+">O+</option><option value="O-">O-</option>
                            <option value="A+">A+</option><option value="A-">A-</option>
                            <option value="B+">B+</option><option value="B-">B-</option>
                            <option value="AB+">AB+</option><option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alergias Conocidas</label>
                        <textarea name="alergias" class="form-control" rows="2" placeholder="Describa alergias a medicamentos o alimentos..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_paciente" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Paciente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>