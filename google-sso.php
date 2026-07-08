<?php
session_start();
require_once 'koneksi.php';

// =========================================================================
// KONFIGURASI GOOGLE OAUTH 2.0
// Silakan ganti nilai di bawah ini dengan kredensial dari Google Cloud Console:
// https://console.cloud.google.com/
// Kredensial -> Buat Kredensial -> ID Klien OAuth (Pilih tipe Aplikasi Web)
// Set Authorized Redirect URIs ke URL file ini, contoh: 
// http://localhost/uts-web-ralipp/google-sso.php
// =========================================================================
define('GOOGLE_CLIENT_ID', '272286035575-uqaqkfhirvauvk39parv8mvbe1lhktq8.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'http://localhost/uts-web-ralipp/google-sso.php');

// Otomatis tambahkan kolom google_id di tabel users jika belum ada
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) DEFAULT NULL UNIQUE AFTER id");
}

// 1. TAHAP INISIASI: Redirect user ke halaman login Google
if (!isset($_GET['code'])) {
    // Buat token state acak untuk mencegah CSRF
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    // Parameter Query Google OAuth
    $params = [
        'response_type' => 'code',
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'scope'         => 'openid email profile',
        'state'         => $state,
        'prompt'        => 'select_account'
    ];

    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    header('Location: ' . $authUrl);
    exit;
}

// 2. TAHAP CALLBACK: Menangani respon setelah user berhasil login di Google
if (isset($_GET['code'])) {
    // Validasi token state untuk keamanan CSRF
    if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
        die('State OAuth tidak valid (Kemungkinan ancaman serangan CSRF).');
    }
    unset($_SESSION['oauth_state']);

    $code = $_GET['code'];

    // Lakukan POST request untuk menukarkan code dengan Access Token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postFields = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Abaikan verifikasi SSL lokal
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        die('Curl Error: ' . curl_error($ch));
    }
    curl_close($ch);

    $tokenData = json_decode($response, true);
    if (!isset($tokenData['access_token'])) {
        die('Gagal mendapatkan Access Token dari Google: ' . ($tokenData['error_description'] ?? json_encode($tokenData)));
    }

    $accessToken = $tokenData['access_token'];

    // Lakukan GET request ke Google UserInfo Endpoint untuk mengambil profil
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $userInfoResponse = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($userInfoResponse, true);
    if (!isset($userInfo['sub'])) {
        die('Gagal mendapatkan informasi pengguna dari Google.');
    }

    $googleId    = $userInfo['sub'];
    $email       = $userInfo['email'] ?? '';
    $namaLengkap = $userInfo['name'] ?? '';
    $avatarUrl   = $userInfo['picture'] ?? '';

    // Cari user di database berdasarkan google_id atau email
    $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
    $stmt->bind_param("ss", $googleId, $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Jika user terdaftar tetapi belum memiliki google_id, perbarui kolom google_id-nya
        if (empty($user['google_id'])) {
            $updateStmt = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $updateStmt->bind_param("si", $googleId, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        // Perbarui avatar menggunakan foto profil Google jika belum diunggah secara lokal
        if (empty($user['avatar']) && !empty($avatarUrl)) {
            $updateAvatar = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $updateAvatar->bind_param("si", $avatarUrl, $user['id']);
            $updateAvatar->execute();
            $updateAvatar->close();
            $user['avatar'] = $avatarUrl;
        }

        // Set session login untuk user yang ada
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role']         = !empty($user['role']) ? $user['role'] : 'user';
        $_SESSION['avatar']       = $user['avatar'] ?? '';

        header("Location: main.php");
        exit;
    } else {
        // Jika user belum terdaftar, buat akun otomatis berbasis info Google
        // Potong username dari email
        $emailParts = explode('@', $email);
        $usernameBase = preg_replace('/[^a-zA-Z0-9]/', '', $emailParts[0]);
        
        // Verifikasi keunikan username
        $username = $usernameBase;
        $cekUsername = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cekUsername->bind_param("s", $username);
        $cekUsername->execute();
        $cekUsername->store_result();
        if ($cekUsername->num_rows > 0) {
            $username = $usernameBase . rand(10, 99);
        }
        $cekUsername->close();

        // Password dummy acak karena user login via Google
        $dummyPassword = password_hash(bin2hex(random_bytes(10)), PASSWORD_BCRYPT);
        $role = 'user';

        $insertStmt = $conn->prepare("INSERT INTO users (google_id, username, password, nama_lengkap, email, avatar, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("sssssss", $googleId, $username, $dummyPassword, $namaLengkap, $email, $avatarUrl, $role);
        
        if ($insertStmt->execute()) {
            $newUserId = $insertStmt->insert_id;
            $insertStmt->close();

            // Set session login untuk user baru
            $_SESSION['user_id']      = $newUserId;
            $_SESSION['username']     = $username;
            $_SESSION['nama_lengkap'] = $namaLengkap;
            $_SESSION['role']         = $role;
            $_SESSION['avatar']       = $avatarUrl;

            header("Location: main.php");
            exit;
        } else {
            die("Gagal mendaftarkan pengguna baru via Google SSO: " . $conn->error);
        }
    }
}
