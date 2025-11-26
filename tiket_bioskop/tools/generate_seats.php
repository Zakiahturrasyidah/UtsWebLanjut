<?php
require_once "../config/db.php";

$studios = $conn->query("SELECT id, nama FROM studios");
if ($studios->num_rows === 0) {
    die("Tidak ada studio. Tambahkan studio dulu.");
}

$rows = ['A','B','C','D','E'];
$numbers = range(1,10);

foreach ($studios as $st) {
    $studioId = $st['id'];

    echo "Generate kursi untuk studio: {$st['nama']}<br>";

    foreach ($rows as $row) {
        foreach ($numbers as $num) {
            $seat = $row . $num;

            // hindari duplikasi
            $check = $conn->prepare("SELECT id FROM seats WHERE studioId=? AND seatCode=?");
            $check->bind_param("is", $studioId, $seat);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows === 0) {
               $stmt = $conn->prepare("INSERT INTO seats (studioId, seatCode) VALUES (?, ?)");
                $stmt->bind_param("is", $studioId, $seat);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

echo "<hr>Generator kursi selesai!";
