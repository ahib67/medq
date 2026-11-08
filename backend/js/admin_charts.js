// Admin dashboard charts (Chart.js)
// Step 1: create the three charts empty. Step 2: fill them with data from the server.

var charts = {};

function createCharts() {
    // Chart 1: line chart, tokens per day
    charts.days = new Chart(document.getElementById('daysChart'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{ label: 'Tokens', data: [], borderColor: '#0b5c8f', backgroundColor: '#0b5c8f', tension: 0.3 }]
        },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    // Chart 2: doughnut chart, today's tokens by status
    charts.status = new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{ data: [], backgroundColor: ['#f5a623', '#4a90e2', '#4cd964', '#9b9b9b', '#e74c3c'] }]
        },
        options: { maintainAspectRatio: false }
    });

    // Chart 3: bar chart, tokens per doctor
    charts.doctors = new Chart(document.getElementById('doctorChart'), {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{ label: 'Tokens (30 days)', data: [], backgroundColor: '#0b5c8f' }]
        },
        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
}

// Put new numbers into one chart and redraw it
function fillChart(chart, part) {
    chart.data.labels = part.labels;
    chart.data.datasets[0].data = part.values;
    chart.update();
}

// Ask the server for the numbers, then update all three charts
function refresh() {
    fetch('chart_data.php')
        .then(function (response) { return response.json(); })
        .then(function (data) {
            fillChart(charts.days, data.days);
            fillChart(charts.status, data.status);
            fillChart(charts.doctors, data.doctors);
            document.getElementById('chartTime').textContent = 'Updated at ' + data.time;
        })
        .catch(function () {
            document.getElementById('chartTime').textContent = 'Could not load chart data.';
        });
}

createCharts();
refresh();
setInterval(refresh, 10000);   // every 10 seconds
