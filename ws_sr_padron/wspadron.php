<?php
#!/usr/bin/php
//	Jasu 27-02-18: Uso del Web Service del Padron de Afip
// ini_set("display_errors","on");
// error_reporting(E_ALL);
require('../config/config.php');

if(!isset($_POST["cuit"])){$doPrint=true;}else{$doPrint=false;}

//	Leo el TA
$TA=simplexml_load_file("TA_a5.xml");
$expiration=$TA->header->expirationTime;

//	Verifico si expiró para pedir uno nuevo
if(strtotime($expiration)<time()){
	require('wsaa-padron.class.php');
	$wsaa = new WSAA('ws_sr_padron_a5');
	if($wsaa->generar_TA()){
		if($doPrint)echo 'Nuevo TA obtenido. ';
		$TA=simplexml_load_file("TA_a5.xml");
		$expiration=$TA->header->expirationTime;
	}else{
		print json_encode(array("cuit"=>$cuit,"error"=>'Error: TA001'));
		exit;
	}
}
if($doPrint)echo 'El TA expira el: '.$expiration.'<br><br>';

	header("access-control-allow-origin: *");
	define("URL","https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5");
	define("WSDL","https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5?WSDL");
	ini_set("soap.wsdl_cache_enabled", "0");

	$client=new soapClient(WSDL,array('soap_version'=>SOAP_1_1,'location'=>URL,'exceptions'=>0,'trace'=>1));

//	Si se pasa el cuit se realiza la busqueda
if(isset($_REQUEST["cuit"])){

	$cuit=trim(substr($_REQUEST["cuit"],0,11));
	$token=$TA->credentials->token;
	$sing=$TA->credentials->sign;
	$param=array('token'=>$token,'sign'=>$sing,'cuitRepresentada'=>30708477148,'idPersona'=>$cuit);

	switch(strlen($cuit)){
		case 7:	$cuit='0'.$cuit;
		case 8:
			foreach(array(20,27) as $ci){
				$param['idPersona']=getCUIT($ci,$cuit);
				$wsp=$client->getPersona($param);
				if(isset($wsp->personaReturn))break;
			}
			process($wsp);
		break;
		case 11:process($client->getPersona($param));break;
		default:
			print json_encode(array("error"=>"Error: format error"));
			exit;
		break;
	}
}else{
	echo"<h4><u>Padron WebService Status</u></h4>";
	foreach($client->Dummy()->return as $k=>$v)echo"<h4>&rarr; $k: $v</h4>";
	echo'<h3>-----</h3><h3>Buscar Persona</h3><form><input name="cuit" type="text" value=""><input type="submit"></form>';
}

//Cambio la provincia por su id en sistema
function BuscaProvinciaId($provincia){
	switch($provincia){
		case 'BUENOS AIRES':				$id=1;break;
		case 'CIUDAD AUTONOMA BUENOS AIRES':$id=3;break;
		case 'CATAMARCA':					$id=4;break;
		case 'CHACO':						$id=5;break;
		case 'CHUBUT':						$id=6;break;
		case 'CORDOBA':						$id=7;break;
		case 'CORRIENTES':					$id=8;break;
		case 'ENTRE RIOS':					$id=9;break;
		case 'FORMOSA':						$id=10;break;
		case 'JUJUY':						$id=11;break;
		case 'LA PAMPA':					$id=12;break;
		case 'LA RIOJA':					$id=13;break;
		case 'MENDOZA':						$id=14;break;
		case 'MISIONES':					$id=15;break;
		case 'NEUQUEN':						$id=16;break;
		case 'RIO NEGRO':					$id=17;break;
		case 'SALTA':						$id=18;break;
		case 'SAN JUAN':					$id=19;break;
		case 'SAN LUIS':					$id=20;break;
		case 'SANTA CRUZ':					$id=21;break;
		case 'SANTA FE':					$id=22;break;
		case 'SANTIAGO DEL ESTERO':			$id=23;break;
		case 'TIERRA DEL FUEGO':			$id=24;break;
		case 'TUCUMAN':						$id=25;break;
		default:							$id=0;break;
	}
	return $id;
}

function getCUIT($ci,$dni){
	$s=0;
	$cuit=$ci.$dni;
	foreach(array(5,4,3,2,7,6,5,4,3,2) as $i=>$v)$s+=$v*$cuit[$i];
	$r=$s%11;
	switch($r){
		case 0:	$dv=0;break;
		case 1:	switch($ci){
					case 20:$dv=9;$ci='23';break;
					case 27:$dv=4;$ci='23';break;
					case 30:$dv=1;break;
				}break;
		default:if(!isset($dv))$dv=11-$r;
		break;
	}
	return $ci.$dni.$dv;
}

function process($wsp){
	global $Conn, $doPrint, $cuit, $data;

	//	Verifico si devuelve algun mensaje de error
	if(isset($wsp->faultstring)){

		print json_encode(array("cuit"=>$cuit,"error"=>"Error: not found"));

	}elseif(isset($wsp->personaReturn->errorConstancia)){

		if(isset($_REQUEST["all"])){
			print json_encode($wsp->personaReturn);
			return;
		}

		$persona=$wsp->personaReturn->errorConstancia;
		print json_encode(array("cuit"=>$persona->idPersona,"nombre"=>$persona->apellido.(isset($persona->nombre)?' '.$persona->nombre:''),"msg"=>"400"));

	}elseif(isset($wsp->personaReturn)){

		if(isset($_REQUEST["all"])){
			print json_encode($wsp->personaReturn);
			return;
		}

		//	Lectura de los datos
		$persona=$wsp->personaReturn->datosGenerales;
		if(isset($persona->domicilioFiscal)){
			if(is_array($persona->domicilioFiscal)){
				foreach($persona->domicilioFiscal as $dom)if($dom->tipoDomicilio=='FISCAL')$domicilio=$dom;
				if(!isset($domicilio))$domicilio=$persona->domicilioFiscal[0];
			}elseif(is_object($persona->domicilioFiscal)){
				$domicilio=$persona->domicilioFiscal;
			}
		}

		$data["cuit"]=$persona->idPersona;
		if(isset($persona->numeroDocumento))$data["dni"]=$persona->numeroDocumento;
		$data["razonsocial"]=html_entity_decode($persona->tipoPersona=='FISICA'?$persona->apellido.(isset($persona->nombre)?' '.$persona->nombre:''):$persona->razonSocial);
		$data["direccion"]=html_entity_decode(isset($domicilio->direccion)?$domicilio->direccion:'');
		if(isset($domicilio->descripcionProvincia)){
			$data["provinciaid"]=BuscaProvinciaId($domicilio->descripcionProvincia);
			if(isset($domicilio->localidad)){
				$where="WHERE id_provincia".($data["provinciaid"]==1?" IN (1,2)":"=".$data["provinciaid"]);
				foreach(explode(" ", str_replace(",","",$domicilio->localidad)) as $val)$where.=" AND localidad LIKE '%$val%'";
				$rs=$Conn->query("SELECT l.*, p.provincia FROM localidades l JOIN provincias p ON l.id_provincia=p.id $where");
				$rs=$rs->fetchObject();
				//$data["localidadid"]=(isset($rs->id)?$rs->id:0);
				if(isset($rs->id)){
					if($data["provinciaid"]==1)$data["provinciaid"]=$rs->id_provincia;
					$data["provincia"]=$rs->provincia;
					$data["localidadid"]=$rs->id;
					$data["localidad"]=$rs->localidad;
				}
			}
			if(isset($_POST["html"]))$data["localidadhtml"]=getLocalidades($data["provinciaid"],(isset($data["localidadid"])?$data["localidadid"]:0));
		}else{
			$data["provinciaid"]=0;
			if(isset($_POST["html"]))$data["localidadhtml"]='<option value="">Seleccionar...</option>';
		}
		$data["codigo_postal"]=(isset($domicilio->codPostal)?$domicilio->codPostal:'');

		if(isset($wsp->personaReturn->datosMonotributo)){
			$data["tipo_contribuyente"]=5;
		}elseif(isset($wsp->personaReturn->datosRegimenGeneral->impuesto)){
			$impuestos=$wsp->personaReturn->datosRegimenGeneral->impuesto;
			if(is_array($impuestos)){
				foreach($impuestos as $imp)if($imp->descripcionImpuesto=='IVA')$data["tipo_contribuyente"]=2;
			}elseif(is_object($impuestos)){
				if($imp->descripcionImpuesto=='IVA')$data["tipo_contribuyente"]=2;
			}
		}
		if(!isset($data["tipo_contribuyente"]))$data["tipo_contribuyente"]=4;

		/*	Ver Contenido completo del padron */
		if($doPrint){
			print'<pre>Get Persona: <br>';
			print_r($wsp);
			print'</pre>';
		}

		$data["msg"]="ok";
		print json_encode($data);

	}else{
		print json_encode(array("msg"=>"500","cuit"=>$cuit));
	}
}

?>