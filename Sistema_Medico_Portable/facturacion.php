<?php
// facturacion.php
require_once 'config/database.php';
require_once 'includes/header.php';

$pdo = Database::connect();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_factura'])) {
    $paciente_id = $_POST['paciente_id'];
    $monto = floatval($_POST['monto']);
    $concepto = trim($_POST['concepto']);

    $stmt = $pdo->prepare("INSERT INTO facturas (paciente_id, monto, concepto) VALUES (?, ?, ?)");
    $stmt->execute([$paciente_id, $monto, $concepto]);
    $msg = '<div class="alert alert-success"><i class="fa-solid fa-file-circle-check me-1"></i> Factura emitida correctamente.</div>';
}

$pacientes = $pdo->query("SELECT id, nombre, documento FROM pacientes ORDER BY nombre ASC")->fetchAll();
$facturas = $pdo->query("
    SELECT f.*, p.nombre as paciente_nombre, p.documento 
    FROM facturas f 
    JOIN pacientes p ON f.paciente_id = p.id 
    ORDER BY f.fecha DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-receipt me-2"></i>Módulo de Caja y Facturación</h4>
        <p class="text-muted small mb-0">Cobro de consultas, farmacia y servicios médicos</p>
    </div>
    <button class="btn btn-gradient-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalFactura">
        <i class="fa-solid fa-plus me-1"></i> Nueva Factura
    </button>
</div>

<?= $msg ?>

<div class="card card-custom p-4 bg-white shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nº Comprobante</th>
                    <th>Fecha</th>
                    <th>Paciente</th>
                    <th>Concepto de Pago</th>
                    <th>Total ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($facturas)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No se han registrado pagos en caja.</td></tr>
                <?php else: ?>
                    <?php foreach ($facturas as $f): ?>
                        <tr>
                            <td><code>FAC-<?= str_pad($f['id'], 5, '0', STR_PAD_LEFT) ?></code></td>
                            <td><?= $f['fecha'] ?></td>
                            <td><strong><?= htmlspecialchars($f['paciente_nombre']) ?></strong> <br><small class="text-muted">Doc: <?= htmlspecialchars($f['documento']) ?></small></td>
                            <td><?= htmlspecialchars($f['concepto']) ?></td>
                            <td><strong class="text-success fs-6">$<?= number_format($f['monto'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalFactura" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="facturacion.php">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Emitir Comprobante de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Paciente *</label>
                        <select name="paciente_id" class="form-select" required>
                            <option value="">-- Seleccionar Paciente --</option>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['documento']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Concepto de Cobro *</label>
                        <input type="text" name="concepto" class="form-control" placeholder="Ej: Consulta de Medicina General + Medicamentos" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Monto Total ($) *</label>
                        <input type="number" step="0.01" name="monto" class="form-control" placeholder="35.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="crear_factura" class="btn btn-success fw-bold"><i class="fa-solid fa-print me-1"></i> Emitir Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>