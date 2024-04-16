<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects

use BeycanPress\CryptoPay\Models\AbstractTransaction;

// @phpcs:ignore
class MeprMemberPressCryptoPayModel extends AbstractTransaction
{
    public string $addon = 'memberpress';

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct('memberpress_transaction');
    }
}
