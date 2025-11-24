<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<?php if(!empty($flashes) && is_array($flashes)): ?>
    <?php foreach($flashes as $f): ?>
        <div style="padding:10px;margin-bottom:12px;border-radius:6px;<?php echo $f['type'] === 'success' ? 'background:#d1ffd1;color:#064e03' : 'background:#ffd1d1;color:#5a0000'; ?>">
            <?php echo htmlspecialchars($f['message']); ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h2>Room <?= htmlspecialchars($room['room_number'] ?? '') ?></h2>
<div style="display:flex;gap:18px;align-items:flex-start">
    <div style="flex:0 0 360px">
        <?php if(!empty($room['image'])): ?>
            <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $room['image']; ?>" style="width:100%;height:260px;object-fit:cover;border-radius:8px" alt="room image" />
        <?php else: ?>
            <div style="width:100%;height:260px;background:#eee;border-radius:8px"></div>
        <?php endif; ?>
    </div>
    <div style="flex:1">
        <p><strong>Type:</strong> <?= htmlspecialchars($room['room_type'] ?? '') ?></p>
        <p><strong>Price per night:</strong> ₱<?= number_format((float)($room['price_per_night'] ?? 0), 2) ?></p>
        <p><strong>Capacity:</strong> <?= intval($room['capacity'] ?? 1) ?> guests</p>
        <p><?= nl2br(htmlspecialchars($room['description'] ?? '')) ?></p>

        <div style="margin-top:16px">
            <a href="<?php echo site_url('bookings/create/' . $room['room_id']); ?>" class="btn" style="padding:8px 12px;background:#0b74de;color:#fff;border-radius:6px;text-decoration:none">Book this room</a>
        </div>
    </div>
</div>