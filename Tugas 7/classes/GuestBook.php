<?php
declare(strict_types=1);
class GuestBook
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
