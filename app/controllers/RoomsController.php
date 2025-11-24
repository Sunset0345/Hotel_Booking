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

    // Show single room details
    public function view($id = null)
    {
        if (!$id) { echo 'Room id required'; return; }
        $room = $this->RoomsModel->find($id);
        if (!$room) { echo 'Room not found'; return; }
        // pass flash messages to view if any
        $flashes = function_exists('flash_get') ? flash_get() : [];
        $this->call->view('rooms/view', ['room' => $room, 'flashes' => $flashes]);
    }
}
