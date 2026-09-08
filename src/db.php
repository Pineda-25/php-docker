<?php
// Configuración y conexión a la base de datos SQL (SQLite vía PDO)
$dbDir = __DIR__ . '/data';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

$dbPath = $dbDir . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Tabla 1: Historial de Conversiones de Divisas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS conversiones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            monto_origen REAL NOT NULL,
            moneda_origen TEXT NOT NULL,
            monto_destino REAL NOT NULL,
            moneda_destino TEXT NOT NULL,
            tasa REAL NOT NULL,
            comision_porcentaje REAL DEFAULT 0,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Tabla 2: Movimientos del Presupuesto (Ingresos y Gastos)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS movimientos_presupuesto (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            descripcion TEXT NOT NULL,
            tipo TEXT NOT NULL CHECK(tipo IN ('ingreso', 'gasto')),
            categoria TEXT NOT NULL,
            monto REAL NOT NULL,
            fecha DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

} catch (PDOException $e) {
    die("Error crítico de conexión a la Base de Datos SQL: " . htmlspecialchars($e->getMessage()));
}
