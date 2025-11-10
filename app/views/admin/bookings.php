<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:12px;color:#000">
    <h2 style="margin-top:0;color:#000">Bookings Management</h2>

    <table style="width:100%;border-collapse:collapse;background:transparent;color:#000">
        <thead>
            <tr style="text-align:left;border-bottom:1px solid #e6e6e6;color:#000">
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
        <caption style="caption-side:top;text-align:left;padding:8px">
            <a href="<?php echo site_url('admin/audit'); ?>" data-ajax data-ajax-no-push style="display:inline-block;padding:6px 10px;background:transparent;color:#000;border-radius:6px;text-decoration:none;margin-bottom:8px">View audit logs</a>
        </caption>
        <tbody>
            <?php if(!empty($bookings)): foreach($bookings as $b): ?>
            <tr style="border-bottom:1px solid #f1f1f1;color:#000">
                <td style="padding:8px;vertical-align:top"><?php echo intval($b['booking_id']); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars(isset($b['full_name']) ? $b['full_name'] : 'Guest'); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars(isset($b['room_number']) ? $b['room_number'] : ''); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars($b['check_in']); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars($b['check_out']); ?></td>
                <td style="padding:8px;vertical-align:top">$<?php echo number_format($b['total_amount'],2); ?></td>
                <td style="padding:8px;vertical-align:top"><?php echo htmlspecialchars($b['status']); ?></td>
                <td style="padding:8px;vertical-align:top">
                    <?php if(isset($b['status']) && strtolower($b['status']) === 'approved'): ?>
                        <!-- After approval: hide approve/decline and show End booking action -->
                        <a href="<?php echo site_url('admin/bookings/update/' . intval($b['booking_id']) . '?status=completed'); ?>" class="btn small" data-ajax-booking style="background:#3498db">End booking</a>
                    <?php elseif(isset($b['status']) && strtolower($b['status']) === 'completed'): ?>
                        <span style="display:inline-block;padding:6px 10px;border-radius:6px;background:#ddd;color:#444">Ended</span>
                    <?php else: ?>
                        <a href="<?php echo site_url('admin/bookings/update/' . intval($b['booking_id']) . '?status=approved'); ?>" class="btn small" data-ajax-booking style="background:#2ecc71">Approve</a>
                        <a href="<?php echo site_url('admin/bookings/update/' . intval($b['booking_id']) . '?status=rejected'); ?>" class="btn small" data-ajax-booking style="background:#ff6b6b;margin-left:6px">Decline</a>
                        <a href="<?php echo site_url('admin/bookings/update/' . intval($b['booking_id']) . '?status=pending'); ?>" class="btn small" data-ajax-booking style="background:#f0ad4e;margin-left:6px">Pending</a>
                    <?php endif; ?>
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

        function getCookie(name){
            const v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
            return v ? v.pop() : '';
        }

        main.querySelectorAll('a[data-ajax-booking]').forEach(a => a.addEventListener('click', function(e){
            e.preventDefault();
            if(!confirm('Change booking status?')) return;

            // parse status from href querystring
            try{
                const url = new URL(this.href, window.location.origin);
                const status = url.searchParams.get('status') || '';
                const bookingId = this.closest('tr') ? this.closest('tr').querySelector('td')?.innerText : null;

                // prepare POST body
                const form = new URLSearchParams();
                if(status) form.append('status', status);
                // include admin token from cookie as fallback
                const token = this.getAttribute('data-admin-token') || getCookie('admin_action_token') || '';
                if(token) form.append('admin_token', token);

                fetch(this.href, { method:'POST', body: form.toString(), headers: { 'Content-Type':'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' } }).then(r => {
                    const ct = r.headers.get('Content-Type') || '';
                    if(ct.indexOf('application/json') !== -1) return r.json();
                    return r.text().then(t => ({ text: t }));
                }).then(result => {
                    // handle JSON response
                    if(result && result.status === 'ok'){
                        const tr = this.closest('tr');
                        if(result.action === 'deleted'){
                            alert('Booking declined and removed.');
                            if(tr && tr.parentNode) tr.parentNode.removeChild(tr);
                            return;
                        }
                        if(result.action === 'updated' || result.new_status){
                            const newStatus = result.new_status || status || '';
                            alert('Booking status updated: ' + newStatus);
                            // update the status cell (assume 7th cell index 6)
                            if(tr){
                                const cells = tr.querySelectorAll('td');
                                if(cells.length >= 7){
                                    cells[6].innerText = newStatus;
                                }
                                // replace actions cell with appropriate controls
                                const actionsCell = cells[cells.length - 1];
                                if(actionsCell){
                                    if(newStatus.toLowerCase() === 'approved'){
                                        actionsCell.innerHTML = '<a href="' + <?php echo json_encode(site_url('admin/bookings/update/')); ?> + (result.booking_id || '') + '?status=completed' + '" class="btn small" data-ajax-booking style="background:#3498db">End booking</a>';
                                    } else if(newStatus.toLowerCase() === 'completed'){
                                        actionsCell.innerHTML = '<span style="display:inline-block;padding:6px 10px;border-radius:6px;background:#ddd;color:#444">Ended</span>';
                                    } else {
                                        // fallback: reload the admin fragment to be sure
                                        fetch('<?php echo site_url('admin/bookings'); ?>', { headers:{'X-Requested-With':'XMLHttpRequest'} }).then(r=>r.text()).then(html=>{ main.innerHTML = html; });
                                    }
                                }
                            }
                            return;
                        }
                        alert('Action completed.');
                        return;
                    }

                    // handle text-only fallback
                    if(result && result.text && result.text.trim() === 'OK'){
                        alert('Action completed.');
                        return;
                    }

                    let msg = 'Failed to update booking';
                    if(result && result.message) msg = result.message;
                    if(result && result.text) msg = result.text;
                    alert(msg);
                }).catch(err=>{ console.error(err); alert('Unable to update booking'); });
            }catch(ex){ console.error(ex); alert('Invalid action URL'); }
        }));
    })();
    </script>
</div>