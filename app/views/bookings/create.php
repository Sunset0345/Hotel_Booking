<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<h2 style="margin-top:0">Book Room</h2>
<?php if(empty($room)){ echo '<p>Room not found.</p>'; return; } ?>
<?php
// Normalize price as float
$price_per_night = isset($room['price_per_night']) ? (float)$room['price_per_night'] : 0.0;
?>
<div style="margin-bottom:12px;color:#0b172e">
    <strong>Room:</strong> <?= htmlspecialchars($room['room_number']) ?> &mdash; <?= htmlspecialchars($room['room_type']) ?>
    <div style="color:#0b74de;font-weight:700">Price per night: $<?= number_format($price_per_night,2) ?></div>
</div>

<form method="post" action="<?= site_url('bookings/create/' . $room['room_id']) ?>" id="bookingForm">
        <input type="hidden" name="room_id" value="<?= htmlspecialchars($room['room_id']) ?>" />

        <label>Check-in</label>
        <input type="date" name="check_in" id="checkIn" required />

        <label>Check-out</label>
        <input type="date" name="check_out" id="checkOut" required />

        <!-- Total amount is managed by admin only. Users cannot set it. -->

        <div style="margin-top:10px">
            <button type="submit">Book</button>
        </div>
</form>
