<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class PaymentsModel extends Model {
    protected $table = 'payments';
    protected $primary_key = 'payment_id';

    public function __construct()
    {
        parent::__construct();
    }
}
