<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php?error=Acceso no autorizado");
    exit();
}

include("conexion.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $rol = $_POST['id_rol'];

    $password_hashed = $password; 

    $nombre = ($rol == 1) ? 'Administrador' : 'Docente';

    $sql_user = "INSERT INTO Usuarios (usuario, password, nombre_completo, id_rol)
                 VALUES (?, ?, ?, ?)";
    $params_user = array($usuario, $password_hashed, $nombre, $rol);

    $stmt_user = sqlsrv_query($conn, $sql_user, $params_user);

    if ($stmt_user) {
        echo "<script>alert('Usuario creado correctamente'); window.location.href='panel_admin.php';</script>";
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
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="css/estilos.css"> 
</head>
<body class="login-page">
    <div class="form-container">
        <h2>Crear Nuevo Usuario</h2>
        <form method="POST" action="crear_usuario.php">
            <label for="usuario">Usuario:</label>
            <input type="text" name="usuario" required>

            <label for="password">Contraseña:</label>
            <input type="password" name="password" required>

            <label for="id_rol">Rol:</label>
            <select name="id_rol" required>
                <option value="1">Administrador</option>
                <option value="2">Docente</option>
            </select>

            <button type="submit">Crear Usuario</button>
        </form>
    </div>
</body>
</html>
