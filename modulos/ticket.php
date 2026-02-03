<?php
require __DIR__ . '/ticket/autoload.php';
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

function get_impresora_connector()
{
    // Lee el nombre de la impresora desde el archivo de configuración.
    // Este puede ser un nombre de impresora local (ej: "POS-80C") o una ruta de red completa (ej: "smb://otro-equipo/impresora").
    $nombre_impresora = trim(file_get_contents(__DIR__ . '/impresora.ini'));

    // Pasa el nombre directamente al conector. La biblioteca se encargará de interpretarlo.
    // Si es solo un nombre, asumirá que es una impresora local.
    // Si es una ruta smb://, la usará directamente.
    // Esto da más flexibilidad para configurar la ubicación de la impresora.
    return new WindowsPrintConnector($nombre_impresora);
}

function abre_cajon()
{
    try {
        $connector = get_impresora_connector();
        $printer = new Printer($connector);
        $printer->cut();
        $printer->pulse();
        $printer->close();
        return true;
    } catch (Exception $e) {
        // No devuelvas el mensaje aquí para no interferir con otras respuestas AJAX
        return false;
    }
}

function imprime_ticket($productos, $id_venta, $cambio)
{
    $nombre_impresora_config = trim(file_get_contents(__DIR__ . '/impresora.ini'));
    try {
        if (!defined("RAIZ")) define("RAIZ", dirname(__FILE__));
        $datos_empresa_recuperados = file(RAIZ . "/modulos/datos_empresa.txt");
        $datos_empresa = array(
            "nombre" => trim($datos_empresa_recuperados[0]),
            "telefono" => trim($datos_empresa_recuperados[1]),
            "rfc" => trim($datos_empresa_recuperados[2]),
            "direccion" => trim($datos_empresa_recuperados[3]),
            "colonia" => trim($datos_empresa_recuperados[4]),
            "cp" => trim($datos_empresa_recuperados[5]),
            "ciudad" => trim($datos_empresa_recuperados[6])
        );

        /*Conectamos con la impresora*/
        $connector = get_impresora_connector();
        $printer = new Printer($connector);

        /*Cargamos el logo*/
        $ruta_imagen_logo = dirname(__DIR__) . "/img/logo.png";
        if (file_exists($ruta_imagen_logo)) {
            try {
                $logo = EscposImage::load($ruta_imagen_logo, false);
                /*Le decimos que centre lo que vaya a imprimir*/
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                /*Imprimimos imagen y avanzamos el papel*/
                $printer->bitImage($logo);
                $printer->feed();
            } catch (Exception $e) {
                // No hacemos nada si hay un error con el logo, solo seguimos imprimiendo lo demás
            }
        }

        /*Imprimimos los datos de la empresa*/
        foreach ($datos_empresa as $dato) {
            $printer->text($dato . "\n");
        }

        /*Hacemos que el texto sea en negritas e imprimimos el nùmero de venta*/
        $printer->setEmphasis(true);
        $printer->text("Venta #" . $id_venta);
        $printer->setEmphasis(false);
        $printer->feed();


        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $total = 0;
        foreach ($productos as $producto) {
            $importe = $producto->cantidad * $producto->precio_venta;
            $total += $importe;
            $importe_formateado = number_format($importe, 2, ".", ",");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text($producto->cantidad . "x" . $producto->nombre . "\n");
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            $printer->text(' S/' . $importe_formateado . "\n");
        }
        $ayudante_total = number_format($total, 2, ".", ",");
        $ayudante_cambio = number_format($cambio, 2, ".", ",");
        $ayudante_pago = number_format($total + $cambio, 2, ".", ",");

        $printer->selectPrintMode(Printer::MODE_EMPHASIZED | Printer::MODE_FONT_B);
        $printer->text("--------\n");
        $printer->text("SU PAGO S/" . $ayudante_pago . "\n");
        $printer->text("TOTAL S/" . $ayudante_total . "\n");
        $printer->text("CAMBIO S/" . $ayudante_cambio);
        $printer->feed();

        /*Calculamos la hora para desearle buenos días, tardes o noches*/
        $hora = date("G");
        $str_deseo = "a";
        if ($hora >= 6 and $hora <= 12) {
            $str_deseo = "le deseamos un buen dia";
        }
        if ($hora >= 12 and $hora <= 19) {
            $str_deseo = "le deseamos una buena tarde";
        }
        if ($hora >= 19 and $hora <= 24) {
            $str_deseo = "le deseamos una buena noche";
        }
        if ($hora >= 0 and $hora <= 6) {
            $str_deseo = "le deseamos un buen dia";
        }
        /*Le deseamos al cliente buenas tardes, noches o días*/

        $printer->selectPrintMode(Printer::MODE_FONT_A);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text(strtoupper($str_deseo));
        $printer->feed();

        /*Terminamos el trabajo de impresión y abrimos el cajón*/
        $printer->feed(2);
        $printer->cut();
        $printer->pulse();
        $printer->close();
        return true;
    } catch (Exception $e) {
        return "Error al imprimir en '" . $nombre_impresora_config . "': " . $e->getMessage();
    }
}
