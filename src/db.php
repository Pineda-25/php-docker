<?php
$dbDir = __DIR__ . '/data';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$dbPath = $dbDir . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Tabla de historial de conversiones
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS conversiones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            monto_origen REAL NOT NULL,
            moneda_origen TEXT NOT NULL,
            monto_destino REAL NOT NULL,
            moneda_destino TEXT NOT NULL,
            tasa REAL NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . htmlspecialchars($e->getMessage()));
}
