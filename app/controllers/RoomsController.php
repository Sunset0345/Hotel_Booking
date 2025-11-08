<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class RoomsController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->model('RoomsModel');
    }

    public function index()
    {
        $data['rooms'] = $this->RoomsModel->All();
        $this->call->view('rooms/index', $data);
    }
}
