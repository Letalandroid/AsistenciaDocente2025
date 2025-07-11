<?php
session_start();
include("conexion.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$fecha_actual = date('Y-m-d');

$sql = "UPDATE Usuarios SET fecha_ignorar_password = ? WHERE id_usuario = ?";
$params = array($fecha_actual, $id_usuario);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    header("Location: panel_docente.php");
    exit();
} else {
    echo "Error al registrar la postergación del aviso.";
    print_r(sqlsrv_errors());
}
?>
