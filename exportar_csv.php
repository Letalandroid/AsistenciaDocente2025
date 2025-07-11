<?php
require 'vendor/autoload.php';
include("conexion.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;

$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$spreadsheet = new Spreadsheet();


$datos = $spreadsheet->getActiveSheet();
$datos->setTitle('Datos');

$datos->fromArray([
    'Docente', 'Curso', 'Aula', 'Semestre',
    'Horas Curso', 'Horas Trabajadas', '% Cumplimiento',
    'Presente', 'Tarde', 'Justificada', 'No Justificada'
], NULL, 'A1');

$fila = 2;
$totales = ['Presente' => 0, 'Tarde' => 0, 'Justificada' => 0, 'NoJustificada' => 0];
$docentes = $cursos = $semestres = [];

$sql = "
SELECT 
    d.nombre_completo,
    c.nombre AS curso,
    au.codigo AS aula,
    s.nombre_ciclo AS semestre,
    a.id_asignacion,
    SUM(DATEDIFF(MINUTE, h.hora_inicio, h.hora_fin)) / 60.0 AS horas_semanales
FROM Asignacion a
JOIN Docentes d ON d.id_docente = a.id_docente
JOIN Cursos c ON c.id_curso = a.id_curso
JOIN Semestres s ON s.id_semestre = a.id_semestre
JOIN Horarios h ON h.id_asignacion = a.id_asignacion
LEFT JOIN Aulas au ON h.id_aula = au.id_aula
GROUP BY d.nombre_completo, c.nombre, au.codigo, s.nombre_ciclo, a.id_asignacion
";

$stmt = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $docente = $row['nombre_completo'];
    $curso = $row['curso'];
    $aula = $row['aula'] ?? 'N/A';
    $semestre = $row['semestre'];
    $id_asignacion = $row['id_asignacion'];
    $horas_curso = round(floatval($row['horas_semanales']) * 16, 2);

    $stmtH = sqlsrv_query($conn, "
        SELECT SUM(DATEDIFF(MINUTE, hora_entrada, hora_salida)) / 60.0 AS horas 
        FROM Asistencia 
        WHERE id_asignacion = ? AND fecha BETWEEN ? AND ? 
              AND hora_entrada IS NOT NULL AND hora_salida IS NOT NULL
    ", [$id_asignacion, $desde, $hasta]);
    $horas_trabajadas = 0;
    if ($r = sqlsrv_fetch_array($stmtH, SQLSRV_FETCH_ASSOC)) {
        $horas_trabajadas = round(floatval($r['horas']), 2);
    }

    $cumplimiento = $horas_curso > 0 ? round(($horas_trabajadas / $horas_curso) * 100, 2) : 0;

    
    $presente = $tarde = $justificada = $noJustificada = 0;
    $stmtC = sqlsrv_query($conn, "
        SELECT estado, justificada, COUNT(*) as total 
        FROM Asistencia 
        WHERE id_asignacion = ? AND fecha BETWEEN ? AND ?
        GROUP BY estado, justificada
    ", [$id_asignacion, $desde, $hasta]);

    while ($c = sqlsrv_fetch_array($stmtC, SQLSRV_FETCH_ASSOC)) {
        if ($c['estado'] === 'Presente') $presente += $c['total'];
        elseif ($c['estado'] === 'Tarde') $tarde += $c['total'];
        elseif ($c['estado'] === 'Falta') {
            if ($c['justificada']) $justificada += $c['total'];
            else $noJustificada += $c['total'];
        }
    }

    
    $totales['Presente'] += $presente;
    $totales['Tarde'] += $tarde;
    $totales['Justificada'] += $justificada;
    $totales['NoJustificada'] += $noJustificada;

    $docentes[] = [$docente, $cumplimiento];
    $cursos[] = [$curso, $horas_trabajadas];
    $semestres[$semestre][] = $cumplimiento;
    $tardanzas[] = [$docente, $tarde];

    $datos->fromArray([
        $docente, $curso, $aula, $semestre,
        $horas_curso, $horas_trabajadas, $cumplimiento,
        $presente, $tarde, $justificada, $noJustificada
    ], NULL, "A$fila");

    $fila++;
}

$lastRow = $fila - 1;


$graficos = $spreadsheet->createSheet();
$graficos->setTitle('Gráficos');


$graficos->fromArray(array_map(null, array_column($docentes, 0), array_column($docentes, 1)), NULL, 'A1');
$series1 = new DataSeries(
    DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, range(0, 1),
    [], [new DataSeriesValues('String', 'Gráficos!A1:A'.count($docentes))],
    [new DataSeriesValues('Number', 'Gráficos!B1:B'.count($docentes))]
);
$graficos->addChart(new Chart('Cumplimiento', new Title('% Cumplimiento por Docente'), new Legend(), new PlotArea(null, [$series1])))
         ->setTopLeftPosition('D1')->setBottomRightPosition('P20');


$graficos->fromArray([
    ['Tipo', 'Total'],
    ['Presente', $totales['Presente']],
    ['Tarde', $totales['Tarde']],
    ['Justificada', $totales['Justificada']],
    ['No Justificada', $totales['NoJustificada']]
], NULL, 'A22');
$series2 = new DataSeries(
    DataSeries::TYPE_PIECHART, null, [0],
    [], [new DataSeriesValues('String', 'Gráficos!A23:A26')],
    [new DataSeriesValues('Number', 'Gráficos!B23:B26')]
);
$graficos->addChart(new Chart('ResumenAsistencia', new Title('Resumen Asistencia Total'), null, new PlotArea(null, [$series2])))
         ->setTopLeftPosition('D22')->setBottomRightPosition('P42');


$graficos->fromArray(array_map(
    fn($sem, $vals) => [$sem, round(array_sum($vals)/count($vals), 2)],
    array_keys($semestres), $semestres
), NULL, 'A45');
$series3 = new DataSeries(
    DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, range(0, 1),
    [], [new DataSeriesValues('String', 'Gráficos!A45:A'.(44+count($semestres)))],
    [new DataSeriesValues('Number', 'Gráficos!B45:B'.(44+count($semestres)))]
);
$graficos->addChart(new Chart('CumplimientoSemestre', new Title('Promedio Cumplimiento por Semestre'), new Legend(), new PlotArea(null, [$series3])))
         ->setTopLeftPosition('D45')->setBottomRightPosition('P65');


$graficos->fromArray(array_map(null, array_column($cursos, 0), array_column($cursos, 1)), NULL, 'A68');
$series4 = new DataSeries(
    DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, range(0, 1),
    [], [new DataSeriesValues('String', 'Gráficos!A68:A'.(67+count($cursos)))],
    [new DataSeriesValues('Number', 'Gráficos!B68:B'.(67+count($cursos)))]
);
$graficos->addChart(new Chart('HorasCurso', new Title('Horas Trabajadas por Curso'), new Legend(), new PlotArea(null, [$series4])))
         ->setTopLeftPosition('D68')->setBottomRightPosition('P88');


$graficos->fromArray(array_map(null, array_column($tardanzas, 0), array_column($tardanzas, 1)), NULL, 'A91');
$series5 = new DataSeries(
    DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, range(0, 1),
    [], [new DataSeriesValues('String', 'Gráficos!A91:A'.(90+count($tardanzas)))],
    [new DataSeriesValues('Number', 'Gráficos!B91:B'.(90+count($tardanzas)))]
);
$graficos->addChart(new Chart('Tardanzas', new Title('Tardanzas por Docente'), new Legend(), new PlotArea(null, [$series5])))
         ->setTopLeftPosition('D91')->setBottomRightPosition('P111');


$filename = "reporte_powerbi.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save("php://output");
exit;


