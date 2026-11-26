<?php
/** Oink 2.2.0 (https://github.com/jcarlosroldan/oink): single-file PHP API **/
namespace oink;

function serve($endpoints_path, $path=null, $debug=false, $base_path="/api/", $escape_unicode=false, $allow_get=false, $allowed_sites=["same-origin"]) {
	global $data, $error, $_debug;
	try {
		$_debug = $debug;
		if ($path === null) $path = explode("?", $_SERVER['REQUEST_URI'], 2)[0];
		if (strpos($path, $base_path) !== 0) return;
		if (isset($_SERVER["HTTP_SEC_FETCH_SITE"]) && !in_array($_SERVER["HTTP_SEC_FETCH_SITE"], $allowed_sites)) exit(http_response_code(403));
		$data = get_data($path, $base_path, $allow_get);
		require_once $endpoints_path;
		$endpoints_path_abs = realpath($endpoints_path);
		$endpoint = strtolower(str_replace("-", "_", $data['path']));
		$found = false;
		foreach (get_defined_functions()["user"] as $e) {
			if ($e[0] !== "_" && realpath((new \ReflectionFunction($e))->getFileName()) === $endpoints_path_abs && ($e === $endpoint || $e === "get_" . $endpoint)) {
				$found = true;
				$res = $e();
				break;
			}
		}
		check($found, "unknownEndpoint", $_debug ? $data['path'] : "");
	} catch (\AssertionError $e) {
		$data["output_format"] = "json";
		http_response_code($error["error"] === "unknownEndpoint" ? 404 : 400);
		$res = $error;
	} catch (\Throwable $e) {
		error_log((string) $e);
		$data["output_format"] = "json";
		http_response_code(500);
		$res = ["error" => "unexpectedError", "args" => $_debug ? [(string) $e] : []];
	}
	if ($data["output_format"] === "file") {
		header("Content-Type: application/octet-stream");
		readfile($data["filename"]);
	} else {
		header("Content-Type: application/json; charset=utf-8");
		if (is_null($res)) $res = ["success" => true];
		echo json_encode($res, $escape_unicode ? 0 : JSON_UNESCAPED_UNICODE);
	}
	exit;
}

function get_data($path, $base_path, $allow_get) {
	$res = array_merge($_COOKIE, getallheaders(), $_FILES, $allow_get ? $_GET : [], $_POST);
	$json = json_decode(file_get_contents("php://input"), true);
	if (is_array($json)) $res = array_merge($res, $json);
	foreach ($res as $key => $value) {
		unset($res[$key]);
		$res[strtolower(trim($key))] = $value;
	}
	$res["ip"] = $_SERVER["REMOTE_ADDR"];
	$res["method"] = $_SERVER['REQUEST_METHOD'];
	$res['path'] = str_replace("/", "_", trim(substr($path, strlen($base_path)), "/"));
	$res['output_format'] = "json";
	if (preg_match("/^(\w+)_(\w+\.\w+)$/", $res['path'], $matches)) {
		$res['path'] = $matches[1];
		$res['filename'] = $matches[2];
	}
	return $res;
}

function send_file($filename) {
	global $data, $_debug;
	check(file_exists($filename), "notFound", $_debug ? $filename : "");
	$data['output_format'] = "file";
	$data['filename'] = $filename;
}

function check($condition, $description, ...$args) {
	global $error;
	if (!$condition) {
		$error = ["error" => $description, "args" => $args];
		throw new \AssertionError();
	}
}

function any($key, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	$is_set = true;
	if ($from_param) {
		global $data;
		$res = $data;
		foreach (explode(".", $key) as $part) {
			if (isset($res[$part])) {
				$res = $res[$part];
			} else {
				$is_set = false;
				break;
			}
		}
	} else {
		$res = $key;
		$is_set = isset($res);
	}
	if ($is_set) return $tell_default ? [$res, false] : $res;
	if ($optional) return $tell_default ? [$default, true] : $default;
	check(false, "notSet", $key);
}

function bool($key, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	$res = filter_var($res, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	check(is_bool($res), "notBoolean", $key);
	return $tell_default ? [$res, false] : $res;
}

function enum($key, $values, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(is_scalar($res), "invalidValue", $key, $values);
	$index = array_search(strval($res), array_map("strval", $values), true);
	check($index !== false, "invalidValue", $key, $values);
	return $tell_default ? [$values[$index], false] : $values[$index];
}

function number($key, $min=null, $max=null, $integer=false, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(is_numeric($res), "notNumber", $key);
	if ($integer) check(floatval($res) == intval($res), "notInteger", $key);
	$res = $integer ? intval($res) : floatval($res);
	if (isset($min)) check($res >= $min, "tooSmall", $key, $min);
	if (isset($max)) check($res <= $max, "tooBig", $key, $max);
	return $tell_default ? [$res, false] : $res;
}

function id($key, $allow_zero=false, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	return number($key, min: $allow_zero ? 0 : 1, integer: true, optional: $optional, default: $default, from_param: $from_param, tell_default: $tell_default);
}

function str($key, $min=null, $max=null, $pattern=null, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(is_string($res), "notString", $key);
	if (isset($min)) check(iconv_strlen($res) >= $min, "tooShort", $key, $min);
	if (isset($max)) check(iconv_strlen($res) <= $max, "tooLong", $key, $max);
	if (isset($pattern)) check(preg_match($pattern, $res), "invalidFormat", $key);
	return $tell_default ? [$res, false] : $res;
}

function email($key, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(filter_var($res, FILTER_VALIDATE_EMAIL), "invalidEmail", $key);
	return $tell_default ? [$res, false] : $res;
}

function datetime($key, $format="Y-m-d H:i:s", $timezone='UTC', $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	if ($timezone !== null) $timezone = new \DateTimeZone($timezone);
	$res = \DateTime::createFromFormat($format, $res, $timezone);
	check($res !== false, "invalidDatetime", $key, $format);
	return $tell_default ? [$res, false] : $res;
}

function file($key, $extensions=null, $max_size=null, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(isset($res["tmp_name"]) && is_uploaded_file($res["tmp_name"]), "invalidFile", $key);
	if (isset($max_size)) check($res["size"] <= $max_size, "fileTooBig", $key, $max_size);
	$res["extension"] = strtolower(pathinfo($res["name"], PATHINFO_EXTENSION));
	if (isset($extensions)) {
		$extensions = array_map("strtolower", $extensions);
		check(in_array($res["extension"], $extensions), "invalidExtension", $key, $extensions);
	}
	return $tell_default ? [$res, false] : $res;
}
