<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id_docente = $_POST['id'];

    
    $sql_delete_usuario = "DELETE FROM Usuarios WHERE id_docente = ?";
    $stmt_usuario = sqlsrv_query($conn, $sql_delete_usuario, array($id_docente));

    if (!$stmt_usuario) {
        die("Error al eliminar el usuario relacionado: " . print_r(sqlsrv_errors(), true));
    }

   
    $sql = "DELETE FROM Docentes WHERE id_docente = ?";
    $params = array($id_docente);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header("Location: panel_admin.php");
        exit();
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
?>
