<?php
require 'vendor/autoload.php';
include("conexion.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();


$sheet->setCellValue('A1', 'Nombre del Docente');
$sheet->mergeCells('A1:B1');
$sheet->setCellValue('C1', 'Curso');
$sheet->setCellValue('D1', 'Aula');
$sheet->setCellValue('E1', 'Semestre');
$sheet->setCellValue('F1', 'Estado');
$sheet->setCellValue('G1', 'Fecha de Asistencia');
$sheet->mergeCells('G1:H1');
$sheet->setCellValue('I1', 'Fecha de Falta (Justificada)');
$sheet->mergeCells('I1:J1');
$sheet->setCellValue('K1', 'Horas Trabajadas');
$sheet->mergeCells('K1:L1');
$sheet->setCellValue('M1', 'Horas del Curso');
$sheet->mergeCells('M1:N1');
$sheet->setCellValue('O1', '% de Cumplimiento');
$sheet->mergeCells('O1:P1');


$sheet->getStyle('A1:P1')->getFont()->setBold(true);
$sheet->getStyle('A1:P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$fila = 2;

$sql = "
SELECT 
    d.nombre_completo,
    c.nombre AS curso,
    au.codigo AS aula,
    s.nombre_ciclo AS semestre,
    a.id_asignacion,
    asi.fecha,
    asi.estado,
    asi.justificada,
    asi.hora_entrada,
    asi.hora_salida,
    j.fecha_subida
FROM Asistencia asi
JOIN Asignacion a ON asi.id_asignacion = a.id_asignacion
JOIN Docentes d ON a.id_docente = d.id_docente
JOIN Cursos c ON a.id_curso = c.id_curso
JOIN Semestres s ON a.id_semestre = s.id_semestre
LEFT JOIN Justificacion j ON asi.justificacion_id = j.id_justificacion
LEFT JOIN Horarios h ON h.id_asignacion = a.id_asignacion
LEFT JOIN Aulas au ON h.id_aula = au.id_aula
WHERE asi.fecha BETWEEN ? AND ?
ORDER BY d.nombre_completo, asi.fecha
";

$stmt = sqlsrv_query($conn, $sql, [$desde, $hasta]);
if (!$stmt) {
    die("Error al obtener datos: " . print_r(sqlsrv_errors(), true));
}

$asignacionHoras = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $docente = $row['nombre_completo'];
    $curso = $row['curso'];
    $aula = $row['aula'] ?? 'N/A';
    $semestre = $row['semestre'];
    $estado = $row['estado'];
    $justificada = $row['justificada'];
    $fecha_asistencia = $row['fecha'] ? $row['fecha']->format('d/m/Y') : '';
    $fecha_falta = ($estado === 'Falta' && $justificada && $row['fecha_subida']) ? $row['fecha_subida']->format('d/m/Y') : '';

    $entrada = $row['hora_entrada'];
    $salida = $row['hora_salida'];
    $horas_trabajadas = 0;
    if ($entrada && $salida) {
        $horas_trabajadas = (strtotime($salida->format('H:i:s')) - strtotime($entrada->format('H:i:s'))) / 3600;
        $horas_trabajadas = round($horas_trabajadas, 2);
    }

    $id_asignacion = $row['id_asignacion'];
    if (!isset($asignacionHoras[$id_asignacion])) {
        $sql_horas = "SELECT SUM(DATEDIFF(MINUTE, hora_inicio, hora_fin)) / 60.0 AS horas_semanales FROM Horarios WHERE id_asignacion = ?";
        $stmt_horas = sqlsrv_query($conn, $sql_horas, [$id_asignacion]);
        $horas_totales = 0;
        if ($stmt_horas && $hr = sqlsrv_fetch_array($stmt_horas, SQLSRV_FETCH_ASSOC)) {
            $horas_totales = floatval($hr['horas_semanales']) * 16;
        }
        $asignacionHoras[$id_asignacion] = $horas_totales;
    } else {
        $horas_totales = $asignacionHoras[$id_asignacion];
    }

    if (!isset($asignacionCumplidas[$id_asignacion])) $asignacionCumplidas[$id_asignacion] = 0;
    $asignacionCumplidas[$id_asignacion] += $horas_trabajadas;
    $cumplimiento = ($horas_totales > 0) ? round(($asignacionCumplidas[$id_asignacion] / $horas_totales) * 100, 2) . '%' : '0%';

    
    $sheet->mergeCells("A$fila:B$fila");
    $sheet->setCellValue("A$fila", $docente);
    $sheet->setCellValue("C$fila", $curso);
    $sheet->setCellValue("D$fila", $aula);
    $sheet->setCellValue("E$fila", $semestre);
    $sheet->setCellValue("F$fila", $estado);
    $sheet->mergeCells("G$fila:H$fila");
    $sheet->setCellValue("G$fila", $fecha_asistencia);
    $sheet->mergeCells("I$fila:J$fila");
    $sheet->setCellValue("I$fila", $fecha_falta);
    $sheet->mergeCells("K$fila:L$fila");
    $sheet->setCellValue("K$fila", $horas_trabajadas);
    $sheet->mergeCells("M$fila:N$fila");
    $sheet->setCellValue("M$fila", round($horas_totales, 2));
    $sheet->mergeCells("O$fila:P$fila");
    $sheet->setCellValue("O$fila", $cumplimiento);

    $fila++;
}

$filename = "reporte_asistencia_completo_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;




