<?php
session_start();
date_default_timezone_set('America/Lima'); 
include("conexion.php");

if (!isset($_SESSION['id_docente']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

$id_docente = $_SESSION['id_docente'];
$hora_actual = date('H:i');
$hora_actual_obj = new DateTime(); 
$dia_actual = date('l');
$dias = ['Sunday'=>'Domingo', 'Monday'=>'Lunes', 'Tuesday'=>'Martes', 'Wednesday'=>'Miércoles', 'Thursday'=>'Jueves', 'Friday'=>'Viernes', 'Saturday'=>'Sábado'];
$dia_semana = $dias[$dia_actual];


$sql = "SELECT MAX(h.hora_fin) AS hora_final
        FROM Asignacion a
        JOIN Horarios h ON a.id_asignacion = h.id_asignacion
        WHERE a.id_docente = ? AND h.dia_semana = ?";
$params = array($id_docente, $dia_semana);
$stmt = sqlsrv_query($conn, $sql, $params);

$autorizado = false;
$hora_final = null;

if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if ($row['hora_final']) {
        $hora_final = date_format($row['hora_final'], 'H:i');
        $hora_final_obj = $row['hora_final'];

        
        if ($hora_actual_obj >= $hora_final_obj) {
            $autorizado = true;
        }
    }
}


$marco_entrada = false;
$sql_check = "SELECT id_asistencia FROM Asistencia 
              WHERE id_docente = ? AND fecha = CONVERT(DATE, GETDATE()) AND hora_entrada IS NOT NULL AND hora_salida IS NULL";
$stmt_check = sqlsrv_query($conn, $sql_check, array($id_docente));  

if ($stmt_check && sqlsrv_has_rows($stmt_check)) {
    $marco_entrada = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$autorizado) {
        echo "<script>alert('No puedes marcar salida aún. Tu jornada no ha terminado.'); window.location.href='panel_docente.php';</script>";
        exit();
    }

    if (!$marco_entrada) {
        echo "<script>alert('Aún no has marcado tu entrada hoy.'); window.location.href='panel_docente.php';</script>";
        exit();
    }

    
    $stmt_check = sqlsrv_query($conn, $sql_check, array($id_docente));
    while ($asistencia = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
        $id_asistencia = $asistencia['id_asistencia'];

        $sql_update = "UPDATE Asistencia SET hora_salida = ? WHERE id_asistencia = ?";
        $params_update = array($hora_actual, $id_asistencia);
        $stmt_update = sqlsrv_query($conn, $sql_update, $params_update);
    }

    echo "<script>alert('Salida registrada correctamente.'); window.location.href='panel_docente.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Marcar Salida</title>
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
        button {
            margin-top: 20px;
            background-color: #2a6ddf;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #1d4fb5;
        }
        a {
            display: block;
            margin-top: 20px;
            color: #2a6ddf;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Marcar Salida</h2>

    <p>Día actual: <strong><?= $dia_semana ?></strong></p>
    <p>Hora actual: <strong><?= $hora_actual ?></strong></p>
    <?php if ($hora_final): ?>
        <p>Hora de salida programada: <strong><?= $hora_final ?></strong></p>
    <?php endif; ?>

    <?php if ($autorizado && $marco_entrada): ?>
        <form method="post">
            <button type="submit">🕔 Confirmar salida</button>
        </form>
    <?php elseif (!$marco_entrada): ?>
        <p>No puedes marcar salida porque aún no registraste tu entrada hoy.</p>
    <?php else: ?>
        <p>No puedes marcar salida aún. Tu jornada no ha terminado.</p>
        <a href="solicitar_salida.php">📤 Solicitar salida anticipada</a>
    <?php endif; ?>

    <a href="panel_docente.php">← Volver al panel</a>
</div>
</body>
</html>




