<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Enums;

enum ConsentPermission: string
{
    case READ_ACCOUNTS_BASIC = 'ReadAccountsBasic';
    case READ_ACCOUNTS_DETAIL = 'ReadAccountsDetail';
    case READ_BALANCES = 'ReadBalances';
    case READ_TRANSACTIONS_BASIC = 'ReadTransactionsBasic';
    case READ_TRANSACTIONS_DETAIL = 'ReadTransactionsDetail';
    case READ_TRANSACTIONS_CREDITS = 'ReadTransactionsCredits';
    case READ_TRANSACTIONS_DEBITS = 'ReadTransactionsDebits';
    case PAYMENT_INITIATION = 'PaymentInitiation';

    public function label(): string
    {
        return match ($this) {
            self::READ_ACCOUNTS_BASIC       => 'Read Accounts (Basic)',
            self::READ_ACCOUNTS_DETAIL      => 'Read Accounts (Detail)',
            self::READ_BALANCES             => 'Read Balances',
            self::READ_TRANSACTIONS_BASIC   => 'Read Transactions (Basic)',
            self::READ_TRANSACTIONS_DETAIL  => 'Read Transactions (Detail)',
            self::READ_TRANSACTIONS_CREDITS => 'Read Transaction Credits',
            self::READ_TRANSACTIONS_DEBITS  => 'Read Transaction Debits',
            self::PAYMENT_INITIATION        => 'Payment Initiation',
        };
    }
}
