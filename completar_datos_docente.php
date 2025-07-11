<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Completar Datos del Docente</title>
    <link rel="stylesheet" href="css/estilos.css"> 
    <style>
        body {
            background-image: url('img/fondo_login.jpg');
            background-size: cover;
            font-family: Arial, sans-serif;
            color: #333;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 750px;
        }

        .form-container {
            background-color: rgba(255, 255, 255, 0.95);
            width: 400px;
            margin: 80px auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.2);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Completa tus datos personales</h2>
        <form action="guardar_datos_docente.php" method="post">
            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">

            <label>Nombre completo:</label>
            <input type="text" name="nombre_completo" required>

            <label>DNI:</label>
            <input type="text" name="dni" maxlength="8" required>

            <label>Correo:</label>
            <input type="email" name="correo" required>

            <label>Teléfono:</label>
            <input type="text" name="telefono" required>

            <button type="submit">Guardar datos</button>
        </form>
    </div>
</body>
</html>


