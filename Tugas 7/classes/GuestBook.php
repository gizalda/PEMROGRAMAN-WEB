<?php
declare(strict_types=1);

/**
 * Kelas GuestBook
 * Mengelola penyimpanan dan pengambilan pesan buku tamu melalui PDO.
 */
class GuestBook
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Menyimpan pesan baru dengan prepared statement (INSERT).
     */
    public function simpan(string $nama, string $email, string $pesan): bool
    {
        $sql = 'INSERT INTO buku_tamu (nama, email, pesan)
                VALUES (:nama, :email, :pesan)';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nama'  => $nama,
            ':email' => $email,
            ':pesan' => $pesan,
        ]);
    }

    /**
     * Mengambil semua pesan, terbaru di atas, dengan prepared statement (SELECT).
     */
    public function ambilSemua(int $limit = 50): array
    {
        $sql = 'SELECT id, nama, email, pesan, tanggal_kirim
                FROM buku_tamu
                ORDER BY tanggal_kirim DESC, id DESC
                LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Validasi masukan. Mengembalikan array pesan galat (kosong = valid).
     */
    public function validasi(string $nama, string $email, string $pesan): array
    {
        $errors = [];

        if ($nama === '') {
            $errors[] = 'Nama tidak boleh kosong.';
        } elseif (mb_strlen($nama) > 100) {
            $errors[] = 'Nama maksimal 100 karakter.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        } elseif (mb_strlen($email) > 100) {
            $errors[] = 'Email maksimal 100 karakter.';
        }

        if (mb_strlen($pesan) < 5) {
            $errors[] = 'Pesan minimal 5 karakter.';
        }

        return $errors;
    }
}