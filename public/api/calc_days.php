<?php
require_once __DIR__ . '/../../lib/auth.php'; require_login();
require_once __DIR__ . '/../../lib/helpers.php';
header('Content-Type: application/json');
$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';
echo json_encode(['days' => working_days_between($start,$end)]);
