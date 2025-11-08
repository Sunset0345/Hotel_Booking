<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Model: UsersModel
 * 
 * Automatically generated via CLI.
 */
class UsersModel extends Model {
    protected $table = 'users';
    protected $primary_key = 'user_id';

    public function __construct()
    {
        parent::__construct();
    }

    public function findByEmail($email)
    {
        // use Model::filter which sets table and applies soft-delete, then call get() to fetch single record
        return $this->filter(['email' => $email])->limit(1)->get();
    }

}