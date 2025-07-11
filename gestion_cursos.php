<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php?error=Acceso no autorizado");
    exit();
}
include("conexion.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_nombre'])) {
    $nombre = trim($_POST['nuevo_nombre']);
    if ($nombre !== '') {
        $stmt = sqlsrv_query($conn, "INSERT INTO Cursos (nombre) VALUES (?)", array($nombre));
        if (!$stmt) {
            $error = "Error al agregar curso: " . print_r(sqlsrv_errors(), true);
        }
    }
}


if (isset($_POST['editar_id'], $_POST['editar_nombre'])) {
    $id = $_POST['editar_id'];
    $nombre = trim($_POST['editar_nombre']);
    if ($nombre !== '') {
        $stmt = sqlsrv_query($conn, "UPDATE Cursos SET nombre = ? WHERE id_curso = ?", array($nombre, $id));
        if (!$stmt) {
            $error = "Error al editar curso: " . print_r(sqlsrv_errors(), true);
        }
    }
}


if (isset($_POST['eliminar_id'])) {
    $id = $_POST['eliminar_id'];

    
    $check = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM Asignacion WHERE id_curso = ?", array($id));
    $row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);

    if ($row['total'] > 0) {
        echo "<script>alert('No se puede eliminar este curso porque está asignado a docentes.');</script>";
    } else {
        $stmt = sqlsrv_query($conn, "DELETE FROM Cursos WHERE id_curso = ?", array($id));
        if (!$stmt) {
            echo "<script>alert('Error al eliminar el curso.');</script>";
        }
    }
}


$cursos = sqlsrv_query($conn, "SELECT * FROM Cursos ORDER BY id_curso");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Cursos</title>
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
            width: 90%;
            max-width: 700px;
            margin: 50px auto;
            background-color: rgba(255,255,255,0.97);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 12px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #2a6ddf;
            margin-bottom: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
        }
        th {
            background-color: #2a6ddf;
            color: white;
        }
        input[type="text"] {
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 90%;
        }
        button {
            background-color: #2a6ddf;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #1d4fb5;
        }
        .volver {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #2a6ddf;
            font-weight: bold;
            text-decoration: none;
        }
        form.inline {
            display: inline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📚 Gestión de Cursos</h2>

    
    <form method="POST" style="margin-bottom: 20px; text-align:center;">
        <input type="text" name="nuevo_nombre" placeholder="Nombre del nuevo curso" required>
        <button type="submit">➕ Agregar Curso</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre del Curso</th>
            <th>Acciones</th>
        </tr>
        <?php while ($curso = sqlsrv_fetch_array($cursos, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
            <td><?= $curso['id_curso']; ?></td>
            <td>
                <form method="POST" class="inline">
                    <input type="hidden" name="editar_id" value="<?= $curso['id_curso']; ?>">
                    <input type="text" name="editar_nombre" value="<?= htmlspecialchars($curso['nombre']); ?>" required>
            </td>
            <td>
                    <button type="submit">✏️ Editar</button>
                </form>
                <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este curso?');">
                    <input type="hidden" name="eliminar_id" value="<?= $curso['id_curso']; ?>">
                    <button type="submit">🗑️ Eliminar</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>

    <a class="volver" href="panel_admin.php">← Volver al Panel Admin</a>
</div>
</body>
</html>
