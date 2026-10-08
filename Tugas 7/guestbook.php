<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/classes/GuestBook.php';

// ---------- Konfigurasi koneksi (sesuaikan dengan MySQL kamu) ----------
const DB_HOST = '127.0.0.1';
const DB_NAME = 'perpustakaan';
const DB_USER = 'root';
const DB_PASS = '';

// Helper sanitasi keluaran (cegah XSS)
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------- Koneksi PDO ----------
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

// ---------- Token CSRF ----------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$old    = ['nama' => '', 'email' => '', 'pesan' => ''];

// ---------- Proses form ----------
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

// Pesan sukses (sekali tampil)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$daftarPesan = $guestBook->ambilSemua();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Tamu Perpustakaan</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; color: #222; }
        h1, h2 { margin-bottom: .5rem; }
        label { display: block; margin-top: .8rem; font-weight: bold; }
        input, textarea { width: 100%; padding: .5rem; box-sizing: border-box; }
        button { margin-top: 1rem; padding: .6rem 1.2rem; cursor: pointer; }
        .alert { padding: .7rem 1rem; margin: 1rem 0; border-radius: 4px; }
        .alert-error { background: #fde8e8; color: #9b1c1c; }
        .alert-success { background: #e6f6ea; color: #1e6b33; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: .5rem; text-align: left; vertical-align: top; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Buku Tamu Perpustakaan</h1>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= e($flash) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="guestbook.php">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" maxlength="100" value="<?= e($old['nama']) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" maxlength="100" value="<?= e($old['email']) ?>" required>

        <label for="pesan">Pesan</label>
        <textarea id="pesan" name="pesan" rows="4" minlength="5" required><?= e($old['pesan']) ?></textarea>

        <button type="submit">Kirim Pesan</button>
    </form>

    <h2>Daftar Pesan</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Pesan</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($daftarPesan)): ?>
                <tr><td colspan="5">Belum ada pesan.</td></tr>
            <?php else: ?>
                <?php foreach ($daftarPesan as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= nl2br(e($row['pesan'])) ?></td>
                        <td><?= e($row['tanggal_kirim']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>