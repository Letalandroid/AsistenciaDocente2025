<?php
$serverName = "localhost";
$connectionOptions = array(
    "Database" => "AsistenciaDocente2025",
    "Uid" => "sa",
    "PWD" => "sa123", 
    "CharacterSet" => "UTF-8"
);


$conn = sqlsrv_connect($serverName, $connectionOptions);


if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
