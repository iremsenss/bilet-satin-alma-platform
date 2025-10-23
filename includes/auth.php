<?php

require_once 'config.php';

/** 
 * @param string $fullName;
 * @param string $email;
 * @param string $password;
 * @return bool
 */

function registerUser($fullName, $email, $password)
{
    $pdo = getdbConnection();
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $role = 'user';

    $sql = "INSERT INTO users (full_name, email, role, password) VALUES (:fullName, :email, :role, :password)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':fullName', $fullName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':password', $hashedPassword);

        return $stmt->execute();
    } catch (PDOException $e) {
        return false;
    }
}


/**
 * @param string $email
 * @param string $password
 * @return bool
 */

function loginUser($email, $password)
{
    $pdo = getdbConnection();

    $sql = "SELECT id, password, role, full_name, company_id FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];


        if ($user['role'] === 'company') {
            $_SESSION['company_id'] = $user['company_id'] ?? null;
        }


        return true;
    }
    return false;
}

/**
 * @return bool
 */

function logoutUser()
{
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    return session_destroy();
}

/**
 * @return bool
 */

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}


/**
 * @param array $allowedRoles
 * @return void 
 * 
 */

function requireRole($allowedRoles)
{
    if (!isLoggedIn() || !in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: /login.php?error=unauthorized');
        exit;
    }
}


function getUserRole()
{
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';
}
