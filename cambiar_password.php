<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$id_usuario = $_SESSION['id_usuario'];
$mensaje = '';
$exito = false; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nueva_password = trim($_POST["nueva_password"]);
    $confirmar_password = trim($_POST["confirmar_password"]);

    if ($nueva_password === $confirmar_password && !empty($nueva_password)) {
        
        $sql = "UPDATE Usuarios SET password = ?, cambio_password = 1, fecha_ignorar_password = NULL WHERE id_usuario = ?";
        $params = array($nueva_password, $id_usuario);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            $_SESSION['cambio_password'] = 1;
            $mensaje = "Contraseña actualizada correctamente.";
            $exito = true; 
            session_destroy(); 
        } else {
            $mensaje = "Error al actualizar la contraseña.";
        }
    } else {
        $mensaje = "Las contraseñas no coinciden o están vacías.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <style>
        body {
            background-color: #f0f0f0; 
            background-image: url('img/fondo_login.jpg');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 750px auto; 
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 90%;
            max-width: 400px;
            margin: 80px auto;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            text-align: center;
        }

        h2 {
            color: #2a6ddf;
        }

        input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            background-color: #2a6ddf;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #1d4fb5;
        }

        .mensaje {
            margin-top: 15px;
            font-weight: bold;
            color: <?= $exito ? '#28a745' : '#dc3545' ?>; 
        }

        .boton-volver {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #2a6ddf;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .boton-volver:hover {
            background-color: #1d4fb5;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Cambiar Contraseña</h2>

    <?php if ($exito): ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <a class="boton-volver" href="login.php">🔑 Iniciar sesión</a>
    <?php else: ?>
        <form method="POST">
            <input type="password" name="nueva_password" placeholder="Nueva contraseña" required><br>
            <input type="password" name="confirmar_password" placeholder="Confirmar contraseña" required><br>
            <button type="submit">Actualizar</button>
        </form>

        <?php if (!empty($mensaje)) { ?>
            <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <?php } ?>
    <?php endif; ?>
</div>
</body>
</html>


