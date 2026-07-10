<?php
require_once __DIR__ . '/api/config.php';

session_destroy();
header('Location: access.html');
exit;