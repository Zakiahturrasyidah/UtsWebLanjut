<?php
session_start();
require_once "../config/db.php";

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// CEK film id
if (!isset($_GET['id'])) {
    die("Film tidak ditemukan.");
}

$filmId = (int)$_GET['id'];

// ==============================
// AMBIL DATA FILM
// ==============================
$stmt = $conn->prepare("SELECT * FROM films WHERE id = ?");
$stmt->bind_param("i", $filmId);
$stmt->execute();
$film = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$film) {
    die("Film tidak ditemukan.");
}

// ==============================
// AMBIL SEMUA JADWAL FILM
// ==============================
$query = "
    SELECT sch.id, sch.showTime, sch.ticketPrice, st.nama AS studio
    FROM schedules sch
    JOIN studios st ON st.id = sch.studioId
    WHERE sch.filmId = ?
    ORDER BY sch.showTime ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $filmId);
$stmt->execute();
$schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($film['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 p-6">

<!-- Tombol Kembali -->
<a href="index.php" class="text-indigo-600 mb-4 inline-block hover:underline">&larr; Kembali</a>

<!-- Judul Film -->
<h2 class="text-3xl font-bold mb-2"><?= htmlspecialchars($film['title']) ?></h2>

<!-- Deskripsi -->
<p class="text-gray-700 leading-relaxed mb-6">
    <?= nl2br(htmlspecialchars($film['description'])) ?>
</p>

<!-- Jadwal -->
<h3 class="text-2xl font-bold mb-3">Jadwal Tersedia</h3>

<?php if (empty($schedules)): ?>
    <p class="text-gray-500">Belum ada jadwal tersedia.</p>
<?php else: ?>
    <div class="space-y-3">

        <?php foreach ($schedules as $sc): ?>
        <div class="p-4 border rounded bg-white shadow-sm">
            <div class="text-lg font-semibold">
                <?= date("d M Y H:i", strtotime($sc['showTime'])) ?>
            </div>

            <div class="text-gray-700 mb-2">
                Studio: <b><?= htmlspecialchars($sc['studio']) ?></b>
            </div>

            <div class="text-gray-900 font-bold">
                Harga: Rp <?= number_format($sc['ticketPrice'], 0, ',', '.') ?>
            </div>

            <a href="booking.php?schedule_id=<?= $sc['id'] ?>"
               class="inline-block mt-3 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
               Pesan Tiket
            </a>
        </div>
        <?php endforeach; ?>

    </div>
<?php endif; ?>

</body>
</html>
