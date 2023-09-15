<?php

use BeycanPress\CryptoPayLite\Models\AbstractTransaction;

class MemberPressCrpyoPayLiteModel extends AbstractTransaction 
{
    public $addon = 'memberpress';
    
    public function __construct()
    {
        parent::__construct('memberpress_transaction');
    }
}
