<?php
#VER 2.0 sistema necxus 2020
/** Ejemplo de uso **
 * ```
 * // ->get (	$recurso,				$parametros				)
 * $obj->get('/veritems/v1', array('token'=>'Jasu', 'limit'=>10));
 * // ->post/put(	$recurso,				$contenido,				$parametros	)
 * $obj->post('/nuevoitem/', array('id'=>123, 'name'=>'software'), array('token'=>'JasuBeAnAngel2020'));
 * ```
 */

class verazSDK{
	// const PROD_URL = 'https://online.org.veraz.com.ar/pls/consulta817/wserv';
	const PROD_URL = 'https://prepro.online.org.veraz.com.ar/pls/consulta817/wserv';
	const TEST_URL = 'https://prepro.online.org.veraz.com.ar/pls/consulta817/wserv';
	public $sandbox = true;
	public $last_status = '000';

	function get($uri, $params=null){
		$result = $this->exec($uri, $params);
		return $result;
	}

	function post($uri, $body=null, $params=null){
		$opts = array(CURLOPT_POST => true);
		if(!is_null($body)){
			$opts += array(CURLOPT_POSTFIELDS => $body);
		}
		$result = $this->exec($uri, $params, $opts);
		return $result;
	}

	function put($uri, $body=null, $params=null){
		$opts = array(CURLOPT_CUSTOMREQUEST => "PUT");
		if(!is_null($body)){
			$opts += array(CURLOPT_POSTFIELDS => $body);
		}
		$result = $this->exec($uri, $params, $opts);
		return $result;
	}

	function delete($uri, $params=null){
		$opts = array(CURLOPT_CUSTOMREQUEST => "DELETE");
		$result = $this->exec($uri, $params, $opts);
		return $result;
	}

	function upload($uri, $body, $params=null){
		$opts = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: multipart/form-data',
			)
		);
		$result = $this->exec($uri, $params, $opts);
		return $result;
	}

	function exec($path, $params, $opts=null){
		$path = $this->endpoint($path, $params);
		$ch = curl_init($path);
		curl_setopt_array($ch, array(
			CURLOPT_TIMEOUT => 60,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/x-www-form-urlencoded',
			)
		));
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		if(!empty($opts))
			curl_setopt_array($ch, $opts);
		$res = curl_exec($ch);
		$this->last_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $res;
	}

	function endpoint($path, $params=array()){
		if(!preg_match("/^(https?:\/\/)?(([-\w]+)\.)?(?2)(?3)/", $path)){
			$url = (!$this->sandbox ? self::PROD_URL : self::TEST_URL);
			if($path && $path<>'/'){
				if(!(preg_match("/^\//", $path) xor preg_match("/\/$/", $url))){
					if(preg_match("/^\//", $path)) $path = substr($path, 1);
					else $path = '/'.$path;
				}
				$uri = $url.$path;
			}
		}else{
			$uri = $path;
		}

		if(!empty($params)){
			$params = (!strpos($uri, '?')?'?':'&').http_build_query($params);
			$uri = $uri.$params;
		}

		return $uri;
	}
}
