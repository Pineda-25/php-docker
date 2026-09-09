<?php
// Conexión simple a la base de datos SQLite
$dbDir = __DIR__ . '/data';
if (!is_dir($dbDir)) mkdir($dbDir, 0777, true);

$pdo = new PDO("sqlite:$dbDir/database.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Crear tabla si no existe
$pdo->exec("
    CREATE TABLE IF NOT EXISTS conversiones (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        monto REAL,
        de TEXT,
        a TEXT,
        resultado REAL,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
