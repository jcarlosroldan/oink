<?php
/** Oink 1.0.0 (https://github.com/jcarlosroldan/oink): single-file PHP API **/
namespace oink;

$data; $error;

function serve($endpoints_path, $debug=false, $base_path="/", $escape_unicode=false, $allow_get=false) {
	global $error;
	try {
		get_data($allow_get);
		$endpoint = strtolower(explode("?", $_SERVER['REQUEST_URI'], 2)[0]);
		check(strpos($endpoint, $base_path) === 0, "invalidPath", $endpoint);
		$endpoint = str_replace("/", "_", trim(substr($endpoint, strlen($base_path)), "/"));
		$endpoints = get_defined_functions()["user"];
		include_once $endpoints_path;
		$endpoints = array_diff(get_defined_functions()["user"], $endpoints);
		if (in_array("get_$endpoint", $endpoints)) $endpoint = "get_$endpoint";
		check(in_array($endpoint, $endpoints), "unknownEndpoint", $endpoint);
		$res = $endpoint();
		if (is_null($res)) $res = ["success" => true];
	} catch (\AssertionError $e) {
		http_response_code(400);
		$res = $error;
	} catch (Throwable $e) {
		http_response_code(500);
		if ($debug) {
			$res = ["error" => $e->getMessage(), "traceback" => $e->getTrace()];
		} else {
			$res = ["error" => "unknownError"];
		}
	}
	exit(json_encode($res, $escape_unicode ? 0 : JSON_UNESCAPED_UNICODE));
	header("Content-Type: application/json");
	
}

function get_data($allow_get=false) {
	global $data;
	$headers = getallheaders();
	$data = array_merge($headers, $_COOKIE, $_FILES, $_POST, $allow_get ? $_GET : []);
	$json = json_decode(file_get_contents("php://input"), true);
	if (isset($json)) $data = array_merge($data, $json);
	foreach ($data as $key => $value) {
		unset($data[$key]);
		$data[strtolower(trim($key))] = $value;
	}
	$data["ip"] = in_array("X-Forwarded-For", $headers) ? $headers["X-Forwarded-For"] : $_SERVER["REMOTE_ADDR"];	
	$data["path"] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$data["method"] = $_SERVER['REQUEST_METHOD'];
}

function check($condition, $description, ...$params) {
	global $error;
	if (!$condition) {
		$error = ["error" => $description, "params" => $params];
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

function date($key, $format="Y-m-d", $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	$res = \DateTime::createFromFormat($format, $res);
	check($res && $res->format($format) === $res, "invalidDate", $key, $format);
	return $tell_default ? [$res, false] : $res;
}

function time($key, $format="H:i:s", $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	$res = \DateTime::createFromFormat($format, $res);
	check($res && $res->format($format) === $res, "invalidTime", $key, $format);
	return $tell_default ? [$res, false] : $res;
}

function datetime($key, $format="Y-m-d H:i:s", $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	$res = \DateTime::createFromFormat($format, $res);
	check($res && $res->format($format) === $res, "invalidDatetime", $key, $format);
	return $tell_default ? [$res, false] : $res;
}

function file($key, $optional=false, $default=null, $from_param=true, $tell_default=false) {
	[$res, $is_default] = any($key, $optional, $default, $from_param, true);
	if ($is_default) return $tell_default ? [$res, true] : $res;
	check(is_array($res), "notFile", $key);
	check(isset($res["name"]) && isset($res["type"]) && isset($res["tmp_name"]) && isset($res["error"]) && isset($res["size"]), "invalidFile", $key);
	return $tell_default ? [$res, false] : $res;
}