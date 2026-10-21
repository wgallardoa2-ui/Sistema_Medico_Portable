<?php
// cita_online.php - Banner Nítido y Limpio
require_once 'config/database.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agendar_online'])) {
    $doc = trim($_POST['documento']);
    $nombre = trim($_POST['nombre']);
    $tel = trim($_POST['telefono']);
    $doctor_id = $_POST['doctor_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $motivo = trim($_POST['motivo']);

    try {
        $pdo = Database::connect();

        $stmtP = $pdo->prepare("SELECT id FROM pacientes WHERE documento = ?");
        $stmtP->execute([$doc]);
        $paciente = $stmtP->fetch();

        if ($paciente) {
            $paciente_id = $paciente['id'];
        } else {
            $insP = $pdo->prepare("INSERT INTO pacientes (documento, nombre, telefono) VALUES (?, ?, ?)");
            $insP->execute([$doc, $nombre, $tel]);
            $paciente_id = $pdo->lastInsertId();
        }

        $insC = $pdo->prepare("INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo, estado, origen) VALUES (?, ?, ?, ?, ?, 'Pendiente', 'Online')");
        $insC->execute([$paciente_id, $doctor_id, $fecha, $hora, $motivo]);

        $msg = '<div class="alert alert-success p-3 rounded-3 shadow border-0 bg-success text-white mb-4">
                    <i class="fa-solid fa-circle-check fa-lg me-2"></i>
                    <strong>¡Solicitud Registrada Con Éxito!</strong> Su cita ha sido enviada a recepción de la Clínica Gallardo. Le contactaremos en breve para confirmar.
                </div>';
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger p-3 rounded-3 shadow mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> Error al procesar su cita. Verifique los datos e intente de nuevo.
                </div>';
    }
}

$foto_banner = Database::getConfig('foto_banner_online') ?: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?q=80&w=1200&auto=format&fit=crop';
$nombre_clinica = Database::getConfig('nombre_clinica') ?: 'Clínica de Especialidades Gallardo';

$doctores = [];
try {
    $pdo = Database::connect();
    $doctores = $pdo->query("SELECT id, nombre, especialidad FROM doctores ORDER BY especialidad ASC")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Citas en Línea — <?= htmlspecialchars($nombre_clinica) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
        }
        .header-banner {
            background: url('<?= htmlspecialchars($foto_banner) ?>') center/cover no-repeat;
            position: relative;
            padding: 60px 0 90px 0;
            border-radius: 0 0 35px 35px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        .header-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(10, 37, 64, 0.55);
            border-radius: 0 0 35px 35px;
        }
        .banner-content {
            position: relative;
            z-index: 2;
            color: white;
        }
        .form-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.08);
            border: none;
            margin-top: -55px;
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body>

<div class="header-banner text-center">
    <div class="container banner-content">
        <span class="bg-white text-primary rounded-circle p-3 d-inline-flex mb-3 shadow">
            <i class="fa-solid fa-hospital-user fa-2x"></i>
        </span>
        <h2 class="fw-extrabold mb-1"><?= htmlspecialchars($nombre_clinica) ?></h2>
        <p class="opacity-90 mb-0 fs-5">Portal Oficial de Reserva y Citas Médicas en Línea</p>
    </div>
</div>

<div class="container py-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">
            
            <div class="form-card p-4 p-md-5">
                
                <?= $msg ?>

                <form method="POST" action="cita_online.php">
                    <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                        <span class="bg-primary text-white p-2 rounded-circle me-3 shadow-sm">
                            <i class="fa-solid fa-user-pen"></i>
                        </span>
                        <div>
                            <h5 class="fw-bold mb-0 text-primary">Datos del Paciente y la Consulta</h5>
                            <small class="text-muted">Ingrese su información y seleccione el especialista deseado</small>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Cédula / Documento *</label>
                            <input type="text" name="documento" class="form-control py-2 fw-semibold" placeholder="Ej: 1712345678" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Nombre Completo *</label>
                            <input type="text" name="nombre" class="form-control py-2 fw-semibold" placeholder="Ej: Juan Pérez" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Teléfono de Contacto *</label>
                            <input type="text" name="telefono" class="form-control py-2 fw-semibold" placeholder="Ej: 0991234567" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Especialista / Doctor *</label>
                            <select name="doctor_id" class="form-select py-2 fw-semibold" required>
                                <option value="">-- Seleccionar Doctor --</option>
                                <?php foreach ($doctores as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nombre']) ?> — <?= htmlspecialchars($d['especialidad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Fecha Deseada *</label>
                            <input type="date" name="fecha" class="form-control py-2 fw-semibold" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Hora Preferida *</label>
                            <input type="time" name="hora" class="form-control py-2 fw-semibold" value="09:00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">Motivo de la Consulta</label>
                            <textarea name="motivo" class="form-control fw-semibold" rows="3" placeholder="Describa brevemente sus síntomas o requerimiento médico..."></textarea>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" name="agendar_online" class="btn btn-primary btn-lg w-100 fw-bold py-3 rounded-3 shadow" style="background: linear-gradient(135deg, #0d6efd 0%, #00c6ff 100%); border:none;">
                                <i class="fa-solid fa-paper-plane me-2"></i> CONFIRMAR CITA MÉDICA
                            </button>
                        </div>
                    </div>
                </form>

            </div>

        </div>
    </div>
</div>

</body>
</html>