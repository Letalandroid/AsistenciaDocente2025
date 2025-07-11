<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}
include("conexion.php");

if (isset($_GET['id']) && isset($_GET['accion'])) {
    $id = $_GET['id'];
    $accion = $_GET['accion'];
    $estado = $accion === 'Aceptar' ? 'Aceptado' : 'Rechazado';

    $sql_update = "UPDATE Justificacion SET estado = ? WHERE id_justificacion = ?";
    $params = array($estado, $id);
    $stmt_upd = sqlsrv_query($conn, $sql_update, $params);

    if ($stmt_upd && $estado === 'Aceptado') {
        $sql_update_asist = "UPDATE Asistencia
                             SET justificada = 1
                             WHERE id_asistencia = (
                                 SELECT id_asistencia FROM Justificacion WHERE id_justificacion = ?
                             )";
        sqlsrv_query($conn, $sql_update_asist, array($id));
    }

    echo "<script>alert('Justificación actualizada correctamente'); window.location.href='validar_justificaciones.php';</script>";
    exit();
}

$sql = "SELECT j.id_justificacion, j.archivo_url, j.motivo, j.estado, j.fecha_subida,
               d.nombre_completo, a.fecha AS fecha_asistencia
        FROM Justificacion j
        LEFT JOIN Asistencia a ON j.id_asistencia = a.id_asistencia
        LEFT JOIN Docentes d ON a.id_docente = d.id_docente
        WHERE j.estado = 'Pendiente'
        ORDER BY j.fecha_subida DESC";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Validar Justificaciones</title>
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
            max-width: 1000px;
            margin: 50px auto;
            background-color: rgba(255, 255, 255, 0.97);
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
            margin-top: 25px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background-color: #2a6ddf;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn-accion {
            padding: 6px 12px;
            margin: 2px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-aceptar { background-color: #28a745; }
        .btn-rechazar { background-color: #dc3545; }
        .btn-accion:hover {
            opacity: 0.85;
        }
        a.back {
            display: block;
            margin-top: 30px;
            text-align: center;
            color: #2a6ddf;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
    <script>
    function confirmar(accion) {
        return confirm("¿Estás seguro que deseas " + accion.toLowerCase() + " esta justificación?");
    }
    </script>
</head>
<body>
<div class="container">
    <h2>Justificaciones Pendientes</h2>

    <?php if (!sqlsrv_has_rows($stmt)) { ?>
        <p style="text-align:center;">No hay justificaciones por revisar.</p>
    <?php } else { ?>
    <table>
        <tr>
            <th>Docente</th>
            <th>Fecha Asistencia</th>
            <th>Motivo</th>
            <th>Archivo</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
            <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
            <td> <?= $row['fecha_asistencia'] instanceof DateTime ? $row['fecha_asistencia']->format('Y-m-d') : '<span style="color:#dc3545; font-weight:bold;">Sin asistencia relacionada</span>' ?></td>
            <td><?= htmlspecialchars($row['motivo']) ?></td>
            <td><a href="<?= $row['archivo_url']; ?>" target="_blank">📎 Ver archivo</a></td>
            <td>
                <a class="btn-accion btn-aceptar" onclick="return confirmar('Aceptar')" href="?id=<?= $row['id_justificacion']; ?>&accion=Aceptar">✔ Aceptar</a>
                <a class="btn-accion btn-rechazar" onclick="return confirmar('Rechazar')" href="?id=<?= $row['id_justificacion']; ?>&accion=Rechazar">✘ Rechazar</a>
            </td>
        </tr>
        <?php } ?>
    </table>
    <?php } ?>

    <a class="back" href="panel_admin.php">← Volver al panel</a>
</div>
</body>
</html>

