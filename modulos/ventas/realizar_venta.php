<?php
if ( !isset( $_POST["productos"] ) ) exit();
if ( !defined( "RAIZ" ) ) 
{
	define( "RAIZ", dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once RAIZ . "/modulos/db.php";
require_once RAIZ . "/modulos/funciones.php";
require_once RAIZ . "/modulos/ventas/ventas.php";
inicia_sesion_segura();
$productos = $_POST["productos"];
$productos = json_decode($productos);
$total = $_POST["total"];
$cambio = $_POST["cambio"];
$ticket = filter_var($_POST["ticket"], FILTER_VALIDATE_BOOLEAN);
$resultado = hacer_venta( $productos, $total, $ticket, $cambio );
echo json_encode($resultado);
?>