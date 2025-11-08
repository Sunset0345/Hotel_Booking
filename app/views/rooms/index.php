<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<h2>Rooms</h2>
<?php if(!empty($rooms)): ?>
    <div style="display:flex;flex-wrap:wrap;gap:12px">
    <?php foreach($rooms as $r): ?>
        <?php if(isset($r['status']) && $r['status'] !== 'available') continue; // hide unavailable from users ?>
        <div style="width:300px;border:1px solid #eee;border-radius:8px;overflow:hidden;background:#fff">
            <div style="height:180px;background:#ccc">
                <?php if(!empty($r['image'])): ?>
                    <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $r['image']; ?>" style="width:100%;height:180px;object-fit:cover" alt="room image" />
                <?php endif; ?>
            </div>
            <div style="padding:12px">
                <strong><?php echo htmlspecialchars($r['room_number']); ?></strong>
                <div style="color:#666"><?php echo htmlspecialchars($r['room_type']); ?></div>
                <div style="margin-top:8px">$<?php echo number_format($r['price_per_night'],2); ?> · <?php echo intval($r['capacity']); ?> guests</div>
                <div style="margin-top:12px">
                    <a href="<?php echo site_url('bookings/create/'.$r['room_id']); ?>" class="btn" style="padding:8px 12px;background:#0b74de;color:#fff;border-radius:6px;text-decoration:none">Book</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>No rooms found.</p>
<?php endif; ?>
