<?php
require_once __DIR__ . '/includes/auth.php';

logout_user();
send_no_cache_headers();
header('Location: login.php');
exit;
