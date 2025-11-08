<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class RoomsModel extends Model {
    protected $table = 'rooms';
    protected $primary_key = 'room_id';

    public function __construct()
    {
        parent::__construct();
    }
}
