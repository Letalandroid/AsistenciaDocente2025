<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

if (!isset($_POST['nombre_completo'], $_POST['dni'], $_POST['correo'], $_POST['telefono'])) {
    mostrarError("Faltan datos del formulario.");
}

$nombre = trim($_POST['nombre_completo']);
$dni = trim($_POST['dni']);
$correo = trim($_POST['correo']);
$telefono = trim($_POST['telefono']);
$id_usuario = $_SESSION['id_usuario'];


if (!preg_match('/^\d{8}$/', $dni)) {
    mostrarError("El DNI debe tener exactamente 8 números.");
}

if (!preg_match('/^[\w\.-]+@unp\.edu\.pe$/', $correo)) {
    mostrarError("El correo debe ser institucional (ej: example@unp.edu.pe).");
}

if (!preg_match('/^\d{9}$/', $telefono)) {
    mostrarError("El número de teléfono debe tener exactamente 9 dígitos.");
}


$sql_check = "SELECT id_docente FROM Docentes WHERE dni = ? OR correo = ?";
$params_check = array($dni, $correo);
$stmt_check = sqlsrv_query($conn, $sql_check, $params_check);

if ($stmt_check && sqlsrv_has_rows($stmt_check)) {
    mostrarError("Ya existen datos del docente con ese DNI o correo.");
}


$sql_insert = "INSERT INTO Docentes (nombre_completo, dni, correo, telefono)
               OUTPUT INSERTED.id_docente
               VALUES (?, ?, ?, ?)";
$params_insert = array($nombre, $dni, $correo, $telefono);
$stmt_insert = sqlsrv_query($conn, $sql_insert, $params_insert);

if ($stmt_insert) {
    $row = sqlsrv_fetch_array($stmt_insert, SQLSRV_FETCH_ASSOC);
    $id_docente = $row['id_docente'] ?? null;

    if ($id_docente) {
        $sql_update = "UPDATE Usuarios SET id_docente = ?, nombre_completo = ? WHERE id_usuario = ?";
        $params_update = array($id_docente, $nombre, $id_usuario);
        $stmt_update = sqlsrv_query($conn, $sql_update, $params_update);

        if ($stmt_update) {
            $_SESSION['id_docente'] = $id_docente;
            $_SESSION['nombre_completo'] = $nombre;
            echo "<script>alert('Datos guardados correctamente'); window.location.href='asignar_datos_docente.php';</script>";
            exit();
        } else {
            mostrarError("Error al actualizar el usuario.", sqlsrv_errors());
        }
    } else {
        mostrarError("No se pudo obtener el ID del docente después del INSERT.");
    }
} else {
    mostrarError("Error al insertar los datos del docente.", sqlsrv_errors());
}

function mostrarError($mensaje, $errores = null) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Error</title>
        <link rel="stylesheet" href="css/estilos.css">
        <style>
            body {
                background-image: url('img/fondo_login.jpg');
                background-size: cover;
                font-family: Arial, sans-serif;
                color: #333;
                background-repeat: no-repeat;
                background-position: center;
                background-size: 750px;
            }
            .error-box {
                width: 500px;
                margin: 80px auto;
                padding: 30px;
                background-color: rgba(255, 255, 255, 0.95);
                border-radius: 12px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
                text-align: center;
            }
            .error-box h2 {
                color: red;
            }
            .btn {
                margin-top: 20px;
                padding: 10px 20px;
                background-color: #2a6ddf;
                color: white;
                border: none;
                border-radius: 8px;
                text-decoration: none;
                display: inline-block;
            }
            .btn:hover {
                background-color: #1d4fb5;
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2>Error</h2>
            <p><?php echo $mensaje; ?></p>
            <?php if ($errores) {
                echo "<pre>" . print_r($errores, true) . "</pre>";
            } ?>
            <a href="completar_datos_docente.php" class="btn">Volver</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>

