<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background-image: url('img/fondo_login.jpg');
            background-repeat: no-repeat;
            background-size: 750px;
            background-position: center;
            font-family: Arial, sans-serif;
            height: 100vh;
            margin: 0;
        }

        .container {
            width: 90%;
            max-width: 400px;
            margin: 80px auto;
            background-color: rgba(255, 255, 255, 0.96);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            text-align: center;
        }

        h2 {
            color: #2a6ddf;
            margin-bottom: 20px;
        }

        input[type="email"] {
            width: 90%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            background-color: #2a6ddf;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background-color: #1d4fb5;
        }

        .volver {
            display: block;
            margin-top: 20px;
            text-decoration: none;
            color:rgb(121, 158, 221);
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🔐 Recuperar Contraseña</h2>
    <form action="procesa_recuperacion.php" method="POST">
        <input type="email" name="correo" placeholder="Correo registrado" required><br>
        <button type="submit">Enviar enlace de recuperación</button>
    </form>
    <a href="login.php" class="volver">← Volver al login</a>
</div>
</body>
</html>
