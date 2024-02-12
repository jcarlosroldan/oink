<?php

namespace DB;

$_conn = null;
function conn() {
	global $_conn;
	if ($_conn) return $_conn;
	$populate = !file_exists("data.db");
	$_conn = new \PDO("sqlite:data.db");
	if ($populate) {
		$_conn->exec("
			CREATE TABLE posts (
				id INTEGER PRIMARY KEY,
				title TEXT,
				body TEXT,
				tag TEXT,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
			);
			CREATE TABLE comments (
				id INTEGER PRIMARY KEY,
				post_id INTEGER,
				author TEXT,
				text TEXT,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
			);
			INSERT INTO posts (title, body, tag) VALUES
				('Hello, world!', 'This is the first post.', 'foo'),
				('Second post', 'This is the second post.', 'foo'),
				('Third post', 'This is the third post.', 'bar');
			INSERT INTO comments (post_id, author, text) VALUES
				(1, 'author1@mail.com', 'This is the first comment.'),
				(1, 'author2@mail.com', 'This is the second comment.'),
				(2, 'author3@mail.com', 'This is the third comment.');
		");
	}
	return $_conn;
}

function posts($tag=null, $page=0, $limit=10) {
	if ($tag) {
		$pages = conn() -> prepare("SELECT COUNT(*) FROM posts WHERE tag = :tag");
		$pages -> execute([":tag" => $tag]);
		$pages = ceil($pages -> fetchColumn() / $limit);
		$posts = conn() -> prepare("SELECT * FROM posts WHERE tag = :tag LIMIT :limit OFFSET :offset");
		$posts -> execute([":tag" => $tag, ":limit" => $limit, ":offset" => $page * $limit]);
	} else {
		$pages = conn() -> prepare("SELECT COUNT(*) FROM posts");
		$pages -> execute();
		$pages = ceil($pages -> fetchColumn() / $limit);
		$posts = conn() -> prepare("SELECT * FROM posts LIMIT :limit OFFSET :offset");
		$posts -> execute([":limit" => $limit, ":offset" => $page * $limit]);
	}
	return [$pages, $posts -> fetchAll(\PDO::FETCH_ASSOC)];
}

function post_exists($id) {
	$exists = conn() -> prepare("SELECT COUNT(*) FROM posts WHERE id = :id");
	$exists -> execute([":id" => $id]);
	return $exists -> fetchColumn() > 0;
}

function comments($post_id) {
	$comments = conn() -> prepare("SELECT * FROM comments WHERE post_id = :post_id");
	$comments -> execute([":post_id" => $post_id]);
	return $comments -> fetchAll(\PDO::FETCH_ASSOC);
}

function create_comment($post_id, $author, $text) {
	$insert = conn() -> prepare("INSERT INTO comments (post_id, author, text) VALUES (:post_id, :author, :text)");
	$insert -> execute([":post_id" => $post_id, ":author" => $author, ":text" => $text]);
	return conn() -> lastInsertId();
}