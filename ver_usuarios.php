<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php?error=Acceso no autorizado");
    exit();
}

include("conexion.php");

$sql = "SELECT u.usuario, u.password, r.nombre_rol
        FROM Usuarios u
        JOIN Roles r ON u.id_rol = r.id_rol
        WHERE u.usuario <> 'admin' 
        ORDER BY u.usuario";

$stmt = sqlsrv_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Usuarios</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background-image: url('img/fondo_login.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 750px;
            font-family: Arial, sans-serif;
        }
        .container {
            width: 90%;
            max-width: 900px;
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
    <h2>👥 Lista de Usuarios</h2>

    <table>
        <tr>
            <th>Usuario</th>
            <th>Contraseña</th>
            <th>Rol</th>
        </tr>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
            <td><?= htmlspecialchars($row['usuario']) ?></td>
            <td><?= htmlspecialchars($row['password']) ?></td>
            <td><?= htmlspecialchars($row['nombre_rol']) ?></td>
        </tr>
        <?php } ?>
    </table>

    <a href="panel_admin.php" class="volver">← Volver al Panel Admin</a>
</div>
</body>
</html>
