<?php
session_start();
require_once "../config/db.php";

// pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['sid']) || !isset($_GET['seats'])) {
    die("Data booking tidak lengkap.");
}

$schedule_id = (int)$_GET['sid'];
$seats = explode(",", $_GET['seats']);

// Ambil info schedule
$stmt = $conn->prepare("
    SELECT s.*, f.title, st.nama AS studio
    FROM schedules s
    JOIN films f ON f.id = s.filmId
    JOIN studios st ON st.id = s.studioId
    WHERE s.id = ?
");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total = $data['ticketPrice'] * count($seats);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Booking Berhasil</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

<div class="max-w-xl mx-auto bg-white p-6 mt-10 shadow rounded">

    <h2 class="text-2xl font-bold text-green-600 mb-3">
        Booking Berhasil!
    </h2>

    <p class="text-gray-700 text-lg mb-4">
        Tiket Anda sudah dikonfirmasi 🎉
    </p>

    <div class="space-y-2 text-gray-800">
        <p><b>Film:</b> <?= htmlspecialchars($data['title']) ?></p>
        <p><b>Studio:</b> <?= htmlspecialchars($data['studio']) ?></p>
        <p><b>Waktu:</b> <?= date("d M Y H:i", strtotime($data['showTime'])) ?></p>
        <p><b>Kursi:</b> <?= implode(", ", $seats) ?></p>
        <p><b>Total:</b> Rp <?= number_format($total, 0, ',', '.') ?></p>
    </div>

    <div class="mt-6 flex gap-3">
        <a href="index.php" 
           class="bg-indigo-600 text-white px-4 py-2 rounded">
           Kembali ke Beranda
        </a>
        
        <button onclick="window.print()" 
                class="bg-gray-300 px-4 py-2 rounded">
            Cetak Tiket
        </button>
    </div>

</div>

</body>
</html>
