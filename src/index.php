<?php
require_once __DIR__ . '/db.php';

// Tasas de cambio de referencia (base USD)
$tasas = [
    'USD' => ['nombre' => 'USD - Dólar Estadounidense', 'tasa' => 1.00],
    'PEN' => ['nombre' => 'PEN - Sol Peruano', 'tasa' => 3.70],
    'EUR' => ['nombre' => 'EUR - Euro', 'tasa' => 0.86],
    'MXN' => ['nombre' => 'MXN - Peso Mexicano', 'tasa' => 19.50],
    'COP' => ['nombre' => 'COP - Peso Colombiano', 'tasa' => 4050.00]
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

        if ($monto > 0 && isset($tasas[$origen], $tasas[$destino])) {
            $tasaEfectiva = $tasas[$destino]['tasa'] / $tasas[$origen]['tasa'];
            $montoDestino = ($monto / $tasas[$origen]['tasa']) * $tasas[$destino]['tasa'];

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
            $error = "Ingrese un monto mayor a 0.";
        }
    }

    if ($accion === 'limpiar') {
        $pdo->exec("DELETE FROM conversiones");
    }
}

// Consultar historial desde SQL
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
        <div class="col-lg-7">
            <h2 class="text-center mb-4 fw-bold">Cambio de Monedas</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="convertir">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Monto a convertir</label>
                            <input type="number" step="0.01" name="monto" class="form-control form-control-lg" placeholder="100" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">De:</label>
                                <select name="origen" class="form-select">
                                    <?php foreach ($tasas as $cod => $info): ?>
                                        <option value="<?= $cod ?>" <?= $cod === 'USD' ? 'selected' : '' ?>>
                                            <?= $info['nombre'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">A:</label>
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
                        <div class="alert alert-success mt-3 text-center mb-0">
                            <h4 class="mb-1">
                                <?= number_format($resultado['monto'], 2) ?> <?= $resultado['origen'] ?> = 
                                <strong><?= number_format($resultado['monto_destino'], 2) ?> <?= $resultado['destino'] ?></strong>
                            </h4>
                            <small class="text-muted">Tasa: 1 <?= $resultado['origen'] ?> = <?= number_format($resultado['tasa'], 4) ?> <?= $resultado['destino'] ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historial SQL -->
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0 fw-bold">Historial</h5>
                        <?php if (!empty($historial)): ?>
                            <form method="POST" action="" onsubmit="return confirm('¿Limpiar historial?');">
                                <input type="hidden" name="accion" value="limpiar">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Limpiar</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($historial)): ?>
                        <p class="text-muted text-center mb-0 py-2">No hay conversiones registradas todavía.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Tasa</th>
                                        <th>Fecha</th>
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
