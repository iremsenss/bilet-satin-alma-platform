<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$trip_id = trim($_POST['trip_id'] ?? '');
$selected_seat = trim($_POST['selected_seat'] ?? '');
$coupon_code_from_form = trim($_POST['coupon_code'] ?? '');

if (empty($trip_id) || empty($selected_seat) || !is_numeric($trip_id) || !is_numeric($selected_seat)) {
    header('Location: book.php?trip_id=' . $trip_id . '&error=1&message=' . urlencode("Sefer veya koltuk bilgisi eksik/geçersiz."));
    exit;
}

try {
    $db = getdbConnection();
    $db->beginTransaction();

    $stmt_trip = $db->prepare("SELECT price, capacity, company_id FROM trips WHERE id = ?");
    $stmt_trip->execute([$trip_id]);
    $trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);
    $stmt_trip->closeCursor();

    if (!$trip) {
        throw new Exception("Sefer bulunamadı.");
    }

    $stmt_seat_check = $db->prepare("
        SELECT COUNT(*) FROM booked_seats bs
        JOIN tickets t ON bs.ticket_id = t.id
        WHERE t.trip_id = ? AND bs.seat_number = ? AND t.status = 'active'
    ");
    $stmt_seat_check->execute([$trip_id, $selected_seat]);
    if ($stmt_seat_check->fetchColumn() > 0) {
        throw new Exception("Üzgünüz, bu koltuk kısa süre önce başkası tarafından satın alındı.");
    }
    $stmt_seat_check->closeCursor();

    $original_price = $trip['price'];
    $final_price = $original_price;
    $coupon_id_to_save = null;

    if (!empty($coupon_code_from_form)) {
        $coupon = checkCouponValidity($coupon_code_from_form, $trip['company_id'], $user_id);

        if ($coupon['success']) {
            $discount_rate = $coupon['discount_rate'];
            $final_price = $original_price * (1 - $discount_rate);

            $coupon_id_to_save = $coupon['coupon_id']; // ID yi kaydet
        } else {
            // kupon gecersizse bakiye kontrolüne geçmeden hata ver
            throw new Exception($coupon['message']);
        }
    }


    $user_balance = getUserBalance($user_id);
    if ($user_balance < $final_price) {
        throw new Exception("Yetersiz bakiye. Lütfen sanal kredinizi doldurunuz.");
    }


    $stmt_ticket = $db->prepare("
        INSERT INTO tickets (user_id, trip_id, total_price, final_price, coupon_id, coupon_code, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt_ticket->execute([
        $user_id,
        $trip_id,
        $original_price,
        $final_price,
        $coupon_id_to_save,
        $coupon_code_from_form,
    ]);
    $ticket_id = $db->lastInsertId();

    $stmt_book_seat = $db->prepare("INSERT INTO booked_seats (ticket_id, seat_number) VALUES (?, ?)");
    $stmt_book_seat->execute([$ticket_id, $selected_seat]);

    $new_balance = $user_balance - $final_price;
    $stmt_update_balance = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt_update_balance->execute([$new_balance, $user_id]);


    if ($coupon_id_to_save) {
        $stmt_update_coupon = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt_update_coupon->execute([$coupon_id_to_save]);
    }

    $db->commit();

    header('Location: user/my_account.php?success=1');
    exit;
} catch (Exception $e) {
    $db->rollBack();
    header('Location: book.php?trip_id=' . $trip_id . '&error=1&message=' . urlencode($e->getMessage()));
    exit;
}
