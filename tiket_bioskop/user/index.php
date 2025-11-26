<?php
// user/index.php
session_start();
require_once "../config/db.php";
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$films = $conn->query("SELECT * FROM films ORDER BY createdAt DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Beranda - Tiket Bioskop</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50 min-h-screen">
<nav class="bg-white shadow p-3">
  <div class="container mx-auto flex justify-between">
    <div class="font-bold">TIKET BIOSKOP</div>
    <div>
      <span class="mr-4"><?=htmlspecialchars($_SESSION['user_name'])?></span>
      <a href="../auth/logout.php" class="text-red-600">Logout</a>
    </div>
  </div>
</nav>

<main class="container mx-auto p-6">
  <h2 class="text-2xl font-bold mb-4">Now Showing</h2>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach($films as $f): ?>
      <div class="bg-white rounded shadow overflow-hidden">
        <div class="h-48 bg-gray-100" style="background-image:url('<?=htmlspecialchars($f['posterUrl'] ?? $f['poster_url'] ?? '')?>');background-size:cover;background-position:center"></div>
        <div class="p-3">
          <h3 class="font-semibold"><?=htmlspecialchars($f['title'] ?? $f['name'] ?? 'Untitled')?></h3>
          <p class="text-sm text-gray-500"><?=htmlspecialchars(substr($f['description'] ?? $f['synopsis'] ?? '',0,80))?></p>
          <div class="mt-3">
            <a href="movie.php?id=<?=$f['id']?>" class="px-3 py-1 bg-indigo-600 text-white rounded">Detail & Jadwal</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>
</body></html>
