<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:18px">
    <h2>Available Rooms</h2>

    <?php if(empty($rooms)): ?>
        <div style="padding:12px;color:#999">No available rooms.</div>
    <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:12px">
        <?php foreach($rooms as $r): ?>
            <div style="width:260px;border-radius:8px;overflow:hidden;border:1px solid #eee;background:#fff;color:#111;display:flex;flex-direction:column">
                <div style="height:140px;background:#ddd;">
                    <?php if(!empty($r['image'])): ?>
                        <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $r['image']; ?>" style="width:100%;height:140px;object-fit:cover" />
                    <?php endif; ?>
                </div>
                <div style="padding:12px;flex:1">
                    <strong><?php echo htmlspecialchars($r['room_number']); ?></strong>
                    <div style="font-size:13px;color:#666"><?php echo htmlspecialchars($r['room_type']); ?></div>
                    <div style="margin-top:8px">$<?php echo number_format($r['price_per_night'],2); ?> · <?php echo intval($r['capacity']); ?> guests</div>
                    <div style="color:#444;font-size:0.9em;margin-top:8px;min-height:36px"><?php echo htmlspecialchars($r['description']); ?></div>
                </div>
                <div style="display:flex;gap:8px;padding:10px;border-top:1px solid #f0f0f0">
                    <a href="<?php echo site_url('admin/rooms/toggle/'.$r['room_id']); ?>" onclick="return confirm('Mark as not available?');" style="flex:1;text-align:center;padding:8px;background:#eaf4ff;color:#0b74de;border-radius:6px;text-decoration:none">Mark Not Available</a>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>