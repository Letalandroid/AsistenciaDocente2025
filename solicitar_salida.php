<?php
session_start();
date_default_timezone_set('America/Lima'); 
if (!isset($_SESSION['id_docente']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

include("conexion.php");
$id_docente = $_SESSION['id_docente'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $motivo = trim($_POST['motivo']);
    $fecha = date('Y-m-d');
    $estado = "Pendiente";

    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Error al subir el archivo.');</script>";
        exit();
    }

    $tipo_permitido = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($_FILES['archivo']['type'], $tipo_permitido)) {
        echo "<script>alert('Solo se permiten archivos PDF, JPG o PNG.'); window.location.href='solicitar_salida.php';</script>";
        exit();
    }

    $max_size = 5 * 1024 * 1024; // 5 MB
    if ($_FILES['archivo']['size'] > $max_size) {
        echo "<script>alert('El archivo excede el tamaño máximo de 5MB.'); window.location.href='solicitar_salida.php';</script>";
        exit();
    }

    if (!is_dir("justificaciones")) {
        mkdir("justificaciones", 0777, true);
    }

    $archivo_nombre = time() . "_" . preg_replace("/[^A-Za-z0-9\-_\.]/", "", basename($_FILES['archivo']['name']));
    $ruta_destino = "justificaciones/" . $archivo_nombre;

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_destino)) {

        
        $sql = "INSERT INTO Justificacion (id_docente, fecha_subida, motivo, archivo_url, estado)
                VALUES (?, ?, ?, ?, ?)";
        $params = array($id_docente, $fecha, $motivo, $ruta_destino, $estado);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            echo "<script>alert('Justificación enviada correctamente.'); window.location.href='panel_docente.php';</script>";
            exit();
        } else {
            die("Error al registrar la justificación: " . print_r(sqlsrv_errors(), true));
        }
    } else {
        echo "<script>alert('Error al subir el archivo.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Salida Anticipada</title>
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
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        textarea, input[type='file'] {
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
        a.back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #2a6ddf;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Solicitud de Salida Anticipada</h2>
    <form method="post" enctype="multipart/form-data">
        <label for="motivo">Motivo:</label>
        <textarea name="motivo" id="motivo" rows="4" required></textarea>

        <label for="archivo">Adjuntar documento (PDF, JPG, PNG):</label>
        <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png" required>

        <button type="submit">Enviar Justificación</button>
    </form>
    <a class="back" href="panel_docente.php">← Volver al panel</a>
</div>
</body>
</html>


