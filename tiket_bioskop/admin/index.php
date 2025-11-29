<?php
// admin/index.php
session_start();
require_once "../config/db.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

function countTable($conn,$t){ $r=$conn->query("SELECT COUNT(*) c FROM $t"); return $r->fetch_assoc()['c'] ?? 0; }
$totalFilms = countTable($conn,'films');
$totalStudios = countTable($conn,'studios');
$totalSchedules = countTable($conn,'schedules');
$totalBookings = countTable($conn,'bookings');
$totalUsers = countTable($conn,'user'); // table name is user

?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin - Tiket Bioskop</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50 min-h-screen">
<nav class="bg-white shadow p-3">
  <div class="container mx-auto flex justify-between items-center">
    <div class="font-bold">Admin Panel</div>
    <div>
      <span class="mr-4"><?=htmlspecialchars($_SESSION['user_name'] ?? 'Admin')?></span>
      <a href="../auth/logout.php" class="text-red-600">Logout</a>
    </div>
  </div>
</nav>
<main class="container mx-auto p-6">
  <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
    <div class="bg-white p-4 rounded shadow"><div class="text-sm text-gray-500">Films</div><div class="text-2xl font-bold"><?=$totalFilms?></div></div>
    <div class="bg-white p-4 rounded shadow"><div class="text-sm text-gray-500">Studios</div><div class="text-2xl font-bold"><?=$totalStudios?></div></div>
    <div class="bg-white p-4 rounded shadow"><div class="text-sm text-gray-500">Schedules</div><div class="text-2xl font-bold"><?=$totalSchedules?></div></div>
    <div class="bg-white p-4 rounded shadow"><div class="text-sm text-gray-500">Bookings</div><div class="text-2xl font-bold"><?=$totalBookings?></div></div>
    <div class="bg-white p-4 rounded shadow"><div class="text-sm text-gray-500">Users</div><div class="text-2xl font-bold"><?=$totalUsers?></div></div>
  </div>

  <div class="mt-6 bg-white p-4 rounded shadow">
    <h3 class="font-semibold">Quick Links</h3>
    <div class="mt-3 space-x-2">
      <a href="films.php" class="px-3 py-2 bg-indigo-600 text-white rounded">Films</a>
      <a href="schedules.php" class="px-3 py-2 bg-indigo-600 text-white rounded">Schedules</a>
      <a href="bookings.php" class="px-3 py-2 bg-green-600 text-white rounded">Bookings</a>
    </div>
  </div>
</main>
</body></html>
