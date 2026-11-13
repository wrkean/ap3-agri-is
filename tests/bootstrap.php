<?php
// tests/bootstrap.php

// Run the suite against a throwaway copy of the seeded database so test
// inserts/deletes never modify the shipped database/agri.db artifact.
$source = __DIR__ . '/../database/agri.db';
if (is_file($source)) {
    $tmp = sys_get_temp_dir() . '/ap3-agri-test-' . getmypid() . '.db';
    copy($source, $tmp);
    putenv('AGRI_DB_PATH=' . $tmp);
    register_shutdown_function(static function () use ($tmp): void {
        if (is_file($tmp)) {
            @unlink($tmp);
        }
    });
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/crud.php';
require_once __DIR__ . '/../includes/auth.php';
