<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// pastikan semua field ada
if (
    !isset($_POST['id']) ||
    !isset($_POST['filmId']) ||
    !isset($_POST['studioId']) ||
    !isset($_POST['showTime']) ||
    !isset($_POST['ticketPrice'])
) {
    die("Data tidak lengkap.");
}

$id          = intval($_POST['id']);
$filmId      = intval($_POST['filmId']);
$studioId    = intval($_POST['studioId']);
$showTime    = $_POST['showTime'];
$ticketPrice = intval($_POST['ticketPrice']);

$sql = "
    UPDATE schedules 
    SET filmId = $filmId,
        studioId = $studioId,
        showTime = '$showTime',
        ticketPrice = $ticketPrice
    WHERE id = $id
";

if ($conn->query($sql)) {
    header("Location: schedules.php?updated=1");
    exit;
} else {
    echo "Gagal update data: " . $conn->error;
}
