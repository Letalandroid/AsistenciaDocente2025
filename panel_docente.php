<?php
session_start();

if (isset($_SESSION['asignacion_exitosa'])) {
    echo "<script>alert('Asignación registrada correctamente.');</script>";
    unset($_SESSION['asignacion_exitosa']);
}

include("conexion.php");

$id_docente = $_SESSION['id_docente'];
$nombre = $_SESSION['nombre_completo'];
$id_usuario = $_SESSION['id_usuario'];


$sql_pass = "SELECT cambio_password, fecha_ignorar_password FROM Usuarios WHERE id_usuario = ?";
$stmt_pass = sqlsrv_query($conn, $sql_pass, array($id_usuario));
$cambio_password = 1;
$mostrar_aviso_password = false;

if ($stmt_pass && $row = sqlsrv_fetch_array($stmt_pass, SQLSRV_FETCH_ASSOC)) {
    $cambio_password = $row['cambio_password'];
    $fecha_ignorar = $row['fecha_ignorar_password'];

    if ($cambio_password == 0) {
        if ($fecha_ignorar === null) {
            $mostrar_aviso_password = true;
        } else {
            $fecha_ignorar_dt = new DateTime($fecha_ignorar->format('Y-m-d'));
            $hoy = new DateTime();
            $dias_diferencia = $fecha_ignorar_dt->diff($hoy)->days;

            if ($dias_diferencia >= 15) {
                $mostrar_aviso_password = true;
            }
        }
    }
}


$_SESSION['mostrar_aviso_password'] = $mostrar_aviso_password;

$sql_check_asig = "SELECT COUNT(*) AS total FROM Asignacion WHERE id_docente = ?";
$stmt_check = sqlsrv_query($conn, $sql_check_asig, array($id_docente));
if ($stmt_check && $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
    if ($row['total'] == 0) {
        echo "<script>alert('Debes registrar al menos una asignación antes de continuar.'); window.location.href='asignar_datos_docente.php';</script>";
        exit();
    }
}

$dias = ['Sunday'=>'Domingo', 'Monday'=>'Lunes', 'Tuesday'=>'Martes', 'Wednesday'=>'Miércoles',
         'Thursday'=>'Jueves', 'Friday'=>'Viernes', 'Saturday'=>'Sábado'];
$dia_actual = $dias[date('l')];

$sql = "SELECT c.nombre AS curso, h.turno, h.dia_semana, h.hora_inicio, h.hora_fin,
               a.codigo AS aula, s.nombre_ciclo AS semestre
        FROM Asignacion ag
        JOIN Cursos c ON ag.id_curso = c.id_curso
        JOIN Semestres s ON ag.id_semestre = s.id_semestre
        JOIN Horarios h ON h.id_asignacion = ag.id_asignacion
        JOIN Aulas a ON a.id_aula = h.id_aula
        WHERE ag.id_docente = ? AND h.dia_semana = ?";
$params = array($id_docente, $dia_actual);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Docente</title>
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
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 0px 12px rgba(0,0,0,0.2);
        }
        h2, h3 {
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
        .botones {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .botones a {
            background-color: #2a6ddf;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .botones a:hover {
            background-color: #1d4fb5;
        }
        .logout {
            display: block;
            text-align: center;
            margin-top: 30px;
            color: #555;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">

    <?php if (isset($_SESSION['mostrar_aviso_password']) && $_SESSION['mostrar_aviso_password']): ?>
       
        <div style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba; margin-bottom: 20px; text-align: center;">
            <strong>🔐 Aviso importante:</strong><br>
            Aún no has cambiado tu contraseña asignada. Por seguridad, te recomendamos actualizarla.<br>
            Este aviso volverá a mostrarse cada vez que inicies sesión si no la cambias en los próximos 15 días.
            <div style="margin-top: 10px;">
                <a href="cambiar_password.php" style="background-color: #2a6ddf; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; margin-right: 10px;">Cambiar ahora</a>
                <a href="ignorar_aviso_password.php" style="background-color: #ccc; color: #333; padding: 8px 15px; border-radius: 6px; text-decoration: none;">Más tarde</a>
            </div>
        </div>
        <?php unset($_SESSION['mostrar_aviso_password']); ?>
    <?php endif; ?>

    <h2>Bienvenido, <?= htmlspecialchars($nombre); ?></h2>

    <h3>Horarios de Hoy: <?= $dia_actual; ?></h3>
    <?php if (!sqlsrv_has_rows($stmt)) { ?>
        <p style="text-align:center;">No tienes horarios asignados para hoy.</p>
    <?php } else { ?>
    <table>
        <tr>
            <th>Curso</th>
            <th>Turno</th>
            <th>Hora inicio</th>
            <th>Hora fin</th>
            <th>Aula</th>
            <th>Semestre</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
            <td><?= $row['curso']; ?></td>
            <td><?= $row['turno']; ?></td>
            <td><?= $row['hora_inicio']->format('H:i'); ?></td>
            <td><?= $row['hora_fin']->format('H:i'); ?></td>
            <td><?= $row['aula']; ?></td>
            <td><?= $row['semestre']; ?></td>
        </tr>
        <?php } ?>
    </table>
    <?php } ?>

    <h3>Opciones</h3>
    <div class="botones">
        <a href="registrar_asistencia.php">🕒 Marcar entrada</a>
        <a href="marcar_salida.php">🕔 Marcar salida</a>
        <a href="solicitar_salida.php">📤 Solicitar salida anticipada</a>
        <a href="justificar_falta.php">📎 Justificar falta</a>
        <a href="historial_asistencia.php">📅 Ver historial</a>
        <a href="record_puntualidad.php">📊 Récord de puntualidad</a>

        <?php if ($cambio_password == 0): ?>
            <a href="cambiar_password.php">🔐 Cambiar Contraseña</a>
        <?php endif; ?>
    </div>

    <a class="logout" href="cerrar_sesion.php">🔒 Cerrar sesión</a>
</div>
</body>
</html>






