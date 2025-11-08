<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:18px;max-width:800px">
    <h2>Edit Room</h2>

    <?php if(isset($error)): ?>
        <div style="padding:10px;background:#ffdddd;color:#800; margin-bottom:12px"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if(!isset($room)): ?>
        <div style="padding:12px;color:#999">Room not found.</div>
    <?php else: ?>
        <form method="post" action="<?php echo site_url('admin/rooms/update/'.$room['room_id']); ?>" enctype="multipart/form-data">
            <div style="margin-bottom:8px">
                <label>Room Number</label><br>
                <input type="text" name="room_number" required value="<?php echo htmlspecialchars($room['room_number']); ?>" style="width:100%;padding:8px" />
            </div>

            <div style="margin-bottom:8px">
                <label>Room Type</label><br>
                <input type="text" name="room_type" required value="<?php echo htmlspecialchars($room['room_type']); ?>" style="width:100%;padding:8px" />
            </div>

            <div style="display:flex;gap:12px;margin-bottom:8px">
                <div style="flex:1">
                    <label>Price per Night</label><br>
                    <input type="text" name="price_per_night" required value="<?php echo htmlspecialchars(number_format($room['price_per_night'],2)); ?>" style="width:100%;padding:8px" />
                </div>
                <div style="width:160px">
                    <label>Capacity</label><br>
                    <input type="number" name="capacity" required min="1" value="<?php echo intval($room['capacity']); ?>" style="width:100%;padding:8px" />
                </div>
            </div>

            <div style="margin-bottom:8px">
                <label>Description</label><br>
                <textarea name="description" rows="5" style="width:100%;padding:8px"><?php echo htmlspecialchars($room['description']); ?></textarea>
            </div>

            <div style="margin-bottom:12px">
                <label>Replace Image (leave empty to keep current)</label><br>
                <input type="file" name="image" accept="image/*" />
                <?php if(!empty($room['image'])): ?>
                    <div style="margin-top:8px">
                        <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $room['image']; ?>" style="width:120px;height:80px;object-fit:cover;border-radius:6px" alt="current image"/>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <button type="submit" style="padding:10px 16px;background:#0b74de;color:#fff;border:none">Save Changes</button>
                <a href="<?php echo site_url('admin/rooms'); ?>" style="margin-left:8px;color:#666">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>