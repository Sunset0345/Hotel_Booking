<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:18px">
    <h2>Rooms List</h2>

    <?php if(empty($rooms)): ?>
        <div style="padding:12px;color:#999">No rooms found. <a href="<?php echo site_url('admin/rooms/add'); ?>" data-ajax>Add one</a>.</div>
    <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:18px">
            <?php foreach($rooms as $r): ?>
                <div style="background:#fff;border-radius:10px;box-shadow:0 2px 8px #0001;padding:18px;width:320px;display:flex;flex-direction:column;gap:10px;position:relative">
                    <div style="display:flex;align-items:center;gap:12px">
                        <?php if(!empty($r['image'])): ?>
                            <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $r['image']; ?>" alt="room" style="width:90px;height:70px;object-fit:cover;border-radius:6px" />
                        <?php else: ?>
                            <div style="width:90px;height:70px;background:#eee;display:flex;align-items:center;justify-content:center;color:#999;border-radius:6px">No Image</div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;font-size:1.1em">Room <?php echo htmlspecialchars($r['room_number']); ?></div>
                            <div style="color:#666;font-size:0.95em"><?php echo htmlspecialchars($r['room_type']); ?></div>
                            <div style="color:#0b74de;font-weight:500">$<?php echo number_format($r['price_per_night'],2); ?></div>
                        </div>
                    </div>
                    <div style="color:#444;font-size:0.97em;min-height:38px"><?php echo htmlspecialchars($r['description']); ?></div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="background:#f5f5f5;padding:3px 10px;border-radius:6px;font-size:0.95em">Capacity: <?php echo intval($r['capacity']); ?></span>
                        <span style="background:#f5f5f5;padding:3px 10px;border-radius:6px;font-size:0.95em">Status: <?php echo htmlspecialchars($r['status']); ?></span>
                    </div>
                    <div style="margin-top:8px;display:flex;gap:10px">
                        <a href="<?php echo site_url('admin/rooms/edit/'.$r['room_id']); ?>" data-ajax style="background:#0b74de;color:#fff;padding:7px 16px;border-radius:6px;text-decoration:none;font-weight:500">Edit</a>
                        <a href="<?php echo site_url('admin/rooms/delete/'.$r['room_id']); ?>" onclick="return confirm('Delete this room?');" style="background:#eee;color:#b00;padding:7px 16px;border-radius:6px;text-decoration:none">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>