<<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST["usuario"]);
    $password = trim($_POST["password"]);

    $sql = "SELECT id_usuario, usuario, password, id_rol, nombre_completo, id_docente, cambio_password
            FROM Usuarios
            WHERE usuario = ?";
    $params = array($usuario);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $passwordGuardada = trim($row['password']);

        if ($password === $passwordGuardada) {
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['id_rol'] = $row['id_rol'];
            $_SESSION['nombre_completo'] = $row['nombre_completo'];

            
            if ($row['id_rol'] == 1) {
                header("Location: panel_admin.php");
                exit();
            }

          
            elseif ($row['id_rol'] == 2) {
                $id_docente = $row['id_docente'];
                $_SESSION['id_docente'] = $id_docente;

                
                $_SESSION['cambio_password'] = $row['cambio_password'];

                
                if (!is_null($id_docente)) {
                    $sqlEstado = "SELECT estado FROM Docentes WHERE id_docente = ?";
                    $paramsEstado = array($id_docente); 
                    $stmtEstado = sqlsrv_query($conn, $sqlEstado, $paramsEstado); 

                    if ($stmtEstado && $rowEstado = sqlsrv_fetch_array($stmtEstado, SQLSRV_FETCH_ASSOC)) {
                        if ($rowEstado['estado'] == 0) {
                            header("Location: login.php?error=Docente inhabilitado. Contacte al administrador.");
                            exit();
                        }
                    }
                }

               
                if (is_null($id_docente)) {
                    header("Location: completar_datos_docente.php");
                    exit();
                } else {
                    $sql2 = "SELECT dni, correo, telefono FROM Docentes WHERE id_docente = ?";
                    $params2 = array($id_docente);
                    $stmt2 = sqlsrv_query($conn, $sql2, $params2);

                    if ($stmt2 && $doc = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
                        if (empty($doc['dni']) || empty($doc['correo']) || empty($doc['telefono'])) {
                            header("Location: completar_datos_docente.php");
                            exit();
                        } else {
                            header("Location: panel_docente.php");
                            exit();
                        }
                    } else {
                        header("Location: login.php?error=No se encontró el docente asociado.");
                        exit();
                    }
                }
            } else {
                header("Location: login.php?error=Rol de usuario no reconocido.");
                exit();
            }
        } else {
            header("Location: login.php?error=Contraseña incorrecta.");
            exit();
        }
    } else {
        header("Location: login.php?error=Usuario no encontrado.");
        exit();
    }
}
?>

