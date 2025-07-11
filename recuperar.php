<?php 
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST["correo"] ?? '';

    $sql = "SELECT usuario FROM Usuarios WHERE email = ?";
    $params = array($correo);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die("Error en la consulta: <pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
    }

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "Tu nombre de usuario registrado es: <strong>" . htmlspecialchars($row['usuario']) . "</strong>";
    } else {
        echo "No se encontró un usuario con ese correo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Usuario</title>
</head>
<body>
    <h2>Recuperar acceso</h2>
    <form method="post">
        <input type="email" name="correo" placeholder="Correo institucional" required>
        <button type="submit">Recuperar</button>
    </form>
    <a href="index.html">Volver al login</a>
</body>
</html>

