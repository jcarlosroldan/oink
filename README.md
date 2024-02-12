# 🐽 Oink

Oink is a free and open source JSON API wrapper for PHP. It's a single file that you can include in your project and start using right away.

## Installation

You can install Oink by downloading the `oink.php` file and including it in your project. As part of the extreme minimalistic philosophy of Oink, there is not even a composer package for it.

You should have PHP 8.0 or later installed in your system, as it makes use of [named arguments](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments). 

## Basic usage

Just include `oink.php` and call the `serve` method from the `Oink` namespace, passing a reference to the PHP file where your endpoints are defined.

index.php
```php
include_once 'oink.php';

Oink\serve('endpoints.php');
```

endpoints.php
```php
include_once 'db.php';  // Your database functions
use function Oink\{str, number, enum, id, email};

function posts() {
	$tag = str("tag", optional: true);
	$page = int("page", min: 1, default: 1, optional: true);
	$limit = enum("limit", values: [10, 20, 50], default: 10, optional: true);
	[$pages, $posts] = DB\posts($page, $limit);
	return [
		"tag" => $tag, "page" => $page, "pages" => $pages, "limit" => $limit, "posts" => $posts
	];
}

function comments() {
	$post_id = id("post_id");
	check(DB\post_exists($post_id), "postNotFound");
	return ["comments" => DB\comments($post_id)];
}

function create_comment() {
	$post_id = id("post_id");
	$author = email("author");
	$text = str("text", min: 5, max: 100);
	check(DB\post_exists($post_id), "postNotFound");
	return ["id" => DB\create_comment($post_id, $author, $text)];
}
```

Let's see what's happening here:

* **Routing:** When calling `serve`, it looks at all function names defined in the file passed as argument and creates an endpoint for each one. Hence, going to `my.website/posts` will call the `posts` function. This routing can be customized by passing extra parameters to `serve`. The endpoints are method-agnostic, so you can use GET, POST, PUT, DELETE, etc. to access them.
* **Parameters:** In the endpoints file, the request parameters are read by calling functions with type names. For instance, calling `str("tag", optional: true)` will read the `tag` parameter, ensure it's a string, and return its value or `null` if it's not present. Since the endpoints are method-agnostic, the parameters can come as form data, JSON, cookies or even headers. By default, GET parameters are not allowed (since parameters are unaware fo the request method, this could lead to CSRF attacks), but you can enable them by passing `allow_get: true` to `serve`.
* **Response:** The requests always return a JSON object or array returned by the endpoint function. If the function doesn't return anything, it will return `{"success": true}`. If a parameter validation fails or if a `check` is evaluated to false, it will return a 400 error with `{"error": "reason"}`. If an exception is thrown, it will return a 500 error.

To know more about the usage, check out the [documentation](reference.md) or the [examples](examples/).

## Contributing

If you want to contribute to Oink, you can do so by forking the repository and creating a pull request. You should have a look at the file first to follow the same ethos and style. If you plan to add a new feature or extend existing functionality, please open an issue first to discuss it.

> Perfection is achieved, not when there is nothing more to add, but when there is nothing left to take away.
>
> -- Antoine de Saint-Exupéry

## Why Oink?

Oink should feel like a pig in the mud: simple and comfortable, even though it's not the cleanest thing in the world. Some of the practices followed by this library (such as merging POST parameters, headers and cookies into a single space) are highly non-standard and laser-focused in development speed. If you are looking for a highly customizable, modular and scalable solution, you should look for a full-featured framework like Laravel, Symfony or Lumen. Oink is intended for small projects, prototypes, or as a quick way to create a JSON API for your existing project.