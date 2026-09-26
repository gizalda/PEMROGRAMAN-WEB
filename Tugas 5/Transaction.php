<?php
declare(strict_types=1);
final class Transaction
{
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    private readonly string $timestamp;
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly float $amount
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Jumlah transaksi harus lebih besar dari nol.'
            );
        }
        if (!in_array(
            $type,
            [self::TYPE_DEPOSIT, self::TYPE_WITHDRAWAL],
            true
        )) {
            throw new InvalidArgumentException(
                'Jenis transaksi tidak valid.'
            );
        }
        $this->timestamp = date('Y-m-d H:i:s');
    }
    public function getId(): string
    {
        return $this->id;
    }
    public function getType(): string
    {
        return $this->type;
    }
    public function getAmount(): float
    {
        return $this->amount;
    }
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
    public function process(float &$balance): bool
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => $this->processDeposit($balance),
            self::TYPE_WITHDRAWAL => $this->processWithdrawal($balance),
        };
    }
    private function processDeposit(float &$balance): bool
    {
        $balance += $this->amount;

        return true;
    }
    private function processWithdrawal(float &$balance): bool
    {
        if ($this->amount > $balance) {
            return false;
        }

        $balance -= $this->amount;

        return true;
    }
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'timestamp' => $this->timestamp,
        ];
    }
}
