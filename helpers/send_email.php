<?php
/**
 * Helper: Kirim notifikasi email saat ada penyewaan baru
 * Menggunakan PHPMailer + Gmail SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Baca .env jika belum di-load
function _loadEnvMail() {
    static $loaded = false;
    if ($loaded) return;
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
    $loaded = true;
}

/**
 * Kirim email notifikasi penyewaan baru ke admin.
 *
 * @param array $data  ['kode_sewa', 'nama_penyewa', 'nama_kamera', 'tanggal_sewa',
 *                      'tanggal_kembali', 'lama_sewa', 'total_bayar', 'catatan']
 * @return array ['ok' => bool, 'msg' => string]
 */
function sendNotifikasiSewa(array $data): array {
    _loadEnvMail();

    $mailFrom = $_ENV['MAIL_FROM'] ?? '';
    $mailPass = $_ENV['MAIL_PASS'] ?? '';
    $mailTo   = $_ENV['MAIL_TO']   ?? '';
    $mailName = $_ENV['MAIL_NAME'] ?? 'SnapGear';

    if (empty($mailFrom) || empty($mailPass) || empty($mailTo)) {
        return ['ok' => false, 'msg' => 'Konfigurasi email tidak lengkap di .env'];
    }

    $mail = new PHPMailer(true);

    try {
        // ---- SMTP Settings ----
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailFrom;
        $mail->Password   = $mailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // ---- Pengirim & Penerima ----
        $mail->setFrom($mailFrom, $mailName);
        $mail->addAddress($mailTo);
        $mail->addReplyTo($mailFrom, $mailName);

        // ---- Konten Email ----
        $mail->isHTML(true);
        $mail->Subject = '📷 Penyewaan Baru: ' . ($data['kode_sewa'] ?? '-') . ' — SnapGear';
        $mail->Body    = _buildEmailBody($data);
        $mail->AltBody = _buildEmailBodyPlain($data);

        $mail->send();
        return ['ok' => true, 'msg' => 'Email notifikasi terkirim.'];

    } catch (Exception $e) {
        return ['ok' => false, 'msg' => 'Gagal kirim email: ' . $mail->ErrorInfo];
    }
}

/** Buat isi email dalam format HTML */
function _buildEmailBody(array $d): string {
    $kode       = htmlspecialchars($d['kode_sewa']      ?? '-');
    $penyewa    = htmlspecialchars($d['nama_penyewa']   ?? '-');
    $kamera     = htmlspecialchars($d['nama_kamera']    ?? '-');
    $tglSewa    = htmlspecialchars($d['tanggal_sewa']   ?? '-');
    $tglKembali = htmlspecialchars($d['tanggal_kembali'] ?? '-');
    $lama       = (int) ($d['lama_sewa']   ?? 0);
    $total      = number_format((float)($d['total_bayar'] ?? 0), 0, ',', '.');
    $catatan    = htmlspecialchars($d['catatan'] ?? '-');
    $waktu      = date('d M Y, H:i') . ' WIB';

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f0f4ff;font-family:'Segoe UI',Arial,sans-serif;">

  <div style="max-width:600px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(54,92,245,0.12);">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#365CF5,#00c6ff);padding:36px 40px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">📷 SnapGear</h1>
      <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Sistem Rental Kamera</p>
    </div>

    <!-- Alert -->
    <div style="background:#eff6ff;border-left:4px solid #365CF5;margin:28px 40px 0;padding:16px 20px;border-radius:0 8px 8px 0;">
      <p style="margin:0;color:#1e3a8a;font-size:14px;font-weight:600;">
        🔔 Ada penyewaan baru yang masuk ke sistem!
      </p>
    </div>

    <!-- Detail Transaksi -->
    <div style="padding:28px 40px;">
      <h2 style="margin:0 0 20px;color:#0f172a;font-size:18px;font-weight:700;">Detail Penyewaan</h2>

      <table style="width:100%;border-collapse:collapse;">
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:12px 0;color:#64748b;font-size:13px;width:40%;">Kode Sewa</td>
          <td style="padding:12px 0;color:#0f172a;font-size:13px;font-weight:700;">{$kode}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:12px 0;color:#64748b;font-size:13px;">Nama Penyewa</td>
          <td style="padding:12px 0;color:#0f172a;font-size:13px;font-weight:600;">{$penyewa}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:12px 0;color:#64748b;font-size:13px;">Kamera</td>
          <td style="padding:12px 0;color:#0f172a;font-size:13px;">{$kamera}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:12px 0;color:#64748b;font-size:13px;">Tanggal Sewa</td>
          <td style="padding:12px 0;color:#0f172a;font-size:13px;">{$tglSewa}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:12px 0;color:#64748b;font-size:13px;">Tanggal Kembali</td>
          <td style="padding:12px 0;color:#0f172a;font-size:13px;">{$tglKembali}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:12px 0;color:#64748b;font-size:13px;">Lama Sewa</td>
          <td style="padding:12px 0;color:#0f172a;font-size:13px;">{$lama} hari</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:12px 0;color:#64748b;font-size:13px;">Catatan</td>
          <td style="padding:12px 0;color:#0f172a;font-size:13px;">{$catatan}</td>
        </tr>
      </table>

      <!-- Total -->
      <div style="margin-top:20px;background:linear-gradient(135deg,rgba(54,92,245,0.08),rgba(0,198,255,0.05));border:1px solid rgba(54,92,245,0.2);border-radius:12px;padding:20px 24px;display:flex;justify-content:space-between;align-items:center;">
        <span style="color:#1e3a8a;font-size:15px;font-weight:600;">Total Pembayaran</span>
        <span style="color:#365CF5;font-size:22px;font-weight:900;">Rp {$total}</span>
      </div>
    </div>

    <!-- Footer -->
    <div style="background:#f8faff;padding:24px 40px;text-align:center;border-top:1px solid #e8eeff;">
      <p style="margin:0;color:#94a3b8;font-size:12px;">
        Email ini dikirim otomatis oleh sistem <strong>SnapGear</strong> pada {$waktu}.<br>
        Segera proses penyewaan melalui dashboard admin.
      </p>
    </div>

  </div>

</body>
</html>
HTML;
}

/** Versi plain text untuk fallback */
function _buildEmailBodyPlain(array $d): string {
    $total = number_format((float)($d['total_bayar'] ?? 0), 0, ',', '.');
    return implode("\n", [
        '=== NOTIFIKASI PENYEWAAN BARU — SnapGear ===',
        '',
        'Kode Sewa    : ' . ($d['kode_sewa']       ?? '-'),
        'Nama Penyewa : ' . ($d['nama_penyewa']    ?? '-'),
        'Kamera       : ' . ($d['nama_kamera']     ?? '-'),
        'Tgl Sewa     : ' . ($d['tanggal_sewa']    ?? '-'),
        'Tgl Kembali  : ' . ($d['tanggal_kembali'] ?? '-'),
        'Lama Sewa    : ' . ($d['lama_sewa']        ?? 0) . ' hari',
        'Catatan      : ' . ($d['catatan']          ?? '-'),
        'Total Bayar  : Rp ' . $total,
        '',
        'Dikirim pada : ' . date('d M Y, H:i') . ' WIB',
        'Silakan cek dashboard admin untuk proses lebih lanjut.',
    ]);
}

// ============================================================
// FUNGSI KHUSUS: Notifikasi dari CUSTOMER (konfirmasi bayar)
// ============================================================

/**
 * Kirim notifikasi ke admin saat CUSTOMER mengkonfirmasi pembayaran sewa.
 *
 * @param array $data  ['kode_bayar', 'nama_penyewa', 'nama_kamera', 'tanggal_sewa',
 *                      'tanggal_kembali', 'lama_sewa', 'total_bayar', 'metode_bayar', 'catatan']
 * @return array ['ok' => bool, 'msg' => string]
 */
function sendNotifikasiSewaCust(array $data): array {
    _loadEnvMail();

    $mailFrom = $_ENV['MAIL_FROM'] ?? '';
    $mailPass = $_ENV['MAIL_PASS'] ?? '';
    $mailTo   = $_ENV['MAIL_TO']   ?? '';
    $mailName = $_ENV['MAIL_NAME'] ?? 'SnapGear';

    if (empty($mailFrom) || empty($mailPass) || empty($mailTo)) {
        return ['ok' => false, 'msg' => 'Konfigurasi email tidak lengkap di .env'];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailFrom;
        $mail->Password   = $mailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($mailFrom, $mailName);
        $mail->addAddress($mailTo);
        $mail->addReplyTo($mailFrom, $mailName);

        $mail->isHTML(true);
        $mail->Subject = '🔔 Pesanan Masuk: ' . ($data['kode_bayar'] ?? '-') . ' — Customer Konfirmasi Pembayaran';
        $mail->Body    = _buildEmailBodyCust($data);
        $mail->AltBody = _buildEmailBodyCustPlain($data);

        $mail->send();
        return ['ok' => true, 'msg' => 'Email notifikasi terkirim.'];

    } catch (Exception $e) {
        return ['ok' => false, 'msg' => 'Gagal kirim email: ' . $mail->ErrorInfo];
    }
}

/** Email HTML untuk notifikasi customer konfirmasi bayar */
function _buildEmailBodyCust(array $d): string {
    $kode       = htmlspecialchars($d['kode_bayar']      ?? '-');
    $penyewa    = htmlspecialchars($d['nama_penyewa']    ?? '-');
    $kamera     = htmlspecialchars($d['nama_kamera']     ?? '-');
    $tglSewa    = htmlspecialchars($d['tanggal_sewa']    ?? '-');
    $tglKembali = htmlspecialchars($d['tanggal_kembali'] ?? '-');
    $lama       = (int) ($d['lama_sewa']   ?? 0);
    $total      = number_format((float)($d['total_bayar'] ?? 0), 0, ',', '.');
    $catatan    = htmlspecialchars($d['catatan'] ?? '-');
    $waktu      = date('d M Y, H:i') . ' WIB';

    // Metode bayar badge
    $metode     = $d['metode_bayar'] ?? 'tunai';
    if ($metode === 'transfer') {
        $metodeBadge = '<span style="background:#dbeafe;color:#1d4ed8;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;">💳 Transfer Bank</span>';
        $metodeInfo  = '<p style="margin:8px 0 0;color:#1e3a8a;font-size:13px;">⚠️ Customer sudah upload bukti transfer. Segera verifikasi di panel admin.</p>';
    } else {
        $metodeBadge = '<span style="background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;">💵 Tunai di Toko</span>';
        $metodeInfo  = '<p style="margin:8px 0 0;color:#065f46;font-size:13px;">Customer akan membayar tunai saat mengambil kamera di toko.</p>';
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f0f4ff;font-family:'Segoe UI',Arial,sans-serif;">

  <div style="max-width:600px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(54,92,245,0.12);">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#365CF5,#00c6ff);padding:36px 40px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">📷 SnapGear</h1>
      <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Notifikasi Pesanan Customer</p>
    </div>

    <!-- Alert Utama -->
    <div style="background:#fffbeb;border-left:4px solid #f59e0b;margin:28px 40px 0;padding:16px 20px;border-radius:0 8px 8px 0;">
      <p style="margin:0;color:#92400e;font-size:15px;font-weight:700;">
        🛒 Customer baru mengkonfirmasi pesanan sewa!
      </p>
      <p style="margin:6px 0 0;color:#78350f;font-size:13px;">Segera proses dan verifikasi pembayaran di dashboard admin.</p>
    </div>

    <!-- Detail Pesanan -->
    <div style="padding:28px 40px;">
      <h2 style="margin:0 0 20px;color:#0f172a;font-size:18px;font-weight:700;">Detail Pesanan</h2>

      <table style="width:100%;border-collapse:collapse;">
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:11px 0;color:#64748b;font-size:13px;width:42%;">Kode Pembayaran</td>
          <td style="padding:11px 0;color:#0f172a;font-size:13px;font-weight:700;font-family:monospace;">{$kode}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:11px 0;color:#64748b;font-size:13px;">Nama Penyewa</td>
          <td style="padding:11px 0;color:#0f172a;font-size:13px;font-weight:600;">{$penyewa}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:11px 0;color:#64748b;font-size:13px;">Kamera</td>
          <td style="padding:11px 0;color:#0f172a;font-size:13px;">{$kamera}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:11px 0;color:#64748b;font-size:13px;">Tanggal Sewa</td>
          <td style="padding:11px 0;color:#0f172a;font-size:13px;">{$tglSewa}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:11px 0;color:#64748b;font-size:13px;">Tanggal Kembali</td>
          <td style="padding:11px 0;color:#0f172a;font-size:13px;">{$tglKembali}</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:11px 0;color:#64748b;font-size:13px;">Durasi Sewa</td>
          <td style="padding:11px 0;color:#0f172a;font-size:13px;">{$lama} hari</td>
        </tr>
        <tr style="border-bottom:1px solid #f1f5f9;">
          <td style="padding:11px 0;color:#64748b;font-size:13px;">Catatan</td>
          <td style="padding:11px 0;color:#0f172a;font-size:13px;">{$catatan}</td>
        </tr>
      </table>

      <!-- Total Bayar -->
      <div style="margin-top:20px;background:linear-gradient(135deg,rgba(54,92,245,0.08),rgba(0,198,255,0.05));border:1px solid rgba(54,92,245,0.2);border-radius:12px;padding:18px 24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <span style="color:#1e3a8a;font-size:15px;font-weight:600;">Total Pembayaran</span>
          <span style="color:#365CF5;font-size:22px;font-weight:900;">Rp {$total}</span>
        </div>
      </div>

      <!-- Metode Bayar -->
      <div style="margin-top:16px;background:#f8faff;border:1px solid #e0e7ff;border-radius:12px;padding:16px 20px;">
        <p style="margin:0 0 6px;color:#64748b;font-size:12px;text-transform:uppercase;font-weight:600;letter-spacing:0.5px;">Metode Pembayaran</p>
        {$metodeBadge}
        {$metodeInfo}
      </div>
    </div>

    <!-- Footer -->
    <div style="background:#f8faff;padding:24px 40px;text-align:center;border-top:1px solid #e8eeff;">
      <p style="margin:0;color:#94a3b8;font-size:12px;">
        Notifikasi otomatis dari <strong>SnapGear</strong> · {$waktu}<br>
        Login ke <strong>panel admin</strong> untuk memproses pesanan ini.
      </p>
    </div>

  </div>

</body>
</html>
HTML;
}

/** Plain text fallback untuk notifikasi customer */
function _buildEmailBodyCustPlain(array $d): string {
    $total  = number_format((float)($d['total_bayar'] ?? 0), 0, ',', '.');
    $metode = strtoupper($d['metode_bayar'] ?? 'tunai');
    return implode("\n", [
        '=== PESANAN BARU DARI CUSTOMER — SnapGear ===',
        '',
        'Kode Bayar   : ' . ($d['kode_bayar']     ?? '-'),
        'Nama Penyewa : ' . ($d['nama_penyewa']   ?? '-'),
        'Kamera       : ' . ($d['nama_kamera']    ?? '-'),
        'Tgl Sewa     : ' . ($d['tanggal_sewa']   ?? '-'),
        'Tgl Kembali  : ' . ($d['tanggal_kembali'] ?? '-'),
        'Lama Sewa    : ' . ($d['lama_sewa']       ?? 0) . ' hari',
        'Metode Bayar : ' . $metode,
        'Catatan      : ' . ($d['catatan']         ?? '-'),
        'Total Bayar  : Rp ' . $total,
        '',
        'Waktu        : ' . date('d M Y, H:i') . ' WIB',
        'Segera login ke dashboard admin untuk memproses pesanan.',
    ]);
}

/**
 * Kirim email reset password ke user.
 *
 * @param string $email
 * @param string $nama
 * @param string $resetLink
 * @return array ['ok' => bool, 'msg' => string]
 */
function sendResetPasswordLink(string $email, string $nama, string $resetLink): array {
    _loadEnvMail();

    $mailFrom = $_ENV['MAIL_FROM'] ?? '';
    $mailPass = $_ENV['MAIL_PASS'] ?? '';
    $mailName = $_ENV['MAIL_NAME'] ?? 'SnapGear';

    if (empty($mailFrom) || empty($mailPass)) {
        return ['ok' => false, 'msg' => 'Konfigurasi email tidak lengkap di .env'];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailFrom;
        $mail->Password   = $mailPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($mailFrom, $mailName);
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = '🔑 Permintaan Reset Password — SnapGear';
        $mail->Body    = _buildResetEmailBody($nama, $resetLink);
        $mail->AltBody = _buildResetEmailBodyPlain($nama, $resetLink);

        $mail->send();
        return ['ok' => true, 'msg' => 'Link reset password berhasil dikirim ke email.'];

    } catch (Exception $e) {
        return ['ok' => false, 'msg' => 'Gagal kirim email reset password: ' . $mail->ErrorInfo];
    }
}

function _buildResetEmailBody(string $nama, string $resetLink): string {
    $namaClean = htmlspecialchars($nama);
    $linkEscaped = htmlspecialchars($resetLink);
    $waktu = date('d M Y, H:i') . ' WIB';

    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f0f4ff;font-family:'Segoe UI',Arial,sans-serif;">

  <div style="max-width:600px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(54,92,245,0.12);">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#365CF5,#00c6ff);padding:36px 40px;text-align:center;">
      <h1 style="margin:0;color:#fff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">📷 SnapGear</h1>
      <p style="margin:8px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Pemulihan Akun Anda</p>
    </div>

    <!-- Konten Utama -->
    <div style="padding:40px;">
      <h2 style="margin:0 0 16px;color:#0f172a;font-size:20px;font-weight:700;">Halo, {$namaClean}!</h2>
      <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.6;">
        Kami menerima permintaan untuk mereset kata sandi akun Anda di <strong>SnapGear</strong>. Silakan klik tombol di bawah ini untuk mereset kata sandi Anda:
      </p>

      <!-- Tombol CTA -->
      <div style="text-align:center;margin:32px 0;">
        <a href="{$linkEscaped}" target="_blank" style="display:inline-block;background:#365CF5;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:50px;font-weight:700;font-size:15px;box-shadow:0 6px 20px rgba(54,92,245,0.3);transition:all 0.3s;">
          Reset Kata Sandi
        </a>
      </div>

      <div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:0 8px 8px 0;margin-bottom:24px;">
        <p style="margin:0;color:#92400e;font-size:13px;line-height:1.5;">
          ⚠️ Tautan reset ini hanya berlaku selama <strong>24 jam</strong>. Jika Anda tidak merasa meminta reset password ini, abaikan email ini dan kata sandi Anda tidak akan berubah.
        </p>
      </div>

      <hr style="border:0;border-top:1px solid #e2e8f0;margin:28px 0;">
      <p style="margin:0;color:#64748b;font-size:12px;line-height:1.5;">
        Jika tombol di atas tidak berfungsi, salin dan tempel tautan berikut ke browser Anda:<br>
        <a href="{$linkEscaped}" target="_blank" style="color:#365CF5;word-break:break-all;">{$linkEscaped}</a>
      </p>
    </div>

    <!-- Footer -->
    <div style="background:#f8faff;padding:24px 40px;text-align:center;border-top:1px solid #e8eeff;">
      <p style="margin:0;color:#94a3b8;font-size:12px;">
        Email ini dikirim otomatis oleh <strong>SnapGear</strong> · {$waktu}
      </p>
    </div>

  </div>

</body>
</html>
HTML;
}

function _buildResetEmailBodyPlain(string $nama, string $resetLink): string {
    return implode("\n", [
        '=== RESET PASSWORD AKUN — SnapGear ===',
        '',
        'Halo, ' . $nama . ',',
        '',
        'Kami menerima permintaan untuk mereset kata sandi akun Anda di SnapGear.',
        'Silakan salin dan tempel tautan di bawah ini ke browser Anda untuk mereset kata sandi Anda:',
        '',
        $resetLink,
        '',
        'Catatan:',
        '- Tautan reset ini hanya berlaku selama 24 jam.',
        '- Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini.',
        '',
        'Dikirim otomatis oleh SnapGear pada ' . date('d M Y, H:i') . ' WIB',
    ]);
}
