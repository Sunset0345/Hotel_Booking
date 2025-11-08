<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AdminAuditModel extends Model {
    protected $table = 'admin_audit';
    protected $primary_key = 'audit_id';

    public function __construct()
    {
        parent::__construct();
    }
}
