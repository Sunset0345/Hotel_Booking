<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class MessagesModel extends Model {
    protected $table = 'messages';
    protected $primary_key = 'message_id';

    public function __construct()
    {
        parent::__construct();
    }
}
