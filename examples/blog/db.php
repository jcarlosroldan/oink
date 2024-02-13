<?php

namespace DB;

$_conn = null;
function conn() {
	global $_conn;
	if ($_conn === null) {
		$populate = !file_exists("data.db");
		$_conn = new \PDO("sqlite:data.db");
		if ($populate) {
			$_conn->exec("
				CREATE TABLE posts (
					id INTEGER PRIMARY KEY,
					title TEXT,
					body TEXT,
					created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				);
				CREATE TABLE comments (
					id INTEGER PRIMARY KEY,
					post_id INTEGER,
					author TEXT,
					text TEXT,
					created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				);
				INSERT INTO posts (title, body) VALUES
					('Porcine intelligence', 'Pigs are among the smartest of all domesticated animals and are even smarter than dogs.'),
					('Lifespan of a pig', 'The average lifespan of a domestic pig is around 15 to 20 years.'),
					('Hogs and pigs', 'Hogs and pigs are the same animal; a hog is a pig that weighs more than 55kg.');
				
				INSERT INTO comments (post_id, author, text) VALUES
					(1, 'author1@mail.com', 'Fascinating! I didn’t know pigs were that intelligent.'),
					(1, 'author2@mail.com', 'That explains why they can learn tricks so easily.'),
					(2, 'author3@mail.com', '20 years is quite long for farm animals. Impressive.');			
				");
		}
	}
	return $_conn;
}

function posts($page=0, $limit=10) {
	$pages = conn() -> prepare("SELECT COUNT(*) FROM posts");
	$pages -> execute();
	$pages = ceil($pages -> fetchColumn() / $limit);
	$posts = conn() -> prepare("SELECT * FROM posts LIMIT :limit OFFSET :offset");
	$posts -> execute([":limit" => $limit, ":offset" => $page * $limit]);
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