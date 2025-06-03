<?php
date_default_timezone_set('Asia/Dhaka');
require_once('db_config.php');

$conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM feedback ORDER BY timestamp DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Feedback Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #0066cc, #66ccff);
      font-family: 'Segoe UI', sans-serif;
      padding-top: 40px;
      color: #fff;
    }

    .container {
      max-width: 95%;
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.15);
      color: #000;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #007BFF;
      font-size: 1.8rem;
    }

    th {
      background-color: #007BFF;
      color: white;
    }

    td, th {
      text-align: center;
      vertical-align: middle;
      font-size: 1rem;
      padding: 12px;
    }

    .badge-happy {
      background-color: #28a745;
      font-size: 0.9rem;
    }

    .badge-sad {
      background-color: #dc3545;
      font-size: 0.9rem;
    }

    @media (max-width: 768px) {
      h2 {
        font-size: 1.4rem;
      }

      td, th {
        font-size: 0.9rem;
        padding: 10px 8px;
      }

      .container {
        padding: 15px;
      }

      .badge-happy, .badge-sad {
        font-size: 0.8rem;
      }

      .table-responsive {
        overflow-x: auto;
      }
    }

    @media (orientation: portrait) and (max-width: 1024px) {
      .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <h2>All Feedback Entries</h2>
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Employee ID</th>
          <th>Mood</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['employee_id']) ?></td>
            <td>
              <span class="badge <?= $row['mood'] === 'happy' ? 'badge-happy' : 'badge-sad' ?>">
                <?= ucfirst($row['mood']) ?>
              </span>
            </td>
            <td><?= date("h:i A, d M Y", strtotime($row['timestamp'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
