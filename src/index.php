<?php
require_once __DIR__ . '/db.php';

// Tasas de cambio de mercado referenciales (Base: 1 USD)
$tasas = [
    'USD' => ['nombre' => 'Dólar Estadounidense (USD)', 'tasa' => 1.00, 'simbolo' => '$'],
    'PEN' => ['nombre' => 'Sol Peruano (PEN)', 'tasa' => 3.70, 'simbolo' => 'S/'], // 1 USD = 3.70 PEN (1 PEN ≈ 0.27 USD)
    'EUR' => ['nombre' => 'Euro (EUR)', 'tasa' => 0.86, 'simbolo' => '€'],         // 1 USD = 0.86 EUR (1 EUR ≈ 1.16 USD)
    'GBP' => ['nombre' => 'Libra Esterlina (GBP)', 'tasa' => 0.76, 'simbolo' => '£'], // 1 USD = 0.76 GBP (1 GBP ≈ 1.31 USD)
    'MXN' => ['nombre' => 'Peso Mexicano (MXN)', 'tasa' => 19.50, 'simbolo' => '$'],
    'COP' => ['nombre' => 'Peso Colombiano (COP)', 'tasa' => 4050.00, 'simbolo' => '$'],
    'ARS' => ['nombre' => 'Peso Argentino (ARS)', 'tasa' => 1250.00, 'simbolo' => '$'],
    'CLP' => ['nombre' => 'Peso Chileno (CLP)', 'tasa' => 940.00, 'simbolo' => '$'],
    'BRL' => ['nombre' => 'Real Brasileño (BRL)', 'tasa' => 5.40, 'simbolo' => 'R$']
];

$resultado = null;
$error = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'convertir') {
        $monto = floatval($_POST['monto'] ?? 0);
        $origen = $_POST['origen'] ?? 'USD';
        $destino = $_POST['destino'] ?? 'PEN';

        if ($monto > 0 && isset($tasas[$origen]) && isset($tasas[$destino])) {
            // Conversión matemática precisa usando USD como moneda pivote
            $montoEnUSD = $monto / $tasas[$origen]['tasa'];
            $montoDestino = $montoEnUSD * $tasas[$destino]['tasa'];
            $tasaEfectiva = $tasas[$destino]['tasa'] / $tasas[$origen]['tasa'];

            // Guardar en la base de datos SQL
            $stmt = $pdo->prepare("
                INSERT INTO conversiones (monto_origen, moneda_origen, monto_destino, moneda_destino, tasa)
                VALUES (:monto_origen, :moneda_origen, :monto_destino, :moneda_destino, :tasa)
            ");
            $stmt->execute([
                ':monto_origen' => $monto,
                ':moneda_origen' => $origen,
                ':monto_destino' => $montoDestino,
                ':moneda_destino' => $destino,
                ':tasa' => $tasaEfectiva
            ]);

            $resultado = [
                'monto' => $monto,
                'origen' => $origen,
                'destino' => $destino,
                'monto_destino' => $montoDestino,
                'tasa' => $tasaEfectiva
            ];
        } else {
            $error = "Por favor ingrese un monto mayor a 0.";
        }
    }

    if ($accion === 'limpiar') {
        $pdo->exec("DELETE FROM conversiones");
    }
}

// Consultar historial de conversiones desde SQL
$historial = $pdo->query("SELECT * FROM conversiones ORDER BY id DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Monedas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="text-center mb-4 fw-bold">Cambio de Monedas</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Formulario de Conversión -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="convertir">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Monto a convertir</label>
                            <input type="number" step="0.01" name="monto" class="form-control form-control-lg" placeholder="Ej: 100" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">De Moneda:</label>
                                <select name="origen" class="form-select">
                                    <?php foreach ($tasas as $cod => $info): ?>
                                        <option value="<?= $cod ?>" <?= $cod === 'USD' ? 'selected' : '' ?>>
                                            <?= $info['nombre'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">A Moneda:</label>
                                <select name="destino" class="form-select">
                                    <?php foreach ($tasas as $cod => $info): ?>
                                        <option value="<?= $cod ?>" <?= $cod === 'PEN' ? 'selected' : '' ?>>
                                            <?= $info['nombre'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold fs-5">Convertir</button>
                    </form>

                    <?php if ($resultado): ?>
                        <div class="alert alert-success mt-4 mb-0 text-center shadow-sm">
                            <h4 class="mb-1">
                                <?= number_format($resultado['monto'], 2) ?> <?= $resultado['origen'] ?> = 
                                <strong><?= number_format($resultado['monto_destino'], 2) ?> <?= $resultado['destino'] ?></strong>
                            </h4>
                            <small class="text-muted">
                                Tasa de cambio: 1 <?= $resultado['origen'] ?> = <?= number_format($resultado['tasa'], 4) ?> <?= $resultado['destino'] ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla de Referencia Rápida -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2 text-muted">Tasas de Referencia Frente al Dólar (USD)</h6>
                    <div class="row text-center g-2 small">
                        <div class="col-4 col-md-2 p-2 bg-white rounded border"><strong>1 USD</strong> = S/ 3.70 PEN</div>
                        <div class="col-4 col-md-2 p-2 bg-white rounded border"><strong>1 PEN</strong> = $0.27 USD</div>
                        <div class="col-4 col-md-2 p-2 bg-white rounded border"><strong>1 EUR</strong> = $1.16 USD</div>
                        <div class="col-4 col-md-2 p-2 bg-white rounded border"><strong>1 GBP</strong> = $1.31 USD</div>
                        <div class="col-4 col-md-2 p-2 bg-white rounded border"><strong>1 USD</strong> = $19.50 MXN</div>
                        <div class="col-4 col-md-2 p-2 bg-white rounded border"><strong>1 USD</strong> = $4,050 COP</div>
                    </div>
                </div>
            </div>

            <!-- Historial Almacenado en SQL -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0 fw-bold">Historial de Conversiones</h5>
                        <?php if (!empty($historial)): ?>
                            <form method="POST" action="" onsubmit="return confirm('¿Desea vaciar el historial?');">
                                <input type="hidden" name="accion" value="limpiar">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Vaciar Historial</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($historial)): ?>
                        <p class="text-muted text-center mb-0 py-3">No hay conversiones registradas todavía en la base de datos SQL.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Tasa</th>
                                        <th>Fecha y Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $row): ?>
                                        <tr>
                                            <td><?= $row['id'] ?></td>
                                            <td><?= number_format($row['monto_origen'], 2) ?> <?= $row['moneda_origen'] ?></td>
                                            <td class="text-success fw-bold"><?= number_format($row['monto_destino'], 2) ?> <?= $row['moneda_destino'] ?></td>
                                            <td><?= number_format($row['tasa'], 4) ?></td>
                                            <td><small class="text-muted"><?= $row['fecha'] ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
