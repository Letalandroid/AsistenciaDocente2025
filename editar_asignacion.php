<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php?error=Acceso no autorizado");
    exit();
}
include("conexion.php");

if (!isset($_GET['id'])) {
    header("Location: asignaciones_docentes.php");
    exit();
}

$id_asignacion = $_GET['id'];


$sql = "SELECT ag.id_curso, ag.id_semestre, h.turno, h.dia_semana, h.hora_inicio, h.hora_fin, h.id_aula
        FROM Asignacion ag
        JOIN Horarios h ON h.id_asignacion = ag.id_asignacion
        WHERE ag.id_asignacion = ?";
$stmt = sqlsrv_query($conn, $sql, array($id_asignacion));

$horarios = [];
$datos_asignacion = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $datos_asignacion['id_curso'] = $row['id_curso'];
    $datos_asignacion['id_semestre'] = $row['id_semestre'];
    $datos_asignacion['id_aula'] = $row['id_aula'];  
    $horarios[$row['dia_semana']] = [
        'hora_inicio' => substr($row['hora_inicio'], 0, 5),
        'hora_fin' => substr($row['hora_fin'], 0, 5)
    ];
}


$cursos = sqlsrv_query($conn, "SELECT id_curso, nombre FROM Cursos");
$semestres = sqlsrv_query($conn, "SELECT id_semestre, nombre_ciclo FROM Semestres");
$aulas = sqlsrv_query($conn, "SELECT id_aula, codigo FROM Aulas");


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_curso = $_POST['id_curso'];
    $id_semestre = $_POST['id_semestre'];
    $id_aula = $_POST['id_aula'];
    $dias = $_POST['dias'] ?? [];

    
    $sql_update = "UPDATE Asignacion SET id_curso = ?, id_semestre = ? WHERE id_asignacion = ?";
    sqlsrv_query($conn, $sql_update, array($id_curso, $id_semestre, $id_asignacion));

    
    sqlsrv_query($conn, "DELETE FROM Horarios WHERE id_asignacion = ?", array($id_asignacion));

    
    foreach ($dias as $dia) {
        $inicio = $_POST["hora_inicio_$dia"];
        $fin = $_POST["hora_fin_$dia"];
        if ($fin <= $inicio) continue;

        $h = intval(substr($inicio, 0, 2));
        if ($h >= 6 && $h < 12) $turno = "Mañana";
        elseif ($h >= 12 && $h < 18) $turno = "Tarde";
        else $turno = "Noche";

        $sql_hor = "INSERT INTO Horarios (id_asignacion, id_aula, dia_semana, hora_inicio, hora_fin, turno)
                    VALUES (?, ?, ?, ?, ?, ?)";
        sqlsrv_query($conn, $sql_hor, array($id_asignacion, $id_aula, $dia, $inicio, $fin, $turno));
    }

    echo "<script>alert('Asignación actualizada correctamente.'); window.location.href='asignaciones_docentes.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Asignación</title>
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
            width: 500px;
            margin: 60px auto;
            background-color: rgba(255,255,255,0.96);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #2a6ddf;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        select, input[type="time"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .horario-box {
            margin-top: 10px;
            padding-left: 10px;
        }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 10px;
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
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            color: #2a6ddf;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>✏️ Editar Asignación</h2>
    <form method="post">
        <label>Curso:</label>
        <select name="id_curso" required>
            <?php while ($c = sqlsrv_fetch_array($cursos, SQLSRV_FETCH_ASSOC)) { ?>
                <option value="<?= $c['id_curso']; ?>" <?= $c['id_curso'] == $datos_asignacion['id_curso'] ? 'selected' : '' ?>>
                    <?= $c['nombre']; ?>
                </option>
            <?php } ?>
        </select>

        <label>Aula:</label>
        <select name="id_aula" required>
            <?php while ($a = sqlsrv_fetch_array($aulas, SQLSRV_FETCH_ASSOC)) { ?>
                <option value="<?= $a['id_aula']; ?>" <?= $a['id_aula'] == $datos_asignacion['id_aula'] ? 'selected' : '' ?>>
                    <?= $a['codigo']; ?>
                </option>
            <?php } ?>
        </select>

        <label>Semestre:</label>
        <select name="id_semestre" required>
            <?php while ($s = sqlsrv_fetch_array($semestres, SQLSRV_FETCH_ASSOC)) { ?>
                <option value="<?= $s['id_semestre']; ?>" <?= $s['id_semestre'] == $datos_asignacion['id_semestre'] ? 'selected' : '' ?>>
                    <?= $s['nombre_ciclo']; ?>
                </option>
            <?php } ?>
        </select>

        <label>Días y horarios:</label>
        <?php
        $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        foreach ($dias_semana as $dia):
            $h_inicio = $horarios[$dia]['hora_inicio'] ?? '';
            $h_fin = $horarios[$dia]['hora_fin'] ?? '';
            $checked = $h_inicio && $h_fin ? 'checked' : '';
        ?>
            <div class="horario-box">
                <input type="checkbox" name="dias[]" value="<?= $dia ?>" <?= $checked ?>>
                <?= $dia ?>:<br>
                Hora inicio: <input type="time" name="hora_inicio_<?= $dia ?>" value="<?= $h_inicio ?>"><br>
                Hora fin: <input type="time" name="hora_fin_<?= $dia ?>" value="<?= $h_fin ?>"><br>
            </div>
        <?php endforeach; ?>

        <button type="submit">Guardar Cambios</button>
    </form>
    <a href="asignaciones_docentes.php">← Volver a Asignaciones</a>
</div>
</body>
</html>
