<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AdminModel extends Model {
    protected $table = 'admin';
    protected $primary_key = 'admin_id';

    public function __construct()
    {
        parent::__construct();
    }

    public function findByUsername($username)
    {
        return $this->filter(['username' => $username])->limit(1)->get();
    }
}
