<?php defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed'); ?>
<div style="padding:18px">
    <h2>Analytics</h2>
    <p>Placeholder: charts and reports.</p>
</div>
    <div style="display:flex;gap:12px;margin-bottom:16px">
        <div style="padding:12px;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.06)">
            <div style="font-size:12px;color:#666">Weekly Income</div>
            <div style="font-size:20px;font-weight:600">₱<?php echo number_format(array_sum($weekly['values']),2); ?></div>
        </div>
        <div style="padding:12px;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.06)">
            <div style="font-size:12px;color:#666">Year to Date</div>
            <div style="font-size:20px;font-weight:600">₱<?php echo number_format(array_sum($monthly['values']),2); ?></div>
        </div>
        <div style="padding:12px;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.06)">
            <div style="font-size:12px;color:#666">Last 5 Years</div>
            <div style="font-size:20px;font-weight:600">₱<?php echo number_format(array_sum($yearly['values']),2); ?></div>
        </div>
    </div>

    <div style="margin-bottom:12px">
        <button data-view="weekly" class="view-btn" style="padding:8px 12px;margin-right:8px">Weekly</button>
        <button data-view="monthly" class="view-btn" style="padding:8px 12px;margin-right:8px">Monthly</button>
        <button data-view="yearly" class="view-btn" style="padding:8px 12px">Yearly</button>
    </div>

    <div style="background:#fff;padding:18px;border-radius:8px">
        <canvas id="analyticsChart" width="900" height="300" style="width:100%;height:300px"></canvas>
    </div>

    <script>
    (function(){
        const weekly = <?php echo json_encode($weekly); ?>;
        const monthly = <?php echo json_encode($monthly); ?>;
        const yearly = <?php echo json_encode($yearly); ?>;

        const chart = document.getElementById('analyticsChart');
        const ctx = chart.getContext('2d');

        function draw(labels, values){
            // simple clear
            ctx.clearRect(0,0,chart.width,chart.height);
            const padding = 40;
            const w = chart.width - padding*2;
            const h = chart.height - padding*2;
            const max = Math.max(1, ...values);
            const barW = w / values.length * 0.7;
            const gap = (w - values.length*barW) / (values.length - 1 || 1);

            // draw labels & bars
            values.forEach((v, i) => {
                const x = padding + i*(barW + gap);
                const barH = (v / max) * (h - 20);
                const y = padding + (h - barH);
                // bar
                ctx.fillStyle = '#0b74de';
                ctx.fillRect(x, y, barW, barH);
                // value
                ctx.fillStyle = '#222';
                ctx.font = '12px sans-serif';
                ctx.fillText('$' + v.toFixed(2), x, y - 6);
                // label
                ctx.fillStyle = '#666';
                ctx.font = '12px sans-serif';
                ctx.fillText(labels[i], x, padding + h + 16);
            });
        }

        function setView(v){
            let labels = [], values = [];
            if(v === 'weekly'){
                labels = weekly.labels; values = weekly.values;
            } else if(v === 'monthly'){
                labels = monthly.labels; values = monthly.values;
            } else {
                labels = yearly.labels; values = yearly.values;
            }
            draw(labels, values);
        }

        document.querySelectorAll('.view-btn').forEach(b => b.addEventListener('click', function(){ setView(this.getAttribute('data-view')); }));
        setView('weekly');
    })();
    </script>
</div>