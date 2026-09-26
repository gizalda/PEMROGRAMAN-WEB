<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/Transaction.php';
// INISIALISASI SESSION
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 1000000.0;
}
if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [];
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$message = '';
$messageType = '';

// PROSES FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        $message = 'Token CSRF tidak valid.';
        $messageType = 'error';
    } else {
        // Ambil data form
        $type = $_POST['type'] ?? '';
        $amountInput = $_POST['amount'] ?? '';
        // Validasi jenis transaksi menggunakan match
        $transactionType = match ($type) {
            'deposit' => Transaction::TYPE_DEPOSIT,
            'withdrawal' => Transaction::TYPE_WITHDRAWAL,
            default => null,
        };
        if ($transactionType === null) {
            $message = 'Jenis transaksi tidak valid.';
            $messageType = 'error';

        } elseif (
            !is_string($amountInput) ||
            !preg_match('/^\d+(\.\d+)?$/', $amountInput)
        ) {
            $message = 'Jumlah transaksi harus berupa angka desimal positif.';
            $messageType = 'error';
        } else {
            $amount = (float) $amountInput;
            if ($amount <= 0) {
                $message = 'Jumlah transaksi harus lebih besar dari nol.';
                $messageType = 'error';
            } else {

                try {
                    $transaction = new Transaction(
                        'TRX-' . strtoupper(bin2hex(random_bytes(4))),
                        $transactionType,
                        $amount
                    );

                    $success = $transaction->process($_SESSION['balance']);

                    if ($success) {

                        $_SESSION['transactions'][] =
                            $transaction->toArray();

                        $message = 'Transaksi berhasil diproses.';
                        $messageType = 'success';

                    } else {

                        $message =
                            'Penarikan ditolak karena saldo tidak mencukupi.';
                        $messageType = 'error';
                    }

                } catch (Throwable $e) {

                    $message = 'Terjadi kesalahan saat memproses transaksi.';
                    $messageType = 'error';
                }
            }
        }
    }
}

// DATA UNTUK TAMPILAN
$balance = (float) $_SESSION['balance'];
$transactions = $_SESSION['transactions'];

// FUNGSI ESCAPE HTML
function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sistem Manajemen Keuangan</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }

        .container {
            width: 90%;
            max-width: 900px;
            margin: 40px auto;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .balance {
            text-align: center;
        }

        .balance h2 {
            margin-bottom: 10px;
        }

        .balance-value {
            font-size: 32px;
            font-weight: bold;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 12px;
            border-radius: 7px;
            font-size: 15px;
        }

        input,
        select {
            border: 1px solid #ccc;
        }

        button {
            margin-top: 20px;
            border: none;
            background: #222;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #444;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 7px;
        }

        .success {
            background: #dff5e1;
            color: #176b2c;
        }

        .error {
            background: #fde2e2;
            color: #a61b1b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 20px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Sistem Manajemen Keuangan</h1>

    <!-- SALDO -->

    <div class="card balance">

        <h2>Sisa Saldo</h2>

        <div class="balance-value">
            Rp <?= e(number_format($balance, 0, ',', '.')) ?>
        </div>

    </div>

    <!-- FORM TRANSAKSI -->

    <div class="card">

        <h2>Tambah Transaksi</h2>

        <?php if ($message !== ''): ?>

            <div class="message <?= e($messageType) ?>">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <label for="type">
                Jenis Transaksi
            </label>

            <select name="type" id="type" required>

                <option value="">
                    -- Pilih Transaksi --
                </option>

                <option value="deposit">
                    Deposit
                </option>

                <option value="withdrawal">
                    Penarikan
                </option>

            </select>

            <label for="amount">
                Jumlah
            </label>

            <input
                type="number"
                name="amount"
                id="amount"
                min="0.01"
                step="0.01"
                placeholder="Masukkan jumlah"
                required
            >

            <button type="submit">
                Proses Transaksi
            </button>

        </form>

    </div>

    <!-- RIWAYAT TRANSAKSI -->

    <div class="card">

        <h2>Riwayat Transaksi</h2>

        <?php if (count($transactions) === 0): ?>

            <div class="empty">
                Belum ada transaksi.
            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Waktu</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($transactions as $transaction): ?>

                    <tr>

                        <td>
                            <?= e((string) $transaction['id']) ?>
                        </td>

                        <td>
                            <?= e((string) $transaction['type']) ?>
                        </td>

                        <td>
                            Rp <?= e(
                                number_format(
                                    (float) $transaction['amount'],
                                    2,
                                    ',',
                                    '.'
                                )
                            ) ?>
                        </td>

                        <td>
                            <?= e((string) $transaction['timestamp']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
