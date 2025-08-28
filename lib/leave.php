<?php
// lib/leave.php
require __DIR__ . '/../config/db.php';

function get_balances($user_id, $year) {
  global $pdo;
  $sql = "SELECT lb.*, lt.name AS type_name, lt.code
          FROM leave_balances lb
          JOIN leave_types lt ON lt.id = lb.type_id
          WHERE lb.user_id = ? AND lb.year = ?";
  $st = $pdo->prepare($sql); $st->execute([$user_id, $year]);
  return $st->fetchAll();
}

function deduct_balance($user_id, $type_id, $year, $days) {
  global $pdo;
  $pdo->beginTransaction();
  $st = $pdo->prepare("SELECT * FROM leave_balances WHERE user_id=? AND type_id=? AND year=? FOR UPDATE");
  $st->execute([$user_id, $type_id, $year]);
  $row = $st->fetch();
  if (!$row || $row['remaining'] < $days) { $pdo->rollBack(); return false; }
  $used = $row['used'] + $days;
  $remaining = $row['remaining'] - $days;
  $up = $pdo->prepare("UPDATE leave_balances SET used=?, remaining=? WHERE id=?");
  $up->execute([$used, $remaining, $row['id']]);
  $pdo->commit();
  return true;
}
