<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<h2>Rooms</h2>
<?php if(!empty($rooms)): ?>
    <div style="display:flex;flex-wrap:wrap;gap:12px">
    <?php foreach($rooms as $r): ?>
        <div style="width:300px;border:1px solid #eee;border-radius:8px;overflow:hidden;background:#fff;position:relative">
            <div style="height:180px;background:#ccc">
                <?php if(!empty($r['image'])): ?>
                    <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $r['image']; ?>" style="width:100%;height:180px;object-fit:cover" alt="room image" />
                <?php endif; ?>
            </div>
            <?php if(isset($r['booking_state']) && strtolower($r['booking_state']) === 'pending'): ?>
                <div style="position:absolute;top:12px;left:12px;z-index:30">
                    <span style="display:inline-block;padding:6px 10px;background:#f59e0b;color:#fff;border-radius:6px;font-weight:700;box-shadow:0 4px 10px rgba(0,0,0,0.12);font-size:13px">Pending</span>
                </div>
            <?php endif; ?>
            <div style="padding:12px">
                <strong><?php echo htmlspecialchars($r['room_number']); ?></strong>
                <div style="color:#666"><?php echo htmlspecialchars($r['room_type']); ?></div>
                <div style="margin-top:8px">₱<?php echo number_format($r['price_per_night'],2); ?> · <?php echo intval($r['capacity']); ?> guests</div>
                <div style="margin-top:12px">
                    <?php
                        // only consider a room reserved when it's currently occupied ('booked')
                        $isReserved = (isset($r['booking_state']) && $r['booking_state'] === 'booked');
                    ?>
                    <?php if($isReserved): ?>
                        <span style="display:inline-block;padding:8px 12px;background:#6b7280;color:#fff;border-radius:6px">Reserved</span>
                    <?php else: ?>
                        <a href="<?php echo site_url('bookings/create/'.$r['room_id']); ?>" class="btn" style="padding:8px 12px;background:#0b74de;color:#fff;border-radius:6px;text-decoration:none">Book</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>No rooms found.</p>
<?php endif; ?>
