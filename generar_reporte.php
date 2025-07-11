<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php");
    exit();
}
include("conexion.php");

$vista = $_GET['vista'] ?? 'basico';
$tipo = $_GET['tipo'] ?? 'semanal';
$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$datos = [];
$total = ['Presente' => 0, 'Tarde' => 0, 'Falta_Justificada' => 0, 'Falta_No_Justificada' => 0];

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if ($vista === 'basico') {
        $sql = "SELECT d.nombre_completo, a.fecha, a.estado, a.justificado
                FROM Asistencia a
                JOIN Docentes d ON a.id_docente = d.id_docente
                WHERE a.fecha BETWEEN ? AND ?
                ORDER BY d.nombre_completo, a.fecha";

        $params = [$desde, $hasta];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $nombre = $row['nombre_completo'];
                $estado = $row['estado'];
                $justificado = $row['justificado'];

                if (!isset($datos[$nombre])) {
                    $datos[$nombre] = ['Presente' => 0, 'Tarde' => 0, 'Falta_Justificada' => 0, 'Falta_No_Justificada' => 0];
                }

                if ($estado === 'Presente') {
                    $datos[$nombre]['Presente']++;
                    $total['Presente']++;
                } elseif ($estado === 'Tarde') {
                    $datos[$nombre]['Tarde']++;
                    $total['Tarde']++;
                } elseif ($estado === 'Falta') {
                    if ($justificado) {
                        $datos[$nombre]['Falta_Justificada']++;
                        $total['Falta_Justificada']++;
                    } else {
                        $datos[$nombre]['Falta_No_Justificada']++;
                        $total['Falta_No_Justificada']++;
                    }
                }
            }
        }
    } elseif ($vista === 'detallado') {
        $sql = "
        SELECT 
            d.nombre_completo,
            c.nombre AS curso,
            SUM(DATEDIFF(MINUTE, h.hora_inicio, h.hora_fin)) / 60.0 AS horas_semanales,
            a.id_asignacion
        FROM Asignacion a
        JOIN Docentes d ON a.id_docente = d.id_docente
        JOIN Cursos c ON a.id_curso = c.id_curso
        JOIN Horarios h ON h.id_asignacion = a.id_asignacion
        GROUP BY d.nombre_completo, c.nombre, a.id_asignacion";

        $stmt = sqlsrv_query($conn, $sql);

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $docente = $row['nombre_completo'];
            $curso = $row['curso'];
            $horas_totales = $row['horas_semanales'] * 16;
            $id_asignacion = $row['id_asignacion'];

            $sql_horas = "SELECT SUM(DATEDIFF(MINUTE, hora_entrada, hora_salida)) / 60.0 AS horas_trabajadas
                          FROM Asistencia
                          WHERE id_asignacion = ? AND fecha BETWEEN ? AND ? AND hora_entrada IS NOT NULL AND hora_salida IS NOT NULL";
            $stmt_horas = sqlsrv_query($conn, [$id_asignacion, $desde, $hasta]);
            $horas_trabajadas = 0;
            if ($stmt_horas && $r = sqlsrv_fetch_array($stmt_horas, SQLSRV_FETCH_ASSOC)) {
                $horas_trabajadas = floatval($r['horas_trabajadas'] ?? 0);
            }

            $cumplimiento = $horas_totales > 0 ? round(($horas_trabajadas / $horas_totales) * 100, 2) . '%' : '0%';

            $sql_asist = "SELECT estado, justificada, COUNT(*) AS cantidad
                          FROM Asistencia
                          WHERE id_asignacion = ? AND fecha BETWEEN ? AND ?
                          GROUP BY estado, justificada";
            $stmt_asist = sqlsrv_query($conn, [$id_asignacion, $desde, $hasta]);
            $resumen = ['Presente' => 0, 'Tarde' => 0, 'Falta_Justificada' => 0, 'Falta_No_Justificada' => 0];
            while ($r = sqlsrv_fetch_array($stmt_asist, SQLSRV_FETCH_ASSOC)) {
                if ($r['estado'] === 'Presente') $resumen['Presente'] += $r['cantidad'];
                elseif ($r['estado'] === 'Tarde') $resumen['Tarde'] += $r['cantidad'];
                elseif ($r['estado'] === 'Falta') {
                    if ($r['justificada']) $resumen['Falta_Justificada'] += $r['cantidad'];
                    else $resumen['Falta_No_Justificada'] += $r['cantidad'];
                }
            }

            $datos[] = [
                'docente' => $docente,
                'curso' => $curso,
                'horas_totales' => round($horas_totales, 2),
                'horas_trabajadas' => round($horas_trabajadas, 2),
                'cumplimiento' => $cumplimiento,
                'Presente' => $resumen['Presente'],
                'Tarde' => $resumen['Tarde'],
                'FJ' => $resumen['Falta_Justificada'],
                'FNJ' => $resumen['Falta_No_Justificada']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencia</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        form { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: center; border: 1px solid #ccc; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h2>Reporte <?= ucfirst($tipo) ?> de Asistencia</h2>

    <form method="get">
        <label>Tipo de Reporte:</label>
        <select name="tipo">
            <option value="semanal" <?= $tipo === 'semanal' ? 'selected' : '' ?>>Semanal</option>
            <option value="mensual" <?= $tipo === 'mensual' ? 'selected' : '' ?>>Mensual</option>
        </select>
        &nbsp;&nbsp;
        <label>Vista:</label>
        <select name="vista">
            <option value="basico" <?= $vista === 'basico' ? 'selected' : '' ?>>Resumen básico</option>
            <option value="detallado" <?= $vista === 'detallado' ? 'selected' : '' ?>>Resumen detallado</option>
        </select>
        &nbsp;&nbsp;
        <label>Desde:</label>
        <input type="date" name="desde" value="<?= $desde ?>">
        &nbsp;&nbsp;
        <label>Hasta:</label>
        <input type="date" name="hasta" value="<?= $hasta ?>">
        &nbsp;&nbsp;
        <button type="submit">Generar</button>
    </form>

    <?php if (!empty($datos)) { ?>
        <?php if ($vista === 'basico') { ?>
            <table>
                <tr>
                    <th>Docente</th>
                    <th>Presente</th>
                    <th>Tarde</th>
                    <th>Faltas Justificadas</th>
                    <th>Faltas No Justificadas</th>
                </tr>
                <?php foreach ($datos as $docente => $valores) { ?>
                <tr>
                    <td><?= $docente ?></td>
                    <td><?= $valores['Presente'] ?></td>
                    <td><?= $valores['Tarde'] ?></td>
                    <td><?= $valores['Falta_Justificada'] ?></td>
                    <td><?= $valores['Falta_No_Justificada'] ?></td>
                </tr>
                <?php } ?>
                <tr style="background:#f0f0f0; font-weight: bold;">
                    <td>Total</td>
                    <td><?= $total['Presente'] ?></td>
                    <td><?= $total['Tarde'] ?></td>
                    <td><?= $total['Falta_Justificada'] ?></td>
                    <td><?= $total['Falta_No_Justificada'] ?></td>
                </tr>
            </table>
        <?php } else { ?>
            <table>
                <tr>
                    <th>Docente</th>
                    <th>Curso</th>
                    <th>Horas del Curso</th>
                    <th>Horas Trabajadas</th>
                    <th>% Cumplimiento</th>
                    <th>Presente</th>
                    <th>Tarde</th>
                    <th>F. Justificada</th>
                    <th>F. No Justificada</th>
                </tr>
                <?php foreach ($datos as $d) { ?>
                <tr>
                    <td><?= $d['docente'] ?></td>
                    <td><?= $d['curso'] ?></td>
                    <td><?= $d['horas_totales'] ?></td>
                    <td><?= $d['horas_trabajadas'] ?></td>
                    <td><?= $d['cumplimiento'] ?></td>
                    <td><?= $d['Presente'] ?></td>
                    <td><?= $d['Tarde'] ?></td>
                    <td><?= $d['FJ'] ?></td>
                    <td><?= $d['FNJ'] ?></td>
                </tr>
                <?php } ?>
            </table>
        <?php } ?>

        <br>
        <a href="exportar_excel.php?desde=<?= $desde ?>&hasta=<?= $hasta ?>&tipo=<?= $tipo ?>&vista=<?= $vista ?>">📥 Exportar a Excel</a> |
        <a href="exportar_csv.php?desde=<?= $desde ?>&hasta=<?= $hasta ?>&tipo=<?= $tipo ?>&vista=<?= $vista ?>">📄 Descargar CSV (Power BI)</a>
    <?php } else { ?>
        <p>No hay registros en el rango seleccionado.</p>
    <?php } ?>

    <br><br>
    <a href="panel_admin.php">← Volver al panel</a>
</div>
</body>
</html>

