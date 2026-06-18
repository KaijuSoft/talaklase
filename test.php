<?php

$password = 'Admin@2025';

$hash = '$2y$10$W448h3lXM60GXS9e.YrhT.byCSrpuU1cfc.Q.WUmSkzFVyR/na8qO';

var_dump(password_verify($password, $hash));