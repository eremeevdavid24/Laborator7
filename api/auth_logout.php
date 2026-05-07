<?php
require __DIR__ . '/_init.php';
session_destroy();
json_out(['ok'=>true]);
?>
