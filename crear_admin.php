<?php
$usuario = 'elmoa8m';
$contrasena = 'eLmoA@2381';
$hash = password_hash($contrasena, PASSWORD_BCRYPT);
echo "Usuario: {$usuario}\n";
echo "Hash: {$hash}\n";