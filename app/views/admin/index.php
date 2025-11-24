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
                            <li><a href="<?php echo site_url('admin/users'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.12)">User Management</a></li>
                            <li><a href="<?php echo site_url('admin/bookings'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Booking Management</a></li>
                            <li><a href="<?php echo site_url('admin/rooms/add'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Add Room</a></li>
                            <li><a href="<?php echo site_url('admin/rooms'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">View Rooms</a></li>
                            <li><a href="<?php echo site_url('admin/rooms/available'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Available Rooms</a></li>
                            <li><a href="<?php echo site_url('admin/rooms'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Edit Room Info</a></li>
                            <li><a href="<?php echo site_url('admin/analytics'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Analytics</a></li>
                            <li><a href="<?php echo site_url('admin/messages'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Messages</a></li>
                            <li><a href="<?php echo site_url('admin/audit'); ?>" data-ajax data-ajax-no-push style="color:#fff;text-decoration:none;display:block;padding:8px;border-radius:6px;background:rgba(0,0,0,0.06)">Audit Logs</a></li>
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
                            <div class="stat"><strong>Revenue</strong><div>₱<?php echo number_format(isset($revenue) ? $revenue : 0, 2); ?></div></div>
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

                // Insert HTML
                main.innerHTML = text;

                // Execute any inline or external <script> tags included in the fragment.
                // Browsers do not execute scripts when inserted via innerHTML, so recreate them.
                try{
                    const container = document.createElement('div');
                    container.innerHTML = text;
                    const scripts = container.querySelectorAll('script');
                    scripts.forEach(s => {
                        const ns = document.createElement('script');
                        if (s.src) {
                            // preserve src and other attributes
                            ns.src = s.src;
                            if (s.type) ns.type = s.type;
                            // append to body to load and execute
                            document.body.appendChild(ns);
                            // remove after load to avoid clutter
                            ns.addEventListener('load', () => ns.parentNode && ns.parentNode.removeChild(ns));
                        } else {
                            // inline script: copy textContent
                            ns.text = s.textContent;
                            document.body.appendChild(ns);
                            document.body.removeChild(ns);
                        }
                    });
                } catch(e) { console.error('Error executing fragment scripts', e); }

                // If the injected fragment contains booking action links, attach handlers
                try{ if(typeof attachBookingActions === 'function') attachBookingActions(); }catch(e){ /* ignore */ }
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
            // If a link has data-ajax-no-push, load the fragment but DO NOT change the URL (keep /admin)
            const noPush = a.hasAttribute('data-ajax-no-push') || a.hasAttribute('data-no-push');
            loadHref(href, !noPush);
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

                // If approve: confirm and send approve POST (total_amount no longer required)
                if(isApprove){
                    if(!confirm('Approve this booking?')) return;
                    const tr = this.closest('tr');
                    const bookingId = tr ? tr.getAttribute('data-booking-id') : null;
                    (async () => {
                        try{
                            const form = new URLSearchParams();
                            form.append('status', 'approved');
                            if(token) form.append('admin_token', token);
                            const response = await fetch(this.href, { method: 'POST', body: form.toString(), headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' } });
                            const contentType = response.headers.get('Content-Type') || '';
                            let result;
                            if(contentType.indexOf('application/json') !== -1){ result = await response.json(); }
                            else { result = { text: await response.text() }; }

                            if(result && result.status === 'ok'){
                                alert('Booking approved.');
                                if(tr && tr.parentNode) tr.parentNode.removeChild(tr);
                                return;
                            }

                            let msg = 'Failed to approve booking';
                            if(result && result.message) msg = result.message;
                            if(result && result.text) msg = result.text;
                            alert(msg);
                        }catch(err){ console.error(err); alert('Unable to approve booking'); }
                    })();
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

            // No modal required: approvals are sent directly via POST above
        }
        // attach on initial load
        attachBookingActions();
    })();
    </script>
    <?php if(isset($initial_load) && !empty($initial_load)): ?>
    <script>
    // If server requested an initial fragment load (audit or bookings), trigger the matching sidebar link.
    document.addEventListener('DOMContentLoaded', function(){
        try{
            var initial = '<?php echo addslashes($initial_load); ?>';
            var sidebar = document.querySelector('nav[aria-label="Admin shortcuts"]');
            if(!sidebar) return;
            // try to find a link that contains the initial path (e.g. 'admin/audit' or 'admin/bookings')
            var link = sidebar.querySelector('a[data-ajax-no-push][href*="' + initial + '"]');
            if(!link){
                // fallback: any data-ajax link that contains the word
                link = sidebar.querySelector('a[data-ajax][href*="' + initial + '"]');
            }
            if(link){
                // Delay slightly to ensure event handlers are attached
                setTimeout(function(){ link.click(); }, 50);
            }
        }catch(e){ console.error('Auto-load fragment failed', e); }
    });
    </script>
    <?php endif; ?>
    
    </div>
</body>
</html>
