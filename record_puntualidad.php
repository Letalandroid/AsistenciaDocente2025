<?php
session_start();
if (!isset($_SESSION['id_docente']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$id_docente = $_SESSION['id_docente'];
$nombre = $_SESSION['nombre_completo'];

$estadisticas = ['Presente' => 0, 'Tarde' => 0, 'Falta' => 0];

$sql_est = "SELECT estado, COUNT(*) AS cantidad FROM Asistencia WHERE id_docente = ? GROUP BY estado";
$stmt_est = sqlsrv_query($conn, $sql_est, array($id_docente));

if ($stmt_est && sqlsrv_has_rows($stmt_est)) {
    while ($r = sqlsrv_fetch_array($stmt_est, SQLSRV_FETCH_ASSOC)) {
        $estado = $r['estado'];
        
        if (array_key_exists($estado, $estadisticas)) {
            $estadisticas[$estado] = $r['cantidad'];
        }
    }
}

$total = array_sum($estadisticas);


function porcentaje($n, $t) {
    return $t > 0 ? round(($n / $t) * 100) : 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Récord de Puntualidad</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background-image: url('img/fondo_login.jpg');
            background-size: cover;
            font-family: Arial, sans-serif;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 750px;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 60px auto;
            background-color: rgba(255, 255, 255, 0.96);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.2);
            text-align: center;
        }
        h2 {
            color: #2a6ddf;
            margin-bottom: 20px;
        }
        .resumen {
            font-size: 1.1em;
            text-align: left;
            margin-top: 20px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        .resumen div {
            margin: 10px 0;
        }
        .resumen strong {
            color: #2a6ddf;
        }
        a {
            display: inline-block;
            margin-top: 25px;
            color: #2a6ddf;
            text-decoration: none;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Récord de Puntualidad – <?= htmlspecialchars($nombre) ?></h2>

    <div class="resumen">
        <div>📅 Total de registros: <strong><?= $total ?></strong></div>
        <div>✅ Asistencias: <strong><?= $estadisticas['Presente'] ?></strong> (<?= porcentaje($estadisticas['Presente'], $total) ?>%)</div>
        <div>🕗 Tardanzas: <strong><?= $estadisticas['Tarde'] ?></strong> (<?= porcentaje($estadisticas['Tarde'], $total) ?>%)</div>
        <div>❌ Faltas: <strong><?= $estadisticas['Falta'] ?></strong> (<?= porcentaje($estadisticas['Falta'], $total) ?>%)</div>
    </div>

    <a href="panel_docente.php">← Volver al panel</a>
</div>
</body>
</html>


