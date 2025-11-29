<?php
session_start();
require_once "../config/db.php";

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Cek schedule_id
if (!isset($_GET['schedule_id'])) {
    die("Schedule tidak ditemukan.");
}
$schedule_id = (int) $_GET['schedule_id'];


$stmt = $conn->prepare("
    SELECT s.*, st.nama AS studio_name, st.id AS studio_id
    FROM schedules s
    JOIN studios st ON st.id = s.studioId
    WHERE s.id = ?
");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$schedule) die("Schedule tidak ditemukan.");


$studio_id = (int)$schedule['studio_id'];

$seats_sql = "
    SELECT seatCode AS seat_code, isActive AS is_active
    FROM seats
    WHERE studioId = ?
    ORDER BY 
        LEFT(seatCode, 1),
        CAST(SUBSTRING(seatCode, 2) AS UNSIGNED)
";

$seats_res = $conn->prepare($seats_sql);
$seats_res->bind_param("i", $studio_id);
$seats_res->execute();
$seats_list = $seats_res->get_result()->fetch_all(MYSQLI_ASSOC);
$seats_res->close();

if (empty($seats_list)) {
    // Tampilkan pesan user-friendly (tidak die langsung)
    die("<h2 style='color:red'>ERROR: Tidak ada kursi untuk studioId = ".htmlspecialchars($studio_id).".<br>
    <small>Isi tabel <b>seats</b> dulu.</small></h2>");
}


$booked = [];

$bk = $conn->prepare("SELECT bookedSeats FROM bookings WHERE scheduleId = ? AND status = 'confirmed'");
$bk->bind_param("i", $schedule_id);
$bk->execute();
$rBooked = $bk->get_result();

while ($row = $rBooked->fetch_assoc()) {
    $arr = json_decode($row['bookedSeats'], true);
    if (is_array($arr)) $booked = array_merge($booked, $arr);
}
$bk->close();

$booked = array_unique($booked); // pastikan unik


$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    // seats should be JSON array string
    $selected_arr = json_decode($_POST['seats'] ?? '[]', true);
    if (!is_array($selected_arr)) $selected_arr = [];

    if (empty($selected_arr)) {
        $error = "Pilih minimal 1 kursi.";
    } else {
        
        $conn->begin_transaction();

        try {
            
            $bk2 = $conn->prepare("SELECT bookedSeats FROM bookings WHERE scheduleId=? AND status='confirmed' FOR UPDATE");
            $bk2->bind_param("i", $schedule_id);
            $bk2->execute();
            $res2 = $bk2->get_result();

            $already = [];
            while ($r = $res2->fetch_assoc()) {
                $a = json_decode($r['bookedSeats'], true);
                if (is_array($a)) $already = array_merge($already, $a);
            }
            $bk2->close();
            $already = array_unique($already);

            // Periksa apakah ada kursi yang sudah dipesan
            $conflict = array_values(array_intersect($already, $selected_arr));

            if (!empty($conflict)) {
                // rollback dan tampilkan pesan
                $conn->rollback();
                $error = "Kursi " . implode(", ", $conflict) . " sudah dipesan.";
            } else {
                
                $placeholders = implode(',', array_fill(0, count($selected_arr), '?'));
                
                $types = str_repeat('s', count($selected_arr));
                $sqlCheck = "SELECT seatCode, isActive FROM seats WHERE studioId = ? AND seatCode IN ($placeholders)";

                $stmtCheck = $conn->prepare($sqlCheck);
                
                $bindParams = [];
                $bindTypes = 'i' . $types;
                $bindParams[] = &$bindTypes;
                $bindParams[] = &$studio_id;
                foreach ($selected_arr as $k => $code) {
                    $bindParams[] = &$selected_arr[$k];
                }
                
                array_unshift($bindParams, $bindTypes);
                
                $refs = [];
                $refs[] = $bindTypes;
                $refs[] = &$studio_id;
                foreach ($selected_arr as $k => $code) {
                    $refs[] = &$selected_arr[$k];
                }
                // actually bind
                $tmp = [];
                foreach ($refs as $i => $r) $tmp[$i] = &$refs[$i];
                call_user_func_array([$stmtCheck, 'bind_param'], $tmp);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();

                $notActive = [];
                $foundSeats = [];
                while ($row = $resCheck->fetch_assoc()) {
                    $foundSeats[] = $row['seatCode'];
                    if (!$row['isActive']) $notActive[] = $row['seatCode'];
                }
                $stmtCheck->close();

                // Jika ada seatCode yang tidak ditemukan (invalid codes), laporkan
                $missing = array_diff($selected_arr, $foundSeats);
                if (!empty($missing)) {
                    $conn->rollback();
                    $error = "Kode kursi tidak valid: " . implode(", ", $missing);
                } elseif (!empty($notActive)) {
                    $conn->rollback();
                    $error = "Kursi " . implode(", ", $notActive) . " tidak aktif / tidak bisa dipilih.";
                } else {
                    // Simpan booking
                    $total = $schedule['ticketPrice'] * count($selected_arr);
                    $json = json_encode(array_values($selected_arr));

                    $ins = $conn->prepare("
                        INSERT INTO bookings (userId, scheduleId, bookedSeats, totalPrice, status)
                        VALUES (?, ?, ?, ?, 'confirmed')
                    ");
                    $ins->bind_param("iisd", $user_id, $schedule_id, $json, $total);

                    if ($ins->execute()) {
                        $ins->close();
                        $conn->commit();
                        $seatString = implode(",", $selected_arr);
                        header("Location: booking_success.php?sid=$schedule_id&seats=" . urlencode($seatString));
                        exit;
                    } else {
                        $ins->close();
                        $conn->rollback();
                        $error = "Gagal menyimpan booking. (" . htmlspecialchars($conn->error) . ")";
                    }
                }
            }

        } catch (Exception $ex) {
            $conn->rollback();
            $error = "Terjadi error sistem: " . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Booking</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

<a href="movie.php?id=<?= htmlspecialchars($schedule['filmId']) ?>" class="text-indigo-600 block mb-4">&larr; Kembali</a>

<div class="max-w-2xl mx-auto bg-white p-5 rounded shadow">

    <h2 class="text-2xl font-bold mb-2">Booking — <?= htmlspecialchars($schedule['studio_name']) ?></h2>

    <div class="text-gray-600 mb-4">
        <?= date("d M Y H:i", strtotime($schedule['showTime'])) ?> •
        Rp <?= number_format($schedule['ticketPrice'], 0, ',', '.') ?>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>


    <form method="post" id="bookingForm">
        <input type="hidden" name="seats" id="seatsInput" value="[]">

        <div class="grid grid-cols-6 gap-2 mb-4" id="seatsGrid">

            <?php foreach ($seats_list as $s):
                $code = htmlspecialchars($s['seat_code']);
                $isBooked = in_array($s['seat_code'], $booked);
                $isActive = (bool)$s['is_active'];
                // data-disabled attribute used in JS
                $disabled = (!$isActive || $isBooked);
            ?>

                <div
                    class="seat p-2 text-center rounded <?= $disabled ? 'bg-gray-300 text-gray-600 cursor-not-allowed' : 'border bg-white cursor-pointer hover:bg-indigo-50' ?>"
                    data-code="<?= $code ?>"
                    data-disabled="<?= $disabled ? '1' : '0' ?>"
                >
                    <?= $code ?>
                </div>

            <?php endforeach; ?>

        </div>

        <button name="book" class="bg-indigo-600 text-white px-4 py-2 rounded">Booking</button>
    </form>

</div>

<script>
const seats = document.querySelectorAll('.seat');
const sel = new Set();
const seatsInput = document.getElementById('seatsInput');

function syncInput() {
    seatsInput.value = JSON.stringify([...sel]);
}

seats.forEach(s => {
    if (s.dataset.disabled === '1') {
        return;
    }

    s.addEventListener('click', () => {
        const code = s.dataset.code;
        if (sel.has(code)) {
            sel.delete(code);
            s.classList.remove('bg-indigo-600','text-white');
            s.classList.add('bg-white');
        } else {
            sel.add(code);
            s.classList.add('bg-indigo-600','text-white');
            s.classList.remove('bg-white');
        }
        syncInput();
    });
});


syncInput();
</script>

</body>
</html>
