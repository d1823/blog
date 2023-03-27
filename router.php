<?php

$document_root = $_SERVER['DOCUMENT_ROOT'];
$url_path = trim($_SERVER['REQUEST_URI'], '/');

if (!$url_path) {
    return false;
}

if (preg_match('/[.]/', $url_path)) {
    return false;
}

readfile("$document_root/$url_path.html");
