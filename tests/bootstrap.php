<?php
// tests/bootstrap.php

// Disable file cache during tests to avoid stale data.
putenv('CACHE_DISABLED=1');

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/crud.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Cache.php';
require_once __DIR__ . '/../includes/models.php';
