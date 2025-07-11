<?php
session_start();
if (!isset($_SESSION['id_docente']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$id_docente = $_SESSION['id_docente'];
$nombre = $_SESSION['nombre_completo'];

$sql = "SELECT fecha, estado, justificada, hora_entrada, hora_salida
        FROM Asistencia
        WHERE id_docente = ?
        ORDER BY fecha DESC";
$params = array($id_docente);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Asistencia</title>
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
            max-width: 900px;
            margin: 50px auto;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #2a6ddf;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
        }
        th {
            background-color: #2a6ddf;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        a {
            display: block;
            margin-top: 25px;
            text-align: center;
            color: #2a6ddf;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Historial de Asistencia – <?= htmlspecialchars($nombre) ?></h2>

    <?php if (!sqlsrv_has_rows($stmt)) { ?>
        <p style="text-align:center;">No se encontraron registros de asistencia.</p>
    <?php } else { ?>
    <table>
        <tr>
            <th>Fecha</th>
            <th>Hora Entrada</th>
            <th>Hora Salida</th>
            <th>Estado</th>
            <th>Justificado</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
            <td><?= isset($row['fecha']) ? $row['fecha']->format('Y-m-d') : '-'; ?></td>
            <td><?= isset($row['hora_entrada']) ? $row['hora_entrada']->format('H:i') : '-'; ?></td>
            <td><?= isset($row['hora_salida']) ? $row['hora_salida']->format('H:i') : '-'; ?></td>
            <td><?= $row['estado']; ?></td>
            <td><?= $row['justificada'] ? 'Sí' : 'No'; ?></td>
        </tr>
        <?php } ?>
    </table>
    <?php } ?>

    <a href="panel_docente.php">← Volver al panel</a>
</div>
</body>
</html>

