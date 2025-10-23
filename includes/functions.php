<?php

/**
 * Belirtilen e-posta adresine sahip kullanıcının var olup olmadığını kontrol eder.
 * @param string $email
 * @return bool Varsa true, yoksa false.
 */
function userExists($email)
{
    $db = getdbConnection();

    $sql = "SELECT COUNT(id) FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    return $stmt->fetchColumn() > 0;
}


function getCities()
{
    $db = getdbConnection();
    $cities = $db->query("SELECT * FROM cities ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    return $cities;
}

/**
 * Sehirin id'sine göre ismini döndürür.
 * @param int $id
 * @return string|null
 */
function getCityNameById($id)
{
    $db = getdbConnection();
    $stmt = $db->prepare("SELECT name FROM cities WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}


/**
 * Oturumdan giriş yapmış kullanıcının ID'sini (primary key) güvenli bir şekilde çeker.
 * Giriş yapılmamışsa 0 veya null döndürür.
 * @return int|null Kullanıcının ID'si veya null.
 */
function getCurrentUserId()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }

    return null; // Giriş yapılmamış veya ID geçersiz.
}

/* --- BİLET SATIN ALMA VE SEFER FONKSİYONLARI ---*/

/**
 * Belirli bir seferin detaylarını çeker.
 */
function getTripDetails($trip_id)
{
    $db = getdbConnection();
    try {
        $stmt = $db->prepare("
            SELECT 
                t.id, t.company_id, t.departure_time, t.arrival_time, t.price, t.capacity,
                c.name AS company_name, c.logo_path,
                c_dep.name AS departure_city_name,
                c_dest.name AS destination_city_name
            FROM trips t
            JOIN companies c ON t.company_id = c.id
            JOIN cities c_dep ON t.departure_city = c_dep.id
            JOIN cities c_dest ON t.destination_city = c_dest.id
            WHERE t.id = ?
        ");
        $stmt->execute([$trip_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getTripDetails Hata: " . $e->getMessage());
        return false;
    }
}

/**
 * Belirli bir sefere ait dolu koltukları çeker.
 */
function getBookedSeats($trip_id)
{
    $db = getdbConnection();
    try {
        $stmt = $db->prepare("
            SELECT 
                booked_seats.seat_number 
            FROM booked_seats 
            INNER JOIN tickets ON booked_seats.ticket_id = tickets.id 
            WHERE tickets.trip_id = ?
        ");
        $stmt->execute([$trip_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("getBookedSeats Hata: " . $e->getMessage());
        return [];
    }
}



/**

 * @param string $code Kupon kodu
 * @param int $company_id Firma ID
 * @param int $user_id Kullanıcı ID (KRİTİK)
 * @return array ['success' => bool, 'message' => string, 'discount_rate' => float, 'coupon_id' => int]
 */
function checkCouponValidity($code, $company_id, $user_id)
{
    $db = getdbConnection();

    $response = [
        'success' => false,
        'message' => 'Geçersiz kupon kodu.',
        'discount_rate' => 0,
        'coupon_id' => null
    ];

    try {
        $stmt = $db->prepare("
            SELECT 
                c.*, 
                comp.name as company_name
            FROM coupons c
            LEFT JOIN companies comp ON c.company_id = comp.id
            WHERE c.code = :code 
            AND c.status = 'active'
            LIMIT 1
        ");

        $stmt->execute([':code' => $code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            return $response;
        }

        // firma kontrolü
        if ($coupon['company_id'] !== null && $coupon['company_id'] != $company_id) {
            $response['message'] = 'Bu kupon sadece ' . $coupon['company_name'] . ' firmasında geçerlidir.';
            return $response;
        }

        $expiry = new DateTime($coupon['expiry_date']);
        $now = new DateTime();
        if ($now > $expiry) {
            $response['message'] = 'Kuponun süresi dolmuş.';
            return $response;
        }
        if ((int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
            $response['message'] = 'Kupon kullanım limiti dolmuş.';
            return $response;
        }

        // kullanıcının bu kuponu daha önce kullanıp kullanmadığı
        $stmt_user_used = $db->prepare("
            SELECT COUNT(*) 
            FROM tickets 
            WHERE user_id = :user_id AND coupon_id = :coupon_id AND status = 'active'
        ");
        $stmt_user_used->execute([
            ':user_id' => $user_id,
            ':coupon_id' => $coupon['id']
        ]);
        $has_user_used = $stmt_user_used->fetchColumn();

        if ($has_user_used > 0) {
            $response['message'] = 'Bu kuponu daha önce kullandınız veya aktif bir biletinizde kullanılıyor.';
            return $response;
        }

        return [
            'success' => true,
            'message' => sprintf(
                'İndirim kuponu başarıyla uygulandı! %%%d indirim kazandınız.',
                (float)$coupon['discount_rate'] * 100
            ),
            'discount_rate' => (float)$coupon['discount_rate'],
            'coupon_id' => (int)$coupon['id']
        ];
    } catch (Exception $e) {
        error_log("Kupon Kontrol Hatası: " . $e->getMessage());
        return $response;
    }
}

/**
 * Bilet satın alma işlemini gerçekleştirir.
 */
function purchaseTicket($trip_id, $user_id, $seat_number, $final_price, $coupon_id = null)
{
    $db = getdbConnection();
    $db->beginTransaction();

    try {
        // kullanıcının bakiyesini kontrol et
        $stmt_user = $db->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['balance'] < $final_price) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Yetersiz sanal kredi. Lütfen bakiyenizi kontrol edin.'];
        }

        // yeni bilet oluştur
        $stmt_ticket = $db->prepare("
            INSERT INTO tickets (user_id, trip_id, purchase_date, price_paid, coupon_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $purchase_date = date('Y-m-d H:i:s');
        $stmt_ticket->execute([$user_id, $trip_id, $purchase_date, $final_price, $coupon_id]);
        $ticket_id = $db->lastInsertId();

        // koltuğu rezerve et
        $stmt_seat = $db->prepare("
            INSERT INTO booked_seats (ticket_id, seat_number) 
            VALUES (?, ?)
        ");
        $stmt_seat->execute([$ticket_id, $seat_number]);

        // kullanıcının bakiyesini güncelle
        $new_balance = $user['balance'] - $final_price;
        $stmt_balance = $db->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt_balance->execute([$new_balance, $user_id]);

        // SESSION'daki bakiyeyi de güncelle 
        if (isset($_SESSION['user_balance'])) {
            $_SESSION['user_balance'] = $new_balance;
        }

        // kupon kullanım sayısını güncelle
        if ($coupon_id) {
            $stmt_coupon = $db->prepare("
                UPDATE coupons SET used_count = used_count + 1 WHERE id = ?
            ");
            $stmt_coupon->execute([$coupon_id]);
        }

        $db->commit();

        return ['success' => true, 'message' => 'Biletiniz başarıyla satın alındı! Kalan bakiyeniz: ' . number_format($new_balance, 2) . ' ₺'];
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Bilet Satın Alma Hata: " . $e->getMessage());
        return ['success' => false, 'message' => 'Bilet satın alma işlemi sırasında bir veritabanı hatası oluştu.'];
    }
}



/**
 * Kullanıcının biletlerini (aktif veya geçmiş) çeker.
 * @param int $user_id
 * @param bool $isActive Eğer true ise aktif biletleri, false ise geçmiş biletleri çeker.
 * @return array
 */
function getTicketsByUser($user_id, $isActive = true)
{
    $db = getdbConnection();
    try {
        $current_time = date('Y-m-d H:i:s');
        $condition = $isActive
            ? "t.departure_time >= ? AND tk.status = 'active'"
            : "t.departure_time < ? AND tk.status = 'active'";

        $orderBy = $isActive ? "t.departure_time ASC" : "t.departure_time DESC";

        $stmt = $db->prepare("
            SELECT
                tk.id AS ticket_id, tk.final_price,
                t.departure_time, t.arrival_time,
                
                -- ŞİRKET BİLGİLERİNİ EKLE
                c.name AS company_name, 
                c.logo_path,
                
                c_dep.name AS departure_city_name,
                c_dest.name AS destination_city_name,
                bs.seat_number
            FROM tickets tk
            JOIN trips t ON tk.trip_id = t.id
            JOIN companies c ON t.company_id = c.id
            JOIN cities c_dep ON t.departure_city = c_dep.id
            JOIN cities c_dest ON t.destination_city = c_dest.id
            JOIN booked_seats bs ON bs.ticket_id = tk.id
            WHERE tk.user_id = ? AND $condition
            ORDER BY $orderBy
        ");
        $stmt->execute([$user_id, $current_time]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getTicketsByUser Hata: " . $e->getMessage());
        return [];
    }
}

/**
 * Kullanıcının anlık sanal kredi bakiyesini çeker.
 */
function getUserBalance($user_id)
{
    $db = getdbConnection();
    try {
        $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $balance = $stmt->fetchColumn();
        return $balance !== false ? (float)$balance : 0.00;
    } catch (PDOException $e) {
        error_log("getUserBalance Hata: " . $e->getMessage());
        return 0.00;
    }
}

/**
 * Bilet iptal işlemini gerçekleştirir ve ücreti iade eder.
 * @param int $ticket_id İptal edilecek bilet ID'si.
 * @param int $user_id İşlemi yapan kullanıcı ID'si.
 * @return array İşlemin sonucunu ve mesajını döndürür.
 */
function cancelTicket($ticket_id, $user_id)
{
    $db = getdbConnection();
    $db->beginTransaction();

    try {
        // 1. Biletin kullanıcıya ait olup olmadığını ve durumunu kontrol et
        $stmt_ticket = $db->prepare("
            SELECT tk.final_price, t.departure_time 
            FROM tickets tk
            JOIN trips t ON tk.trip_id = t.id
            WHERE tk.id = ? AND tk.user_id = ? AND tk.status = 'active'
        ");
        $stmt_ticket->execute([$ticket_id, $user_id]);
        $ticket = $stmt_ticket->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Bu bileti iptal edemezsiniz. Ya bilet size ait değil, ya da sefer geçmiş.'];
        }

        // 2. Kalkışa son 1 saat kuralını kontrol et
        $departure_time = new DateTime($ticket['departure_time']);
        $current_time = new DateTime();
        $interval = $current_time->diff($departure_time);
        $minutes_left = ($interval->h * 60) + $interval->i;

        if ($interval->invert || $minutes_left < 60) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Bilet kalkışa son 1 saatten az kaldığı için iptal edilemez.'];
        }

        // 3. Biletin durumunu 'canceled' olarak güncelle
        $stmt_update_ticket = $db->prepare("UPDATE tickets SET status = 'canceled' WHERE id = ?");
        $stmt_update_ticket->execute([$ticket_id]);

        // 4. Koltuk kaydını sil (Bu, o koltuğun tekrar boş görünmesini sağlar)
        $stmt_delete_seat = $db->prepare("DELETE FROM booked_seats WHERE ticket_id = ?");
        $stmt_delete_seat->execute([$ticket_id]);

        // 5. Ücreti iade et
        $refund_amount = (float)$ticket['final_price'];
        $stmt_refund = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt_refund->execute([$refund_amount, $user_id]);

        $db->commit();
        return ['success' => true, 'message' => 'Bilet başarıyla iptal edildi! ' . number_format($refund_amount, 2) . ' ₺ hesabınıza iade edildi.'];
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Bilet İptali Hata: " . $e->getMessage());
        return ['success' => false, 'message' => 'Bilet iptali sırasında bir veritabanı hatası oluştu.'];
    }
}

/**
 * Kullanılan bir kuponun kullanım limitini bir azaltır.
 * @param string $coupon_code Kullanılan kupon kodu.
 */
function updateCouponUsage($coupon_code)
{
    $db = getdbConnection();
    try {
        $stmt = $db->prepare("UPDATE coupons SET usage_limit = usage_limit - 1 WHERE code = ?");
        $stmt->execute([$coupon_code]);
    } catch (PDOException $e) {
        error_log("updateCouponUsage Hata: " . $e->getMessage());
    }
}

/**
 * Bilet ID'sine göre bilet detaylarını çeker.
 * @param int $ticket_id
 * @return array|false Bilet detayları veya false.
 */
function getTicketDetailsById($ticket_id, $user_id)
{
    if (!$ticket_id || !$user_id) {
        error_log("Yetkisiz erişim girişimi: Boş ticket_id veya user_id");
        return false;
    }

    $db = getDbConnection();
    try {
        // Önce biletin kullanıcıya ait olup olmadığını kontrol et
        $check = $db->prepare("SELECT COUNT(*) FROM tickets WHERE id = ? AND user_id = ?");
        $check->execute([$ticket_id, $user_id]);

        if ($check->fetchColumn() == 0) {
            error_log("Yetkisiz bilet erişimi: Bilet #{$ticket_id}, Kullanıcı #{$user_id}");
            return false;
        }

        // Bilet kullanıcıya aitse detayları getir
        $stmt = $db->prepare("
            SELECT 
                tk.*,
                t.departure_time,
                t.arrival_time,
                t.price as trip_price,
                comp.name AS company_name,
                comp.logo_path,
                dep.name AS departure_city_name,
                dest.name AS destination_city_name,
                bs.seat_number,
                u.full_name,
                coup.code AS coupon_code,
                coup.discount_rate
            FROM tickets tk
            LEFT JOIN trips t ON t.id = tk.trip_id
            LEFT JOIN companies comp ON comp.id = t.company_id
            LEFT JOIN cities dep ON dep.id = t.departure_city
            LEFT JOIN cities dest ON dest.id = t.destination_city
            LEFT JOIN booked_seats bs ON bs.ticket_id = tk.id
            LEFT JOIN users u ON u.id = tk.user_id
            LEFT JOIN coupons coup ON coup.id = tk.coupon_id
            WHERE tk.id = ? AND tk.user_id = ?
            LIMIT 1
        ");

        $stmt->execute([$ticket_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;
        }

        // Varsayılan değerler
        $result['company_name'] = $result['company_name'] ?? 'Bilinmiyor';
        $result['departure_city_name'] = $result['departure_city_name'] ?? 'Bilinmiyor';
        $result['destination_city_name'] = $result['destination_city_name'] ?? 'Bilinmiyor';
        $result['seat_number'] = $result['seat_number'] ?? '';
        $result['full_name'] = $result['full_name'] ?? 'Bilinmiyor';
        $result['departure_time'] = $result['departure_time'] ?? null;
        $result['arrival_time'] = $result['arrival_time'] ?? null;

        // Fiyat hesaplamaları
        $result['base_price'] = (float)($result['trip_price'] ?? $result['final_price']);
        $result['final_price'] = (float)($result['final_price'] ?? $result['base_price']);
        $result['discount_amount'] = $result['base_price'] - $result['final_price'];

        return $result;
    } catch (PDOException $e) {
        error_log("Bilet detay hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Veritabanındaki tüm kuponları çeker.
 * Şirket adını da JOIN ile dahil eder.
 */
function getAllCoupons()
{
    $db = getDbConnection();
    try {
        $stmt = $db->query("
            SELECT 
                c.id, c.code, c.discount_rate, c.usage_limit, c.used_count, c.expiry_date,
                c.company_id, c.status,        -- BURAYA ekledik
                comp.name AS company_name
            FROM coupons c
            LEFT JOIN companies comp ON c.company_id = comp.id
            ORDER BY c.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getAllCoupons Hata: " . $e->getMessage());
        return [];
    }
}


/**
 * Tüm otobüs firmalarını çeker (Kupon formunda kullanmak için).
 */
function getAllCompanies()
{
    $db = getdbConnection();
    try {
        $stmt = $db->query("SELECT id, name FROM companies ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getAllCompanies Hata: " . $e->getMessage());
        return [];
    }
}

/**
 * Belirtilen uzunlukta rastgele, okunabilir bir kupon kodu oluşturur.
 * @param int $length Oluşturulacak kodun uzunluğu
 * @return string Rastgele Kupon Kodu
 */
function generateCouponCode($length = 10)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Kullanıcıya ait etkin kuponları çeker.
 * @param int $user_id
 * @return array
 * @throws PDOException
 * @throws Exception
 * 
 */
function getActiveCouponsForDisplay()
{
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                comp.name as company_name
            FROM coupons c
            LEFT JOIN companies comp ON c.company_id = comp.id
            WHERE c.expiry_date > CURRENT_TIMESTAMP
            AND c.used_count < c.usage_limit
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getActiveCouponsForDisplay Hata: " . $e->getMessage());
        return [];
    }
}

/**
 * Belirli bir firmaya ait kuponları çeker.
 */
function getCouponsByCompany($company_id)
{
    $db = getDbConnection();
    try {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                comp.name as company_name,
                (SELECT COUNT(*) FROM tickets WHERE coupon_id = c.id) as used_count
            FROM coupons c
            LEFT JOIN companies comp ON c.company_id = comp.id
            WHERE c.company_id = ?
            ORDER BY c.expiry_date DESC
        ");

        $stmt->execute([$company_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getCouponsByCompany Error: " . $e->getMessage());
        return [];
    }
}


/**
 * Kullanıcının hesabına kredi ekler.
 * * @param int $userId Kredi yüklenecek kullanıcının ID'si.
 * @param float $amount Yüklenecek kredi miktarı.
 * @return array Sonuç dizisi (success ve message içerir).
 */
function addCreditToUser(int $userId, float $amount): array
{
    // HATA BURADAYDI. Global $pdo yerine bağlantıyı alın.
    $db = getdbConnection();

    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Yüklenecek tutar pozitif bir sayı olmalıdır.'];
    }

    try {
        $stmt = $db->prepare(" 
            UPDATE users SET balance = balance + :amount WHERE id = :user_id
        ");

        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => number_format($amount, 2) . ' ₺ başarıyla hesabınıza yüklendi. Yeni bakiyeniz: ' . number_format(getUserBalance($userId), 2) . ' ₺.'];
        } else {
            return ['success' => false, 'message' => 'Kullanıcı bulunamadı veya bakiye yüklenemedi.'];
        }
    } catch (PDOException $e) {
        error_log("Kredi yükleme hatası: " . $e->getMessage());
        return ['success' => false, 'message' => 'Bir veritabanı hatası oluştu. Lütfen tekrar deneyin.'];
    }
}

/**
 * Kullanıcının adını, soyadını ve e-postasını çeker.
 * @param int $userId
 * @return array|false Kullanıcı bilgileri (full_name, email) veya false.
 */
function getUserProfileInfo(int $userId)
{
    $db = getdbConnection();
    try {
        $stmt = $db->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getUserProfileInfo Hata: " . $e->getMessage());
        return false;
    }
}
