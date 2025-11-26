<?php
// admin/reports.php
session_start();
require_once "../config/db.php";
if (!isset($_SESSION['role'])||$_SESSION['role']!=='admin'){ header("Location: ../auth/login.php"); exit; }

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$q = $conn->prepare("
SELECT DATE_FORMAT(b.created_at,'%Y-%m') ym, c.name cinema, SUM(b.total_price) revenue, COUNT(b.id) cnt
FROM bookings b
JOIN schedules s ON s.id=b.schedule_id
JOIN studios st ON st.id=s.studio_id
JOIN cinemas c ON c.id=st.cinema_id
WHERE b.status='confirmed' AND b.created_at BETWEEN ? AND ?
GROUP BY ym, c.id ORDER BY ym DESC
");
$from_dt = $from . " 00:00:00";
$to_dt = $to . " 23:59:59";
$q->bind_param("ss",$from_dt,$to_dt);
$q->execute();
$res = $q->get_result();
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Reports</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="p-6 bg-slate-50">
<div class="container mx-auto">
  <h2 class="text-xl font-bold mb-3">Revenue Reports</h2>
  <form class="flex gap-2 mb-3">
    <input type="date" name="from" value="<?=htmlspecialchars($from)?>" class="border p-1 rounded">
    <input type="date" name="to" value="<?=htmlspecialchars($to)?>" class="border p-1 rounded">
    <button class="px-3 py-1 bg-indigo-600 text-white rounded">Filter</button>
    <a href="export_csv.php?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>" class="px-3 py-1 bg-green-600 text-white rounded">Export CSV</a>
  </form>

  <div class="bg-white p-4 rounded shadow">
    <table class="w-full">
      <thead><tr><th>Month</th><th>Cinema</th><th>Revenue</th><th>Bookings</th></tr></thead>
      <tbody>
        <?php while($r=$res->fetch_assoc()): ?>
          <tr class="border-t">
            <td><?=htmlspecialchars($r['ym'])?></td>
            <td><?=htmlspecialchars($r['cinema'])?></td>
            <td>Rp <?=number_format($r['revenue'],0,',','.')?></td>
            <td><?= $r['cnt'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
</body></html>
