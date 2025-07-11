<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}
include("conexion.php");

$fecha_hoy = date('Y-m-d');


$sql_faltantes = "
    SELECT DISTINCT d.id_docente, d.nombre_completo, a.id_asignacion, c.nombre AS curso
    FROM Justificacion j
    JOIN Asistencia a ON j.id_asistencia = a.id_asistencia
    JOIN Asignacion ag ON a.id_asignacion = ag.id_asignacion
    JOIN Cursos c ON ag.id_curso = c.id_curso
    JOIN Docentes d ON a.id_docente = d.id_docente
    WHERE j.estado = 'Aprobado' AND a.fecha = ?
";
$stmt_faltantes = sqlsrv_query($conn, $sql_faltantes, array($fecha_hoy));


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id_docente'], $_POST['id_docente_reemplazo'], $_POST['motivo'])) {
    $titular = $_POST['id_docente'];
    $reemplazo = $_POST['id_docente_reemplazo'];
    $motivo = $_POST['motivo'];

    $insert = "INSERT INTO Reemplazos (id_docente, id_docente_reemplazo, fecha, motivo) VALUES (?, ?, ?, ?)";
    $params = array($titular, $reemplazo, $fecha_hoy, $motivo);
    $stmt_insert = sqlsrv_query($conn, $insert, $params);

    if ($stmt_insert) {
        echo "<script>alert('Reemplazo asignado correctamente.'); window.location.href='gestionar_reemplazos.php';</script>";
        exit();
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Reemplazos</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background-image: url('img/fondo_login.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            font-family: Arial, sans-serif;
            background-position: center;
            background-size: 750px;
        }
        .container {
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            background-color: rgba(255,255,255,0.96);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 12px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #2a6ddf;
        }
        .bloque {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
        }
        label {
            font-weight: bold;
        }
        select, textarea {
            width: 100%;
            margin-top: 5px;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #2a6ddf;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #1d4fb5;
        }
        a {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #2a6ddf;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>👥 Asignación de Reemplazos (<?= $fecha_hoy ?>)</h2>

    <?php if (!sqlsrv_has_rows($stmt_faltantes)) { ?>
        <p style="text-align:center;">No hay docentes con faltas justificadas o salidas aprobadas hoy.</p>
    <?php } else { ?>
        <?php while ($faltante = sqlsrv_fetch_array($stmt_faltantes, SQLSRV_FETCH_ASSOC)) {
            $id_docente = $faltante['id_docente'];
            $curso = $faltante['curso'];
            ?>

            <div class="bloque">
                <form method="post">
                    <input type="hidden" name="id_docente" value="<?= $id_docente ?>">

                    <p><strong>Docente ausente:</strong> <?= $faltante['nombre_completo'] ?></p>
                    <p><strong>Curso:</strong> <?= $curso ?></p>

                    <label>Seleccionar docente reemplazo:</label>
                    <select name="id_docente_reemplazo" required>
                        <option value="">-- Seleccionar --</option>
                        <?php
                        
                        $sql_disp = "
                            SELECT DISTINCT d.id_docente, d.nombre_completo
                            FROM Asignacion a
                            JOIN Docentes d ON a.id_docente = d.id_docente
                            WHERE a.id_curso = (SELECT id_curso FROM Asignacion WHERE id_asignacion = ?) 
                              AND d.id_docente != ?
                        ";
                        $stmt_disp = sqlsrv_query($conn, $sql_disp, array($faltante['id_asignacion'], $id_docente));
                        while ($r = sqlsrv_fetch_array($stmt_disp, SQLSRV_FETCH_ASSOC)) {
                            echo "<option value='{$r['id_docente']}'>{$r['nombre_completo']}</option>";
                        }
                        ?>
                    </select>

                    <label>Motivo del reemplazo:</label>
                    <textarea name="motivo" rows="3" required>Docente ausente por justificación aprobada.</textarea>

                    <button type="submit">Asignar Reemplazo</button>
                </form>
            </div>
        <?php } ?>
    <?php } ?>

    <a href="panel_admin.php">← Volver al panel admin</a>
</div>
</body>
</html>
