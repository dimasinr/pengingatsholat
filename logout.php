<?php
require_once __DIR__ . '/lib/auth.php';
auth_logout();
header('Location: index.php');
exit;
