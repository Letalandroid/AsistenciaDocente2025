<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}
include("conexion.php");


$sql = "
    SELECT r.id_reemplazo, r.fecha, r.motivo,
           d1.nombre_completo AS docente_titular,
           d2.nombre_completo AS docente_reemplazo
    FROM Reemplazos r
    JOIN Docentes d1 ON r.id_docente = d1.id_docente
    JOIN Docentes d2 ON r.id_docente_reemplazo = d2.id_docente
    ORDER BY r.fecha DESC";
$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reemplazos Asignados</title>
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
            max-width: 1000px;
            margin: 50px auto;
            background-color: rgba(255, 255, 255, 0.96);
            padding: 30px;
            border-radius: 15px;
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
        a.btn {
            padding: 6px 12px;
            background-color: #2a6ddf;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
        a.btn:hover {
            background-color: #1d4fb5;
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: #2a6ddf;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📋 Reemplazos Asignados</h2>

    <?php if (!sqlsrv_has_rows($stmt)) { ?>
        <p style="text-align:center;">No hay reemplazos registrados aún.</p>
    <?php } else { ?>
        <table>
            <tr>
                <th>Fecha</th>
                <th>Docente Titular</th>
                <th>Docente Reemplazo</th>
                <th>Motivo</th>
                <th>Acción</th>
            </tr>
            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
                <tr>
                    <td><?= $row['fecha']->format('Y-m-d') ?></td>
                    <td><?= $row['docente_titular'] ?></td>
                    <td><?= $row['docente_reemplazo'] ?></td>
                    <td><?= htmlspecialchars($row['motivo']) ?></td>
                    <td>
                        <a class="btn" href="editar_reemplazo.php?id=<?= $row['id_reemplazo'] ?>">✏️ Editar</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>

    <a class="back" href="panel_admin.php">← Volver al Panel</a>
</div>
</body>
</html>
