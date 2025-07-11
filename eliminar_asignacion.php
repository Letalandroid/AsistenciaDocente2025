<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php?error=Acceso no autorizado");
    exit();
}
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id_asignacion = $_POST['id'];

    
    $sql_horarios = "DELETE FROM Horarios WHERE id_asignacion = ?";
    $stmt_horarios = sqlsrv_query($conn, $sql_horarios, array($id_asignacion));

    
    $sql_asignacion = "DELETE FROM Asignacion WHERE id_asignacion = ?";
    $stmt_asignacion = sqlsrv_query($conn, $sql_asignacion, array($id_asignacion));

    if ($stmt_horarios && $stmt_asignacion) {
        echo "<script>alert('Asignación eliminada correctamente.'); window.location.href='asignaciones_docentes.php';</script>";
    } else {
        echo "<script>alert('Error al eliminar asignación.'); window.location.href='asignaciones_docentes.php';</script>";
    }
} else {
    header("Location: asignaciones_docentes.php");
    exit();
}
?>
