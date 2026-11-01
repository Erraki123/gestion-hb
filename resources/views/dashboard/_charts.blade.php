<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="panel p-5 lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="panel-title"><span class="ico"><x-icon name="chart" class="h-4 w-4" /></span> Facturé vs encaissé</h3>
            <span class="text-xs text-gray-500">12 derniers mois</span>
        </div>
        <div class="relative h-72"><canvas id="chartMonthly"></canvas></div>
    </div>

    <div class="panel p-5">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="panel-title"><span class="ico"><x-icon name="receipt" class="h-4 w-4" /></span> Factures par statut</h3>
        </div>
        <div class="relative h-72"><canvas id="chartStatus"></canvas></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.Chart) { return; }
        const c = @js($charts);

        new Chart(document.getElementById('chartMonthly'), {
            type: 'bar',
            data: {
                labels: c.labels,
                datasets: [
                    { label: 'Facturé TTC', data: c.billed, backgroundColor: '#5B574C', borderRadius: 6, maxBarThickness: 18 },
                    { label: 'Encaissé', data: c.collected, backgroundColor: '#C9A227', borderRadius: 6, maxBarThickness: 18 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8 } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.05)' }, border: { display: false } },
                },
            },
        });

        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: c.status_labels,
                datasets: [{ data: c.status_data, backgroundColor: ['#C9A227', '#8A867A', '#4A473F', '#DFBF63'], borderColor: '#151412', borderWidth: 4 }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '68%',
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 } } },
            },
        });
    });
</script>