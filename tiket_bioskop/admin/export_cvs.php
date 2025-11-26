<?php
// admin/export_csv.php
session_start();
require_once "../config/db.php";
if (!isset($_SESSION['role'])||$_SESSION['role']!=='admin'){ header("Location: ../auth/login.php"); exit; }

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$from_dt = $from . " 00:00:00"; $to_dt = $to . " 23:59:59";

$q = $conn->prepare("
SELECT DATE_FORMAT(b.created_at,'%Y-%m') ym, c.name cinema, SUM(b.total_price) revenue, COUNT(b.id) cnt
FROM bookings b
JOIN schedules s ON s.id=b.schedule_id
JOIN studios st ON st.id=s.studio_id
JOIN cinemas c ON c.id=st.cinema_id
WHERE b.status='confirmed' AND b.created_at BETWEEN ? AND ?
GROUP BY ym, c.id ORDER BY ym DESC
");
$q->bind_param("ss",$from_dt,$to_dt);
$q->execute();
$res = $q->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=revenue_report_'.$from.'_'.$to.'.csv');
$out = fopen('php://output','w');
fputcsv($out, ['Month','Cinema','Revenue','Bookings']);
while($r=$res->fetch_assoc()){
    fputcsv($out, [$r['ym'],$r['cinema'],$r['revenue'],$r['cnt']]);
}
fclose($out);
exit;
