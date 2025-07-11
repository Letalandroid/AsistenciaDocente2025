<?php
session_start();
if (!isset($_SESSION['id_docente']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$id_docente = $_SESSION['id_docente'];


$sql_asist = "SELECT a.id_asistencia, a.fecha, a.estado
              FROM Asistencia a
              LEFT JOIN Justificacion j ON j.id_asistencia = a.id_asistencia
              WHERE a.id_docente = ? AND (a.estado = 'Falta' OR a.estado = 'Tarde') AND j.id_justificacion IS NULL
              ORDER BY a.fecha DESC";
$stmt_asist = sqlsrv_query($conn, $sql_asist, array($id_docente)); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['id_asistencia'], $_POST['motivo']) || empty($_FILES['archivo']['name'])) {
        echo "<script>alert('Todos los campos son obligatorios.'); window.location.href='justificar_falta.php';</script>";
        exit();
    }

    $id_asistencia = $_POST['id_asistencia'];
    $motivo = trim($_POST['motivo']);
    $archivo_url = '';

    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($_FILES['archivo']['type'], $allowed_types)) {
        echo "<script>alert('Formato de archivo no permitido. Solo PDF, JPG o PNG.'); window.location.href='justificar_falta.php';</script>";
        exit();
    }

    $max_size = 5 * 1024 * 1024;
    if ($_FILES['archivo']['size'] > $max_size) {
        echo "<script>alert('El archivo excede el tamaño máximo permitido (5MB).'); window.location.href='justificar_falta.php';</script>";
        exit();
    }

    if (!is_dir("justificaciones")) {
        mkdir("justificaciones", 0777, true);
    }

    $nombre_archivo = time() . "_" . preg_replace("/[^A-Za-z0-9\-_\.]/", "", basename($_FILES['archivo']['name']));
    $ruta_destino = "justificaciones/" . $nombre_archivo;

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_destino)) {
        $archivo_url = $ruta_destino;
    } else {
        die("Error al subir el archivo.");
    }

    
    $insert = "INSERT INTO Justificacion (id_asistencia, archivo_url, motivo, estado, fecha_subida)
               VALUES (?, ?, ?, 'Por Revisar', GETDATE())";
    $params = array($id_asistencia, $archivo_url, $motivo);
    $stmt_insert = sqlsrv_query($conn, $insert, $params);

    if ($stmt_insert) {
        echo "<script>alert('Justificación enviada correctamente.'); window.location.href='panel_docente.php';</script>";
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
    <title>Justificar Falta o Tardanza</title>
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
        }
        h2 {
            text-align: center;
            color: #2a6ddf;
        }
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        select, input[type=text], input[type=file] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
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
    <h2>Subir Justificación</h2>

    <form method="post" enctype="multipart/form-data">
        <?php if (!sqlsrv_has_rows($stmt_asist)) { ?>
            <p style="text-align:center;">No hay asistencias pendientes de justificación.</p>
        <?php } else { ?>
            <label for="id_asistencia">Selecciona asistencia a justificar:</label>
            <select name="id_asistencia" required>
                <?php while ($row = sqlsrv_fetch_array($stmt_asist, SQLSRV_FETCH_ASSOC)) { ?>
                    <option value="<?= $row['id_asistencia']; ?>">
                        <?= $row['fecha']->format('Y-m-d') . " ({$row['estado']})"; ?>
                    </option>
                <?php } ?>
            </select>

            <label for="motivo">Motivo:</label>
            <input type="text" name="motivo" required>

            <label for="archivo">Archivo (PDF o imagen):</label>
            <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required>

            <button type="submit">Enviar Justificación</button>
        <?php } ?>
    </form>

    <a href="panel_docente.php">← Volver al panel</a>
</div>
</body>
</html>



