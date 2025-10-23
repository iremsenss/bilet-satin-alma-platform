<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// JSON response function
function sendJsonResponse($success, $message, $data = [])
{
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    sendJsonResponse(false, 'Oturum açmanız gerekiyor.');
}

// Validate POST data
$coupon_code = $_POST['coupon_code'] ?? '';
$trip_id = $_POST['trip_id'] ?? '';

if (empty($coupon_code) || empty($trip_id)) {
    sendJsonResponse(false, 'Geçersiz istek.');
}

try {
    // Get trip details
    $stmt = $db->prepare("SELECT company_id, price FROM trips WHERE id = ?");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        sendJsonResponse(false, 'Sefer bulunamadı.');
    }

    // Check coupon validity
    $couponResult = checkCouponValidity($coupon_code, $trip['company_id'], getCurrentUserId());

    if (!$couponResult['success']) {
        sendJsonResponse(false, $couponResult['message']);
    }

    // Calculate discounted price
    $final_price = $trip['price'] * (1 - $couponResult['discount_rate']);

    sendJsonResponse(true, $couponResult['message'], [
        'final_price' => round($final_price, 2),
        'coupon_id' => $couponResult['coupon_id'],
        'discount_rate' => $couponResult['discount_rate']
    ]);
} catch (Exception $e) {
    error_log("Kupon uygulama hatası: " . $e->getMessage());
    sendJsonResponse(false, 'Sistem hatası oluştu. Lütfen daha sonra tekrar deneyiniz.');
}
