<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$id_docente = $_GET['id'] ?? null;
if (!$id_docente) {
    echo "ID de docente no válido.";
    exit();
}


$sql = "SELECT * FROM Docentes WHERE id_docente = ?";
$params = array($id_docente);
$stmt = sqlsrv_query($conn, $sql, $params);
$docente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$docente) {
    echo "Docente no encontrado.";
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre_completo'];
    $dni = $_POST['dni'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];

    $sql_update = "UPDATE Docentes SET nombre_completo = ?, dni = ?, correo = ?, telefono = ? WHERE id_docente = ?";
    $params_update = array($nombre, $dni, $correo, $telefono, $id_docente);
    $update_stmt = sqlsrv_query($conn, $sql_update, $params_update);

    if ($update_stmt) {
        echo "<script>alert('Datos actualizados correctamente'); window.location.href='panel_admin.php';</script>";
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
    <title>Editar Docente</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<div class="container">
    <h2>Editar Docente</h2>
    <form method="post">
        <label>Nombre completo:</label>
        <input type="text" name="nombre_completo" value="<?php echo htmlspecialchars($docente['nombre_completo']); ?>" required><br>
        <label>DNI:</label>
        <input type="text" name="dni" value="<?php echo htmlspecialchars($docente['dni']); ?>" required><br>
        <label>Correo:</label>
        <input type="email" name="correo" value="<?php echo htmlspecialchars($docente['correo']); ?>" required><br>
        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?php echo htmlspecialchars($docente['telefono']); ?>"><br><br>
        <button type="submit">Guardar cambios</button>
    </form>
    <br>
    <a href="panel_admin.php">Volver al panel</a>
</div>
</body>
</html>
