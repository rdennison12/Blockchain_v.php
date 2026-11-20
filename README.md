# Blockchain in PHP

In this version of Blockchain, I am going to use pure PHP to create both the backend and the user interface. Once I have
a working prototype, I will introduce Twig templating engine through Composer. I will also be adding extensive testing,
documentation, and refactoring to improve code quality and maintainability.

## Setup

Install PHP 8.1 or newer and Composer, then run `composer install` from the project root.
Composer generates the autoloader used by the application and tests. Run the blockchain checks with `composer test`.
Copy `App/Config-temp.txt` to `App/Config.php` and configure it for your environment.

## Blockchain API

Each PHP session maintains its own chain. Send JSON objects to POST endpoints and retain the session cookie between requests.
Amounts are positive integer units; mine a reward before spending it. The default reward is 100 and mining difficulty is 2.

* `GET /blockchain/index` returns the chain, pending transactions and settings.
* `GET /blockchain/block?index=1` returns the block at that index.
* `GET /blockchain/validate-chain` returns `{"valid":true}` or `{"valid":false}`.
* `POST /blockchain/mine` with `{"miner":"alice"}` mines pending transfers and a reward.
* `POST /blockchain/transfer` with `{"from":"alice","to":"bob","amount":25}` queues a transfer. An optional `id` must be unique.
* `POST /blockchain/tamper` with `{"index":1,"transactions":[]}` replaces a block's transactions without updating its hash, demonstrating validation failure.
* `POST /blockchain/reset` replaces the session chain with a fresh genesis block.

This is an unsigned educational ledger, not a secure cryptocurrency: addresses are not authenticated, and session state is not a distributed consensus mechanism. Balances, history and supply count confirmed transactions only.

## Commits

* Initial commit: Project created
* Added a brief description of the project and an initial TODO list
* Added a remote repo 
* Removed Config.php from repo and added Config-temp.txt 
* Deleted Config.php
* Moving project to Linux for now, working to get Composer installed on Windows
* Installed Composer on Windows, working out the bugs

In the folder structure, I named the public folder public_html as that is the standard on Linux servers.
It makes it easier to deploy the project on a Linux server.

** My TODO list **

* Create a basic Block class
* Create a basic Blockchain class
* Implement basic blockchain functionality
* Add unit tests for blockchain class
* Create a basic user interface
* Add Twig templating engine
* Add more extensive testing and documentation
* Refactor code for better maintainability
