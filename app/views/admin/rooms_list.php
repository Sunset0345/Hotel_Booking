<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:18px">
    <h2>Rooms List</h2>

    <?php if(empty($rooms)): ?>
        <div style="padding:12px;color:#999">No rooms found. <a href="<?php echo site_url('admin/rooms/add'); ?>" data-ajax>Add one</a>.</div>
    <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;gap:18px;align-items:flex-start;">
            <?php foreach($rooms as $r): ?>
                <div style="background:#fff;border-radius:10px;box-shadow:0 2px 8px #0001;padding:18px;flex:0 0 calc(33.333% - 12px);max-width:calc(33.333% - 12px);box-sizing:border-box;display:flex;flex-direction:column;gap:10px;position:relative">
                    <div style="display:flex;align-items:center;gap:12px">
                        <?php if(!empty($r['image'])): ?>
                            <img src="<?php echo base_url() . PUBLIC_DIR . '/' . $r['image']; ?>" alt="room" style="width:90px;height:70px;object-fit:cover;border-radius:6px" />
                        <?php else: ?>
                            <div style="width:90px;height:70px;background:#eee;display:flex;align-items:center;justify-content:center;color:#999;border-radius:6px">No Image</div>
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:600;font-size:1.1em">Room <?php echo htmlspecialchars($r['room_number']); ?></div>
                            <div style="color:#666;font-size:0.95em"><?php echo htmlspecialchars($r['room_type']); ?></div>
                            <div style="color:#0b74de;font-weight:500">₱<?php echo number_format($r['price_per_night'],2); ?></div>
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
        <?php if(isset($last_page) && $last_page > 1): ?>
            <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                <?php for($p=1;$p<=$last_page;$p++): ?>
                    <a href="<?php echo site_url('admin/rooms') . '?page=' . $p; ?>" class="btn small" data-ajax style="<?php echo ($p == $current_page) ? 'background:#2995ff;color:#fff' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
(function(){
    // Attach AJAX behaviour to pagination links in this fragment so clicking pages updates #admin-main via AJAX
    try{
        var main = document.getElementById('admin-main');
        if(!main) return;
        var links = Array.prototype.slice.call(document.querySelectorAll('a[data-ajax]'));
        links.forEach(function(a){
            // only bind links pointing to admin/rooms to avoid interfering with other fragments
            var href = a.getAttribute('href') || '';
            if(href.indexOf('/admin/rooms') === -1) return;
            a.addEventListener('click', function(e){
                e.preventDefault();
                var url = this.href;
                main.innerHTML = '<div style="padding:18px">Loading…</div>';
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(res){ if(!res.ok) throw new Error('HTTP ' + res.status); return res.text(); }).then(function(text){
                    main.innerHTML = text;
                    // execute inline scripts present in the fragment
                    try{
                        var container = document.createElement('div'); container.innerHTML = text;
                        var scripts = container.querySelectorAll('script');
                        scripts.forEach(function(s){
                            var ns = document.createElement('script');
                            if(s.src){ ns.src = s.src; if(s.type) ns.type = s.type; document.body.appendChild(ns); ns.addEventListener('load', function(){ ns.parentNode && ns.parentNode.removeChild(ns); }); }
                            else { ns.text = s.textContent; document.body.appendChild(ns); document.body.removeChild(ns); }
                        });
                    }catch(e){ console.error('exec fragment scripts', e); }
                    try{ if(typeof attachBookingActions === 'function') attachBookingActions(); }catch(e){}
                    try{ history.pushState({url:url}, '', url); }catch(e){}
                }).catch(function(err){ main.innerHTML = '<div style="padding:18px;color:#c00">Unable to load page</div>'; });
            });
        });
    }catch(e){ console.error('pagination ajax bind failed', e); }
})();
</script>