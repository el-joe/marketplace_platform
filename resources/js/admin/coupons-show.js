import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('coupon-usage-chart');
    if (!ctx || !window.COUPON_DAILY_REDEMPTIONS) return;

    const daily = window.COUPON_DAILY_REDEMPTIONS;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(daily),
            datasets: [{
                label: window.TRANSLATIONS?.dailyRedemptions || 'Daily Redemptions',
                data: Object.values(daily),
                backgroundColor: '#3b82f6',
            }],
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } },
        },
    });
});
