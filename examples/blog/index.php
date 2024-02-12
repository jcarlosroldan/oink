<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../oink.php';

Oink\serve('endpoints.php', base_path: '/examples/blog', allow_get: true);