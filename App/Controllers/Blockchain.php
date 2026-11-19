<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/19/2026
 */

namespace App\Controllers;

use Core\Controller;
use App\Models\Blockchain as BlockchainModel;

class Blockchain extends Controller
{
    private BlockchainModel $blockchain;
    /**
     * Needed functions:
     * index
     * block
     * validateChain
     * tamper
     * reset
     */
    /**
     * Constructor
     * @param array $route_params
     */
    public function __construct(array $route_params){
        parent::__construct($route_params);
        $this->blockchain = new BlockchainModel();
    }

    public function index(): void
    {
        $this->json($this->state());
    }

    public function block(): void
    {
        $index = filter_input(INPUT_GET, 'index', FILTER_VALIDATE_INT);
        $chain = $this->state()['chain'];
        if ($index === false || $index === null || !isset($chain[$index])) {
            $this->json(['error' => 'Block not found.'], 404);
            return;
        }
        $this->json($chain[$index]);
    }

    public function validateChain(): void
    {
        try {
            $valid = BlockchainModel::fromArray($this->state())->isChainValid();
        } catch (\InvalidArgumentException) {
            $valid = false;
        }
        $this->json(['valid' => $valid]);
    }

    public function transfer(): void
    {
        $this->mutate(function (BlockchainModel $chain, array $input): array {
            return ['id' => $chain->addTransaction($input)];
        });
    }

    public function mine(): void
    {
        $this->mutate(function (BlockchainModel $chain, array $input): array {
            if (!isset($input['miner']) || !is_string($input['miner'])) {
                throw new \InvalidArgumentException('A miner address is required.');
            }
            return $chain->minePendingTransactions($input['miner'])->toArray();
        });
    }

    public function tamper(): void
    {
        if (!$this->requirePost()) {
            return;
        }
        try {
            $input = $this->input();
            $state = $this->state();
            $index = $input['index'] ?? null;
            if (!is_int($index) || !isset($state['chain'][$index]) || !isset($input['transactions']) || !is_array($input['transactions']) || !array_is_list($input['transactions'])) {
                throw new \InvalidArgumentException('Provide an existing block index and a transactions array.');
            }
            $state['chain'][$index]['transactions'] = $input['transactions'];
            $_SESSION['blockchain'] = $state;
            $this->json($state['chain'][$index]);
        } catch (\InvalidArgumentException $exception) {
            $this->json(['error' => $exception->getMessage()], 400);
        }
    }

    public function reset(): void
    {
        if (!$this->requirePost()) {
            return;
        }
        $_SESSION['blockchain'] = (new BlockchainModel())->toArray();
        $this->json($_SESSION['blockchain']);
    }

    private function state(): array
    {
        return $_SESSION['blockchain'] ??= $this->blockchain->toArray();
    }

    private function requirePost(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(['error' => 'POST required.'], 405);
            return false;
        }
        return true;
    }

    private function input(): array
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Invalid JSON body.', 0, $exception);
        }
        if (!is_array($input) || array_is_list($input)) {
            throw new \InvalidArgumentException('Expected a JSON object.');
        }
        return $input;
    }

    private function mutate(callable $operation): void
    {
        if (!$this->requirePost()) {
            return;
        }
        try {
            $chain = BlockchainModel::fromArray($this->state());
            $result = $operation($chain, $this->input());
            $_SESSION['blockchain'] = $chain->toArray();
            $this->json($result);
        } catch (\InvalidArgumentException $exception) {
            $this->json(['error' => $exception->getMessage()], 400);
        } catch (\RuntimeException $exception) {
            $this->json(['error' => $exception->getMessage()], 409);
        }
    }
}