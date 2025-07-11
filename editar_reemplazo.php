<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}
include("conexion.php");

if (!isset($_GET['id'])) {
    header("Location: reemplazos_asignados.php");
    exit();
}

$id_reemplazo = $_GET['id'];


$sql = "SELECT r.*, d1.nombre_completo AS titular, d2.nombre_completo AS reemplazo
        FROM Reemplazos r
        JOIN Docentes d1 ON r.id_docente = d1.id_docente
        JOIN Docentes d2 ON r.id_docente_reemplazo = d2.id_docente
        WHERE id_reemplazo = ?";
$stmt = sqlsrv_query($conn, $sql, array($id_reemplazo));
$datos = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$datos) {
    die("Reemplazo no encontrado.");
}


$sql_curso = "
    SELECT a.id_curso FROM Asignacion a
    JOIN Horarios h ON a.id_asignacion = h.id_asignacion
    WHERE a.id_docente = ? AND CAST(h.dia_semana AS VARCHAR) = DATENAME(WEEKDAY, ?)";
$stmt_curso = sqlsrv_query($conn, $sql_curso, array($datos['id_docente'], $datos['fecha']));
$row_curso = sqlsrv_fetch_array($stmt_curso, SQLSRV_FETCH_ASSOC);
$id_curso = $row_curso['id_curso'] ?? null;


$reemplazantes = [];
if ($id_curso) {
    $sql_disp = "
        SELECT DISTINCT d.id_docente, d.nombre_completo
        FROM Asignacion a
        JOIN Docentes d ON a.id_docente = d.id_docente
        WHERE a.id_curso = ? AND d.id_docente != ?";
    $stmt_disp = sqlsrv_query($conn, $sql_disp, array($id_curso, $datos['id_docente']));
    while ($r = sqlsrv_fetch_array($stmt_disp, SQLSRV_FETCH_ASSOC)) {
        $reemplazantes[] = $r;
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nuevo_id_reemplazo = $_POST['id_docente_reemplazo'];
    $motivo = $_POST['motivo'];

    $update = "UPDATE Reemplazos SET id_docente_reemplazo = ?, motivo = ? WHERE id_reemplazo = ?";
    $params = array($nuevo_id_reemplazo, $motivo, $id_reemplazo);
    $stmt_update = sqlsrv_query($conn, $update, $params);

    if ($stmt_update) {
        echo "<script>alert('Reemplazo actualizado correctamente.'); window.location.href='reemplazos_asignados.php';</script>";
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
    <title>Editar Reemplazo</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            background-image: url('img/fondo_login.jpg');
            background-size: cover;
            font-family: Arial, sans-serif;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 750px;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: rgba(255,255,255,0.96);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 12px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #2a6ddf;
        }
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        input[type="text"], select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            background-color: #2a6ddf;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #1d4fb5;
        }
        a {
            display: block;
            margin-top: 25px;
            text-align: center;
            color: #2a6ddf;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>✏️ Editar Reemplazo</h2>

    <p><strong>Docente titular:</strong> <?= htmlspecialchars($datos['titular']) ?></p>
    <p><strong>Fecha:</strong> <?= $datos['fecha']->format('Y-m-d') ?></p>

    <form method="post">
        <label>Docente reemplazo:</label>
        <select name="id_docente_reemplazo" required>
            <option value="">-- Seleccionar --</option>
            <?php foreach ($reemplazantes as $r) { ?>
                <option value="<?= $r['id_docente'] ?>" <?= $r['id_docente'] == $datos['id_docente_reemplazo'] ? 'selected' : '' ?>>
                    <?= $r['nombre_completo'] ?>
                </option>
            <?php } ?>
        </select>

        <label>Motivo:</label>
        <textarea name="motivo" rows="3" required><?= htmlspecialchars($datos['motivo']) ?></textarea>

        <button type="submit">Guardar Cambios</button>
    </form>

    <a href="reemplazos_asignados.php">← Volver a Reemplazos</a>
</div>
</body>
</html>
