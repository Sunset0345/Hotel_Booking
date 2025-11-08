<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:12px">
    <h2 style="margin-top:0">Bookings Management</h2>

    <table style="width:100%;border-collapse:collapse;background:rgba(255,255,255,0.02)">
        <thead>
            <tr style="text-align:left;border-bottom:1px solid rgba(255,255,255,0.04)">
                <th style="padding:8px">ID</th>
                <th style="padding:8px">Guest</th>
                <th style="padding:8px">Room</th>
                <th style="padding:8px">Check In</th>
                <th style="padding:8px">Check Out</th>
                <th style="padding:8px">Amount</th>
                <th style="padding:8px">Status</th>
                <th style="padding:8px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($bookings)): foreach($bookings as $b): ?>
            <tr style="border-bottom:1px solid rgba(255,255,255,0.02)">
                <td style="padding:8px;vertical-align:top"><?php echo intval($b['booking_id']); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars(isset($b['full_name']) ? $b['full_name'] : 'Guest'); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars(isset($b['room_number']) ? $b['room_number'] : ''); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars($b['check_in']); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars($b['check_out']); ?></td>
                <td style="padding:8px;vertical-align:top">$<?php echo number_format($b['total_amount'],2); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars($b['status']); ?></td>
                <td style="padding:8px;vertical-align:top">
                    <a href="<?php echo site_url('admin/bookings/update/' . intval($b['booking_id']) . '?status=approved'); ?>" class="btn small" data-ajax-booking style="background:#2ecc71">Approve</a>
                    <a href="<?php echo site_url('admin/bookings/update/' . intval($b['booking_id']) . '?status=rejected'); ?>" class="btn small" data-ajax-booking style="background:#ff6b6b;margin-left:6px">Decline</a>
                    <a href="<?php echo site_url('admin/bookings/update/' . intval($b['booking_id']) . '?status=pending'); ?>" class="btn small" data-ajax-booking style="background:#f0ad4e;margin-left:6px">Pending</a>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="8" style="padding:12px">No bookings found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
    (function(){
        const main = document.getElementById('admin-main');
        if(!main) return;
        main.querySelectorAll('a[data-ajax-booking]').forEach(a => a.addEventListener('click', function(e){
            e.preventDefault();
            if(!confirm('Change booking status?')) return;
            fetch(this.href, { method:'GET', headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>{
                // prefer JSON responses
                var ct = r.headers.get('Content-Type') || '';
                if(ct.indexOf('application/json') !== -1){ return r.json().then(j => ({ json: j })); }
                return r.text().then(t => ({ text: t }));
            }).then(result=>{
                    if(result.json && result.json.status === 'ok'){
                        // if server indicates deletion, remove the row in-place and alert
                        if(result.json.action === 'deleted'){
                            alert('Booking declined and removed.');
                            // remove the table row containing the clicked link
                            var tr = this.closest('tr'); if(tr && tr.parentNode) tr.parentNode.removeChild(tr);
                            return;
                        }
                        // if updated, show a quick alert and refresh the fragment to reflect status change
                        if(result.json.action === 'updated'){
                            alert('Booking status updated: ' + (result.json.new_status || ''));
                            fetch('<?php echo site_url('admin/bookings'); ?>', { headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(html=>{ main.innerHTML = html; });
                            return;
                        }
                        // generic success
                        alert('Action completed.');
                        fetch('<?php echo site_url('admin/bookings'); ?>', { headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(html=>{ main.innerHTML = html; });
                    } else if(result.text && result.text.trim() === 'OK'){
                        // backward compatibility fallback
                        alert('Action completed.');
                        fetch('<?php echo site_url('admin/bookings'); ?>', { headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(html=>{ main.innerHTML = html; });
                    } else {
                        var msg = 'Failed to update booking';
                        if(result.json && result.json.message) msg = result.json.message;
                        if(result.text) msg = result.text;
                        alert(msg);
                    }
            }).catch(err=>{ console.error(err); alert('Unable to update booking'); });
        }));
    })();
    </script>
</div>