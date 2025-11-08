<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class BookingsModel extends Model {
    protected $table = 'bookings';
    protected $primary_key = 'booking_id';

    public function __construct()
    {
        parent::__construct();
    }

    // 🧾 Insert a new booking
    public function add_booking($data)
    {
        return $this->db->table($this->table)->insert($data);
    }

    // 📋 Get all bookings with user + room details
    public function get_all_with_details()
    {
        return $this->db->table($this->table . ' b')
            ->select('b.*, u.full_name, r.room_number')
            ->join('users u', 'b.user_id = u.user_id')
            ->join('rooms r', 'b.room_id = r.room_id')
            ->order_by('b.date_booked', 'DESC')
            ->get_all();
    }

    // ⚙️ Update booking status (approve/reject/pending)
    public function update_status($booking_id, $status)
    {
        return $this->db->table($this->table)
            ->where($this->primary_key, $booking_id)
            ->update(['status' => $status]);
    }

    // 🧮 Get room price by ID (for total calculation)
    public function get_room_price($room_id)
    {
        $result = $this->db->table('rooms')
            ->select('price_per_night')
            ->where('room_id', $room_id)
            ->get();

        return $result ? (float)$result['price_per_night'] : 0.0;
    }
}
