<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

$nombre_clinica = 'Clínica Gallardo';
$foto_usuario = $_SESSION['usuario_foto'] ?? '';

try {
    $pdo = Database::connect();
    $nombre_clinica = Database::getConfig('nombre_clinica') ?: $nombre_clinica;

    if (empty($foto_usuario) && isset($_SESSION['usuario_id'])) {
        $stU = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
        $stU->execute([$_SESSION['usuario_id']]);
        $u = $stU->fetch();
        $foto_usuario = $u['foto'] ?? '';
        $_SESSION['usuario_foto'] = $foto_usuario;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nombre_clinica) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }
        body {
            background: #f4f8fb;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #0a3d62 0%, #0d6efd 100%);
            box-shadow: 0 4px 18px rgba(13, 110, 253, 0.25);
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .navbar-brand {
            font-size: 1.1rem;
            white-space: nowrap;
        }
        .nav-link {
            font-size: 0.88rem;
            font-weight: 600;
            padding-left: 10px !important;
            padding-right: 10px !important;
            white-space: nowrap;
            transition: all 0.2s ease-in-out;
        }
        .nav-link:hover {
            color: #ffd166 !important;
            transform: translateY(-1px);
        }
        .card-custom {
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            border: none;
        }
        main {
            flex: 1;
        }
        .user-avatar-nav {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        .user-chip {
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(6px);
            font-size: 0.82rem;
        }
    </style>
</head>
<body>

<?php if (isset($_SESSION['usuario_id'])): ?>
<nav class="navbar navbar-expand-xl navbar-dark navbar-custom mb-4">
    <div class="container-fluid px-3 px-lg-4">
        
        <a class="navbar-brand d-flex align-items-center fw-extrabold me-3" href="dashboard.php">
            <span class="bg-white text-primary rounded-circle me-2 d-inline-flex align-items-center justify-content-center shadow-sm" style="width:38px; height:38px;">
                <i class="fa-solid fa-hospital-user text-primary"></i>
            </span>
            <span class="d-none d-sm-inline"><?= htmlspecialchars($nombre_clinica) ?></span>
            <span class="d-inline d-sm-none">Clínica Gallardo</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-xl-0 align-items-xl-center">
                <li class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php"><i class="fa-solid fa-chart-pie me-1"></i> Panel</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="pacientes.php"><i class="fa-solid fa-hospital-user me-1"></i> Pacientes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="doctores.php"><i class="fa-solid fa-user-doctor me-1"></i> Doctores</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="citas.php"><i class="fa-solid fa-calendar-check me-1"></i> Citas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="farmacia.php"><i class="fa-solid fa-pills me-1"></i> Farmacia</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="laboratorio.php"><i class="fa-solid fa-vial me-1"></i> Laboratorio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="facturacion.php"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Caja</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="configuracion.php"><i class="fa-solid fa-gears me-1"></i> Ajustes</a>
                </li>
            </ul>

            <div class="d-flex align-items-center flex-wrap gap-2 mt-2 mt-xl-0 flex-shrink-0">
                <div class="user-chip text-white rounded-pill px-2 py-1 d-flex align-items-center">
                    <?php if ($foto_usuario): ?>
                        <img src="<?= htmlspecialchars($foto_usuario) ?>" class="user-avatar-nav me-2">
                    <?php else: ?>
                        <span class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width:28px; height:28px; font-size:12px;">
                            <i class="fa-solid fa-user-doctor"></i>
                        </span>
                    <?php endif; ?>
                    <span class="fw-bold pe-2"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></span>
                </div>

                <a href="cita_online.php" target="_blank" class="btn btn-warning btn-sm fw-bold px-2 py-1 shadow-sm text-nowrap">
                    <i class="fa-solid fa-globe me-1"></i> Citas Web
                </a>

                <a href="index.php?logout=1" class="btn btn-danger btn-sm fw-bold px-3 py-1 shadow-sm text-nowrap">
                    <i class="fa-solid fa-right-from-bracket me-1"></i> Salir
                </a>
            </div>
        </div>

    </div>
</nav>
<?php endif; ?>

<main class="container-fluid px-3 px-lg-4 my-3">