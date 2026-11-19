<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */

namespace App\Models;

use Core\Model;

/**
 * An in-memory, unsigned ledger. Amounts are positive integer units, not floats.
 * Transactions have id, from, to and amount fields; a null sender denotes a reward.
 */
class Blockchain extends Model
{
    private array $chain = [];
    private array $pendingTransactions = [];
    private int $difficulty;
    private int $miningReward;

    public function __construct(int $difficulty = 2, int $miningReward = 100)
    {
        if ($difficulty < 0 || $difficulty > 64 || $miningReward <= 0) {
            throw new \InvalidArgumentException('Invalid difficulty or mining reward.');
        }
        $this->difficulty = $difficulty;
        $this->miningReward = $miningReward;
        $this->chain = [$this->createGenesisBlockChain()];
    }

    public function createGenesisBlockChain(): Block
    {
        return new Block(0, 0, [], '0');
    }

    /** Mine pending transactions and one reward into a new block. */
    public function createBlock(string $minerAddress): Block
    {
        return $this->minePendingTransactions($minerAddress);
    }

    /** @return Block[] Defensive copies in chronological order. */
    public function getChain(): array
    {
        return array_map(static fn (Block $block): Block => clone $block, $this->chain);
    }

    public function getLatestBlock(): Block
    {
        return clone $this->chain[count($this->chain) - 1];
    }

    /** Queue a transfer, generating its unique ID when omitted. Returns the ID. */
    public function addTransaction(array $transaction): string
    {
        if (!isset($transaction['id'])) {
            $transaction['id'] = bin2hex(random_bytes(16));
        }
        if (!$this->validTransaction($transaction, false)) {
            throw new \InvalidArgumentException('Invalid transaction.');
        }
        if ($this->getTransactionById($transaction['id']) !== null) {
            throw new \InvalidArgumentException('Duplicate transaction ID.');
        }
        $available = $this->getBalanceOfAddress($transaction['from']);
        foreach ($this->pendingTransactions as $pending) {
            if ($pending['from'] === $transaction['from']) {
                $available -= $pending['amount'];
            }
        }
        if ($transaction['amount'] > $available) {
            throw new \InvalidArgumentException('Insufficient confirmed balance.');
        }
        $this->pendingTransactions[] = $transaction;
        return $transaction['id'];
    }

    public function minePendingTransactions(string $minerAddress): Block
    {
        if ($minerAddress === '') {
            throw new \InvalidArgumentException('Miner address must not be empty.');
        }
        if (!$this->isChainValid()) {
            throw new \RuntimeException('Cannot mine an invalid chain.');
        }
        if ($this->getCirculatingSupply() > PHP_INT_MAX - $this->miningReward) {
            throw new \RuntimeException('Mining reward would overflow the supply.');
        }
        do {
            $id = bin2hex(random_bytes(16));
        } while ($this->getTransactionById($id) !== null);
        $transactions = $this->pendingTransactions;
        $transactions[] = ['id' => $id, 'from' => null, 'to' => $minerAddress, 'amount' => $this->miningReward];
        $latest = $this->chain[count($this->chain) - 1];
        $block = new Block($latest->index + 1, time(), $transactions, $latest->hash);
        $block->mineBlock($this->difficulty);
        $this->chain[] = $block;
        $this->pendingTransactions = [];
        return clone $block;
    }

    public function getBalanceOfAddress(string $address): int
    {
        $balance = 0;
        foreach ($this->chain as $block) {
            foreach ($block->transactions as $transaction) {
                if ($transaction['from'] === $address) {
                    $balance -= $transaction['amount'];
                }
                if ($transaction['to'] === $address) {
                    $balance += $transaction['amount'];
                }
            }
        }
        return $balance;
    }

    /** Confirmed transactions only, in chain order. */
    public function getTransactionHistory(string $address): array
    {
        $history = [];
        foreach ($this->chain as $block) {
            foreach ($block->transactions as $transaction) {
                if ($transaction['from'] === $address || $transaction['to'] === $address) {
                    $history[] = $transaction;
                }
            }
        }
        return $history;
    }

    public function isChainValid(): bool
    {
        try {
            if ($this->chain === [] || $this->chain[0]->toArray() !== $this->createGenesisBlockChain()->toArray()) {
                return false;
            }
            $balances = [];
            $ids = [];
            $supply = 0;
            foreach ($this->chain as $index => $block) {
                if ($block->index !== $index || $block->hash !== $block->calculateHash()) {
                    return false;
                }
                if ($index === 0) {
                    continue;
                }
                if ($block->previousHash !== $this->chain[$index - 1]->hash ||
                    strncmp($block->hash, str_repeat('0', $this->difficulty), $this->difficulty) !== 0 ||
                    $block->timestamp < 0 || $block->nonce < 0 || $block->transactions === []) {
                    return false;
                }
                $rewards = 0;
                $available = $balances;
                foreach ($block->transactions as $transaction) {
                    if (!is_array($transaction) || !$this->validTransaction($transaction, true) || isset($ids[$transaction['id']])) {
                        return false;
                    }
                    $ids[$transaction['id']] = true;
                    $from = $transaction['from'];
                    $to = $transaction['to'];
                    $amount = $transaction['amount'];
                    if ($from === null) {
                        if (++$rewards !== 1 || $amount !== $this->miningReward || $supply > PHP_INT_MAX - $amount) {
                            return false;
                        }
                        $supply += $amount;
                    } else {
                        if (($available[$from] ?? 0) < $amount) {
                            return false;
                        }
                        $available[$from] -= $amount;
                        $balances[$from] -= $amount;
                    }
                    if (($balances[$to] ?? 0) > PHP_INT_MAX - $amount) {
                        return false;
                    }
                    $balances[$to] = ($balances[$to] ?? 0) + $amount;
                }
                if ($rewards !== 1 || end($block->transactions)['from'] !== null) {
                    return false;
                }
            }
            foreach ($this->pendingTransactions as $transaction) {
                if (!is_array($transaction) || !$this->validTransaction($transaction, false) || isset($ids[$transaction['id']])) {
                    return false;
                }
                $ids[$transaction['id']] = true;
                $from = $transaction['from'];
                if (($balances[$from] ?? 0) < $transaction['amount']) {
                    return false;
                }
                $balances[$from] -= $transaction['amount'];
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getCirculatingSupply(): int
    {
        $supply = 0;
        foreach ($this->chain as $block) {
            foreach ($block->transactions as $transaction) {
                if ($transaction['from'] === null) {
                    $supply += $transaction['amount'];
                }
            }
        }
        return $supply;
    }

    public function getBlockIndex(): int
    {
        return $this->chain[count($this->chain) - 1]->index;
    }

    public function getBlockHash(): string
    {
        return $this->chain[count($this->chain) - 1]->hash;
    }

    /** Find either a confirmed or pending transaction. */
    public function getTransactionById(string $id): ?array
    {
        foreach ($this->chain as $block) {
            foreach ($block->transactions as $transaction) {
                if ($transaction['id'] === $id) {
                    return $transaction;
                }
            }
        }
        foreach ($this->pendingTransactions as $transaction) {
            if ($transaction['id'] === $id) {
                return $transaction;
            }
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'difficulty' => $this->difficulty,
            'miningReward' => $this->miningReward,
            'chain' => array_map(static fn (Block $block): array => $block->toArray(), $this->chain),
            'pendingTransactions' => $this->pendingTransactions,
        ];
    }

    public static function fromArray(array $data): self
    {
        try {
            if (!isset($data['difficulty'], $data['miningReward'], $data['chain'], $data['pendingTransactions']) ||
                !is_int($data['difficulty']) || !is_int($data['miningReward']) ||
                !is_array($data['chain']) || !array_is_list($data['chain']) ||
                !is_array($data['pendingTransactions']) || !array_is_list($data['pendingTransactions'])) {
                throw new \InvalidArgumentException('Invalid blockchain data.');
            }
            $blockchain = new self($data['difficulty'], $data['miningReward']);
            $blockchain->chain = [];
            foreach ($data['chain'] as $block) {
                if (!is_array($block) || !isset($block['index'], $block['timestamp'], $block['transactions'], $block['previousHash'], $block['nonce'], $block['hash']) ||
                    !is_int($block['index']) || !is_int($block['timestamp']) || !is_array($block['transactions']) ||
                    !array_is_list($block['transactions']) || !is_string($block['previousHash']) ||
                    !is_int($block['nonce']) || !is_string($block['hash'])) {
                    throw new \InvalidArgumentException('Invalid block data.');
                }
                $blockchain->chain[] = Block::fromArray($block);
            }
            $blockchain->pendingTransactions = $data['pendingTransactions'];
            if (!$blockchain->isChainValid()) {
                throw new \InvalidArgumentException('Invalid blockchain.');
            }
            return $blockchain;
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Invalid blockchain data.', 0, $exception);
        }
    }

    private function validTransaction(array $transaction, bool $allowReward): bool
    {
        return count($transaction) === 4 &&
            isset($transaction['id'], $transaction['to'], $transaction['amount']) &&
            array_key_exists('from', $transaction) &&
            is_string($transaction['id']) && $transaction['id'] !== '' &&
            is_string($transaction['to']) && $transaction['to'] !== '' &&
            is_int($transaction['amount']) && $transaction['amount'] > 0 &&
            (($allowReward && $transaction['from'] === null) ||
                (is_string($transaction['from']) && $transaction['from'] !== ''));
    }
}
