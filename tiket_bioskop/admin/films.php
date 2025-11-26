<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM films WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: films.php");
    exit;
}

// HANDLE CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $dur = intval($_POST['durationMinutes']);
    $lang = $_POST['language'];
    $poster = $_POST['posterUrl'];

    if ($id == 0) {
        // insert
        $stmt = $conn->prepare("
            INSERT INTO films (title, description, durationMinutes, language, posterUrl)
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param("ssiss", $title, $desc, $dur, $lang, $poster);
    } else {
        // update
        $stmt = $conn->prepare("
            UPDATE films
            SET title=?, description=?, durationMinutes=?, language=?, posterUrl=?
            WHERE id=?
        ");
        $stmt->bind_param("ssissi", $title, $desc, $dur, $lang, $poster, $id);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: films.php");
    exit;
}

// FETCH LIST
$films = $conn->query("SELECT * FROM films ORDER BY createdAt DESC")->fetch_all(MYSQLI_ASSOC);

// FETCH EDIT DATA
$editData = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM films WHERE id=$id");
    $editData = $res->fetch_assoc();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Films</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50">

<!-- NAV -->
<nav class="bg-white shadow p-3 mb-6">
  <div class="container mx-auto flex justify-between items-center">
    <h1 class="text-xl font-bold">Admin - Films</h1>
    <a href="../auth/logout.php" class="text-red-500">Logout</a>
  </div>
</nav>

<div class="container mx-auto p-6">

    <!-- Tombol Kembali -->
    <a href="index.php" class="inline-block mb-4 text-indigo-600 hover:text-indigo-800">
        ← Kembali
    </a>

    <!-- FORM TAMBAH / EDIT -->
    <div class="bg-white p-5 rounded shadow mb-6">
        <h2 class="font-bold text-lg mb-4">
            <?= $editData ? "Edit Film" : "Tambah Film" ?>
        </h2>

        <form method="post" class="space-y-4">
            <input type="hidden" name="id" value="<?= $editData['id'] ?? 0 ?>">

            <div>
                <label class="font-semibold">Title</label>
                <input name="title" required value="<?= $editData['title'] ?? '' ?>" 
                       class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="font-semibold">Description</label>
                <textarea name="description" rows="4"
                    class="w-full border p-2 rounded"><?= $editData['description'] ?? '' ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="font-semibold">Duration (minutes)</label>
                    <input type="number" name="durationMinutes" required
                        value="<?= $editData['durationMinutes'] ?? '' ?>"
                        class="w-full border p-2 rounded">
                </div>

                <div>
                    <label class="font-semibold">Language</label>
                    <input name="language" value="<?= $editData['language'] ?? '' ?>"
                        class="w-full border p-2 rounded">
                </div>

                <div>
                    <label class="font-semibold">Poster URL</label>
                    <input name="posterUrl" value="<?= $editData['posterUrl'] ?? '' ?>"
                        class="w-full border p-2 rounded">
                </div>
            </div>

            <button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                <?= $editData ? "Update" : "Tambah" ?>
            </button>
        </form>
    </div>

    <!-- DAFTAR FILM -->
    <div class="bg-white p-5 rounded shadow">
        <h2 class="font-bold text-lg mb-3">Daftar Film</h2>

        <table class="w-full border rounded overflow-hidden">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border">Poster</th>
                    <th class="p-2 border">Title</th>
                    <th class="p-2 border">Durasi</th>
                    <th class="p-2 border">Bahasa</th>
                    <th class="p-2 border">Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($films as $f): ?>
                <tr class="border-t">
                    <td class="p-2 border">
                        <?php if ($f['posterUrl']): ?>
                            <img src="<?= $f['posterUrl'] ?>" class="h-16 rounded shadow">
                        <?php endif; ?>
                    </td>
                    <td class="p-2 border"><?= htmlspecialchars($f['title']); ?></td>
                    <td class="p-2 border"><?= $f['durationMinutes'] ?> menit</td>
                    <td class="p-2 border"><?= $f['language'] ?></td>
                    <td class="p-2 border">
                        <a href="films.php?edit=<?= $f['id'] ?>" class="text-blue-600 hover:underline">Edit</a> |
                        <a href="films.php?delete=<?= $f['id'] ?>" 
                           onclick="return confirm('Yakin hapus film ini?')"
                           class="text-red-600 hover:underline">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

</body>
</html>
