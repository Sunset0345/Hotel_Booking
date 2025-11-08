<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:18px; max-width:800px;">
    <h2>Add Room</h2>

    <?php if(isset($error)): ?>
        <div style="padding:10px;background:#ffdddd;color:#800; margin-bottom:12px"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo site_url('admin/rooms/add'); ?>" enctype="multipart/form-data">
        <div style="margin-bottom:8px">
            <label>Room Number</label><br>
            <input type="text" name="room_number" required style="width:100%;padding:8px" />
        </div>

        <div style="margin-bottom:8px">
            <label>Room Type</label><br>
            <input type="text" name="room_type" required style="width:100%;padding:8px" />
        </div>

        <div style="display:flex;gap:12px;margin-bottom:8px">
            <div style="flex:1">
                <label>Price per Night</label><br>
                <input type="text" name="price_per_night" required style="width:100%;padding:8px" />
            </div>
            <div style="width:160px">
                <label>Capacity</label><br>
                <input type="number" name="capacity" required min="1" style="width:100%;padding:8px" />
            </div>
        </div>

        <div style="margin-bottom:8px">
            <label>Description</label><br>
            <textarea name="description" rows="5" style="width:100%;padding:8px"></textarea>
        </div>

        <div style="margin-bottom:12px">
            <label>Room Image (jpg, png)</label><br>
            <input type="file" name="image" accept="image/*" />
        </div>

        <div>
            <button type="submit" style="padding:10px 16px;background:#0b74de;color:#fff;border:none">Add Room</button>
            <a href="<?php echo site_url('admin/rooms_available'); ?>" style="margin-left:8px;color:#666">Cancel</a>
        </div>
    </form>
</div>