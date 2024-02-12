# 🐽 Reference

## Routing

## Parameters

## Response

## Error messages

| Code | Arguments | Example template | Explanation |
| - | - | - |
| invalidPath | path | `The path {path} is invalid.` | The path defined in the REQUEST_URI did not start by `/` (or the base path defined in `serve`'s `base_path` argument if any). |
| unknownEndpoint | function_name | `No endpoint was found for {function_name}.` | The file containing the endpoints was expected to have a function named `{function_name}` or `get_{function_name}`, but it was not found. |

## Changelog