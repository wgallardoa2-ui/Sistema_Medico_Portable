<?php
// index.php - Login con Cachorrito Fluffy que jala el panel desde fuera de la pantalla
require_once 'config/database.php';
session_start();

$error = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit();
}

$foto_portada = Database::getConfig('foto_portada_login') ?: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?q=80&w=1000&auto=format&fit=crop';$nombre_clinica = Database::getConfig('nombre_clinica') ?: 'Clínica de Especialidades Gallardo';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        try {
            $pdo = Database::connect();
            $stmt =$pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $user =$stmt->fetch();

            if ($user && password_verify($password,$user['password'])) {
                $_SESSION['usuario_id'] =$user['id'];
                $_SESSION['usuario_nombre'] =$user['nombre'];
                $_SESSION['usuario_rol'] =$user['rol'];
                $_SESSION['usuario_foto'] =$user['foto'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Exception $e) {$error = 'Error de conexión con la base de datos.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema — <?= htmlspecialchars($nombre_clinica) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #07182c 0%, #0d3b66 50%, #1e40af 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px 10px;
            overflow-x: hidden;
        }

        /* ESCENARIO PRINCIPAL: COMPLETAMENTE OCULTO FUERA DE LA PANTALLA INICIALMENTE */
        .stage-assembly {
            position: relative;
            max-width: 960px;
            width: 100%;
            opacity: 0;
            transform: translateX(110vw); /* Totalmente invisible al cargar, pantalla 100% azul */
        }

        /* Animación suave y progresiva de arrastre (4.8 segundos) */
        .stage-assembly.start-pulling {
            opacity: 1;
            animation: pullFromFarEdge 4.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes pullFromFarEdge {
            0% {
                transform: translateX(110vw);
            }
            75% {
                transform: translateX(-18px); /* Amortiguación elástica de parada */
            }
            88% {
                transform: translateX(6px);
            }
            100% {
                transform: translateX(0);
            }
        }

        .login-card {
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 10;
            overflow: visible !important;
        }

        .side-banner {
            background: url('<?= htmlspecialchars($foto_portada) ?>') center/cover no-repeat;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 35px 30px;
            min-height: 100%;
            border-radius: 28px 0 0 28px;
            overflow: hidden;
        }

        .side-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(180deg, rgba(7, 24, 44, 0.25) 0%, rgba(7, 24, 44, 0.85) 100%);
            z-index: 1;
        }

        .side-banner-content {
            position: relative;
            z-index: 2;
        }

        /* 🐶 PERSONAJE CACHORRITO TIERNO Y AFELPADO UBICADO EN LA ESQUINA IZQUIERDA */
        .puppy-mascot-box {
            position: absolute;
            left: -155px; /* ESQUINA IZQUIERDA */
            bottom: -5px;
            width: 165px;
            height: 220px;
            z-index: 35;
            cursor: pointer;
        }

        @media (max-width: 991px) {
            .puppy-mascot-box {
                position: relative;
                left: auto;
                bottom: auto;
                margin: 0 auto -20px auto;
            }
        }

        .puppy-svg {
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        /* CUERDA EN EL HOCICO SE DESVANECES TRAS ENCAJAR (4.8s) */
        .rope-mouth {
            stroke: #d97706;
            stroke-width: 4.5;
            stroke-dasharray: 6 3;
            animation: hideRopeSlow 0.3s 4.8s forwards;
        }

        @keyframes hideRopeSlow {
            to { opacity: 0; visibility: hidden; }
        }

        /* MOVIMIENTO PASO A PASO DE LAS 4 PATITAS DURANTE LA ENTRADA */
        .leg-front-l, .leg-back-r {
            transform-origin: top center;
            animation: legWalkingStepA 0.4s infinite ease-in-out alternate;
        }
        .leg-front-r, .leg-back-l {
            transform-origin: top center;
            animation: legWalkingStepB 0.4s infinite ease-in-out alternate;
        }

        @keyframes legWalkingStepA {
            0% { transform: rotate(-22deg); }
            100% { transform: rotate(22deg); }
        }
        @keyframes legWalkingStepB {
            0% { transform: rotate(22deg); }
            100% { transform: rotate(-22deg); }
        }

        /* DETENER PASOS AL CENTRARSE Y ACOMODARSE */
        .arrived .leg-front-l, .arrived .leg-front-r, .arrived .leg-back-l, .arrived .leg-back-r {
            animation: none !important;
            transform: rotate(0deg);
        }

        /* MENEANDO LA COLITA 🐕 (ALEGRE Y PERPETUA) */
        .puppy-tail {
            transform-origin: 20px 115px;
            animation: wagTailContinuous 0.4s infinite ease-in-out alternate;
        }

        @keyframes wagTailContinuous {
            0% { transform: rotate(-28deg); }
            100% { transform: rotate(32deg); }
        }

        /* BALANCEO NATURAL DEL CUERPO AL JALAR EL PESO */
        .puppy-body-group {
            transform-origin: 90px 130px;
            animation: puppyDraggingMotion 4.8s ease-in-out forwards, puppyBreatheIdle 3s 4.8s infinite ease-in-out;
        }

        @keyframes puppyDraggingMotion {
            0% { transform: translateY(-4px) rotate(-5deg); }
            20% { transform: translateY(0) rotate(2deg); }
            40% { transform: translateY(-5px) rotate(-6deg); }
            60% { transform: translateY(-2px) rotate(3deg); }
            80% { transform: translateY(-4px) rotate(-4deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        @keyframes puppyBreatheIdle {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02, 0.98) translateY(-2px); }
        }

        /* PARPADEO Y BRILLO DE OJOS TIERNOS */
        .eye-pupil {
            transform-origin: center;
            animation: blinkPupils 4s infinite;
            transition: transform 0.15s ease-out;
        }

        @keyframes blinkPupils {
            0%, 94%, 98%, 100% { transform: scaleY(1); }
            96% { transform: scaleY(0.1); }
        }

        /* OREJITAS AFELPADAS */
        .ear-l {
            transform-origin: 75px 45px;
            animation: wiggleEarL 3.5s infinite ease-in-out;
        }
        .ear-r {
            transform-origin: 125px 45px;
            animation: wiggleEarR 3.5s infinite ease-in-out;
        }

        @keyframes wiggleEarL {
            0%, 85%, 100% { transform: rotate(0deg); }
            92% { transform: rotate(-14deg); }
        }
        @keyframes wiggleEarR {
            0%, 85%, 100% { transform: rotate(0deg); }
            92% { transform: rotate(14deg); }
        }

        /* PATITAS CUBRIÉNDOSE LOS OJOS (AL TOCAR CONTRASEÑA 🙈) */
        .paw-cover {
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            transform-origin: bottom center;
        }

        .covering-eyes .paw-cover-l {
            transform: translate(28px, -55px) rotate(32deg);
        }
        .covering-eyes .paw-cover-r {
            transform: translate(-28px, -55px) rotate(-32deg);
        }

        /* SALTO ALEGRE AL TOCAR AL CACHORRITO */
        .happy-bounce {
            animation: puppyJump 0.5s ease-in-out;
        }

        @keyframes puppyJump {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-22px) rotate(-3deg); }
        }
    </style>
</head>
<body>

<!-- ESCENARIO COMPLETO QUE VIENE JALADO DESDE AFUERA DE LA PANTALLA -->
<div class="stage-assembly" id="stageAssembly">

    <!-- TARJETA CONTENEDORA PRINCIPAL DEL LOGIN -->
    <div class="row justify-content-center align-items-stretch g-0 login-card">
        
        <!-- 🐶 CACHORRITO FLUFFY BEAGLE/GOLDEN EN 4 PATAS (UBICADO EN LA ESQUINA IZQUIERDA) -->
        <div class="puppy-mascot-box" id="dogContainer" title="¡Hazme clic para saludar!">
            <svg class="puppy-svg" viewBox="0 0 190 210" xmlns="http://www.w3.org/2000/svg">
                
                <defs>
                    <linearGradient id="bodyGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#f6b158" />
                        <stop offset="100%" stop-color="#e59333" />
                    </linearGradient>
                    <linearGradient id="darkFurGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#2d2118" />
                        <stop offset="100%" stop-color="#19110b" />
                    </linearGradient>
                    <linearGradient id="earGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#c87422" />
                        <stop offset="100%" stop-color="#9d520f" />
                    </linearGradient>
                </defs>

                <!-- Cuerdita amarrada de la boquita al recuadro -->
                <path class="rope-mouth" d="M 135 78 C 158 80, 175 100, 205 118" fill="none" />

                <!-- Colita Peluda Moviéndose -->
                <path class="puppy-tail" d="M 18 122 C 0 100, -5 65, 14 52 C 24 62, 18 95, 26 118 Z" fill="url(#bodyGrad)" />
                <circle cx="12" cy="54" r="7" fill="#ffffff" /> <!-- Punta blanca tierno -->

                <!-- GRUPO DEL CUERPO DEL CACHORRITO DE 4 PATAS -->
                <g class="puppy-body-group">
                    
                    <!-- PATA TRASERA IZQUIERDA -->
                    <g class="leg-back-l">
                        <path d="M 32 140 C 25 155, 25 175, 30 185 C 38 185, 45 175, 42 155 Z" fill="url(#bodyGrad)" />
                        <ellipse cx="33" cy="186" rx="9" ry="6" fill="#ffffff" />
                    </g>

                    <!-- PATA TRASERA DERECHA -->
                    <g class="leg-back-r">
                        <path d="M 58 140 C 52 155, 52 175, 56 185 C 64 185, 70 175, 68 155 Z" fill="url(#bodyGrad)" />
                        <ellipse cx="59" cy="186" rx="9" ry="6" fill="#ffffff" />
                    </g>

                    <!-- LOMO / MANCHA OSCURA ESTILO BEAGLE EN LA ESPALDA -->
                    <path d="M 28 115 C 50 95, 110 98, 138 115 C 130 162, 40 165, 28 115 Z" fill="url(#bodyGrad)" />
                    <path d="M 45 110 C 65 102, 95 102, 112 112 C 105 142, 55 145, 45 110 Z" fill="url(#darkFurGrad)" />

                    <!-- PECHITO Y BARRIGUITA PELUDA BLANCA -->
                    <path d="M 68 118 Q 98 110 128 120 C 122 160, 78 162, 62 150 Z" fill="#ffffff" />
                    <path d="M 92 120 C 88 135, 105 135, 102 120" stroke="#2563eb" stroke-width="3" fill="none" stroke-linecap="round" />
                    <circle cx="97" cy="138" r="5" fill="#94a3b8" stroke="#1e293b" stroke-width="1.5" />

                    <!-- PATA DELANTERA IZQUIERDA -->
                    <g class="leg-front-l">
                        <path d="M 98 140 L 96 182 C 96 188, 110 188, 112 182 L 114 140 Z" fill="url(#bodyGrad)" />
                        <ellipse cx="104" cy="186" rx="9" ry="6" fill="#ffffff" />
                    </g>

                    <!-- PATA DELANTERA DERECHA -->
                    <g class="leg-front-r">
                        <path d="M 122 140 L 120 182 C 120 188, 134 188, 136 182 L 138 140 Z" fill="url(#bodyGrad)" />
                        <ellipse cx="128" cy="186" rx="9" ry="6" fill="#ffffff" />
                    </g>

                    <!-- CABEZA TIERNA DEL CACHORRITO CON DETALLES REDONDEADOS -->
                    <g id="dogHeadGroup">
                        <!-- OREJITA IZQUIERDA CAÍDA -->
                        <path class="ear-l" d="M 68 48 C 42 55, 36 92, 58 106 C 70 98, 70 65, 68 48 Z" fill="url(#earGrad)" />
                        
                        <!-- OREJITA DERECHA CAÍDA -->
                        <path class="ear-r" d="M 126 48 C 152 55, 158 92, 136 106 C 124 98, 124 65, 126 48 Z" fill="url(#earGrad)" />

                        <!-- FORMA REDONDA Y AFELPADA DE LA CABEZA -->
                        <ellipse cx="97" cy="62" rx="33" ry="29" fill="url(#bodyGrad)" />
                        <!-- Franja blanca frontal tierno en la frente -->
                        <path d="M 92 34 C 95 34, 99 34, 102 34 L 104 68 C 97 75, 90 75, 90 68 Z" fill="#ffffff" />

                        <!-- GORRITO DE DOCTOR CON CRUZ ROJA -->
                        <rect x="80" y="20" width="34" height="18" rx="5" fill="#ffffff" stroke="#2563eb" stroke-width="2" />
                        <path d="M 97 24 L 97 34 M 92 29 L 102 29" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" />

                        <!-- HOCICO Y NARIZ DE CACHORRO -->
                        <ellipse cx="97" cy="74" rx="16" ry="12" fill="#fff3e0" />
                        <path d="M 90 68 C 93 64, 101 64, 104 68 C 104 73, 90 73, 90 68 Z" fill="#19110b" />
                        <path d="M 93 80 C 93 88, 101 88, 101 80 Z" fill="#f43f5e" /> <!-- Lengüita sonriente -->

                        <!-- OJOS GRANDES BRILLANTES Y TIERNOS -->
                        <g id="eyesGroup">
                            <circle cx="81" cy="58" r="6" fill="#19110b" />
                            <circle class="eye-pupil" id="pupilL" cx="81" cy="58" r="3.5" fill="#0f172a" />
                            <circle cx="79" cy="56" r="1.8" fill="#ffffff" />
                            <circle cx="83" cy="60" r="0.8" fill="#ffffff" />

                            <circle cx="113" cy="58" r="6" fill="#19110b" />
                            <circle class="eye-pupil" id="pupilR" cx="113" cy="58" r="3.5" fill="#0f172a" />
                            <circle cx="111" cy="56" r="1.8" fill="#ffffff" />
                            <circle cx="115" cy="60" r="0.8" fill="#ffffff" />
                        </g>
                    </g>

                    <!-- PATITAS PARA CUBRIRSE LOS OJOS (🙈 CONTRASEÑA) -->
                    <g class="paw-cover paw-cover-l">
                        <ellipse cx="68" cy="142" rx="9" ry="14" fill="url(#bodyGrad)" stroke="#c87422" stroke-width="1.5" />
                        <ellipse cx="68" cy="136" rx="7" ry="5" fill="#ffffff" />
                    </g>
                    <g class="paw-cover paw-cover-r">
                        <ellipse cx="128" cy="142" rx="9" ry="14" fill="url(#bodyGrad)" stroke="#c87422" stroke-width="1.5" />
                        <ellipse cx="128" cy="136" rx="7" ry="5" fill="#ffffff" />
                    </g>

                </g>
            </svg>
        </div>

        <!-- Lado Izquierdo: Banner Institucional -->
        <div class="col-lg-5 d-none d-lg-block p-0">
            <div class="side-banner">
                <div class="side-banner-content">
                    <span class="badge bg-warning text-dark fw-bold mb-2 py-2 px-3 shadow-sm">
                        <i class="fa-solid fa-hospital-user me-1"></i> Staff Médico Gallardo
                    </span>
                    <h3 class="fw-extrabold text-white mb-2"><?= htmlspecialchars($nombre_clinica) ?></h3>
                    <p class="small text-white-50 leading-relaxed mb-0">
                        Sistema integral de historias clínicas, consultas especializadas, laboratorio y farmacia.
                    </p>
                </div>

                <div class="side-banner-content">
                    <div class="p-3 rounded-3 text-white" style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3);">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-stethoscope fa-2x me-3 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Atención Especializada</h6>
                                <small class="text-white-50">Consultas Presenciales y en Línea</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lado Derecho: Formulario de Acceso al Sistema -->
        <div class="col-md-8 col-lg-7 p-4 p-md-5 bg-white position-relative" style="border-radius: 0 28px 28px 0;">
            
            <div class="text-center mb-4">
                <h4 class="fw-extrabold text-primary mb-0">Acceso al Sistema</h4>
                <p class="text-muted small">Ingrese sus credenciales de especialista</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small text-center fw-bold rounded-3 mb-3">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Usuario Médico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-user-doctor text-primary"></i></span>
                        <input type="text" id="userInput" name="usuario" class="form-control bg-light border-0 py-2 fw-semibold" placeholder="Ej: admin" required autofocus autocomplete="off">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-key text-primary"></i></span>
                        <input type="password" id="passInput" name="password" class="form-control bg-light border-0 py-2 fw-semibold" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" name="login" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow" style="background: linear-gradient(135deg, #0d6efd 0%, #00c6ff 100%); border:none;">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> INGRESAR AL SISTEMA
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <a href="cita_online.php" target="_blank" class="btn btn-outline-success btn-sm fw-bold py-2 rounded-3">
                    <i class="fa-solid fa-globe me-1"></i> Portal Citas Web
                </a>
                <button type="button" onclick="location.reload();" class="btn btn-link text-muted btn-sm fw-semibold text-decoration-none">
                    <i class="fa-solid fa-rotate-right me-1"></i> Ver Animación Otra Vez 🔄
                </button>
            </div>

        </div>

    </div>
</div>

<script>
// CONTROLADORES DE ANIMACIÓN E INTERACTIVIDAD
const stageAssembly = document.getElementById('stageAssembly');
const dogContainer = document.getElementById('dogContainer');
const userInput = document.getElementById('userInput');
const passInput = document.getElementById('passInput');
const pupilL = document.getElementById('pupilL');
const pupilR = document.getElementById('pupilR');
const dogHeadGroup = document.getElementById('dogHeadGroup');

window.addEventListener('DOMContentLoaded', () => {
    // 1. Inicia el despliegue del escenario desde la esquina oculta
    setTimeout(() => {
        stageAssembly.classList.add('start-pulling');
    }, 150);

    // 2. Al encajar en el centro (~4.8s), detiene los pasos y habilita la caja de texto
    setTimeout(() => {
        dogContainer.classList.add('arrived');
        userInput.focus();
    }, 4800);
});

// 3. Taparse los ojitos al tocar la contraseña 🙈
passInput.addEventListener('focus', () => {
    dogContainer.classList.add('covering-eyes');
});

passInput.addEventListener('blur', () => {
    dogContainer.classList.remove('covering-eyes');
});

// 4. Seguir el texto con la miradita al escribir el usuario 👁️
userInput.addEventListener('input', (e) => {
    const textLength = e.target.value.length;
    const moveX = Math.min(Math.max((textLength - 5) * 0.7, -4), 4);
    
    pupilL.style.transform = `translate(${moveX}px, 2px)`;
    pupilR.style.transform = `translate(${moveX}px, 2px)`;
    dogHeadGroup.style.transform = `rotate(${moveX * 0.8}deg)`;
});

userInput.addEventListener('blur', () => {
    pupilL.style.transform = 'translate(0, 0)';
    pupilR.style.transform = 'translate(0, 0)';
    dogHeadGroup.style.transform = 'rotate(0deg)';
});

// 5. Salto de alegría al presionar el cachorrito 🐶
dogContainer.addEventListener('click', () => {
    dogContainer.classList.add('happy-bounce');
    setTimeout(() => {
        dogContainer.classList.remove('happy-bounce');
    }, 500);
});
</script>

</body>
</html>