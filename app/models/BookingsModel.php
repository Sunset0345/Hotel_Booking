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

    /**
     * Check if a proposed booking overlaps existing bookings for the same room.
     * Overlap is inclusive: if existing.check_in <= new.check_out AND existing.check_out >= new.check_in
     * We ignore bookings that are cancelled or rejected.
     * Returns true if there is an overlap.
     */
    public function has_overlap($room_id, $check_in, $check_out)
    {
        // normalize dates to Y-m-d
        try{
            $d1 = (new DateTime($check_in))->format('Y-m-d');
            $d2 = (new DateTime($check_out))->format('Y-m-d');
        } catch(Exception $e){
            return false; // invalid dates => do not treat as overlap here
        }

        try{
            // Ensure we only check overlaps for the same room. Use an explicit inclusive overlap condition:
            // overlap exists when NOT (existing.check_out < new.check_in OR existing.check_in > new.check_out)
            $stmt = $this->raw("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE room_id = ? AND COALESCE(LOWER(status),'') NOT IN ('rejected','cancelled') AND NOT (check_out < ? OR check_in > ?)", [(int)$room_id, $d1, $d2]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $cnt = isset($row['cnt']) ? intval($row['cnt']) : 0;
            return $cnt > 0;
        } catch(Exception $e){
            return false;
        }
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
