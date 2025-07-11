<?php
require 'vendor/autoload.php';
include("conexion.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$correo = trim($_POST['correo']);


$sql = "SELECT u.id_usuario FROM Usuarios u
        JOIN Docentes d ON u.id_docente = d.id_docente
        WHERE d.correo = ?";
$params = [$correo];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id_usuario = $row['id_usuario'];
    $token = bin2hex(random_bytes(16));

   
    $sql_upd = "UPDATE Usuarios SET token_recuperacion = ? WHERE id_usuario = ?";
    sqlsrv_query($conn, $sql_upd, [$token, $id_usuario]);

    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'asistencia.docente.pruebas@gmail.com'; 
        $mail->Password = 'ptsu rtcz xpbx helz'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('tu-cuenta-de-prueba@gmail.com', 'Soporte Asistencia');
        $mail->addAddress($correo);

        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña';
        $link = "http://localhost/asistencia_docente/restablecer_password.php?token=$token";
        $mail->isHTML(true);
$mail->Subject = '🔐 Recuperación de contraseña - Asistencia Docente';

$link = "http://localhost/asistencia_docente/restablecer_password.php?token=$token"; // Esto se cambiara luego

$mail->Body = "
    <div style='font-family: Arial, sans-serif; background-color: #f8f9fa; padding: 20px;'>
        <div style='max-width: 600px; margin: auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.05);'>
            <h2 style='color: #2a6ddf;'>Recuperación de contraseña</h2>
            <p>Hola,</p>
            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en el sistema <strong>Asistencia Docente</strong>.</p>
            <p>Para continuar, haz clic en el siguiente botón:</p>
            <p style='text-align: center;'>
                <a href='$link' style='background-color: #2a6ddf; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Restablecer contraseña</a>
            </p>
            <p>Este enlace estará disponible solo por tiempo limitado. Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
            <hr style='margin-top: 30px;'>
            <p style='font-size: 12px; color: #888;'>© ".date("Y")." Asistencia Docente. Todos los derechos reservados.</p>
        </div>
    </div>
";


        $mail->send();

echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Correo Enviado</title>
    <style>
        body {
            background-color: #f0f2f5;
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 100px;
        }
        .mensaje {
            background-color: #ffffff;
            display: inline-block;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .mensaje h2 {
            color: #2a6ddf;
        }
        .volver {
            margin-top: 20px;
            display: inline-block;
            padding: 10px 15px;
            background-color: #2a6ddf;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }
        .volver:hover {
            background-color: #1d4fb5;
        }
    </style>
</head>
<body>
    <div class='mensaje'>
        <h2>✅ Correo enviado</h2>
        <p>Te hemos enviado un enlace para restablecer tu contraseña.<br>
        Revisa tu bandeja de entrada o la carpeta de spam.</p>
        <a class='volver' href='login.php'>← Volver al login</a>
    </div>
</body>
</html>
HTML;

exit;

    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }
} else {
    echo "Correo no encontrado.";
}
