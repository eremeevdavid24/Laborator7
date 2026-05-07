<?php
require __DIR__ . '/_init.php';
if (!user_id()) json_out(['ok'=>true,'user'=>null]);

json_out(['ok'=>true,'user'=>[
  'id'=>(int)$_SESSION['uid'],
  'name'=>(string)($_SESSION['name'] ?? ''),
  'role'=>(string)($_SESSION['role'] ?? 'user')
]]);
?>