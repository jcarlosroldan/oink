# 🐽 Documentation

## Serve options

// TODO Explanation of serve, table with all parameters available.

## Routing

// TODO all defined with methods, optional get_ prefix, _ is reserved for your private methods

## Parameters

// TODO list of all parameter types and their options, mention the nested keys like access.this.parameter

## Errors

All error codes, params and explanations are defined in the following table. It is advised to follow the same format when calling `check`, so that an homogeneous i18n is possible at the frontend side.

| Code | Error | Arguments | Explanation |
| - | - | - | - |
| 400 | invalidDate | parameter, format | The {parameter} is not a valid date. Expected format is {format}. |
| 400 | invalidDatetime | parameter, format | The {parameter} is not a valid datetime. Expected format is {format}. |
| 400 | invalidEmail | parameter | The {parameter} is not a valid email address. |
| 400 | invalidExtension | extension, allowed extensions | The file has an invalid extension. Allowed extensions are {allowed extensions}. |
| 400 | invalidFile | parameter | The {parameter} is an invalid file. |
| 400 | invalidFormat | parameter | The {parameter} has an invalid format. |
| 400 | invalidTime | parameter, format | The {parameter} is not a valid time. Expected format is {format}. |
| 400 | invalidValue | parameter, allowed values | The {parameter} has an invalid value. Allowed values are {allowed values}. |
| 400 | fileTooBig | parameter, maximum size | The {parameter} is too big. Maximum size is {maximum size}. |
| 400 | notBoolean | parameter | The {parameter} is not a boolean value. |
| 400 | notFile | parameter | The {parameter} is not a file. |
| 400 | notFound | parameter* | The file with path {parameter} was not found. |
| 400 | notInteger | parameter | The {parameter} is not an integer. |
| 400 | notNumber | parameter | The {parameter} is not a number. |
| 400 | notSet | parameter | The {parameter} is not set. |
| 400 | notString | parameter | The {parameter} is not a string. |
| 400 | tooBig | parameter, maximum | The {parameter} is too big. Maximum value is {maximum}. |
| 400 | tooLong | parameter, maximum length | The {parameter} is too long. Maximum length is {maximum length}. |
| 400 | tooShort | parameter, minimum length | The {parameter} is too short. Minimum length is {minimum length}. |
| 400 | tooSmall | parameter, minimum | The {parameter} is too small. Minimum value is {minimum}. |
| 400 | unknownEndpoint | endpoint name | No endpoint called {endpoint_name}. |
| 500 | unexpectedError | trace* | An unexpected error occurred with the following trace: {trace}. |

* The argument is only shown when debug mode is active.

## Changelog

### 1.1.0 File uploading

* Added parameter method `file`.
* Added method `send_file` to return a file as a response.
* Added example for file uploading.

### 1.0.0 Initial library

* Added controller methods `serve` and `get_data`.
* Added parameter and asserition methods `check`, `any`, `bool`, `number`, `id`, `str`, `email`, `date`, `time`, `datetime`, `file`.
* Added documentation and examples.