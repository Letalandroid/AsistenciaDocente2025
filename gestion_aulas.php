<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php?error=Acceso no autorizado");
    exit();
}
include("conexion.php");


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_codigo'])) {
    $codigo = trim($_POST['nuevo_codigo']);
    if ($codigo !== '') {
        $stmt = sqlsrv_query($conn, "INSERT INTO Aulas (codigo) VALUES (?)", array($codigo));
        if (!$stmt) {
            echo "<script>alert('Error al agregar aula.');</script>";
        }
    }
}


if (isset($_POST['editar_id'], $_POST['editar_codigo'])) {
    $id = $_POST['editar_id'];
    $codigo = trim($_POST['editar_codigo']);
    if ($codigo !== '') {
        $stmt = sqlsrv_query($conn, "UPDATE Aulas SET codigo = ? WHERE id_aula = ?", array($codigo, $id));
        if (!$stmt) {
            echo "<script>alert('Error al editar aula.');</script>";
        }
    }
}


if (isset($_POST['eliminar_id'])) {
    $id = $_POST['eliminar_id'];
    $check = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM Horarios WHERE id_aula = ?", array($id));
    $row = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);

    if ($row['total'] > 0) {
        echo "<script>alert('No se puede eliminar el aula porque está asignada en horarios.');</script>";
    } else {
        $stmt = sqlsrv_query($conn, "DELETE FROM Aulas WHERE id_aula = ?", array($id));
        if (!$stmt) {
            echo "<script>alert('Error al eliminar aula.');</script>";
        }
    }
}


$aulas = sqlsrv_query($conn, "SELECT * FROM Aulas ORDER BY id_aula");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Aulas</title>
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
    <h2>🏫 Gestión de Aulas</h2>

    
    <form method="POST" style="margin-bottom: 20px; text-align:center;">
        <input type="text" name="nuevo_codigo" placeholder="Código del aula" required>
        <button type="submit">➕ Agregar Aula</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Código del Aula</th>
            <th>Acciones</th>
        </tr>
        <?php while ($aula = sqlsrv_fetch_array($aulas, SQLSRV_FETCH_ASSOC)) { ?>
        <tr>
            <td><?= $aula['id_aula']; ?></td>
            <td>
                <form method="POST" class="inline">
                    <input type="hidden" name="editar_id" value="<?= $aula['id_aula']; ?>">
                    <input type="text" name="editar_codigo" value="<?= htmlspecialchars($aula['codigo']); ?>" required>
            </td>
            <td>
                    <button type="submit">✏️ Editar</button>
                </form>
                <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta aula?');">
                    <input type="hidden" name="eliminar_id" value="<?= $aula['id_aula']; ?>">
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
