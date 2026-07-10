<?php
$hash = '$2y$10$og0ZPeiz8c4Txwcd9Nig9O8l2QL.qyUYOTYyhx46mJuc7bm.3w0.6';
$tests = ['admin', 'password', '123456', 'admin123', 'flaver', 'admin@123', 'password123'];
foreach ($tests as $t) {
    echo $t . ' => ' . (password_verify($t, $hash) ? 'MATCH' : 'NO') . PHP_EOL;
}
