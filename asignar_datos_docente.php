<?php
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    header("Location: login.php");
    exit();
}

include("conexion.php");


if (!isset($_SESSION['id_docente']) || empty($_SESSION['id_docente'])) {
    $id_usuario = $_SESSION['id_usuario'];
    $sql = "SELECT id_docente FROM Usuarios WHERE id_usuario = ?";
    $stmt = sqlsrv_query($conn, $sql, array($id_usuario));
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($row['id_docente'] !== null) {
            $_SESSION['id_docente'] = $row['id_docente'];
        } else {
            die("Error: El docente aún no tiene asociado un id_docente. Contacte al administrador.");
        }
    } else {
        die("Error al obtener el ID del docente: " . print_r(sqlsrv_errors(), true));
    }
}

$id_docente = $_SESSION['id_docente'];


function obtenerCursos($conn) {
    return sqlsrv_query($conn, "SELECT id_curso, nombre FROM Cursos");
}

function obtenerAulas($conn) {
    return sqlsrv_query($conn, "SELECT id_aula, codigo FROM Aulas");
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cantidad = count($_POST['id_curso']);
    for ($i = 0; $i < $cantidad; $i++) {
        $id_curso = $_POST['id_curso'][$i];
        $id_aula = $_POST['id_aula'][$i];
        $nombre_ciclo = trim($_POST['semestre'][$i]);
        $id_semestre = null;

        
        $sql_check = "SELECT id_semestre FROM Semestres WHERE nombre_ciclo = ?";
        $stmt_check = sqlsrv_query($conn, $sql_check, array($nombre_ciclo));
        if ($stmt_check && $row = sqlsrv_fetch_array($stmt_check, SQLSRV_FETCH_ASSOC)) {
            $id_semestre = $row['id_semestre'];
        } else {
            $sql_insert_sem = "INSERT INTO Semestres (nombre_ciclo) OUTPUT INSERTED.id_semestre VALUES (?)";
            $stmt_insert_sem = sqlsrv_query($conn, $sql_insert_sem, array($nombre_ciclo));
            if ($stmt_insert_sem && $row_insert = sqlsrv_fetch_array($stmt_insert_sem, SQLSRV_FETCH_ASSOC)) {
                $id_semestre = $row_insert['id_semestre'];
            } else {
                die("Error al insertar el semestre: " . print_r(sqlsrv_errors(), true));
            }
        }

        
        $sql_asignacion = "INSERT INTO Asignacion (id_docente, id_curso, turno, id_semestre)
                           OUTPUT INSERTED.id_asignacion 
                           VALUES (?, ?, NULL, ?)";
        $params_asig = array($id_docente, $id_curso, $id_semestre);
        $stmt_asig = sqlsrv_query($conn, $sql_asignacion, $params_asig);

        if ($stmt_asig && $row = sqlsrv_fetch_array($stmt_asig, SQLSRV_FETCH_ASSOC)) {
            $id_asignacion = $row['id_asignacion'];
        } else {
            die("Error al insertar asignación: " . print_r(sqlsrv_errors(), true));
        }

        
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        foreach ($dias as $dia) {
            $dias_key = "dias_{$i}";
            if (isset($_POST[$dias_key]) && in_array($dia, $_POST[$dias_key])) {
                $hora_inicio = $_POST["hora_inicio_{$i}_$dia"] ?? null;
                $hora_fin = $_POST["hora_fin_{$i}_$dia"] ?? null;

                if (!$hora_inicio || !$hora_fin || $hora_fin <= $hora_inicio) continue;

                $sql_conflicto = "
                        SELECT h.id_horario
                        FROM Horarios h
                        INNER JOIN Asignacion a ON h.id_asignacion = a.id_asignacion
                        WHERE a.id_curso = ?
                        AND a.id_semestre = ?
                        AND a.id_docente != ?
                        AND h.dia_semana = ?
                        AND h.id_aula = ?
                             AND (
                            (h.hora_inicio <= ? AND h.hora_fin > ?) OR
                            (h.hora_inicio < ? AND h.hora_fin >= ?) OR
                            (h.hora_inicio >= ? AND h.hora_fin <= ?)
                            )";

$params_conflicto = array(
    $id_curso, $id_semestre, $id_docente, $dia, $id_aula,
    $hora_inicio, $hora_inicio,
    $hora_fin, $hora_fin,
    $hora_inicio, $hora_fin
);

        $stmt_conflicto = sqlsrv_query($conn, $sql_conflicto, $params_conflicto);

        if ($stmt_conflicto && sqlsrv_has_rows($stmt_conflicto)) {
            echo "<script>alert('⚠️ Ya existe una asignación de este curso por otro docente en ese horario. Puedes cambiar de aula o modificar el horario.'); window.history.back();</script>";
            exit;
        }

                
                $h = intval(substr($hora_inicio, 0, 2));
                if ($h >= 6 && $h < 13) {
                    $turno = "Mañana";
                } elseif ($h >= 13 && $h < 19) {
                    $turno = "Tarde";
                } else {
                    $turno = "Noche";
                }

                
                $sql_horario = "INSERT INTO Horarios (id_asignacion, id_aula, dia_semana, hora_inicio, hora_fin, turno)
                                VALUES (?, ?, ?, ?, ?, ?)";
                $params_hor = array($id_asignacion, $id_aula, $dia, $hora_inicio, $hora_fin, $turno);
                $stmt_hor = sqlsrv_query($conn, $sql_horario, $params_hor);
                if (!$stmt_hor) {
                    die("Error al insertar horario: " . print_r(sqlsrv_errors(), true));
                }
            }
        }

        
        $id_usuario = $_SESSION['id_usuario'];
        $sql_check_usuario = "SELECT id_docente FROM Usuarios WHERE id_usuario = ?";
        $stmt_usuario = sqlsrv_query($conn, $sql_check_usuario, array($id_usuario));
        if ($stmt_usuario && $row = sqlsrv_fetch_array($stmt_usuario, SQLSRV_FETCH_ASSOC)) {
            if ($row['id_docente'] === null) {
                $sql_update_usuario = "UPDATE Usuarios SET id_docente = ? WHERE id_usuario = ?";
                sqlsrv_query($conn, $sql_update_usuario, array($id_docente, $id_usuario));
            }
        }
    }

    $_SESSION['asignacion_exitosa'] = true;
    header("Location: panel_docente.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Curso y Horario</title>
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
            background-color: rgba(255,255,255,0.95);
            width: 500px;
            margin: 60px auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 0px 12px rgba(0,0,0,0.2);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2a6ddf;
        }

        label {
            font-weight: bold;
        }

        select, input[type="time"], input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .checkbox-dia {
            margin-top: 10px;
        }

        button, .add-btn {
            width: 100%;
            padding: 10px;
            background-color: #2a6ddf;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            margin-top: 20px;
            cursor: pointer;
        }

        button:hover, .add-btn:hover {
            background-color: #1d4fb5;
        }

        .horario-box {
            display: none;
            margin-left: 20px;
            margin-bottom: 10px;
        }

        .bloque-curso {
            margin-bottom: 40px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Asignar Curso y Horarios</h2>
    <form method="post" id="formCursos" onsubmit="return validateForm()">
        <div id="cursosContainer">
            <div class="bloque-curso">
                <label>Curso:</label>
                <select name="id_curso[]" required>
                    <?php $resCursos = obtenerCursos($conn); while ($c = sqlsrv_fetch_array($resCursos, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?= $c['id_curso']; ?>"><?= $c['nombre']; ?></option>
                    <?php } ?>
                </select>

                <label>Aula:</label>
                <select name="id_aula[]" required>
                    <?php $resAulas = obtenerAulas($conn); while ($a = sqlsrv_fetch_array($resAulas, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?= $a['id_aula']; ?>"><?= $a['codigo']; ?></option>
                    <?php } ?>
                </select>

                <label>Semestre:</label>
                <input type="text" name="semestre[]" placeholder="Ej: 2025-I" required>

                <label>Días y horarios:</label><br>
                <?php
                $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                foreach ($dias as $dia): ?>
                    <div class="checkbox-dia">
                        <input type="checkbox" name="dias_0[]" value="<?= $dia ?>" onclick="mostrarHorario(this, '0_<?= $dia ?>')"> <?= $dia ?><br>
                        <div id="horario_0_<?= $dia ?>" class="horario-box">
                            Hora inicio: <input type="time" name="hora_inicio_0_<?= $dia ?>"><br>
                            Hora fin: <input type="time" name="hora_fin_0_<?= $dia ?>"><br>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="button" class="add-btn" onclick="agregarCurso()">➕ Agregar otro curso</button>
        <button type="submit">Guardar asignaciones</button>
    </form>
</div>

<script>
let cursoIndex = 1;

function mostrarHorario(checkbox, diaId) {
    const seccion = document.getElementById('horario_' + diaId);
    seccion.style.display = checkbox.checked ? 'block' : 'none';
}

function agregarCurso() {
    const container = document.getElementById("cursosContainer");
    const nuevo = container.children[0].cloneNode(true);

    nuevo.querySelectorAll("select, input").forEach(input => {
        if (input.name.includes("dias_")) return;
        if (input.type !== "checkbox") input.value = "";
    });

    nuevo.querySelectorAll("input[type='checkbox']").forEach(cb => {
        const dia = cb.value;
        cb.name = `dias_${cursoIndex}[]`;
        cb.checked = false;
        cb.setAttribute("onclick", `mostrarHorario(this, '${cursoIndex}_${dia}')`);
    });

    nuevo.querySelectorAll(".horario-box").forEach(div => {
        div.style.display = "none";
        const parts = div.id.split('_');
        const dia = parts[2];
        div.id = `horario_${cursoIndex}_${dia}`;

        div.querySelectorAll("input").forEach(input => {
            const tipo = input.name.includes("inicio") ? "inicio" : "fin";
            input.name = `hora_${tipo}_${cursoIndex}_${dia}`;
            input.value = "";
        });
    });

    container.appendChild(nuevo);
    cursoIndex++;
}

function validateForm() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
    if (checkboxes.length === 0) {
        alert("Debes seleccionar al menos un día y horario.");
        return false;
    }
    return true;
}
</script>
</body>
</html>







