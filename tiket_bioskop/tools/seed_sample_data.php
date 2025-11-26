<?php
// tools/seed_sample_data.php
require_once "../config/db.php";

// create 1 cinema, 1 studio, seats A1-A10, 1 film, 3 schedules
$conn->query("INSERT INTO cinemas (name,city,address) VALUES ('CGV Mall A','Jakarta','Jl. Contoh No.1')");
$cinema_id = $conn->insert_id;
$conn->query("INSERT INTO studios (cinema_id,name,total_seats) VALUES ($cinema_id,'Studio 1',10)");
$studio_id = $conn->insert_id;
for ($i=1;$i<=10;$i++){
  $code = 'A'.$i;
  $stmt = $conn->prepare("INSERT INTO seats (studio_id,seat_code,is_active) VALUES (?,?,1)");
  $stmt->bind_param("is",$studio_id,$code); $stmt->execute(); $stmt->close();
}
$stmt = $conn->prepare("INSERT INTO films (title,synopsis,duration_minutes,language,poster_url) VALUES (?,?,?,?,?)");
$poster = "https://via.placeholder.com/400x600.png?text=Poster";
$stmt->bind_param("ssiss","Contoh Film","Sinopsis contoh", 120, "Indonesia", $poster); $stmt->execute(); $film_id = $conn->insert_id; $stmt->close();
$times = [date('Y-m-d H:00:00', strtotime('+1 day 14:00')), date('Y-m-d H:00:00', strtotime('+1 day 17:00'))];
foreach($times as $t){
  $s = $conn->prepare("INSERT INTO schedules (film_id,studio_id,show_time,ticket_price) VALUES (?,?,?,?)");
  $price=35000.00; $s->bind_param("iisd",$film_id,$studio_id,$t,$price); $s->execute(); $s->close();
}
echo "Seed complete.";
