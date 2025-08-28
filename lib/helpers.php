<?php
// lib/helpers.php
require __DIR__ . '/../config/db.php';

function is_weekend($date) {
  $w = date('N', strtotime($date)); // 6,7 weekend
  return $w >= 6;
}

function is_holiday($date) {
  global $pdo;
  $stmt = $pdo->prepare("SELECT 1 FROM holidays WHERE hdate = ?");
  $stmt->execute([$date]);
  return (bool)$stmt->fetchColumn();
}

function working_days_between($start, $end) {
  $s = new DateTime($start);
  $e = new DateTime($end);
  if ($e < $s) return 0;
  $days = 0;
  for ($d = clone $s; $d <= $e; $d->modify('+1 day')) {
    $ds = $d->format('Y-m-d');
    if (!is_weekend($ds) && !is_holiday($ds)) $days++;
  }
  return $days;
}
