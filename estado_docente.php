<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

if (!isset($_GET['id']) || !isset($_GET['accion'])) {
    header("Location: panel_admin.php");
    exit();
}

$id_docente = intval($_GET['id']);
$accion = $_GET['accion'];

if ($accion === 'desactivar') {
    $estado = 0;
} elseif ($accion === 'activar') {
    $estado = 1;
} else {
    header("Location: panel_admin.php");
    exit();
}

$sql = "UPDATE Docentes SET estado = ? WHERE id_docente = ?";
$params = array($estado, $id_docente);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    $mensaje = ($estado === 1) ? "Docente activado correctamente." : "Docente deshabilitado correctamente.";
    echo "<script>alert('$mensaje'); window.location.href='panel_admin.php';</script>";
    exit();
} else {
    die(print_r(sqlsrv_errors(), true));
}
?>
