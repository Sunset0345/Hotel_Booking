<?php
$pw = 'admin123';
$h = password_hash($pw, PASSWORD_DEFAULT);
echo "generated: $h\n";
var_dump(password_verify($pw, $h));

$stored = '$2y$10$aQW/U/Ofn6nsdk6MJN7DFuPfn.nrb7r9ZBrBd85on2ErNSO6NvGti';
echo "stored: $stored\n";
var_dump(password_verify($pw, $stored));
