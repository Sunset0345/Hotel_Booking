<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Blue Lagoon Hotel</title>
    <style>
        body{margin:0;font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;}
    .hero{min-height:100vh;background-image:url('https://www.kayak.com.ph/rimg/himg/e1/5b/f8/expediav2-42996-f561b3-210999.jpg?width=2000&height=1200&crop=true');background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;color:#fff}
        .hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg, rgba(0,0,0,0.45), rgba(0,0,0,0.55));}
        .container{position:relative;z-index:2;max-width:1100px;width:100%;padding:40px;}
        .card{background:rgba(255,255,255,0.08);backdrop-filter:blur(6px);border-radius:12px;padding:28px;color:#fff}
        .card h1{margin:0 0 8px;font-size:28px}
        .card p{margin:0 0 18px;color:rgba(255,255,255,0.9)}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:18px}
        .stat{background:rgba(255,255,255,0.06);padding:18px;border-radius:8px}
        a.btn{display:inline-block;background:#2995ff;color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none}
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-overlay"></div>
        <div class="container">
            <div style="display:flex;gap:20px;align-items:stretch">
                <!-- Sidebar -->
                <nav aria-label="Admin shortcuts" style="width:260px;flex:0 0 260px">
                    <div class="card" style="padding:14px">
                        <h2 style="margin:0 0 8px;font-size:18px">Shortcuts</h2>
                        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px">
                            <li><a href="<?php echo site_url('admin/users'); ?>" data-ajax style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.12)">User Management</a></li>
                            <li><a href="<?php echo site_url('admin/bookings'); ?>" data-ajax style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Booking Management</a></li>
                            <li><a href="<?php echo site_url('admin/rooms/add'); ?>" data-ajax style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Add Room</a></li>
                            <li><a href="<?php echo site_url('admin/rooms'); ?>" data-ajax style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">View Rooms</a></li>
                            <li><a href="<?php echo site_url('admin/rooms/available'); ?>" data-ajax style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Available Rooms</a></li>
                            <li><a href="<?php echo site_url('admin/rooms'); ?>" data-ajax style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Edit Room Info</a></li>
                            <li><a href="<?php echo site_url('admin/analytics'); ?>" data-ajax style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Analytics</a></li>
                            <li><a href="<?php echo site_url('admin/messages'); ?>" data-ajax style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Messages</a></li>
                            <li><a href="<?php echo site_url('admin/logout'); ?>" style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Logout</a></li>
                        </ul>
                    </div>
                </nav>

                <!-- Main content area -->
                <main style="flex:1">
                    <div id="admin-main" class="card" role="main" aria-live="polite">
                        <h1>Admin Dashboard</h1>
                        <p>Welcome back, <?php echo htmlspecialchars(isset($admin_name) ? $admin_name : 'Admin'); ?> — manage rooms, bookings and users.</p>

                        <div class="grid">
                            <div class="stat"><strong>Rooms</strong><div><?php echo intval(isset($rooms) ? $rooms : 0); ?></div></div>
                            <div class="stat"><strong>Bookings</strong><div><?php echo intval(isset($bookings) ? $bookings : 0); ?></div></div>
                            <div class="stat"><strong>Users</strong><div><?php echo intval(isset($users) ? $users : 0); ?></div></div>
                            <div class="stat"><strong>Revenue</strong><div>$<?php echo number_format(isset($revenue) ? $revenue : 0, 2); ?></div></div>
                        </div>

                        <div style="margin-top:18px">
                            <a class="btn" href="<?php echo site_url('admin/rooms'); ?>" data-ajax>View Rooms</a>
                            <a class="btn" href="<?php echo site_url('admin/logout'); ?>" style="background:#ff6b6b;margin-left:8px">Logout</a>
                        </div>
                        
                        <!-- Quick pending bookings panel (allow actions only from dashboard) -->
                        <div style="margin-top:18px;margin-top:22px;">
                            <h3 style="margin:8px 0 10px 0;color:#fff">Pending Bookings</h3>
                            <div style="background:rgba(255,255,255,0.02);padding:12px;border-radius:8px">
                                <?php if(!empty($pending_bookings)): ?>
                                    <table style="width:100%;border-collapse:collapse;color:#fff;font-size:0.95rem">
                                        <thead>
                                            <tr style="text-align:left;border-bottom:1px solid rgba(255,255,255,0.04)"><th style="padding:6px">ID</th><th style="padding:6px">Guest</th><th style="padding:6px">Room</th><th style="padding:6px">Dates</th><th style="padding:6px">Actions</th></tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach($pending_bookings as $pb): ?>
                                            <tr data-booking-id="<?php echo intval($pb['booking_id']); ?>">
                                                <td style="padding:6px;vertical-align:top"><?php echo intval($pb['booking_id']); ?></td>
                                                <td style="padding:6px;vertical-align:top"><?php echo htmlspecialchars($pb['full_name'] ?? 'Guest'); ?></td>
                                                <td style="padding:6px;vertical-align:top"><?php echo htmlspecialchars($pb['room_number'] ?? ''); ?></td>
                                                <td style="padding:6px;vertical-align:top"><?php echo htmlspecialchars($pb['check_in']); ?> → <?php echo htmlspecialchars($pb['check_out']); ?></td>
                                                <td style="padding:6px;vertical-align:top">
                                                    <?php $token = isset($admin_action_token) ? $admin_action_token : ''; ?>
                                                    <a href="<?php echo site_url('admin/bookings/update/' . intval($pb['booking_id']) . '?status=approved'); ?>" class="btn small" data-ajax-booking data-admin-token="<?php echo htmlspecialchars($token); ?>" style="background:#2ecc71">Approve</a>
                                                    <a href="<?php echo site_url('admin/bookings/update/' . intval($pb['booking_id']) . '?status=rejected'); ?>" class="btn small" data-ajax-booking data-admin-token="<?php echo htmlspecialchars($token); ?>" style="background:#ff6b6b;margin-left:6px">Decline</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div style="color:#ddd">No pending bookings.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script>
    (function(){
        // AJAX load admin subpages into #admin-main
        const sidebar = document.querySelector('nav[aria-label="Admin shortcuts"]');
        const main = document.getElementById('admin-main');
        if(!sidebar || !main) return;

        function setActive(link){
            sidebar.querySelectorAll('a').forEach(a => a.style.opacity = '0.9');
            if(link) link.style.opacity = '1';
        }

        async function loadHref(href, push){
            try{
                main.innerHTML = '<div style="padding:18px">Loading…</div>';
                const res = await fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if(!res.ok) throw new Error('HTTP ' + res.status);
                const text = await res.text();
                main.innerHTML = text;
                if(push){
                    try{ history.pushState({url:href}, '', href); }catch(e){}
                }
            }catch(err){
                main.innerHTML = '<div style="padding:18px;color:#ffd1d1">Unable to load content: ' + (err.message || err) + '</div>';
            }
        }

        sidebar.addEventListener('click', function(e){
            const a = e.target.closest && e.target.closest('a');
            if(!a) return;
            // only intercept links explicitly marked with data-ajax
            if(!a.hasAttribute('data-ajax')) return;
            const href = a.getAttribute('href');
            if(!href) return;
            e.preventDefault();
            setActive(a);
            loadHref(href, true);
        });

        // handle back/forward
        window.addEventListener('popstate', function(ev){
            const url = (ev.state && ev.state.url) || location.pathname;
            loadHref(url, false);
        });

    // Expose admin action token to JS
    const ADMIN_ACTION_TOKEN = '<?php echo isset($admin_action_token) ? addslashes($admin_action_token) : ''; ?>';

        // Attach booking action handler for dashboard pending bookings (data-ajax-booking links)
        // This handler uses a small modal for approving bookings (enter total) for better UX.
        function attachBookingActions(){
            const mainEl = document.getElementById('admin-main');
            if(!mainEl) return;
            // We'll store a pending action context when admin clicks Approve, then open a modal to capture the total
            let pendingAction = null;

            mainEl.querySelectorAll('a[data-ajax-booking]').forEach(a => a.addEventListener('click', function(e){
                e.preventDefault();

                const href = this.getAttribute('href') || '';
                const isApprove = href.indexOf('status=approved') !== -1 || this.dataset.action === 'approve';
                const token = this.getAttribute('data-admin-token') || ADMIN_ACTION_TOKEN || '';

                // If approve: open modal and set pending action
                if(isApprove){
                    const tr = this.closest('tr');
                    const bookingId = tr ? tr.getAttribute('data-booking-id') : null;
                    pendingAction = { href: this.href, token: token, row: tr };
                    openTotalModal(bookingId, token);
                    return;
                }

                // For decline and other actions: confirm and send POST with token
                if(!confirm('Change booking status?')) return;
                (async () => {
                    try{
                        const form = new URLSearchParams();
                        form.append('status', 'rejected');
                        if(token) form.append('admin_token', token);
                        const response = await fetch(this.href, { method: 'POST', body: form.toString(), headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' } });
                        const contentType = response.headers.get('Content-Type') || '';
                        let result;
                        if(contentType.indexOf('application/json') !== -1){ result = await response.json(); }
                        else { result = { text: await response.text() }; }

                        if(result && result.status === 'ok'){
                            if(result.action === 'deleted'){
                                alert('Booking declined and removed.');
                                const tr = this.closest('tr'); if(tr && tr.parentNode) tr.parentNode.removeChild(tr);
                                return;
                            }
                            alert('Action completed.');
                            return;
                        }

                        let msg = 'Failed to update booking';
                        if(result && result.message) msg = result.message;
                        if(result && result.text) msg = result.text;
                        alert(msg);
                    }catch(err){ console.error(err); alert('Unable to update booking'); }
                })();
            }));

            // Modal handlers
            const modalOverlay = document.getElementById('total-modal-overlay');
            const modal = document.getElementById('total-modal');
            const inputAmount = document.getElementById('total-amount');
            const btnConfirm = document.getElementById('total-confirm');
            const btnCancel = document.getElementById('total-cancel');

            function openTotalModal(bookingId, token){
                if(!modal || !modalOverlay) return;
                modalOverlay.style.display = 'block';
                modal.style.display = 'block';
                inputAmount.value = '';
                inputAmount.focus();
                // store pending context on modal element
                modal.dataset.pendingHref = pendingAction ? pendingAction.href : '';
                modal.dataset.pendingToken = token || '';
                modal.dataset.pendingBookingId = bookingId || '';
            }

            function closeTotalModal(){
                if(!modal || !modalOverlay) return;
                modalOverlay.style.display = 'none';
                modal.style.display = 'none';
                modal.removeAttribute('data-pending-href');
                modal.removeAttribute('data-pending-token');
                modal.removeAttribute('data-pending-booking-id');
                pendingAction = null;
            }

            btnCancel && btnCancel.addEventListener('click', function(e){ e.preventDefault(); closeTotalModal(); });

            btnConfirm && btnConfirm.addEventListener('click', async function(e){
                e.preventDefault();
                if(!modal) return;
                const href = modal.dataset.pendingHref || '';
                const token = modal.dataset.pendingToken || '';
                const amount = (inputAmount && inputAmount.value) ? inputAmount.value.trim() : '';
                if(amount === '' || isNaN(parseFloat(amount))){ alert('Please enter a valid amount'); inputAmount.focus(); return; }

                try{
                    const form = new URLSearchParams();
                    form.append('status', 'approved');
                    form.append('total_amount', amount);
                    if(token) form.append('admin_token', token);
                    const response = await fetch(href, { method: 'POST', body: form.toString(), headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' } });
                    const contentType = response.headers.get('Content-Type') || '';
                    let result;
                    if(contentType.indexOf('application/json') !== -1){ result = await response.json(); }
                    else { result = { text: await response.text() }; }

                    if(result && result.status === 'ok'){
                        alert('Booking approved.');
                        if(pendingAction && pendingAction.row && pendingAction.row.parentNode) pendingAction.row.parentNode.removeChild(pendingAction.row);
                        closeTotalModal();
                        return;
                    }

                    let msg = 'Failed to approve booking';
                    if(result && result.message) msg = result.message;
                    if(result && result.text) msg = result.text;
                    alert(msg);
                } catch(err){ console.error(err); alert('Unable to approve booking'); }
            });
        }
        // attach on initial load
        attachBookingActions();
    })();
    </script>
    <!-- Total entry modal -->
    <div id="total-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9998"></div>
    <div id="total-modal" role="dialog" aria-modal="true" style="display:none;position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);z-index:9999;background:#fff;padding:18px;border-radius:8px;min-width:320px;max-width:480px;box-shadow:0 8px 30px rgba(0,0,0,0.4);color:#111">
        <h3 style="margin:0 0 8px 0;font-size:18px">Approve Booking</h3>
        <p style="margin:0 0 12px 0;color:#444">Enter the total amount to charge the guest for this booking.</p>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:12px">
            <label for="total-amount" style="min-width:80px;color:#333">Total</label>
            <input id="total-amount" type="text" inputmode="decimal" placeholder="e.g. 150.00" style="flex:1;padding:8px;border:1px solid #ddd;border-radius:6px;" />
        </div>
        <div style="text-align:right;display:flex;gap:8px;justify-content:flex-end">
            <button id="total-cancel" style="background:#f5f5f5;border:1px solid #ddd;padding:8px 10px;border-radius:6px">Cancel</button>
            <button id="total-confirm" style="background:#2ecc71;color:#fff;border:none;padding:8px 12px;border-radius:6px">Approve</button>
        </div>
    </div>
    </div>
</body>
</html>
