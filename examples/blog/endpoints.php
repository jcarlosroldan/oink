<?php

include_once 'db.php';
use function Oink\{str, number, enum, id, email, check};

function posts() {
	$tag = str("tag", optional: true);
	$page = number("page", min: 1, integer: true, default: 1, optional: true);
	$limit = enum("limit", values: [10, 20, 50], default: 10, optional: true);
	[$pages, $posts] = DB\posts($tag, $page - 1, $limit);
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