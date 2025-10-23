<?php
if (ob_get_level()) ob_end_clean();

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('Europe/Istanbul');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$basePath = dirname(dirname(__DIR__));
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', realpath($basePath . '/fpdf/font/') . '/');
}

require_once $basePath . '/fpdf/fpdf.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = getCurrentUserId();
$ticket_id = filter_input(INPUT_GET, 'ticket_id', FILTER_VALIDATE_INT);

if (!$user_id || !$ticket_id) {
    error_log("Geçersiz bilet erişimi: user_id veya ticket_id eksik");
    header("Location: ../index.php");
    exit;
}

$ticket = getTicketDetailsById($ticket_id, $user_id);

if (!$ticket) {
    error_log("Yetkisiz bilet erişimi denemesi: Bilet #{$ticket_id}, Kullanıcı #{$user_id}");
    header("Location: ../index.php");
    exit;
}

$departureFormatted = $ticket['departure_time']
    ? date('d.m.Y H:i', strtotime($ticket['departure_time']))
    : '-';
$arrivalFormatted = $ticket['arrival_time']
    ? date('d.m.Y H:i', strtotime($ticket['arrival_time']))
    : '-';

// Fiyat hesaplamaları
$basePrice = (float)($ticket['base_price'] ?? $ticket['final_price']);
$finalPrice = (float)$ticket['final_price'];
$discountAmount = $basePrice - $finalPrice;
$hasDiscount = $discountAmount > 0;

function convertToFPDF($text)
{
    return iconv('UTF-8', 'ISO-8859-9//TRANSLIT//IGNORE', $text);
}

try {

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12, '', false, 'cp1254');

    $pdf->SetFontSize(16);
    $pdf->SetTextColor(40, 167, 69);
    $pdf->Cell(0, 10, convertToFPDF('TicketBox Otobüs Bileti'), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);

    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetFont('Arial', 'B', 12, '', false, 'cp1254');
    $pdf->Cell(0, 8, convertToFPDF('SEFER VE YOLCU BILGILERI'), 1, 1, 'C', true);
    $pdf->SetFont('Arial', '', 12);

    $pdf->Cell(45, 8, convertToFPDF('Firma:'), 1, 0, 'L', true);
    $pdf->Cell(55, 8, convertToFPDF($ticket['company_name']), 1, 0, 'L');
    $pdf->Cell(45, 8, convertToFPDF('Yolcu Adi:'), 1, 0, 'L', true);
    $pdf->Cell(45, 8, convertToFPDF($ticket['full_name']), 1, 1, 'L');

    $pdf->Cell(45, 8, convertToFPDF('Kalkis:'), 1, 0, 'L', true);
    $pdf->Cell(55, 8, convertToFPDF($ticket['departure_city_name']), 1, 0, 'L');
    $pdf->Cell(45, 8, convertToFPDF('Varis:'), 1, 0, 'L', true);
    $pdf->Cell(45, 8, convertToFPDF($ticket['destination_city_name']), 1, 1, 'L');

    $pdf->Cell(45, 8, convertToFPDF('Kalkis T/S:'), 1, 0, 'L', true);
    $pdf->Cell(55, 8, convertToFPDF($departureFormatted), 1, 0, 'L');
    $pdf->Cell(45, 8, convertToFPDF('Varis T/S:'), 1, 0, 'L', true);
    $pdf->Cell(45, 8, convertToFPDF($arrivalFormatted), 1, 1, 'L');

    $pdf->Cell(45, 8, convertToFPDF('Koltuk No:'), 1, 0, 'L', true);
    $pdf->SetFont('Arial', 'B', 14, '', false, 'cp1254');
    $pdf->SetTextColor(220, 53, 69);
    $pdf->Cell(55, 8, convertToFPDF('#' . $ticket['seat_number']), 1, 0, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->Cell(45, 8, convertToFPDF('Kupon Kodu:'), 1, 0, 'L', true);
    $couponDisplay = !empty($ticket['coupon_code']) ? $ticket['coupon_code'] : '—';
    $pdf->SetFont('Arial', 'B', 12, '', false, 'cp1254');
    $pdf->Cell(45, 8, convertToFPDF($couponDisplay), 1, 1, 'L');
    $pdf->SetFont('Arial', '', 12);

    if ($hasDiscount) {
        $pdf->Cell(45, 8, convertToFPDF('Ham Fiyat:'), 1, 0, 'L', true);
        $pdf->Cell(55, 8, convertToFPDF(number_format($basePrice, 2, ',', '.') . ' ₺'), 1, 0, 'L');
        $pdf->Cell(45, 8, convertToFPDF('Indirim:'), 1, 0, 'L', true);
        $pdf->SetTextColor(25, 135, 84);
        $pdf->Cell(45, 8, convertToFPDF('- ' . number_format($discountAmount, 2, ',', '.') . ' ₺'), 1, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(90, 10, convertToFPDF('TOPLAM ÖDENEN:'), 1, 0, 'L', true);
    $pdf->SetTextColor(220, 53, 69);
    $pdf->Cell(100, 10, convertToFPDF(number_format($finalPrice, 2, ',', '.') . ' ₺'), 1, 1, 'C', true);

    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 5, convertToFPDF('Biletinizi doğrulamak için QR kodu kullanın:'), 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->Cell(0, 30, convertToFPDF('(QR KOD YERİ)'), 1, 1, 'C');

    if (ob_get_length()) ob_clean();

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Bilet-' . $ticket['id'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    $pdf->Output('I', 'Bilet-' . $ticket['id'] . '.pdf');
    exit;
} catch (Exception $e) {
    error_log("PDF Oluşturma Hatası: " . $e->getMessage());
    header("Location: ../index.php");
    exit;
}
