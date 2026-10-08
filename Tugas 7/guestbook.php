<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/classes/GuestBook.php';

const DB_HOST = 'localhost';
const DB_NAME = 'perpustakaan';
const DB_USER = 'root';
const DB_PASS = '';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal.');
}
$guestBook = new GuestBook($pdo);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$errors = [];
$old    = ['nama' => '', 'email' => '', 'pesan' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], (string) $token)) {
        http_response_code(403);
        $errors[] = 'Token CSRF tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $old['nama']  = trim((string) ($_POST['nama'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $old['pesan'] = trim((string) ($_POST['pesan'] ?? ''));

        $errors = $guestBook->validasi($old['nama'], $old['email'], $old['pesan']);

        if (empty($errors)) {
            try {
                $guestBook->simpan($old['nama'], $old['email'], $old['pesan']);

                // Token diperbarui, lalu redirect (Post/Redirect/Get)
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['flash']      = 'Pesan berhasil dikirim. Terima kasih!';
                header('Location: guestbook.php');
                exit;
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                $errors[] = 'Terjadi kesalahan saat menyimpan pesan.';
            }
        }
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$daftarPesan = $guestBook->ambilSemua();