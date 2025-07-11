<?php
session_start();
date_default_timezone_set('America/Lima'); 
include("conexion.php");

if (!isset($_SESSION['id_docente']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

$id_docente = $_SESSION['id_docente'];
$hora_actual_obj = new DateTime();
$hora_actual_str = $hora_actual_obj->format('H:i');
$hora_actual_h = (int)$hora_actual_obj->format('H');


if ($hora_actual_h >= 6 && $hora_actual_h < 13) {
    $turno_actual = "Mañana";
} elseif ($hora_actual_h >= 13 && $hora_actual_h < 19) {
    $turno_actual = "Tarde";
} else {
    $turno_actual = "Noche";
}

$dias = ['Sunday'=>'Domingo', 'Monday'=>'Lunes', 'Tuesday'=>'Martes', 'Wednesday'=>'Miércoles',
         'Thursday'=>'Jueves', 'Friday'=>'Viernes', 'Saturday'=>'Sábado'];
$dia_actual = $dias[date('l')];

$sql = "SELECT a.id_asignacion, c.nombre AS curso, h.hora_inicio, h.hora_fin, h.turno
        FROM Asignacion a
        JOIN Cursos c ON a.id_curso = c.id_curso
        JOIN Horarios h ON a.id_asignacion = h.id_asignacion
        WHERE a.id_docente = ? AND h.dia_semana = ?";
$params = array($id_docente, $dia_actual);
$stmt = sqlsrv_query($conn, $sql, $params);

$asignacion_activa = null;
$curso_nombre = null;
$tolerancia_antes = 10; 

if ($stmt && sqlsrv_has_rows($stmt)) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        
        if ($row['turno'] !== $turno_actual) continue;

        $hora_inicio = $row['hora_inicio'];
        $hora_fin = $row['hora_fin'];

        $inicio_permitido = clone $hora_inicio;
        $inicio_permitido->modify("-{$tolerancia_antes} minutes");

        if ($hora_actual_obj >= $inicio_permitido && $hora_actual_obj <= $hora_fin) {
            $asignacion_activa = $row['id_asignacion'];
            $curso_nombre = $row['curso'];
            break;
        }
    }
}


$ya_marco_entrada = false;
$sql_check = "SELECT * FROM Asistencia WHERE id_docente = ? AND fecha = CONVERT(DATE, GETDATE())";
$check_stmt = sqlsrv_query($conn, $sql_check, array($id_docente));
if ($check_stmt && sqlsrv_has_rows($check_stmt)) {
    $ya_marco_entrada = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($ya_marco_entrada) {
        echo "<script>alert('Ya registraste tu asistencia hoy.'); window.location.href='panel_docente.php';</script>";
        exit();
    }

    if (!$asignacion_activa) {
        echo "<script>alert('No tienes un curso activo en este momento.'); window.location.href='panel_docente.php';</script>";
        exit();
    }

    $insert_sql = "INSERT INTO Asistencia (id_docente, id_asignacion, fecha, hora_entrada, estado, justificada)
                   VALUES (?, ?, CONVERT(DATE, GETDATE()), ?, 'Presente', 0)";
    $insert_params = array($id_docente, $asignacion_activa, $hora_actual_str);
    $insert_stmt = sqlsrv_query($conn, $insert_sql, $insert_params);

    if ($insert_stmt) {
        $curso_nombre_escaped = htmlspecialchars($curso_nombre);
        echo "<script>alert('Asistencia para el curso \"$curso_nombre_escaped\" registrada exitosamente.'); window.location.href='panel_docente.php';</script>";
    } else {
        die("Error al registrar asistencia: " . print_r(sqlsrv_errors(), true));
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Asistencia</title>
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
    <h2>Registrar Asistencia</h2>
    <p>Día actual: <strong><?= $dia_actual ?></strong></p>
    <p>Hora actual: <strong><?= $hora_actual_str ?></strong></p>

    <?php if ($ya_marco_entrada): ?>
        <p>Ya registraste tu asistencia hoy.</p>
    <?php elseif ($asignacion_activa): ?>
        <p>Curso actual: <strong><?= htmlspecialchars($curso_nombre) ?></strong></p>
        <form method="post">
            <button type="submit">🕒 Marcar entrada</button>
        </form>
    <?php else: ?>
        <p>No tienes un curso activo en este momento.</p>
    <?php endif; ?>

    <a href="panel_docente.php">← Volver al panel</a>
</div>
</body>
</html>




