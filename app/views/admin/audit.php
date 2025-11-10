<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:12px;color:#000">
    <h2 style="margin-top:0;color:#000">Admin Audit Logs</h2>

    <!-- Back to bookings button (loads bookings fragment into #admin-main without changing the URL) -->
    <div style="margin-bottom:10px">
        <a href="<?php echo site_url('admin/bookings'); ?>" data-ajax data-ajax-no-push style="display:inline-block;padding:8px 12px;background:transparent;color:#000;border-radius:6px;text-decoration:none;border:1px solid #e6e6e6">Back to Bookings</a>
    </div>

    <!-- Audit table removed per request; completed bookings shown below -->

        <div style="margin-top:18px">
            <h3 style="margin:8px 0 10px 0;color:#000">Completed Bookings</h3>

            <div style="background:transparent;padding:12px;border-radius:8px">
                <?php if(!empty($completed_bookings)): ?>
                <table style="width:100%;border-collapse:collapse;background:transparent;color:#000;font-size:0.95rem">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid #e6e6e6"><th style="padding:6px">ID</th><th style="padding:6px">Guest</th><th style="padding:6px">Room</th><th style="padding:6px">Check In</th><th style="padding:6px">Check Out</th><th style="padding:6px">Amount</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($completed_bookings as $cb): ?>
                        <tr style="border-bottom:1px solid #f1f1f1;color:#000">
                            <td style="padding:6px;vertical-align:top"><?php echo intval($cb['booking_id'] ?? $cb['id'] ?? 0); ?></td>
                            <td style="padding:6px;vertical-align:top"><?php echo htmlspecialchars($cb['full_name'] ?? 'Guest'); ?></td>
                            <td style="padding:6px;vertical-align:top"><?php echo htmlspecialchars($cb['room_number'] ?? $cb['room_id'] ?? ''); ?></td>
                            <td style="padding:6px;vertical-align:top"><?php echo htmlspecialchars($cb['check_in'] ?? ''); ?></td>
                            <td style="padding:6px;vertical-align:top"><?php echo htmlspecialchars($cb['check_out'] ?? ''); ?></td>
                            <td style="padding:6px;vertical-align:top">$<?php echo number_format($cb['total_amount'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="color:#333">No completed bookings found.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
