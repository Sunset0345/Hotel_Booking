<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class BookingsController extends Controller {

    public function __construct()
    {
        parent::__construct();
        $this->call->model('BookingsModel');
    }

    // 🏨 User creates booking
    public function create($room_id)
    {
        // If GET: render booking form fragment
        if ($this->io->method() === 'get') {
            // load room data and render booking form
            $this->call->model('RoomsModel');
            $room = $this->RoomsModel->find($room_id);

            // If AJAX, return JSON with rendered HTML fragment; otherwise render normally
            if ($this->io->is_ajax()){
                // capture view output
                ob_start();
                $this->call->view('bookings/create', ['room' => $room]);
                $html = ob_get_clean();
                header('Content-Type: application/json');
                echo json_encode(['status' => 'ok', 'html' => $html]);
                return;
            }

            $this->call->view('bookings/create', ['room' => $room]);
            return;
        }

        // POST: process booking submission
        // Get logged in user id from framework session
        $user = $this->session->userdata('user');
        $user_id = is_array($user) && isset($user['user_id']) ? $user['user_id'] : null;
        if (!$user_id) {
            // For AJAX calls, return a specific login_required status so the front-end can redirect
            if ($this->io->is_ajax()){ header('Content-Type: application/json'); http_response_code(401); echo json_encode(['status'=>'login_required','message'=>'Not authenticated']); return; }
            redirect('auth/login');
            return;
        }

        $check_in  = $this->io->post('check_in');
        $check_out = $this->io->post('check_out');

        // Validate dates
        try{
            $inDate = new DateTime($check_in);
            $outDate = new DateTime($check_out);
        } catch(Exception $e){
            if ($this->io->is_ajax()){ header('Content-Type: application/json'); http_response_code(400); echo json_encode(['status'=>'error','message'=>'Invalid dates']); return; }
            flash_set('error', 'Invalid dates provided.'); redirect('rooms/view/' . $room_id); return;
        }

        $interval = $inDate->diff($outDate)->days;
        if ($interval <= 0) {
            if ($this->io->is_ajax()){ header('Content-Type: application/json'); http_response_code(400); echo json_encode(['status'=>'error','message'=>'Check-out date must be after check-in.']); return; }
            flash_set('error', 'Check-out date must be after check-in.');
            redirect('rooms/view/' . $room_id);
            return;
        }

        // Calculate total using room price
        $this->call->model('RoomsModel');
        $price_per_night = $this->BookingsModel->get_room_price($room_id);
        $total = $interval * (float)$price_per_night;

        // Insert booking
        $data = [
            'user_id'      => $user_id,
            'room_id'      => $room_id,
            'check_in'     => $check_in,
            'check_out'    => $check_out,
            'total_amount' => number_format((float)$total, 2, '.', ''),
            'status'       => 'pending'
        ];

        $inserted = false;
        try{
            $inserted = $this->BookingsModel->add_booking($data);
        } catch(Exception $e){
            // log for debugging
            error_log('[BookingsController] add_booking error: ' . $e->getMessage());
            $inserted = false;
        }

        if ($this->io->is_ajax()){
            header('Content-Type: application/json');
            if($inserted) {
                // add_booking returns last insert id on success
                echo json_encode(['status'=>'ok','message'=>'Booking created','booking_id'=>$inserted]);
            } else {
                http_response_code(500);
                echo json_encode(['status'=>'error','message'=>'Unable to create booking']);
            }
            return;
        }

        if ($inserted) {
            flash_set('success', 'Room booked successfully! Waiting for admin approval.');
        } else {
            flash_set('error', 'Failed to book room.');
        }

        redirect('rooms/view/' . $room_id);
    }

    // 🧾 Admin: show all bookings
    public function admin_list()
    {
        $bookings = $this->BookingsModel->get_all_with_details();
        $this->call->view('admin/bookings', ['bookings' => $bookings]);
    }

    // ⚙️ Admin: update booking status (AJAX)
    public function update($booking_id)
    {
        $status = $this->io->get('status');

        if (!in_array($status, ['approved', 'rejected', 'pending'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            return;
        }

        $updated = $this->BookingsModel->update_status($booking_id, $status);

        if ($updated) {
            echo json_encode(['status' => 'ok', 'action' => 'updated', 'new_status' => $status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed']);
        }
    }
}
