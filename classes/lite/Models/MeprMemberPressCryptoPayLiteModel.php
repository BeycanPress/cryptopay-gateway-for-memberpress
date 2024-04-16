<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
use BeycanPress\CryptoPayLite\Models\AbstractTransaction;

// @phpcs:ignore
class MeprMemberPressCryptoPayLiteModel extends AbstractTransaction
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
