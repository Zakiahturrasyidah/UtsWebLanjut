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

$sql = "DELETE FROM schedules WHERE id = $id";

if ($conn->query($sql)) {
    header("Location: schedules.php?deleted=1");
    exit;
} else {
    echo "Gagal menghapus data: " . $conn->error;
}
