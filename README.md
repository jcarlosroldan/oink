# 🐽 oink.php

oink.php is a free and open source API wrapper for PHP, in a single file.

## Installation

You can install it by downloading the `oink.php` file and including it in your project. It doesn't have dependencies and is compatible with PHP 8.0 or later.

You will need to make all requests go through your `index.php` file. This can be done by using an `.htaccess` file, a `web.config` file, or by configuring your web server. You can find examples of `.htaccess` files in the [examples](examples/) directory.

## Basic Usage

This minimal example shows how to create a simple blog API:

**index.php**
```php
include_once 'oink.php';

Oink\serve('endpoints.php');
```

**endpoints.php**
```php
include_once 'db.php';  // Your database functions
use function Oink\{str, enum, id, email};

function post_list() {
    $page = id("page", min: 1, default: 1, optional: true);
    $limit = enum("limit", values: [10, 20, 50], default: 10, optional: true);
    [$pages, $posts] = DB\posts($page, $limit);
    return [
        "page" => $page, "pages" => $pages, "limit" => $limit, "posts" => $posts
    ];
}

function comment_list() {
    $post_id = id("post_id");
    check(DB\post_exists($post_id), "postNotFound");
    return ["comments" => DB\comments($post_id)];
}

function comment_create() {
    $post_id = id("post_id");
    $author = email("author");
    $text = str("text", min: 5, max: 100);
    check(DB\post_exists($post_id), "postNotFound");
    return ["id" => DB\create_comment($post_id, $author, $text)];
}
```

Let's see what's happening here:

* **Routing:** When calling `serve`, it looks at all function names defined in the file passed as an argument and creates an endpoint for each one. Hence, going to `my.website/post/list` will call the `post_list` function. This routing can be customized by passing extra parameters to `serve`. The endpoints are method-agnostic, so you can use GET, POST, PUT, DELETE, etc., to access them.
* **Parameters:** In the endpoints file, the request parameters are read by calling functions with type names. For instance, calling `str("tag", optional: true)` will read the `tag` parameter, ensure it's a string, and return its value or `null` if not present. Parameters can come as form data, JSON, cookies, or even headers. By default, GET parameters are not allowed (since parameters are unaware of the request method, this could lead to CSRF attacks), but you can enable them by passing `allow_get: true` to `serve`.
* **Response:** The response is a JSON object or array returned by the endpoint function. If the function doesn't return anything, the response will be `{"success": true}`. If a parameter validation fails or if a `check` is evaluated to false, it will return a 400 error with `{"error": <reason>}`. If an exception is thrown, it will return a 500 error.

To know more about the usage, check out the [documentation](documentation.md) and the [examples](examples).

## Contributing

If you want to contribute, you can do so by forking the repository and creating a pull request. You should have a look at the file first to follow the same ethos and style. If you plan to add a new feature or extend existing functionality, please open an issue first to discuss it. Feel free to open an issue if you find a bug or have a suggestion.

> Perfection is achieved, not when there is nothing more to add, but when there is nothing left to take away.
>
> -- Antoine de Saint-Exupéry

## Why Oink?

Oink should feel like a pig in the mud: simple and comfortable, even though it's not the cleanest thing in the world. This library prioritizes development speed and simplicity over everything else, including best practices and standards. If you are looking for a highly customizable, modular, and scalable solution, you should look for a full-featured framework like Laravel, Symfony, or Lumen.