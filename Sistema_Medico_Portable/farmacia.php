<?php
// farmacia.php
require_once 'config/database.php';
require_once 'includes/header.php';

$pdo = Database::connect();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_medicamento'])) {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $presentacion = trim($_POST['presentacion']);
    $stock = intval($_POST['stock']);
    $precio = floatval($_POST['precio']);

    try {
        $stmt = $pdo->prepare("INSERT INTO farmacia (codigo, nombre, presentacion, stock, precio) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $nombre, $presentacion, $stock, $precio]);
        $msg = '<div class="alert alert-success"><i class="fa-solid fa-circle-check me-1"></i> Medicamento agregado a farmacia.</div>';
    } catch (PDOException $e) {
        $msg = '<div class="alert alert-danger">Error: El código de medicamento ya existe.</div>';
    }
}

$medicamentos = $pdo->query("SELECT * FROM farmacia ORDER BY nombre ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-pills me-2"></i>Inventario de Farmacia</h4>
        <p class="text-muted small mb-0">Gestión de medicamentos y existencias</p>
    </div>
    <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalFarmacia">
        <i class="fa-solid fa-plus me-1"></i> Agregar Medicamento
    </button>
</div>

<?= $msg ?>

<div class="card card-custom p-4 bg-white shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Medicamento</th>
                    <th>Presentación</th>
                    <th>Precio ($)</th>
                    <th>Stock Disponibles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medicamentos as $m): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($m['codigo']) ?></code></td>
                        <td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($m['presentacion']) ?></td>
                        <td><strong class="text-success">$<?= number_format($m['precio'], 2) ?></strong></td>
                        <td>
                            <span class="badge bg-<?= $m['stock'] > 20 ? 'success' : 'danger' ?> fs-6">
                                <?= $m['stock'] ?> unidades
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalFarmacia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="farmacia.php">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-kit-medical me-2"></i>Nuevo Medicamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Código *</label>
                        <input type="text" name="codigo" class="form-control" placeholder="Ej: FAR-009" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre Medicamento *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Azitromicina 500mg" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Presentación</label>
                        <input type="text" name="presentacion" class="form-control" placeholder="Ej: Caja x 3 tabletas">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cantidad Stock *</label>
                        <input type="number" name="stock" class="form-control" value="50" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Precio ($) *</label>
                        <input type="number" step="0.01" name="precio" class="form-control" value="0.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="guardar_medicamento" class="btn btn-success fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Medicamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>