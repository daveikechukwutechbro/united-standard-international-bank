# Supervisor Configuration

This directory contains the supervisor configuration for the FinAegis Core Banking application.

## Queue Workers

The application uses multiple queue workers to process different types of jobs:

### 1. Events Worker (`finaegis-events-worker`)
- **Queue**: `events`
- **Purpose**: Processes general event sourcing events
- **Workers**: 2
- **Log**: `storage/logs/events-worker.log`

### 2. Ledger Worker (`finaegis-ledger-worker`)
- **Queue**: `ledger`
- **Purpose**: Processes ledger-related events and transactions
- **Workers**: 2
- **Log**: `storage/logs/ledger-worker.log`

### 3. Transactions Worker (`finaegis-transactions-worker`)
- **Queue**: `transactions`
- **Purpose**: Processes transaction events (money added, subtracted, transferred)
- **Workers**: 2
- **Log**: `storage/logs/transactions-worker.log`

### 4. Liquidity Pools Worker (`finaegis-liquidity-pools-worker`)
- **Queue**: `liquidity_pools`
- **Purpose**: Processes liquidity pool events (swaps, liquidity changes, rebalancing)
- **Workers**: 3 (higher concurrency due to frequent operations)
- **Log**: `storage/logs/liquidity-pools-worker.log`

### 5. Default Worker (`finaegis-default-worker`)
- **Queue**: default
- **Purpose**: Processes all other jobs not assigned to specific queues
- **Workers**: 2
- **Log**: `storage/logs/default-worker.log`

## Installation

1. Copy the configuration file to supervisor's config directory:
   ```bash
   sudo cp etc/supervisor.conf /etc/supervisor/conf.d/finaegis.conf
   ```

2. Reload supervisor configuration:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   ```

3. Start the workers:
   ```bash
   sudo supervisorctl start finaegis-workers:*
   ```

## Management Commands

- **Check status**: `sudo supervisorctl status finaegis-workers:*`
- **Stop all workers**: `sudo supervisorctl stop finaegis-workers:*`
- **Start all workers**: `sudo supervisorctl start finaegis-workers:*`
- **Restart all workers**: `sudo supervisorctl restart finaegis-workers:*`
- **View logs**: `tail -f storage/logs/{worker-name}.log`

## Queue Configuration

Queue names are defined in `app/Values/EventQueues.php`. Each event class can specify its queue by setting the `$queue` property.