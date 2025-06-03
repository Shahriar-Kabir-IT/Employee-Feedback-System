<?php include 'db.php'; ?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
  <style>
    body { font-family: Arial; background: #2d8fd4; color: white; margin: 0; }
    .sidebar { width: 220px; float: left; background: #1e6dbf; height: 100vh; padding: 20px; }
    .sidebar a { color: white; text-decoration: none; display: block; padding: 10px; margin-bottom: 10px; }
    .content { margin-left: 240px; padding: 20px; }
    .card { background: #ffffff33; padding: 20px; margin: 10px 0; border-radius: 10px; }
  </style>
</head>
<body>

<div class="sidebar">
  <h2>Admin</h2>
  <a href="index.php">Dashboard</a>
  <a href="see_all.php">See All Responses</a>
  <a href="resolve.php">Resolve Responses</a>
</div>

<div class="content">
  <h1>Dashboard Summary</h1>

  <?php
  $today = date('Y-m-d');
  $stmtToday = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE DATE(submitted_at) = ?");
  $stmtToday->execute([$today]);
  $todayCount = $stmtToday->fetchColumn();

  $stmtHappy = $pdo->query("SELECT COUNT(*) FROM feedback WHERE mood = 'happy'");
  $happyCount = $stmtHappy->fetchColumn();

  $stmtSad = $pdo->query("SELECT COUNT(*) FROM feedback WHERE mood = 'sad'");
  $sadCount = $stmtSad->fetchColumn();
  ?>

  <div class="card">📅 Today’s Responses: <strong><?= $todayCount ?></strong></div>
  <div class="card">😊 Total Happy: <strong><?= $happyCount ?></strong></div>
  <div class="card">😞 Total Sad: <strong><?= $sadCount ?></strong></div>
</div>

</body>
</html>
