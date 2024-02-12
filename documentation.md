# 🐽 Documentation

## Serve options

## Routing

## Parameters

## Response

## Errors

The error status of a request can be determined with the status code. Only the following ones are used:

* **200:** The request finised with no errors.
* **500:** There was an unexpected error in the request that is not handled by Oink nor the endpoint code. The output will be `{"error": "unexpectedError"}`. If debug mode is active, the error trace will be available under the key `"trace"`.
* **404:** A file intended to be served from the `static` folder was not defined. The output will be `{"error": "notFound"}`. If debug mode is active, the absolute path that was not found will be available under the key `"path"`.
* **400:** Some parameter type check or custom assertion (via the Oink's `check` function) failed. The output will be `{"error": <code>, "params": <params>}`. All error codes, params and explanations are defined in the following table. It is advised to follow the same format when calling `check`, so that an homogeneous i18n is possible at the frontend side.

| Code | Arguments | Explanation |
| - | - | - |
| invalidDate | parameter, format | The {parameter} is not a valid date. Expected format is {format}. |
| invalidDatetime | parameter, format | The {parameter} is not a valid datetime. Expected format is {format}. |
| invalidEmail | parameter | The {parameter} is not a valid email address. |
| invalidFile | parameter | The {parameter} is an invalid file. |
| invalidFormat | parameter | The {parameter} has an invalid format. |
| invalidPath | path | The URL path {path} is not supported. |
| invalidTime | parameter, format | The {parameter} is not a valid time. Expected format is {format}. |
| invalidValue | parameter, allowed values | The {parameter} has an invalid value. Allowed values are {allowed values}. |
| notBoolean | parameter | The {parameter} is not a boolean value. |
| notFile | parameter | The {parameter} is not a file. |
| notInteger | parameter | The {parameter} is not an integer. |
| notNumber | parameter | The {parameter} is not a number. |
| notSet | parameter | The {parameter} is not set. |
| notString | parameter | The {parameter} is not a string. |
| tooBig | parameter, maximum | The {parameter} is too big. Maximum value is {maximum}. |
| tooLong | parameter, maximum length | The {parameter} is too long. Maximum length is {maximum length}. |
| tooShort | parameter, minimum length | The {parameter} is too short. Minimum length is {minimum length}. |
| tooSmall | parameter, minimum | The {parameter} is too small. Minimum value is {minimum}. |
| unknownEndpoint | endpoint name | No endpoint called {endpoint_name}. |

## Changelog

### 1.0.0 Initial library

* Added controller methods `serve` and `get_data`.
* Added parameter and asserition methods `check`, `any`, `bool`, `number`, `id`, `str`, `email`, `date`, `time`, `datetime`, `file`.
* Added documentation and examples.