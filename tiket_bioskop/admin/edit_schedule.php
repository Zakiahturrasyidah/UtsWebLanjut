<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID jadwal tidak ditemukan.");
}

$id = intval($_GET['id']);

// Ambil data jadwal
$sql = "SELECT * FROM schedules WHERE id = $id";
$result = $conn->query($sql);
$schedule = $result->fetch_assoc();

if (!$schedule) {
    die("Jadwal tidak ditemukan.");
}

// Ambil daftar film
$films = $conn->query("SELECT * FROM films");

// Ambil daftar studio
$studios = $conn->query("SELECT * FROM studios");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Jadwal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 p-6">
<div class="max-w-xl mx-auto bg-white shadow p-6 rounded">

    <h1 class="text-2xl font-bold mb-4">Edit Jadwal</h1>

    <form action="update_schedule.php" method="POST" class="space-y-4">
        <input type="hidden" name="id" value="<?= $schedule['id'] ?>">

        <!-- FILM -->
        <div>
            <label class="font-semibold">Film</label>
            <select name="filmId" class="w-full p-2 border rounded">
                <?php while ($film = $films->fetch_assoc()): ?>
                    <option value="<?= $film['id'] ?>"
                        <?= $film['id'] == $schedule['filmId'] ? 'selected' : '' ?>>
                        <?= $film['title'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- STUDIO -->
        <div>
            <label class="font-semibold">Studio</label>
            <select name="studioId" class="w-full p-2 border rounded">
                <?php while ($st = $studios->fetch_assoc()): ?>
                    <option value="<?= $st['id'] ?>"
                        <?= $st['id'] == $schedule['studioId'] ? 'selected' : '' ?>>
                        <?= $st['nama'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- WAKTU -->
        <div>
            <label class="font-semibold">Waktu Tayang</label>
            <input type="datetime-local"
                   name="showTime"
                   class="w-full p-2 border rounded"
                   value="<?= date('Y-m-d\TH:i', strtotime($schedule['showTime'])) ?>">
        </div>

        <!-- HARGA -->
        <div>
            <label class="font-semibold">Harga Tiket</label>
            <input type="number"
                   name="ticketPrice"
                   class="w-full p-2 border rounded"
                   value="<?= $schedule['ticketPrice'] ?>">
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Simpan Perubahan
        </button>

        <a href="schedules.php" class="text-gray-600 underline ml-2">Batal</a>

    </form>

</div>
</body>
</html>
