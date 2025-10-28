<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'Hello from PHP', 'time' => time()]);

?>
