<?php
require_once __DIR__ . '/db.php';

// Tasas de cambio (base: 1 USD)
$tasas = [
    'USD' => 1.00,
    'PEN' => 3.70,
    'EUR' => 0.86,
    'MXN' => 19.50,
    'COP' => 4050.00
];

$res = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = floatval($_POST['monto'] ?? 0);
    $de = $_POST['de'] ?? 'USD';
    $a = $_POST['a'] ?? 'PEN';

    if ($monto > 0 && isset($tasas[$de], $tasas[$a])) {
        $resultado = ($monto / $tasas[$de]) * $tasas[$a];

        // Guardar en la base de datos SQL
        $stmt = $pdo->prepare("INSERT INTO conversiones (monto, de, a, resultado) VALUES (?, ?, ?, ?)");
        $stmt->execute([$monto, $de, $a, $resultado]);

        $res = "$monto $de = " . number_format($resultado, 2) . " $a";
    }
}

// Consultar últimos 5 registros
$historial = $pdo->query("SELECT * FROM conversiones ORDER BY id DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Monedas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #eef2f7; font-family: sans-serif; }
        .card-custom { max-width: 500px; margin: 40px auto; border-radius: 16px; border: none; }
        .btn-convert { background: #2563eb; color: white; border-radius: 10px; font-weight: bold; }
        .btn-convert:hover { background: #1d4ed8; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="card card-custom shadow-lg p-4 bg-white">
        <h3 class="text-center fw-bold mb-4 text-primary">💱 Cambio de Monedas</h3>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Monto</label>
                <input type="number" step="0.01" name="monto" class="form-control form-control-lg" placeholder="100" required>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">De:</label>
                    <select name="de" class="form-select">
                        <option value="USD">USD - Dólar</option>
                        <option value="PEN">PEN - Sol</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="MXN">MXN - Peso MX</option>
                        <option value="COP">COP - Peso COL</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">A:</label>
                    <select name="a" class="form-select">
                        <option value="PEN">PEN - Sol</option>
                        <option value="USD">USD - Dólar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="MXN">MXN - Peso MX</option>
                        <option value="COP">COP - Peso COL</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-convert w-100 py-2">Convertir</button>
        </form>

        <?php if ($res): ?>
            <div class="alert alert-success mt-3 text-center fw-bold fs-5 mb-0">
                <?= $res ?>
            </div>
        <?php endif; ?>

        <hr class="my-4">

        <h6 class="fw-bold text-secondary mb-2">Últimas Conversiones (SQL):</h6>
        <?php if (empty($historial)): ?>
            <p class="text-muted small mb-0">Aún no hay conversiones guardadas.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush small">
                <?php foreach ($historial as $row): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><?= $row['monto'] ?> <?= $row['de'] ?> ➔ <?= number_format($row['resultado'], 2) ?> <?= $row['a'] ?></span>
                        <span class="badge bg-light text-muted"><?= substr($row['fecha'], 11, 5) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
