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
				CREATE TABLE image (
					id INTEGER PRIMARY KEY,
					title TEXT,
					extension TEXT,
					created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				);
				
				INSERT INTO image (title, extension) VALUES
					('Not all pigs wear capes', 'png'),
					('Happy pig', 'png'),
					('Porks playing poker (porker?)', 'png'),
					('Hog in the mud', 'gif')
				");
		}
	}
	return $_conn;
}

function image_latest() {
	$latest = conn() -> prepare("SELECT * FROM image ORDER BY created DESC LIMIT 6");
	$latest -> execute();
	return $latest -> fetchAll(\PDO::FETCH_ASSOC);
}

function image_exists($id) {
	$exists = conn() -> prepare("SELECT 1 FROM image WHERE id = ?");
	$exists -> execute([$id]);
	return $exists -> fetchColumn() === 1;
}

function image_insert($title, $extension) {
	$insert = conn() -> prepare("INSERT INTO image (title, extension) VALUES (?, ?)");
	$insert -> execute([$title, $extension]);
	return conn() -> lastInsertId();
}