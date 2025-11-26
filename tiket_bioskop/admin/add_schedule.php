<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Ambil daftar film
$films = $conn->query("SELECT id, title FROM films ORDER BY title ASC")->fetch_all(MYSQLI_ASSOC);

// Ambil daftar studio
$studios = $conn->query("SELECT id, nama FROM studios ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// PROSES SIMPAN JADWAL
$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $filmId    = $_POST['film'];
    $studioId  = $_POST['studio'];
    $showTime  = $_POST['showTime'];
    $price     = $_POST['price'];

    if (!$filmId || !$studioId || !$showTime || !$price) {
        $error = "Semua field wajib diisi.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO schedules (filmId, studioId, showTime, ticketPrice)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $filmId, $studioId, $showTime, $price);

        if ($stmt->execute()) {
            header("Location: schedules.php");
            exit;
        } else {
            $error = "Gagal menyimpan jadwal.";
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tambah Jadwal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 p-6">

<div class="max-w-xl mx-auto bg-white p-5 rounded shadow">

    <h2 class="text-xl font-bold mb-4">Tambah Jadwal</h2>

    <a href="schedules.php" class="inline-block mb-4 text-indigo-600">← Kembali</a>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-3"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">

        <!-- Film -->
        <div>
            <label class="font-semibold">Film</label>
            <select name="film" class="w-full border p-2 rounded" required>
                <option value="">-- pilih film --</option>
                <?php foreach ($films as $f): ?>
                    <option value="<?= $f['id'] ?>"><?= $f['title'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Studio -->
        <div>
            <label class="font-semibold">Studio</label>
            <select name="studio" class="w-full border p-2 rounded" required>
                <option value="">-- pilih studio --</option>
                <?php foreach ($studios as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= $s['nama'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Waktu -->
        <div>
            <label class="font-semibold">Tanggal & Waktu</label>
            <input type="datetime-local" name="showTime" class="w-full border p-2 rounded" required>
        </div>

        <!-- Harga -->
        <div>
            <label class="font-semibold">Harga Tiket</label>
            <input type="number" name="price" class="w-full border p-2 rounded" required>
        </div>

        <button class="bg-indigo-600 text-white px-4 py-2 rounded">Simpan</button>

    </form>
</div>

</body>
</html>
