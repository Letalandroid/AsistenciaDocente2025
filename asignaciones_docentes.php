<?php
session_start();
date_default_timezone_set('America/Lima'); 
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php?error=Acceso no autorizado");
    exit();
}
include("conexion.php");


$sql = "SELECT 
            ag.id_asignacion, 
            d.nombre_completo AS docente, 
            c.nombre AS curso, 
            s.nombre_ciclo AS semestre, 
            h.dia_semana,
            h.turno,
            h.hora_inicio,
            h.hora_fin,
            a.codigo AS aula
        FROM Asignacion ag
        JOIN Docentes d ON ag.id_docente = d.id_docente
        JOIN Cursos c ON ag.id_curso = c.id_curso
        JOIN Semestres s ON ag.id_semestre = s.id_semestre
        LEFT JOIN Horarios h ON h.id_asignacion = ag.id_asignacion
        LEFT JOIN Aulas a ON h.id_aula = a.id_aula
        ORDER BY d.nombre_completo, h.dia_semana";




$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignaciones de Docentes</title>
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
            max-width: 1100px;
            margin: 50px auto;
            background-color: rgba(255,255,255,0.97);
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
            text-align: center;
            border: 1px solid #ccc;
        }
        th {
            background-color: #2a6ddf;
            color: white;
        }
        .acciones button {
            background-color: #2a6ddf;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin: 2px;
        }
        .acciones button:hover {
            background-color: #1d4fb5;
        }
        .volver {
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
    <h2>📋 Asignaciones de Docentes</h2>

    <table>
    <tr>
        <th>Docente</th>
        <th>Curso</th>
        <th>Aula</th>
        <th>Semestre</th>
        <th>Día</th> 
        <th>Hora Inicio</th> 
        <th>Hora Fin</th> 
        <th>Turno</th>
        <th>Acciones</th>
    </tr>

        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
            <tr>
                <td><?= $row['docente']; ?></td>
                <td><?= $row['curso']; ?></td>
                <td><?= $row['aula']; ?></td>
                <td><?= $row['semestre']; ?></td>
                <td><?= $row['dia_semana']; ?></td> 
                <td><?= $row['hora_inicio'] instanceof DateTime ? $row['hora_inicio']->format('H:i') : ''; ?></td>
                <td><?= $row['hora_fin'] instanceof DateTime ? $row['hora_fin']->format('H:i') : ''; ?></td>
                <td><?= $row['turno']; ?></td>
                <td class="acciones">
                    <form method="GET" action="editar_asignacion.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['id_asignacion']; ?>">
                        <button type="submit">✏️ Editar</button>
                    </form>
                    <form method="POST" action="eliminar_asignacion.php" onsubmit="return confirm('¿Eliminar esta asignación?');" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['id_asignacion']; ?>">
                        <button type="submit">🗑️ Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>

    <a href="panel_admin.php" class="volver">← Volver al Panel Admin</a>
</div>
</body>
</html>
