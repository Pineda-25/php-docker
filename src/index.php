<?php
require_once __DIR__ . '/db.php';

// Tasas de cambio de referencia base USD
$tasas = [
    'USD' => ['nombre' => 'Dólar Estadounidense', 'tasa' => 1.00, 'simbolo' => '$'],
    'EUR' => ['nombre' => 'Euro', 'tasa' => 0.92, 'simbolo' => '€'],
    'COP' => ['nombre' => 'Peso Colombiano', 'tasa' => 4050.00, 'simbolo' => '$'],
    'MXN' => ['nombre' => 'Peso Mexicano', 'tasa' => 18.50, 'simbolo' => '$'],
    'PEN' => ['nombre' => 'Sol Peruano', 'tasa' => 3.75, 'simbolo' => 'S/'],
    'ARS' => ['nombre' => 'Peso Argentino', 'tasa' => 950.00, 'simbolo' => '$'],
    'GBP' => ['nombre' => 'Libra Esterlina', 'tasa' => 0.79, 'simbolo' => '£'],
    'BRL' => ['nombre' => 'Real Brasileño', 'tasa' => 5.40, 'simbolo' => 'R$'],
    'CLP' => ['nombre' => 'Peso Chileno', 'tasa' => 930.00, 'simbolo' => '$'],
];

$mensaje = null;
$tipoMensaje = 'success';
$resultadoConversion = null;

// Manejo de peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'convertir') {
        $monto = floatval($_POST['monto'] ?? 0);
        $origen = $_POST['origen'] ?? 'USD';
        $destino = $_POST['destino'] ?? 'EUR';
        $comision = floatval($_POST['comision'] ?? 0);

        if ($monto > 0 && isset($tasas[$origen]) && isset($tasas[$destino])) {
            // Conversión usando base USD
            $montoEnUSD = $monto / $tasas[$origen]['tasa'];
            $montoDestinoBruto = $montoEnUSD * $tasas[$destino]['tasa'];
            
            // Aplicar comisión si existe
            $descuentoComision = $montoDestinoBruto * ($comision / 100);
            $montoDestinoFinal = $montoDestinoBruto - $descuentoComision;
            $tasaEfectiva = $tasas[$destino]['tasa'] / $tasas[$origen]['tasa'];

            // Insertar registro en la Base de Datos SQL
            $stmt = $pdo->prepare("
                INSERT INTO conversiones (monto_origen, moneda_origen, monto_destino, moneda_destino, tasa, comision_porcentaje)
                VALUES (:monto_origen, :moneda_origen, :monto_destino, :moneda_destino, :tasa, :comision)
            ");
            $stmt->execute([
                ':monto_origen' => $monto,
                ':moneda_origen' => $origen,
                ':monto_destino' => $montoDestinoFinal,
                ':moneda_destino' => $destino,
                ':tasa' => $tasaEfectiva,
                ':comision' => $comision
            ]);

            $resultadoConversion = [
                'monto' => $monto,
                'origen' => $origen,
                'destino' => $destino,
                'resultado' => $montoDestinoFinal,
                'tasa' => $tasaEfectiva,
                'comision' => $comision,
                'descuento' => $descuentoComision
            ];
            $mensaje = "¡Conversión realizada y registrada en SQL con éxito!";
        } else {
            $mensaje = "Por favor ingrese un monto válido mayor a 0.";
            $tipoMensaje = 'danger';
        }
    }

    if ($accion === 'agregar_movimiento') {
        $descripcion = trim($_POST['descripcion'] ?? '');
        $tipo = $_POST['tipo'] ?? 'gasto';
        $categoria = trim($_POST['categoria'] ?? 'General');
        $monto = floatval($_POST['monto'] ?? 0);

        if (!empty($descripcion) && $monto > 0 && in_array($tipo, ['ingreso', 'gasto'])) {
            $stmt = $pdo->prepare("
                INSERT INTO movimientos_presupuesto (descripcion, tipo, categoria, monto)
                VALUES (:descripcion, :tipo, :categoria, :monto)
            ");
            $stmt->execute([
                ':descripcion' => $descripcion,
                ':tipo' => $tipo,
                ':categoria' => $categoria,
                ':monto' => $monto
            ]);
            $mensaje = "Movimiento registrado correctamente en el presupuesto.";
        } else {
            $mensaje = "Datos incompletos o monto inválido para el movimiento.";
            $tipoMensaje = 'danger';
        }
    }

    if ($accion === 'eliminar_movimiento') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM movimientos_presupuesto WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $mensaje = "Registro eliminado de la base de datos.";
        }
    }

    if ($accion === 'limpiar_conversiones') {
        $pdo->exec("DELETE FROM conversiones");
        $mensaje = "Historial de conversiones vaciado.";
    }
}

// Consultas SQL para lectura
$historialConversiones = $pdo->query("SELECT * FROM conversiones ORDER BY id DESC LIMIT 6")->fetchAll();

$movimientos = $pdo->query("SELECT * FROM movimientos_presupuesto ORDER BY id DESC LIMIT 10")->fetchAll();

// Totales de presupuesto calculados vía SQL
$totales = $pdo->query("
    SELECT 
        SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) AS total_ingresos,
        SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) AS total_gastos
    FROM movimientos_presupuesto
")->fetch();

$totalIngresos = floatval($totales['total_ingresos'] ?? 0);
$totalGastos = floatval($totales['total_gastos'] ?? 0);
$balanceNeto = $totalIngresos - $totalGastos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanSmart - Divisas & Presupuesto (Docker + PHP + SQL)</title>
    <!-- Bootstrap 5 CSS y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .header-banner {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1.5rem 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }
        .stat-card {
            padding: 1.25rem;
            border-radius: 1rem;
            color: white;
        }
        .bg-ingresos { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .bg-gastos { background: linear-gradient(135deg, #eb3349, #f45c43); }
        .bg-balance { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .badge-docker {
            background-color: #0db7ed;
            color: white;
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
        }
    </style>
</head>
<body>

<!-- Header Principal -->
<header class="header-banner">
    <div class="container text-center">
        <span class="badge badge-docker mb-2"><i class="bi bi-box-seam"></i> Corriendo en Contenedor Docker</span>
        <h1 class="fw-bold"><i class="bi bi-wallet2"></i> FinanSmart</h1>
        <p class="lead mb-0">Conversor de Divisas & Calculadora de Presupuesto con Persistencia SQL</p>
    </div>
</header>

<div class="container pb-5">
    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Pestañas de Navegación -->
    <ul class="nav nav-pills nav-fill mb-4 bg-white p-2 rounded-4 shadow-sm" id="mainTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="conversor-tab" data-bs-toggle="tab" data-bs-target="#conversor" type="button" role="tab">
                <i class="bi bi-currency-exchange me-1"></i> Conversor de Divisas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="presupuesto-tab" data-bs-toggle="tab" data-bs-target="#presupuesto" type="button" role="tab">
                <i class="bi bi-calculator me-1"></i> Presupuesto y Gastos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                <i class="bi bi-cpu me-1"></i> Arquitectura Docker
            </button>
        </li>
    </ul>

    <div class="tab-content" id="mainTabContent">
        <!-- SECCIÓN 1: CONVERSOR DE DIVISAS -->
        <div class="tab-pane fade show active" id="conversor" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card p-4">
                        <h4 class="card-title fw-bold mb-3"><i class="bi bi-cash-stack text-primary"></i> Realizar Conversión</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="accion" value="convertir">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Monto a Convertir</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-123"></i></span>
                                    <input type="number" step="0.01" name="monto" class="form-control" placeholder="Ej: 100.00" required>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">De Moneda</label>
                                    <select name="origen" class="form-select">
                                        <?php foreach ($tasas as $cod => $info): ?>
                                            <option value="<?= $cod ?>" <?= $cod === 'USD' ? 'selected' : '' ?>>
                                                <?= $cod ?> - <?= $info['nombre'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">A Moneda</label>
                                    <select name="destino" class="form-select">
                                        <?php foreach ($tasas as $cod => $info): ?>
                                            <option value="<?= $cod ?>" <?= $cod === 'EUR' ? 'selected' : '' ?>>
                                                <?= $cod ?> - <?= $info['nombre'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Comisión de Cambio (Opcional)</label>
                                <select name="comision" class="form-select">
                                    <option value="0">0% (Sin comisión bancaria)</option>
                                    <option value="1">1% (Tarifa estándar)</option>
                                    <option value="2.5">2.5% (Tarifa con tarjeta internacional)</option>
                                    <option value="4">4% (Cambio en aeropuerto / casa de cambio)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-arrow-repeat"></i> Calcular y Guardar en SQL
                            </button>
                        </form>

                        <?php if ($resultadoConversion): ?>
                            <div class="mt-4 p-3 bg-light rounded-3 border-start border-4 border-primary">
                                <div class="text-muted small">Resultado Calculado:</div>
                                <div class="fs-4 fw-bold text-dark">
                                    <?= number_format($resultadoConversion['monto'], 2) ?> <?= $resultadoConversion['origen'] ?> = 
                                    <span class="text-success"><?= number_format($resultadoConversion['resultado'], 2) ?> <?= $resultadoConversion['destino'] ?></span>
                                </div>
                                <div class="small text-muted mt-1">
                                    Tasa aplicada: 1 <?= $resultadoConversion['origen'] ?> = <?= number_format($resultadoConversion['tasa'], 4) ?> <?= $resultadoConversion['destino'] ?>
                                    <?php if ($resultadoConversion['comision'] > 0): ?>
                                        | Comisión: <?= $resultadoConversion['comision'] ?>% (-<?= number_format($resultadoConversion['descuento'], 2) ?> <?= $resultadoConversion['destino'] ?>)
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Historial SQL de Conversiones -->
                <div class="col-lg-6">
                    <div class="card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title fw-bold mb-0"><i class="bi bi-clock-history text-secondary"></i> Historial (Tabla SQL)</h4>
                            <?php if (!empty($historialConversiones)): ?>
                                <form method="POST" action="" onsubmit="return confirm('¿Limpiar historial?');">
                                    <input type="hidden" name="accion" value="limpiar_conversiones">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Vaciar</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($historialConversiones)): ?>
                            <p class="text-muted text-center py-4">No hay conversiones guardadas aún en la base de datos SQL.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Origen</th>
                                            <th>Destino</th>
                                            <th>Tasa</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historialConversiones as $conv): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary">#<?= $conv['id'] ?></span></td>
                                                <td><?= number_format($conv['monto_origen'], 2) ?> <strong><?= $conv['moneda_origen'] ?></strong></td>
                                                <td class="text-success fw-bold"><?= number_format($conv['monto_destino'], 2) ?> <?= $conv['moneda_destino'] ?></td>
                                                <td><small class="text-muted"><?= number_format($conv['tasa'], 4) ?></small></td>
                                                <td><small class="text-muted"><?= date('H:i:s', strtotime($conv['fecha'])) ?></small></td>
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

        <!-- SECCIÓN 2: CALCULADORA DE PRESUPUESTO -->
        <div class="tab-pane fade" id="presupuesto" role="tabpanel">
            <!-- Métricas Financieras -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card bg-ingresos shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 text-uppercase fw-bold">Total Ingresos</h6>
                                <h3 class="fw-bold mb-0">$ <?= number_format($totalIngresos, 2) ?></h3>
                            </div>
                            <i class="bi bi-arrow-up-circle fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-gastos shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 text-uppercase fw-bold">Total Gastos</h6>
                                <h3 class="fw-bold mb-0">$ <?= number_format($totalGastos, 2) ?></h3>
                            </div>
                            <i class="bi bi-arrow-down-circle fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-balance shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 text-uppercase fw-bold">Balance Neto</h6>
                                <h3 class="fw-bold mb-0">$ <?= number_format($balanceNeto, 2) ?></h3>
                            </div>
                            <i class="bi bi-piggy-bank fs-1 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Formulario Movimiento -->
                <div class="col-lg-5">
                    <div class="card p-4">
                        <h4 class="card-title fw-bold mb-3"><i class="bi bi-plus-circle text-primary"></i> Registrar Movimiento</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="accion" value="agregar_movimiento">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Descripción</label>
                                <input type="text" name="descripcion" class="form-control" placeholder="Ej: Sueldo mensual, Supermercado..." required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tipo</label>
                                    <select name="tipo" class="form-select">
                                        <option value="gasto">Gasto (-)</option>
                                        <option value="ingreso">Ingreso (+)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Monto</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" name="monto" class="form-control" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="Alimentación">Alimentación</option>
                                    <option value="Transporte">Transporte</option>
                                    <option value="Vivienda y Servicios">Vivienda y Servicios</option>
                                    <option value="Trabajo / Sueldo">Trabajo / Sueldo</option>
                                    <option value="Ahorro e Inversión">Ahorro e Inversión</option>
                                    <option value="Ocio / Otros">Ocio / Otros</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">
                                <i class="bi bi-check2-circle"></i> Guardar en Base de Datos
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tabla de Movimientos -->
                <div class="col-lg-7">
                    <div class="card p-4">
                        <h4 class="card-title fw-bold mb-3"><i class="bi bi-list-check text-secondary"></i> Movimientos Registrados</h4>
                        <?php if (empty($movimientos)): ?>
                            <p class="text-muted text-center py-4">No hay ingresos ni gastos registrados.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Categoría</th>
                                            <th>Monto</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($movimientos as $mov): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($mov['tipo'] === 'ingreso'): ?>
                                                        <span class="badge bg-success"><i class="bi bi-arrow-up"></i> Ingreso</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger"><i class="bi bi-arrow-down"></i> Gasto</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-semibold"><?= htmlspecialchars($mov['descripcion']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($mov['categoria']) ?></span></td>
                                                <td class="fw-bold <?= $mov['tipo'] === 'ingreso' ? 'text-success' : 'text-danger' ?>">
                                                    <?= $mov['tipo'] === 'ingreso' ? '+' : '-' ?>$<?= number_format($mov['monto'], 2) ?>
                                                </td>
                                                <td>
                                                    <form method="POST" action="" onsubmit="return confirm('¿Eliminar este registro?');">
                                                        <input type="hidden" name="accion" value="eliminar_movimiento">
                                                        <input type="hidden" name="id" value="<?= $mov['id'] ?>">
                                                        <button class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </td>
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

        <!-- SECCIÓN 3: INFORMACIÓN TÉCNICA DEL CONTENEDOR DOCKER -->
        <div class="tab-pane fade" id="info" role="tabpanel">
            <div class="card p-4">
                <h4 class="card-title fw-bold mb-3"><i class="bi bi-info-circle-fill text-primary"></i> Detalles del Entorno Dockerizado</h4>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Versión de PHP:</div>
                            <div class="fs-5 fw-bold text-dark"><?= phpversion() ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Motor de Base de Datos:</div>
                            <div class="fs-5 fw-bold text-dark">SQLite 3 (PDO Nativo)</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Servidor Web:</div>
                            <div class="fs-5 fw-bold text-dark">Apache 2.4 en Linux</div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold"><i class="bi bi-check2-all text-success"></i> ¿Por qué esta solución funciona en cualquier máquina?</h5>
                <ul class="text-secondary mt-2">
                    <li><strong>100% Contenedorizado:</strong> No requiere tener instalado PHP ni Apache ni motores SQL en la máquina del usuario. Solo se necesita Docker.</li>
                    <li><strong>Persistencia Integrada:</strong> La base de datos SQL se crea y almacena en el volumen local dentro de la carpeta <code>src/data/</code>, garantizando que los datos no se pierdan al reiniciar el contenedor.</li>
                    <li><strong>Cero Conflictos de Puertos de BD:</strong> Al usar SQL integrado vía SQLite/PDO en el contenedor, se evitan errores clásicos de puertos ocupados (ej. puerto 3306 de MySQL).</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
