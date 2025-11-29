<?php
// admin/bookings.php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}


$userColsRes = $conn->query("SHOW COLUMNS FROM `user`");
if (!$userColsRes) {
    die("Tidak dapat membaca struktur tabel user: " . $conn->error);
}

$cols = $userColsRes->fetch_all(MYSQLI_ASSOC);
$userNameCol = null;
$possible = ['name','nama','username','user_name','full_name','fullname'];

foreach ($cols as $c) {
    $f = strtolower($c['Field']);
    if (in_array($f, $possible)) {
        $userNameCol = $c['Field'];
        break;
    }
}

if ($userNameCol === null) {
    // fallback jika tidak ditemukan
    foreach ($cols as $c) {
        if (strtolower($c['Field']) !== 'id') {
            $userNameCol = $c['Field'];
            break;
        }
    }
}

$sql = "
SELECT 
  b.id AS booking_id,
  b.userId,
  u.`{$userNameCol}` AS userName,
  f.title AS filmTitle,
  sch.showTime,
  b.bookedSeats,
  b.totalPrice,
  b.status,
  b.createdAt
FROM bookings b
LEFT JOIN `user` u ON u.id = b.userId
LEFT JOIN schedules sch ON sch.id = b.scheduleId
LEFT JOIN films f ON f.id = sch.filmId
ORDER BY b.createdAt DESC
";

$result = $conn->query($sql);
if (!$result) {
    die("<pre>SQL ERROR:\n" . $conn->error . "\n\nQUERY:\n$sql</pre>");
}

$bookings = $result->fetch_all(MYSQLI_ASSOC);

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Bookings</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 p-6">
<div class="container mx-auto">

  <!-- HEADER -->
  <div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold">Admin — Daftar Booking</h1>

        <!-- Tombol kembali -->
        <a href="index.php" 
           class="inline-block mt-2 text-sm bg-gray-200 text-gray-700 px-3 py-1 rounded hover:bg-gray-300">
           ← Kembali
        </a>
    </div>

    <a href="../auth/logout.php" class="text-red-600 hover:underline">Logout</a>
  </div>

  <!-- TABEL -->
  <div class="bg-white p-4 rounded shadow overflow-x-auto">

    <table class="w-full text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 text-left w-10">No</th>
          <th class="p-2 text-left">User</th>
          <th class="p-2 text-left">Film</th>
          <th class="p-2 text-left">Show Time</th>
          <th class="p-2 text-left">Kursi</th>
          <th class="p-2 text-left">Total</th>
          <th class="p-2 text-left">Status</th>
          <th class="p-2 text-left">Dibuat</th>
        </tr>
      </thead>

      <tbody>
        <?php if (empty($bookings)): ?>
          <tr>
            <td class="p-4 text-center" colspan="8">Belum ada pemesanan.</td>
          </tr>

        <?php else: foreach ($bookings as $i => $b): 
            $kursi = json_decode($b['bookedSeats'], true);
            if (!is_array($kursi)) $kursi = [$b['bookedSeats']];
        ?>
          <tr class="border-t">
            <td class="p-2"><?= $i+1 ?></td>

            <td class="p-2"><?= htmlspecialchars($b['userName'] ?? '—') ?></td>

            <td class="p-2"><?= htmlspecialchars($b['filmTitle'] ?? '—') ?></td>

            <td class="p-2">
              <?= $b['showTime'] ? date('d M Y H:i', strtotime($b['showTime'])) : '—' ?>
            </td>

            <td class="p-2">
              <?= implode(', ', array_map('htmlspecialchars', $kursi)) ?>
            </td>

            <td class="p-2">
              Rp <?= number_format($b['totalPrice'], 0, ',', '.') ?>
            </td>

            <td class="p-2">
              <span class="px-2 py-1 rounded text-white 
                <?= $b['status'] === 'confirmed' ? 'bg-green-600' : 'bg-gray-500' ?>">
                <?= htmlspecialchars($b['status']) ?>
              </span>
            </td>

            <td class="p-2"><?= htmlspecialchars($b['createdAt']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

  </div>
</div>
</body>
</html>
