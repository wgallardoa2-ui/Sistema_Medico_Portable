<?php
// config/database.php - Conexión Optimizada Singleton
class Database {
    private static $pdo = null;
    private static $dbFile = __DIR__ . '/citas_medicas.db';

    public static function connect() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            self::$pdo = new PDO("sqlite:" . self::$dbFile);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Evitar bloqueos de base de datos en SQLite
            self::$pdo->exec("PRAGMA busy_timeout = 5000;");

            self::initTables(self::$pdo);

            return self::$pdo;
        } catch (PDOException $e) {
            die("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }

    private static function initTables($pdo) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            nombre TEXT NOT NULL,
            rol TEXT NOT NULL,
            foto TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS doctores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            especialidad TEXT NOT NULL,
            telefono TEXT,
            email TEXT,
            exequatur TEXT,
            foto TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS pacientes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            documento TEXT UNIQUE NOT NULL,
            nombre TEXT NOT NULL,
            telefono TEXT,
            email TEXT,
            fecha_nacimiento DATE,
            sangre TEXT,
            alergias TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS citas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            paciente_id INTEGER NOT NULL,
            doctor_id INTEGER,
            fecha DATE NOT NULL,
            hora TIME NOT NULL,
            motivo TEXT,
            estado TEXT DEFAULT 'Pendiente',
            origen TEXT DEFAULT 'Presencial',
            FOREIGN KEY(paciente_id) REFERENCES pacientes(id),
            FOREIGN KEY(doctor_id) REFERENCES doctores(id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS historias_clinicas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cita_id INTEGER,
            paciente_id INTEGER NOT NULL,
            sintomas TEXT,
            diagnostico TEXT,
            tratamiento TEXT,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(paciente_id) REFERENCES pacientes(id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS farmacia (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT UNIQUE NOT NULL,
            nombre TEXT NOT NULL,
            presentacion TEXT,
            stock INTEGER DEFAULT 0,
            precio REAL DEFAULT 0.0
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS laboratorio (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            paciente_id INTEGER NOT NULL,
            tipo_examen TEXT NOT NULL,
            resultado TEXT,
            estado TEXT DEFAULT 'Pendiente',
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(paciente_id) REFERENCES pacientes(id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS facturas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            paciente_id INTEGER NOT NULL,
            monto REAL NOT NULL,
            concepto TEXT NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(paciente_id) REFERENCES pacientes(id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
            clave TEXT PRIMARY KEY,
            valor TEXT
        )");

        // Migración limpia de columnas adicionales si faltaran
        self::addColIfMissing($pdo, 'usuarios', 'foto', 'TEXT');
        self::addColIfMissing($pdo, 'doctores', 'foto', 'TEXT');
        self::addColIfMissing($pdo, 'citas', 'origen', "TEXT DEFAULT 'Presencial'");

        // Usuario Administrador inicial
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        if ($stmt->fetchColumn() == 0) {
            $pass = password_hash('admin123', PASSWORD_DEFAULT);
            $foto_def = 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?q=80&w=400&auto=format&fit=crop';
            $pdo->exec("INSERT INTO usuarios (usuario, password, nombre, rol, foto) VALUES ('admin', '$pass', 'Dr. Gallardo', 'admin', '$foto_def')");
        }

        // Configuración de Portadas e Institución
        $configs = [
            'foto_portada_login' => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?q=80&w=1000&auto=format&fit=crop',
            'foto_banner_online' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?q=80&w=1200&auto=format&fit=crop',
            'nombre_clinica' => 'Clínica de Especialidades Gallardo'
        ];

        $insCfg = $pdo->prepare("INSERT OR IGNORE INTO configuracion (clave, valor) VALUES (?, ?)");
        foreach ($configs as $k => $v) {
            $insCfg->execute([$k, $v]);
        }
    }

    private static function addColIfMissing($pdo, $table, $col, $type) {
        try {
            $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll();
            $exists = false;
            foreach ($cols as $c) {
                if ($c['name'] === $col) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $pdo->exec("ALTER TABLE $table ADD COLUMN $col $type");
            }
        } catch (Exception $e) {}
    }

    public static function getConfig($clave) {
        try {
            $pdo = self::connect();
            $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = ?");
            $stmt->execute([$clave]);
            $res = $stmt->fetch();
            return $res ? $res['valor'] : '';
        } catch (Exception $e) {
            return '';
        }
    }
}