<?php

include_once 'db.php';
use function Oink\{check, str, file, id, send_file};

function latest() {
	return DB\image_latest();
}

function image() {
	$filename = str('filename', pattern: "/^\d+\.\w+$/");
	$image = check(DB\image_exists(explode(".", $filename, 2)[0]), "notFound", "image");
	return send_file('images/' . $filename);
}

function upload() {
	$title = str('title', min: 3, max: 120);
	$file = file('image', extensions: ['jpg', 'jpeg', 'png', 'gif'], max_size: 5*1024*1024);
	$id = DB\image_insert($title, $file['extension']);
	move_uploaded_file($file['tmp_name'], 'images/' . $id . '.' . $file['extension']);
	return ['id' => $id];
}