function initWeeklyChart(labels, data) {
    const ctx = document.getElementById('weeklyChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Students Present',
                data: data,
                backgroundColor: 'rgba(26,86,219,0.15)',
                borderColor: 'rgba(26,86,219,0.8)',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.parsed.y} students present`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, color: '#718096', font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { color: '#718096', font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
}

function initAttendancePie(present, absent, late) {
    const ctx = document.getElementById('attendancePie');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late'],
            datasets: [{
                data: [present, absent, late],
                backgroundColor: ['rgba(15,157,88,0.8)', 'rgba(217,48,37,0.8)', 'rgba(244,161,0,0.8)'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 12 }, padding: 16, usePointStyle: true }
                }
            }
        }
    });
}
