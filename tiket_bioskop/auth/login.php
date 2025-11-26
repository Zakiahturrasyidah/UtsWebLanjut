<?php
session_start();
require_once "../config/db.php";

$error = null;

/* ----------------------------
   Validasi tabel user
----------------------------- */
$check = $conn->query("SHOW TABLES LIKE 'user'");
if (!$check || $check->num_rows === 0) {
    die("<div style='padding:20px;background:#fee;border:1px solid #d00;color:#900'>
            ERROR: Tabel <b>user</b> tidak ditemukan dalam database <b>tiket_bioskop</b>.
         </div>");
}

/* ----------------------------
   LOGIN PROSES
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    if ($email === '' || $pass === '') {
        $error = "Email & Password wajib diisi.";
    } else {

        // Query sesuai tabel user
        $stmt = $conn->prepare("
            SELECT id, nama, email, passwordHash, role 
            FROM user WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        /* ----------------------------
           Validasi email ditemukan
        ----------------------------- */
        if (!$user) {
            $error = "Email tidak ditemukan.";
        } else {

            $stored = $user['passwordHash'];

            $valid = false;

            /* ----------------------------
               PRIMARY: Verify hashed password
            ----------------------------- */
            if (!empty($stored) && password_verify($pass, $stored)) {
                $valid = true;
            }

            /* ----------------------------
               FALLBACK: jika passwordHash kosong 
               dan database masih menyimpan plaintext (untuk testing)
            ----------------------------- */
            if (!$valid && $stored === $pass) {
                $valid = true;

                // convert plaintext ke hash Bcrypt
                $newHash = password_hash($pass, PASSWORD_BCRYPT);

                $u = $conn->prepare("UPDATE user SET passwordHash=? WHERE id=?");
                $u->bind_param("si", $newHash, $user['id']);
                $u->execute();
                $u->close();
            }

            if ($valid) {
                // set session
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['nama'];
                $_SESSION['role']      = $user['role'];

                // redirect otomatis
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/index.php");
                } else {
                    header("Location: ../user/index.php");
                }
                exit;

            } else {
                $error = "Password salah.";
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login - Tiket Bioskop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-b from-slate-50 to-white min-h-screen flex items-center justify-center">

<div class="w-full max-w-md bg-white p-6 rounded shadow">

    <h2 class="text-2xl font-bold text-center mb-4">TIKET BIOSKOP</h2>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-800 p-2 rounded mb-3">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-3">
        <input name="email" type="email" placeholder="Email"
               class="w-full border p-2 rounded" required>

        <input name="password" type="password" placeholder="Password"
               class="w-full border p-2 rounded" required>

        <button class="w-full bg-indigo-600 text-white p-2 rounded">Login</button>
    </form>

    <p class="text-center text-sm mt-3">
        Belum punya akun?
        <a href="register.php" class="text-indigo-600">Daftar</a>
    </p>

</div>
</body>
</html>
