<?php
// api/seat_availability.php
require_once "../config/db.php";
$schedule = (int)($_GET['schedule'] ?? 0);
if (!$schedule) { http_response_code(400); echo json_encode(['error'=>'schedule required']); exit; }
$res = $conn->prepare("SELECT seats_json FROM bookings WHERE schedule_id=? AND status='confirmed'");
$res->bind_param("i",$schedule); $res->execute(); $r = $res->get_result();
$booked = [];
while($row=$r->fetch_assoc()){ $arr=json_decode($row['seats_json'],true); if (is_array($arr)) $booked = array_merge($booked,$arr); }
header('Content-Type: application/json'); echo json_encode(['booked'=>array_values($booked)]);
