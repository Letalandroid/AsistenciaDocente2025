<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Control de Asistencia</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-image: url('img/fondo_login.jpg'); 
            background-repeat: no-repeat;
            background-position: center;
            background-size: 580px;
        }

        .login-box {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 10px;
            width: 90%;
            max-width: 300px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        input[type="text"],
        input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            margin-top: 15px;
        }

        .logo {
            width: 100px;
            margin-bottom: 20px;
        }

        .olvide {
         display: block;
         margin-top: 12px;
         color:rgb(114, 131, 160);
         text-decoration: none;
         font-weight: bold;
         font-size: 14px;
        }
        .olvide:hover {
        text-decoration: underline;
        }

    </style>
</head>
<body>

    <div class="login-box">
        <img src="img/logo_institucion.jpg" class="logo" alt="Logo del sistema">
        <h2>Iniciar Sesión</h2>

        <form action="procesar_login.php" method="post">
            <input type="text" name="usuario" placeholder="Usuario" required><br>
            <input type="password" name="password" placeholder="Contraseña" required><br>
            <button type="submit">Ingresar</button>
            <a href="recuperar_password.php" class="olvide">¿Olvidaste tu contraseña?</a>
        </form>

        <?php if (isset($_GET['error'])): ?>
            <div class="error">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
