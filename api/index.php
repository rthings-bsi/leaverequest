<?php

// Set document root ke public folder Laravel
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';

// Jalankan Laravel dari public/index.php
require __DIR__ . '/../public/index.php';
