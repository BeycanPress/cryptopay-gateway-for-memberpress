<?php

use BeycanPress\CryptoPay\Models\AbstractTransaction;

class MemberPressCrpyoPayModel extends AbstractTransaction 
{
    public $addon = 'memberpress';
    
    public function __construct()
    {
        parent::__construct('memberpress_transaction');
    }
}
