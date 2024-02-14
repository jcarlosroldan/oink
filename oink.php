<?php
/** Oink 1.2.1 (https://github.com/jcarlosroldan/oink): single-file PHP API **/
namespace oink;

$data; $error; $_debug;

function serve($endpoints_path, $path=null, $debug=false, $base_path="/api/", $escape_unicode=false, $allow_get=false) {
	global $data, $error, $_debug;
	try {
		$_debug = $debug;
		if ($path === null) $path = strtolower(explode("?", $_SERVER['REQUEST_URI'], 2)[0]);
		if (strpos($path, $base_path) !== 0) return;
		$data = get_data($path, $base_path, $allow_get);
		require_once $endpoints_path;
		$res = null;
		$endpoints_path_abs = realpath($endpoints_path);
		$found = false;
		foreach (get_defined_functions()["user"] as $e) {
			if (strpos($e, "_") !== 0 && realpath((new \ReflectionFunction($e))->getFileName()) === $endpoints_path_abs && ($e === $data['path'] || $e === "get_" . $data['path'])) {
				$found = true;
				$res = $e();
				break;
			}
		}
		check($found, "unknownEndpoint", $_debug ? $data['path'] : "");
	} catch (\AssertionError $e) {
		$data["output_format"] = "json";
		http_response_code(400);
		$res = $error;
	} catch (Throwable $e) {
		$data["output_format"] = "json";
		http_response_code(500);
		$res = ["error" => "unexpectedError", "args" => $_debug ? $e->getTrace() : ""];
	}
	if ($data["output_format"] === "file") {
		header("Content-Type: application/octet-stream");
		readfile($data["filename"]);
	} else {
		header("Content-Type: application/json");
		if (is_null($res)) $res = ["success" => true];
		exit(json_encode($res, $escape_unicode ? 0 : JSON_UNESCAPED_UNICODE));
	}
}

function get_data($path, $base_path, $allow_get) {
	$headers = getallheaders();
	$res = array_merge($headers, $_COOKIE, $_FILES, $allow_get ? $_GET : [], $_POST);
	$json = json_decode(file_get_contents("php://input"), true);
	if (isset($json)) $res = array_merge($res, $json);
	foreach ($res as $key => $value) {
		unset($res[$key]);
		$res[strtolower(trim($key))] = $value;
	}
	$res["ip"] = in_array("X-Forwarded-For", $headers) ? $headers["X-Forwarded-For"] : $_SERVER["REMOTE_ADDR"];
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
	if ($is_set) {
		return $tell_default ? [$res, false] : $res;
	} else {
		if ($optional) {
			return $tell_default ? [$default, true] : $default;
		} else {
			check(false, "notSet", $key);
		}
	}
}

function bool($key, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(is_bool($res), "notBoolean", $key);
	return $tell_default ? [$res, false] : $res;
}

function enum($key, $values, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(in_array($res, $values), "invalidValue", $key, $values);
	return $tell_default ? [$res, false] : $res;
}

function number($key, $min=null, $max=null, $integer=false, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(is_numeric($res), "notNumber", $key);
	$res = floatval($res);
	if ($integer) check($res == intval($res), "notInteger", $key);
	if (isset($min)) check($res >= $min, "tooSmall", $key, $min);
	if (isset($max)) check($res <= $max, "tooBig", $key, $max);
	return $tell_default ? [$res, false] : $res;
}

function id($key, $allow_zero=false, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	$res = number($res, min: $allow_zero ? 0 : 1, integer: true, optional: false, from_param: false);
	return $tell_default ? [$res, false] : $res;
}

function str($key, $min=null, $max=null, $pattern=null, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(is_string($res), "notString", $key);
	if (isset($min)) check(strlen($res) >= $min, "tooShort", $key, $min);
	if (isset($max)) check(strlen($res) <= $max, "tooLong", $key, $max);
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
	check($res !== null, "invalidDatetime", $key, $format);
	$timezone = $timezone === null ? null : new \DateTimeZone($timezone);
	$res = \DateTime::createFromFormat($format, $res, $timezone);
	check($res !== false, "invalidDatetime", $key, $format);
	return $tell_default ? [$res, false] : $res;
}

function file($key, $extensions=null, $max_size=null, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(is_array($res) && isset($res["name"]) && isset($res["type"]) && isset($res["tmp_name"]) && isset($res["error"]) && isset($res["size"]), "invalidFile", $key);
	if (isset($max_size)) check($res["size"] <= $max_size, "fileTooBig", $key, $max_size);
	$res["extension"] = strtolower(pathinfo($res["name"], PATHINFO_EXTENSION));
	if (isset($extensions)) {
		$extensions = array_map("strtolower", $extensions);
		check(in_array($res["extension"], $extensions), "invalidExtension", $key, $extensions);
	}
	return $tell_default ? [$res, false] : $res;
}