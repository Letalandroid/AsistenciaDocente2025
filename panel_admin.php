<?php
session_start();
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header("Location: login.php?error=Acceso no autorizado");
    exit();
}
include("conexion.php");

function obtenerTotal($conn, $query) {
    $stmt = sqlsrv_query($conn, $query);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        return $row['total'];
    }
    return 0;
}

$total_docentes     = obtenerTotal($conn, "SELECT COUNT(*) AS total FROM Docentes");
$total_asistencias  = obtenerTotal($conn, "SELECT COUNT(*) AS total FROM Asistencia");
$tardanzas          = obtenerTotal($conn, "SELECT COUNT(*) AS total FROM Asistencia WHERE estado = 'Tarde'");
$faltas             = obtenerTotal($conn, "SELECT COUNT(*) AS total FROM Asistencia WHERE estado = 'Falta'");
$pendientes         = obtenerTotal($conn, "SELECT COUNT(*) AS total FROM Justificacion WHERE estado = 'Pendiente'");

$sql_docentes = "SELECT * FROM Docentes";
$stmt = sqlsrv_query($conn, $sql_docentes);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin</title>
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
            max-width: 1100px;
            margin: 50px auto;
            background-color: rgba(255, 255, 255, 0.97);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 12px rgba(0,0,0,0.2);
        }
        h2, h3 {
            text-align: center;
            color:rgb(12, 12, 12);
        }
        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 30px;
        }
        .card {
            background:rgb(63, 144, 230);
            color: white;
            padding: 20px;
            border-radius: 10px;
            width: 200px;
            text-align: center;
        }
        .card:nth-child(2) { background:rgb(99, 202, 218); }
        .card:nth-child(3) { background: #ffc107; color: black; }
        .card:nth-child(4) { background: #dc3545; }
        .card:nth-child(5) { background: #6f42c1; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
        }
        th {
            background:rgb(0, 89, 255);
            color: white;
        }
        .acciones a, .acciones button {
            margin: 0 5px;
            font-size: 0.9em;
        }
        .acciones form {
            display: inline;
        }
        .enlaces {
            text-align: center;
            margin-top: 25px;
        }
        .enlaces a, .enlaces button {
            display: inline-block;
            margin: 5px 10px;
            background-color: #2a6ddf;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .enlaces a:hover, .enlaces button:hover {
            background-color: #1d4fb5;
        }
        #opcionesReporte a {
            display: inline-block;
            margin: 5px 10px;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #ff9800;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            font-weight: bold;
            z-index: 9999;
            animation: desaparecer 10s forwards;
        }

        @keyframes desaparecer {
            0%   { opacity: 1; }
            80%  { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
    </style>
</head>
<body>

<?php if ($pendientes > 0): ?>

<div class="toast">
    ⚠️ Tienes <?= $pendientes ?> justificación(es) pendiente(s) por revisar.
</div>
<?php endif; ?>

<div class="container">
    <h2>Panel de Administrador</h2>

    <div class="grid">
        <div class="card"><h3><?= $total_docentes ?></h3>Docentes Registrados</div>
        <div class="card"><h3><?= $total_asistencias ?></h3>Asistencias Totales</div>
        <div class="card"><h3><?= $tardanzas ?></h3>Tardanzas</div>
        <div class="card"><h3><?= $faltas ?></h3>Faltas</div>
        <div class="card"><h3><?= $pendientes ?></h3>Justificaciones Pendientes</div>
    </div>

    <h3>Docentes Registrados</h3>
    <table>
        <tr><th>ID</th><th>Nombre</th><th>DNI</th><th>Correo</th><th>Teléfono</th><th>Acciones</th></tr>

        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $inactivo = ($row['estado'] == 0); 
        ?>
        <tr style="<?= $inactivo ? 'background-color:#e0e0e0; color:#888;' : '' ?>"> 
            <td><?= $row['id_docente']; ?></td>
            <td><?= $row['nombre_completo']; ?></td>
            <td><?= $row['dni']; ?></td>
            <td><?= $row['correo']; ?></td>
            <td><?= $row['telefono']; ?></td>
            <td class="acciones">
                <?php if (!$inactivo): ?>
                    <a href="editar_docente.php?id=<?= $row['id_docente']; ?>" title="Editar">✏️</a>
                    <form method="post" action="eliminar_docente.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar al docente?');">
                        <input type="hidden" name="id" value="<?= $row['id_docente']; ?>">
                        <button type="submit" style="border:none; background:none;" title="Eliminar">🗑️</button>
                    </form>
                <?php endif; ?>

                <?php if ($inactivo): ?>
                    <a href="estado_docente.php?id=<?= $row['id_docente']; ?>&accion=activar" title="Habilitar">🟢</a>
                <?php else: ?>
                    <a href="estado_docente.php?id=<?= $row['id_docente']; ?>&accion=desactivar" title="Deshabilitar">🔴</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php } ?>
    </table>

    <div class="enlaces">
        <a href="validar_justificaciones.php">📎 Validar Justificaciones</a>
        <a href="gestion_cursos.php">📚 Gestionar Cursos</a>
        <a href="gestion_aulas.php">🏫 Gestionar Aulas</a>
        <a href="crear_usuario.php">👤 Crear Usuarios</a>
        <a href="ver_usuarios.php">🔐 Ver Usuarios</a>
        <a href="asignaciones_docentes.php">📌 Ver Asignaciones</a>
        <a href="gestionar_reemplazos.php">🛠️ Asignar Reemplazos</a>
        <a href="reemplazos_asignados.php">🔁 Ver Reemplazos</a>

        <button onclick="mostrarOpcionesReporte()">📈 Generar Reporte</button>
        <div id="opcionesReporte" style="display:none; margin-top:10px;">
    
    <a href="exportar_excel.php" target="_blank" style="background-color:#28a745; color:white;">
        📥 Exportar en Excel
    </a>

    <button id="btnPowerBI" style="background-color:#ffc107; color:black;">
        📄 POWER BI
    </button>
</div>

        <a href="cerrar_sesion.php">🔒 Cerrar sesión</a>
    </div>
</div>


<script>
function mostrarOpcionesReporte() {
    const panel = document.getElementById("opcionesReporte");
    panel.style.display = (panel.style.display === "none" || panel.style.display === "") ? "block" : "none";
}


document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("btnPowerBI");
    if (btn) {
        btn.addEventListener("click", () => {
            
            window.open('exportar_csv.php', '_blank');

            
            if (!localStorage.getItem('pbixDescargado')) {
                setTimeout(() => {
                    window.open('templates/reporte_powerbi_template.pbix', '_blank');
                    localStorage.setItem('pbixDescargado', '1');
                }, 1500); 
            } else {
                alert("✅ Ya descargaste la plantilla Power BI.\nSolo reemplaza el archivo Excel y pulsa 'Actualizar' en Power BI.");
            }
        });
    }
});
</script>
</body>
</html>






