<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace App\Models;

class Block
{
    public int $index;
    public int $timestamp;
    public array $transactions;
    public string $previousHash;
    public int $nonce;
    public string $hash;

    public function __construct(
        int $index,
        int $timestamp,
        array $transactions,
        string $previousHash,
        int $nonce = 0,
        ?string $hash = null
    ) {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->transactions = $transactions;
        $this->previousHash = $previousHash;
        $this->nonce = $nonce;
        $this->hash = $hash ?? $this->calculateHash();
    }

    public function calculateMerkleRoot(): string
    {
        $hashes = [];
        foreach ($this->transactions as $transaction) {
            $hashes[] = hash('sha256', json_encode($transaction, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        }

        if ($hashes === []) {
            return hash('sha256', '');
        }

        while (count($hashes) > 1) {
            if (count($hashes) % 2 !== 0) {
                $hashes[] = end($hashes);
            }

            $nextLevel = [];
            for ($i = 0; $i < count($hashes); $i += 2) {
                $nextLevel[] = hash('sha256', $hashes[$i] . $hashes[$i + 1]);
            }
            $hashes = $nextLevel;
        }

        return $hashes[0];
    }

    public function calculateHash(): string
    {
        return hash('sha256', json_encode([
            $this->index,
            $this->timestamp,
            $this->calculateMerkleRoot(),
            $this->previousHash,
            $this->nonce,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    public function mineBlock(int $difficulty): void
    {
        if ($difficulty < 0 || $difficulty > 64) {
            throw new \InvalidArgumentException('Difficulty must be between 0 and 64.');
        }

        $target = str_repeat('0', $difficulty);
        $this->hash = $this->calculateHash();
        while (strncmp($this->hash, $target, $difficulty) !== 0) {
            $this->nonce++;
            $this->hash = $this->calculateHash();
        }
    }

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'timestamp' => $this->timestamp,
            'transactions' => $this->transactions,
            'previousHash' => $this->previousHash,
            'nonce' => $this->nonce,
            'hash' => $this->hash,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['index'],
            $data['timestamp'],
            $data['transactions'],
            $data['previousHash'],
            $data['nonce'],
            $data['hash']
        );
    }
}