<?php
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|ico|svg|mp4|webm)$/', $_SERVER["REQUEST_URI"])) {
    return false; // serve the requested file as-is.
}

// Handle other requests through index.php
require_once 'index.php';
