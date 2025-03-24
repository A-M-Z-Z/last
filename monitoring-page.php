<?php
session_start(); // Start session

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: expired");
    exit();
}

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudBOX - System Monitoring</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .dashboard-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title .icon {
            font-size: 24px;
        }
        
        .card-content {
            height: 200px;
            position: relative;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .metric-card {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #4f46e5;
        }
        
        .metric-label {
            font-size: 14px;
            color: #6b7280;
        }
        
        .temp-gauge {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .gauge {
            width: 200px;
            height: 100px;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .gauge-background {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(0deg, #22c55e 0%, #22c55e 60%, #f59e0b 60%, #f59e0b 80%, #ef4444 80%, #ef4444 100%);
            position: absolute;
            bottom: 0;
        }
        
        .gauge-mask {
            width: 160px;
            height: 160px;
            background: #ffffff;
            border-radius: 50%;
            position: absolute;
            bottom: 0;
            left: 20px;
        }
        
        .gauge-needle {
            width: 4px;
            height: 100px;
            background-color: #1f2937;
            position: absolute;
            bottom: 0;
            left: 98px;
            transform-origin: bottom center;
            transform: rotate(0deg);
            transition: transform 0.5s ease;
        }
        
        .gauge-value {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .gauge-label {
            font-size: 16px;
            color: #6b7280;
        }
        
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .status-good {
            background-color: #22c55e;
        }
        
        .status-warning {
            background-color: #f59e0b;
        }
        
        .status-critical {
            background-color: #ef4444;
        }
        
        .last-updated {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="logo">
            <img src="logo.png" alt="CloudBOX Logo" height="40">
        </div>
        <h1>CloudBOX</h1>
        <div class="search-bar">
            <input type="text" placeholder="Search here...">
        </div>
    </div>
    
    <nav class="dashboard-nav">
        <a href="home">📊 Dashboard</a>
        <a href="drive">📁 My Drive</a>
        <?php if($isAdmin): ?>
        <a href="admin">👑 Admin Panel</a>
        <?php endif; ?>
        <a href="shared">🔄 Shared Files</a>
        <a href="monitoring">📈 Monitoring</a>
        <a href="#">🗑️ Trash</a>
        <a href="logout">🚪 Logout</a>
    </nav>

    <main>
        <h1>System Monitoring</h1>
        
        <div class="dashboard-grid">
            <!-- Personal Storage Usage Card -->
            <div class="dashboard-card">
                <div class="card-title">
                    <span>Your Storage Usage</span>
                    <span class="icon">💾</span>
                </div>
                <div class="card-content">
                    <canvas id="personalStorageChart"></canvas>
                </div>
                <div id="personalStorageInfo"></div>
            </div>
            
            <?php if($isAdmin): ?>
            <!-- System Disk Usage Card -->
            <div class="dashboard-card">
                <div class="card-title">
                    <span>System Disk Usage</span>
                    <span class="icon">💽</span>
                </div>
                <div class="card-content">
                    <canvas id="diskUsageChart"></canvas>
                </div>
                <div id="diskUsageInfo"></div>
            </div>
            
            <!-- CPU Temperature Card -->
            <div class="dashboard-card">
                <div class="card-title">
                    <span>CPU Temperature</span>
                    <span class="icon">🌡️</span>
                </div>
                <div class="card-content">
                    <div class="temp-gauge">
                        <div class="gauge">
                            <div class="gauge-background"></div>
                            <div class="gauge-mask"></div>
                            <div class="gauge-needle" id="tempNeedle"></div>
                        </div>
                        <div class="gauge-value" id="tempValue">--°C</div>
                        <div class="gauge-label">CPU Temperature</div>
                    </div>
                </div>
            </div>
            
            <!-- CPU Usage Card -->
            <div class="dashboard-card">
                <div class="card-title">
                    <span>CPU Usage</span>
                    <span class="icon">⚙️</span>
                </div>
                <div class="card-content">
                    <canvas id="cpuUsageChart"></canvas>
                </div>
                <div id="cpuUsageInfo"></div>
            </div>
            
            <!-- Memory Usage Card -->
            <div class="dashboard-card">
                <div class="card-title">
                    <span>Memory Usage</span>
                    <span class="icon">🧠</span>
                </div>
                <div class="card-content">
                    <canvas id="memoryUsageChart"></canvas>
                </div>
                <div id="memoryUsageInfo"></div>
            </div>
            
            <!-- System Overview Card -->
            <div class="dashboard-card full-width">
                <div class="card-title">
                    <span>System Overview</span>
                    <span class="icon">📊</span>
                </div>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value" id="userCount">--</div>
                        <div class="metric-label">Total Users</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="fileCount">--</div>
                        <div class="metric-label">Total Files</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="totalStorage">--</div>
                        <div class="metric-label">Total Storage Used</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="systemStatus">--</div>
                        <div class="metric-label">System Status</div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Non-admin message -->
            <div class="dashboard-card full-width">
                <div class="card-title">
                    <span>System Information</span>
                    <span class="icon">ℹ️</span>
                </div>
                <p>System-wide monitoring data is only available to administrators.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="last-updated">
            Last updated: <span id="lastUpdated">--</span>
            <div>Auto-refresh: <span id="countdownTimer">10</span>s</div>
        </div>
    </main>

    <script>
    // Charts configuration
    let charts = {};
    let chartData = {};
    let refreshInterval = 10000; // 10 seconds
    let countdownTimer = 10;
    
    // Initialize the personal storage chart
    function initPersonalStorageChart() {
        const ctx = document.getElementById('personalStorageChart').getContext('2d');
        charts.personalStorage = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Free'],
                datasets: [{
                    data: [0, 100],
                    backgroundColor: ['#4f46e5', '#e5e7eb'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    <?php if($isAdmin): ?>
    // Initialize the disk usage chart
    function initDiskUsageChart() {
        const ctx = document.getElementById('diskUsageChart').getContext('2d');
        charts.diskUsage = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Free'],
                datasets: [{
                    data: [0, 100],
                    backgroundColor: [
                        // Color gradient based on usage
                        function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            if (value < 70) return '#22c55e'; // Green
                            if (value < 85) return '#f59e0b'; // Orange
                            return '#ef4444'; // Red
                        },
                        '#e5e7eb' // Gray for free space
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Initialize the CPU usage chart as a doughnut
    function initCpuUsageChart() {
        const ctx = document.getElementById('cpuUsageChart').getContext('2d');
        
        charts.cpuUsage = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['User', 'System', 'Idle'],
                datasets: [{
                    data: [0, 0, 100],
                    backgroundColor: [
                        '#4f46e5', // User - Blue
                        '#f59e0b', // System - Orange
                        '#e5e7eb'  // Idle - Gray
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Initialize the memory usage chart as a doughnut
    function initMemoryUsageChart() {
        const ctx = document.getElementById('memoryUsageChart').getContext('2d');
        charts.memoryUsage = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Cache', 'Available'],
                datasets: [{
                    data: [0, 0, 100],
                    backgroundColor: [
                        '#4f46e5', // Used - Blue
                        '#f59e0b', // Cache - Orange
                        '#e5e7eb'  // Available - Gray
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw.toFixed(0) + ' MB';
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Update the temperature gauge
    function updateTemperatureGauge(temperature) {
        const needle = document.getElementById('tempNeedle');
        const value = document.getElementById('tempValue');
        
        if (!needle || !value) return;
        
        // Update the value display
        value.textContent = temperature + '°C';
        
        // Calculate rotation (0° at 0°C, 180° at 100°C)
        let rotation = Math.min(180, Math.max(0, temperature * 1.8));
        needle.style.transform = `rotate(${rotation}deg)`;
        
        // Update color based on temperature range
        let color;
        if (temperature <= 60) {
            color = '#22c55e'; // Green (good)
        } else if (temperature <= 80) {
            color = '#f59e0b'; // Yellow (warning)
        } else {
            color = '#ef4444'; // Red (critical)
        }
        value.style.color = color;
    }
    
    // Update charts based on data
    function updateCharts(data) {
        // Update personal storage chart
        if (charts.personalStorage && data.personal && data.personal.storage) {
            const used = data.personal.storage.used;
            const quota = data.personal.storage.quota;
            const free = Math.max(0, quota - used);
            const percent = data.personal.storage.percent;
            
            charts.personalStorage.data.datasets[0].data = [used, free];
            charts.personalStorage.update();
            
            const info = document.getElementById('personalStorageInfo');
            if (info) {
                info.innerHTML = `
                    <p>Used: <strong>${used.toFixed(2)} MB</strong> (${percent.toFixed(1)}%)</p>
                    <p>Quota: <strong>${quota.toFixed(2)} MB</strong></p>
                `;
            }
        }
        
        <?php if($isAdmin): ?>
        // Update system disk usage chart
        if (charts.diskUsage && data.system && data.system.disk) {
            const usedPercent = data.system.disk.percent;
            const freePercent = 100 - usedPercent;
            
            charts.diskUsage.data.datasets[0].data = [usedPercent, freePercent];
            charts.diskUsage.update();
            
            const info = document.getElementById('diskUsageInfo');
            if (info) {
                info.innerHTML = `
                    <p>Used: <strong>${usedPercent}%</strong></p>
                    <p>Free: <strong>${freePercent}%</strong></p>
                `;
            }
        }
        
        // Update CPU temperature
        if (data.system && data.system.temperature !== null) {
            updateTemperatureGauge(data.system.temperature);
        }
        
        // Update CPU usage chart
        if (charts.cpuUsage && data.system && data.system.cpu) {
            // Add the new data point (1-minute load)
            chartData.cpu.labels.push('');
            chartData.cpu.datasets[0].data.push(data.system.cpu.load_1min);
            
            // Remove the oldest data point if we have more than 10
            if (chartData.cpu.labels.length > 10) {
                chartData.cpu.labels.shift();
                chartData.cpu.datasets[0].data.shift();
            }
            
            charts.cpuUsage.update();
            
            const info = document.getElementById('cpuUsageInfo');
            if (info) {
                info.innerHTML = `
                    <p>Current: <strong>${data.system.cpu.load_1min.toFixed(1)}%</strong></p>
                    <p>5min avg: <strong>${data.system.cpu.load_5min.toFixed(1)}%</strong></p>
                    <p>15min avg: <strong>${data.system.cpu.load_15min.toFixed(1)}%</strong></p>
                `;
            }
        }
        
        // Update memory usage chart
        if (charts.memoryUsage && data.system && data.system.memory) {
            const used = data.system.memory.used;
            const free = data.system.memory.total - used;
            
            charts.memoryUsage.data.datasets[0].data = [used];
            charts.memoryUsage.data.datasets[1].data = [free];
            charts.memoryUsage.update();
            
            const info = document.getElementById('memoryUsageInfo');
            if (info) {
                info.innerHTML = `
                    <p>Used: <strong>${used} MB</strong> (${data.system.memory.percent.toFixed(1)}%)</p>
                    <p>Total: <strong>${data.system.memory.total} MB</strong></p>
                `;
            }
        }
        
        // Update system overview metrics
        if (data.system) {
            if (data.system.users) {
                document.getElementById('userCount').textContent = data.system.users;
            }
            
            if (data.system.files) {
                document.getElementById('fileCount').textContent = data.system.files;
            }
            
            if (data.system.storage) {
                document.getElementById('totalStorage').textContent = data.system.storage.used.toFixed(2) + ' MB';
            }
            
            // Determine system status based on various metrics
            let status = 'Good';
            let statusClass = 'status-good';
            
            if (data.system.cpu && data.system.cpu.load_1min > 80 || 
                data.system.memory && data.system.memory.percent > 90 || 
                data.system.disk && data.system.disk.percent > 90 || 
                data.system.temperature && data.system.temperature > 80) {
                status = 'Critical';
                statusClass = 'status-critical';
            } else if (data.system.cpu && data.system.cpu.load_1min > 60 || 
                       data.system.memory && data.system.memory.percent > 70 || 
                       data.system.disk && data.system.disk.percent > 80 || 
                       data.system.temperature && data.system.temperature > 60) {
                status = 'Warning';
                statusClass = 'status-warning';
            }
            
            const systemStatus = document.getElementById('systemStatus');
            systemStatus.innerHTML = `<span class="status-indicator ${statusClass}"></span>${status}`;
        }
        <?php endif; ?>
        
        // Update timestamp
        if (data.timestamp) {
            document.getElementById('lastUpdated').textContent = data.timestamp;
        }
    }
    
    // Fetch data from the server
    function fetchData() {
        fetch('system_data.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                updateCharts(data);
                startCountdown();
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
    }
    
    // Countdown timer for next refresh
    function startCountdown() {
        countdownTimer = 10;
        updateCountdown();
        
        const countdownInterval = setInterval(() => {
            countdownTimer--;
            updateCountdown();
            
            if (countdownTimer <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }
    
    function updateCountdown() {
        document.getElementById('countdownTimer').textContent = countdownTimer;
    }
    
    // Initialize charts and start data refresh
    window.addEventListener('DOMContentLoaded', () => {
        initPersonalStorageChart();
        
        <?php if($isAdmin): ?>
        initDiskUsageChart();
        initCpuUsageChart();
        initMemoryUsageChart();
        <?php endif; ?>
        
        // Initial data fetch
        fetchData();
        
        // Set up periodic refresh
        setInterval(fetchData, refreshInterval);
    });
    </script>
</body>
</html>
