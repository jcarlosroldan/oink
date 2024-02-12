<?php

include_once '../../oink.php';

Oink\serve('endpoints.php', base_path: '/examples/blog', allow_get: true);