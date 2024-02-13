<?php

include_once '../../oink.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

Oink\serve('endpoints.php', base_path: '/examples/gallery/api', debug: true);

?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='0.9em' font-size='90'%3E🐽%3C/text%3E%3C/svg%3E">
<title>Gallery</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<form enctype="multipart/form-data">
	<input type="text" name="title" placeholder="Add a title">
	<input type="file" name="image">
	<div>
		<button type="submit">UPLOAD</button>
		<div id="message"></div>
	</div>
</form>
<div id="gallery"></div>
<script src="app.js"></script>
</body>
</html>