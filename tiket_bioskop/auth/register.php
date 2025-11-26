<?php
// auth/register.php
session_start();
require_once "../config/db.php";

$errors = [];
$success = null;

// pastikan tabel 'user' ada
$chk = $conn->query("SHOW TABLES LIKE 'user'");
if ($chk->num_rows === 0) {
    die("Tabel 'user' tidak ditemukan. Silakan buat tabel user di database tiket_bioskop.");
}

// get columns list to know which field names to use
$cols = [];
$res = $conn->query("SHOW COLUMNS FROM `user`");
while ($c = $res->fetch_assoc()) $cols[] = $c['Field'];

$nameField = in_array('nama',$cols) ? 'nama' : (in_array('name',$cols) ? 'name' : null);
$pwdField  = in_array('passwordHash',$cols) ? 'passwordHash' : (in_array('password_hash',$cols) ? 'password_hash' : (in_array('password',$cols) ? 'password' : null));
$roleField = in_array('role',$cols) ? 'role' : null;

if (!$nameField || !$pwdField) {
    die("Tabel user tidak memiliki kolom nama/namaField atau passwordField yang diperlukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!$nama || !$email || !$pass) $errors[] = "Semua field wajib diisi.";

    // cek unique email
    $s = $conn->prepare("SELECT id FROM `user` WHERE email=? LIMIT 1");
    $s->bind_param("s",$email);
    $s->execute();
    $s->store_result();
    if ($s->num_rows) $errors[] = "Email sudah terdaftar.";
    $s->close();

    if (empty($errors)) {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        // role default 'user', but if table has role column and user count==0 -> make admin
        $role = 'user';
        if ($roleField) {
            $count = $conn->query("SELECT COUNT(*) as c FROM `user`")->fetch_assoc()['c'] ?? 0;
            if ($count == 0) $role = 'admin';
        }

        // build insert depending on available columns
        if ($roleField) {
            $stmt = $conn->prepare("INSERT INTO `user` (`$nameField`, `email`, `$pwdField`, `role`) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $nama, $email, $hash, $role);
        } else {
            $stmt = $conn->prepare("INSERT INTO `user` (`$nameField`, `email`, `$pwdField`) VALUES (?,?,?)");
            $stmt->bind_param("sss", $nama, $email, $hash);
        }
        if ($stmt->execute()) {
            $_SESSION['flash'] = "Registrasi berhasil. Silakan login.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Gagal menyimpan: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Register - Tiket Bioskop</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center">
  <div class="w-full max-w-md bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold text-center mb-4">Daftar Akun</h2>
    <?php if(!empty($errors)): ?>
      <div class="bg-red-50 text-red-800 p-2 rounded mb-3">
        <?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
      </div>
    <?php endif; ?>
    <form method="post" class="space-y-3">
      <input name="name" placeholder="Nama" class="w-full border p-2 rounded" required>
      <input name="email" placeholder="Email" type="email" class="w-full border p-2 rounded" required>
      <input name="password" type="password" placeholder="Password" class="w-full border p-2 rounded" required>
      <button class="w-full bg-indigo-600 text-white p-2 rounded">Daftar</button>
    </form>
    <p class="text-center text-sm mt-3">Sudah punya akun? <a href="login.php" class="text-indigo-600">Login</a></p>
  </div>
</body></html>
