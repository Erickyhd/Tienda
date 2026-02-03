<?php
$ruta_powershel = 'c:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe'; #Necesitamos el powershell
$opciones_para_ejecutar_comando = "-c";#Ejecutamos el powershell y necesitamos el "-c" para decirle que ejecutaremos un comando
$espacio = " "; #ayudante para concatenar
$comillas = '"'; #ayudante para concatenar
$comando = 'get-WmiObject -class Win32_printer | select-object name'; #Comando de powershell para obtener lista de impresoras
$lista_de_impresoras = array(); #Aquí pondremos las impresoras
$impresorasxdxd = exec(
    $ruta_powershel
    . $espacio
    . $opciones_para_ejecutar_comando
    . $espacio
    . $comillas
    . $comando
    . $comillas,
    $resultado,
    $codigo_salida);
if ($codigo_salida === 0) {
    if (is_array($resultado)) {
        foreach ($resultado as $linea) {
            $linea = trim($linea);
            if (strlen($linea) > 0 && $linea !== "name" && strpos($linea, "----") === false) {
                array_push($lista_de_impresoras, $linea);
            }
        }
    }
    echo json_encode($lista_de_impresoras);
} else {
    echo json_encode("Error al ejecutar el comando.");
}
