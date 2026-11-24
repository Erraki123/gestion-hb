<div class="rounded-lg border border-gray-200 bg-white p-4">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900">Facturé vs encaissé</h3>
        <span class="text-xs text-gray-500">12 derniers mois</span>
    </div>
    <div class="relative h-64"><canvas id="chartMonthly"></canvas></div>
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
                    { label: 'Facturé TTC', data: c.billed, backgroundColor: '#6366F1', borderRadius: 4, maxBarThickness: 18 },
                    { label: 'Encaissé', data: c.collected, backgroundColor: '#10B981', borderRadius: 4, maxBarThickness: 18 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8 } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: 'rgba(17,24,39,.06)' }, border: { display: false } },
                },
            },
        });
    });
</script>