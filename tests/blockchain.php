<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Models\Blockchain;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$chain = new Blockchain(1, 100);
check($chain->isChainValid(), 'Genesis must be valid');
$chain->minePendingTransactions('alice');
check($chain->getBalanceOfAddress('alice') === 100, 'Reward must be credited');
$id = $chain->addTransaction(['from' => 'alice', 'to' => 'bob', 'amount' => 40]);
try {
    $chain->addTransaction(['from' => 'alice', 'to' => 'bob', 'amount' => 61]);
    throw new RuntimeException('Overspending was accepted');
} catch (InvalidArgumentException) {
}
$chain->minePendingTransactions('miner');
check($chain->getBalanceOfAddress('alice') === 60 && $chain->getBalanceOfAddress('bob') === 40, 'Transfer balances');
check($chain->getCirculatingSupply() === 200, 'Supply');
check($chain->getTransactionById($id)['amount'] === 40, 'Transaction lookup');
check(count($chain->getTransactionHistory('bob')) === 1, 'Transaction history');
$state = $chain->toArray();
check(Blockchain::fromArray($state)->isChainValid(), 'Round trip');
$state['chain'][1]['transactions'][0]['amount'] = 999;
try {
    Blockchain::fromArray($state);
    throw new RuntimeException('Tampering was accepted');
} catch (InvalidArgumentException) {
}
echo "Blockchain checks passed\n";