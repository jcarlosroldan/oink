# 🐽 Documentation

## The `serve` method

It is the entrypoint of the library. It will read the endpoints script, and route the current request to the appropriate function. It can be called with the following parameters:

| Parameter | Type | Default | Description |
| - | - | - | - |
| `endpoints_path` | string | | The path to the file containing the endpoint functions. |
| `path` | string | `null` | The path to route the request to. If not set, it will use the current request URI. |
| `debug` | bool | `false` | If set to `true`, the debug mode will be activated. This will show the trace of any unexpected error, so it's advised to keep it off in production. |
| `base_path` | string | `/api/` | All requests must start with this path to be routed, and it will be removed from the endpoint name. |
| `escape_unicode` | bool | `false` | If set to `true`, the JSON response will escape all Unicode characters. |
| `allow_get` | bool | `false` | If set to `true`, the GET parameters will be allowed. |
| `allowed_sites` | array | `["same-origin"]` | Allowed `Sec-Fetch-Site` values for incoming requests, either `same-origin`, `same-site`, `cross-site` or `none`. Not listed values will be rejected with a 403. |

## Routing

The way oink.php does routing is a bit unconventional. Instead of using some of route table, it maps it from the function names defined in the endpoints file. It works as follows:

1. First, the path is obtained by taking the pathname (everything in the URL after the first / and before ? or #).
2. The remaining path is trimmed to remove slashes from both ends.
3. Then, it is transformed into a function name by replacing slashes with underscores.
4. If the function contains a dot after the last underscore (as in `thumbnails_4.png`), the part before the last underscore is considered the endpoint name (`thumbnails`), and the part after the last underscore is considered the filename (`4.png`). This is useful for serving files.

This is a simple and effective way to define the endpoints, but it has some limitations:

* You can't have a dynamic endpoint, like `/post/{id}`. Instead, you can use a query or POST parameter, like `/post?id=4`.
* You can't have a dynamic filename, like `/thumbnail/4`. Instead, you can use an extension filename, like `/thumbnail/4.png`.
* Requesting `/post/list` and `/post_list` will call the same function, but it's advised to use the former for consistency.
* All methods defined in the endpoints file will be exposed, except for those starting with an underscore or imported from other files.
* You can't spread endpoints across multiple files.

## Parameters

Parameters are pieces of data that come from the user. It can be a query parameter from a GET URL, a form data from a POST request, a JSON object, a cookie, or even a header. The `serve` method will read these parameters and merge them into a single array, all lowercased. In case of collision, the priority will be as follows (rightmost will prevail): cookies, headers, files, get, post, json. GET parameters are disabled by default, but they can be enabled by passing `allow_get: true` to `serve`.

Besides these parameters, the `serve` method will also add the following ones:

* `ip`: The IP address of the user.
* `method`: The request method.
* `path`: The endpoint name.
* `output_format`: The output format of the response. It's always `json`, but it can be changed by the endpoint function to `file` by returning a file with the `send_file` method.
* `filename`: The filename of the file to be sent. It's only set when the request format is `file`.

Merging all parameters into a single array is not conventional, and it requires some considerations:

* It's not possible to have two parameters with the same name coming from different sources.
* Security-wise, the merge itself is not a problem since all these fields come from the user and they can be manipulated anyways.
* However, it could lead to CSRF attacks if GET parameters are enabled, since someone could craft a link like `your.website/account/delete?confirm=true` and trick the user into clicking it. It is advised to keep GET params disabled, but if enable them, you should consider using a CSRF token.
* If you customize your environment (web server, PHP configuration, etc.) to set some specific headers, they might be overwritten by the parameters.

## Validators

Each validator reads a parameter, checks it and returns it, or fails with one of the [errors](#errors). All of them take `optional` and `default`: a missing optional parameter returns `default` without being checked.

| Function | Accepts | Returns |
| - | - | - |
| `any` | Anything that is set | The value as it came |
| `bool` | JSON booleans, or `true`, `1`, `on`, `yes`, `false`, `0`, `off`, `no` and an empty string | A boolean |
| `enum` | A value whose text matches one of `values` exactly, so the form value `"10"` matches `10` but `"010"` does not | The matching element of `values` |
| `number` | A number or numeric string between `min` and `max`, and a whole one if `integer` is set | An integer if `integer` is set, a float otherwise |
| `id` | A whole number from 1, or from 0 with `allow_zero` | An integer |
| `str` | A string whose length in characters is between `min` and `max` and that matches `pattern` | The string |
| `email` | A valid email address | The string |
| `datetime` | A string in `format`, read in `timezone` | A `DateTime` |
| `file` | A file uploaded in this request, with one of `extensions` and at most `max_size` bytes | The `$_FILES` entry plus its lowercase `extension` |

## Errors

All error codes, params and explanations are defined in the following table. It is advised to follow the same format when calling `check`, so that an homogeneous i18n is possible at the frontend side.

| Code | Error | Arguments | Explanation |
| - | - | - | - |
| 400 | fileTooBig | parameter, max_size | The {parameter} is too big. Maximum size is {max_size}. |
| 400 | invalidDatetime | parameter, format | The {parameter} is not a valid datetime. Expected format is {format}. |
| 400 | invalidEmail | parameter | The {parameter} is not a valid email address. |
| 400 | invalidExtension | parameter, allowed_exts | The {parameter} has an invalid extension. Allowed extensions are {allowed_exts}. |
| 400 | invalidFile | parameter | The {parameter} is not a file uploaded in this request. |
| 400 | invalidFormat | parameter | The {parameter} has an invalid format. |
| 400 | invalidValue | parameter, allowed_values | The {parameter} has an invalid value. Allowed values are {allowed_values}. |
| 400 | notBoolean | parameter | The {parameter} is not a boolean value. |
| 400 | notFile | parameter | The {parameter} is not a file. |
| 400 | notFound | parameter* | The file with path {parameter} was not found. |
| 400 | notInteger | parameter | The {parameter} is not an integer. |
| 400 | notNumber | parameter | The {parameter} is not a number. |
| 400 | notSet | parameter | The {parameter} is not set. |
| 400 | notString | parameter | The {parameter} is not a string. |
| 400 | tooBig | parameter, max | The {parameter} is too big. Maximum value is {max}. |
| 400 | tooLong | parameter, max_length | The {parameter} is too long. Maximum length is {max_length}. |
| 400 | tooShort | parameter, min_length | The {parameter} is too short. Minimum length is {min_length}. |
| 400 | tooSmall | parameter, min | The {parameter} is too small. Minimum value is {min}. |
| 404 | unknownEndpoint | endpoint* | No endpoint called {endpoint}. |
| 500 | unexpectedError | trace* | An unexpected error occurred with the following trace: {trace}. |

\*The argument is an empty string when not in debug mode.

## Changelog

### 2.2.0 Headers over cookies

* BREAKING headers now prevail over cookies, so a cookie set from a sibling subdomain can no longer replace a header such as `Authorization`.

### 2.1.0 Logging and status codes

* Unexpected errors are written to the server's error log.
* FIX unknown endpoints returned 400 instead of 404.
* JSON responses declare their charset as UTF-8.

### 2.0.0 Security and validator fixes

* BREAKING requests coming from other websites are rejected, unless allowed with the new `allowed_sites` parameter.
* BREAKING enums no longer accept true for any value, they compare values as text.
* BREAKING booleans can now come from forms, as true, 1 or on.
* BREAKING ids and integer numbers are returned as integers instead of floats.
* BREAKING dashes in the URL are routed as underscores, so `/user-name` calls `user_name`.
* FIX uploaded files could be faked with a server path in the request body.
* FIX sent files had the rest of the page appended to them.
* FIX string lengths counted bytes instead of characters.
* FIX JSON bodies that weren't objects caused a 500.
* FIX filenames in the URL were lowercased.
* Documented the validators and fixed the parameter priority order.

### 1.2.1 File serving adjustments

* Removed file mime guessing for improved speed and security.
* Adjusted some of the examples.
* Added snapshots to the documentation and ellaborated a bit more on the examples.

### 1.2.0 Date adjustments

* Removed `date` and `time`, since only DateTime is available in PHP. Not pushing the major because no one is using it yet.
* Allowed `datetime` to receive a timezone.
* FIX some datetime exceptions were 500 instead of 400.
* Updated docs.

### 1.1.2 Launch and bugfixing

* oink.php is now a public repo on GitHub! 🥳
* FIX non-matched endpoints were returning 500 instead of 404.
* FIX multiple fixes to make examples standalone.

### 1.1.0 File uploading

* Added parameter method `file`.
* Added method `send_file` to return a file as a response.
* Added example for file uploading.

### 1.0.0 Initial library

* Added controller methods `serve` and `get_data`.
* Added parameter and asserition methods `check`, `any`, `bool`, `number`, `id`, `str`, `email`, `date`, `time`, `datetime`, `file`.
* Added documentation and examples.
