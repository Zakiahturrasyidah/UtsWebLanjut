<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Ambil semua jadwal
$sql = "
SELECT s.*, f.title AS filmTitle, st.nama AS studioName
FROM schedules s
LEFT JOIN films f ON f.id = s.filmId
LEFT JOIN studios st ON st.id = s.studioId
ORDER BY s.showTime ASC
";
$result = $conn->query($sql);
$schedules = $result->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin — Kelola Jadwal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 p-6">
<div class="container mx-auto">

    <!-- HEADER + TOMBOL KEMBALI -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Kelola Jadwal</h1>

            <a href="index.php"
               class="inline-block mt-2 text-sm bg-gray-200 text-gray-700 px-3 py-1 rounded hover:bg-gray-300">
                ← Kembali
            </a>
        </div>

        <a href="add_schedule.php"
           class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            Tambah Jadwal
        </a>
    </div>

    <!-- TABEL -->
    <div class="bg-white rounded shadow p-4">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left">Film</th>
                <th class="p-2 text-left">Studio</th>
                <th class="p-2 text-left">Waktu</th>
                <th class="p-2 text-left">Harga</th>
                <th class="p-2 text-left">Aksi</th>
            </tr>
            </thead>

            <tbody>
            <?php if (empty($schedules)): ?>
                <tr>
                    <td colspan="5" class="p-4 text-center">Belum ada jadwal.</td>
                </tr>

            <?php else: foreach ($schedules as $s): ?>
                <tr class="border-t">
                    <td class="p-2"><?= htmlspecialchars($s['filmTitle']) ?></td>

                    <td class="p-2"><?= htmlspecialchars($s['studioName']) ?></td>

                    <td class="p-2">
                        <?= date("d M Y — H:i", strtotime($s['showTime'])) ?>
                    </td>

                    <td class="p-2">
                        Rp <?= number_format($s['ticketPrice'], 0, ',', '.') ?>
                    </td>

                    <td class="p-2 flex gap-4">

                        <!-- TOMBOL EDIT -->
                        <a href="edit_schedule.php?id=<?= $s['id'] ?>"
                           class="text-blue-600 hover:underline">
                            Edit
                        </a>

                        <!-- TOMBOL HAPUS -->
                        <a href="delete_schedule.php?id=<?= $s['id'] ?>"
                           onclick="return confirm('Yakin ingin menghapus jadwal ini?');"
                           class="text-red-600 hover:underline">
                            Hapus
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>

        </table>
    </div>

</div>
</body>
</html>
