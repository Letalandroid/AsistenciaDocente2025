<?php
include("conexion.php");

$token = $_GET['token'] ?? null;
$mensaje = '';
$exito = false;


if ($token) {
    $sql = "SELECT id_usuario FROM Usuarios WHERE token_recuperacion = ?";
    $stmt = sqlsrv_query($conn, $sql, [$token]);

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $id_usuario = $row['id_usuario'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nueva = trim($_POST['nueva_password']);
            $confirmar = trim($_POST['confirmar_password']);

            if ($nueva === $confirmar && !empty($nueva)) {
                
                $update = "UPDATE Usuarios SET password = ?, token_recuperacion = NULL, cambio_password = 1 WHERE id_usuario = ?";
                $stmt2 = sqlsrv_query($conn, $update, [$nueva, $id_usuario]);

                if ($stmt2) {
                    $mensaje = "Contraseña actualizada correctamente.";
                    $exito = true;
                } else {
                    $mensaje = "Error al actualizar contraseña.";
                }
            } else {
                $mensaje = "Las contraseñas no coinciden o están vacías.";
            }
        }
    } else {
        $mensaje = "Token inválido o expirado.";
        $token = null;
    }
} else {
    $mensaje = "No se proporcionó un token válido.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <style>
        body {
            background-image: url('img/fondo_login.jpg');
            background-repeat: no-repeat;
            background-size: 750px;
            background-position: center;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 90%;
            max-width: 400px;
            margin: 80px auto;
            background-color: rgba(255,255,255,0.96);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
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
        }

        button:hover {
            background-color: #1d4fb5;
        }

        .mensaje {
            margin-top: 15px;
            font-weight: bold;
            color: <?= $exito ? '#28a745' : '#dc3545' ?>;
        }

        .volver {
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
    <h2>🔑 Restablecer Contraseña</h2>

    <?php if ($exito): ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <a href="login.php" class="volver">← Iniciar sesión</a>
    <?php elseif ($token): ?>
        <form method="POST">
            <input type="password" name="nueva_password" placeholder="Nueva contraseña" required><br>
            <input type="password" name="confirmar_password" placeholder="Confirmar contraseña" required><br>
            <button type="submit">Actualizar</button>
        </form>
        <?php if (!empty($mensaje)) { ?>
            <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <?php } ?>
    <?php else: ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <a href="login.php" class="volver">← Volver al inicio</a>
    <?php endif; ?>
</div>
</body>
</html>
