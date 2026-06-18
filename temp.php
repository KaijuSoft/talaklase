<?php

$password = 'Admin@2025';

$hash = password_hash($password, PASSWORD_DEFAULT);

echo $hash;