<div class="h-64">
    <canvas id="basket-value-chart-{{ $getRecord()->id }}"></canvas>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('basket-value-chart-{{ $getRecord()->id }}').getContext('2d');
    
    fetch('/api/v2/baskets/{{ $getRecord()->code }}/history?days=30')
        .then(response => response.json())
        .then(data => {
            const labels = data.values.map(v => new Date(v.calculated_at).toLocaleDateString());
            const values = data.values.map(v => v.value);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Basket Value (USD)',
                        data: values,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading basket value history:', error));
});
</script>
@endpush