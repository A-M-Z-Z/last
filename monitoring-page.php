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
        /* Dashboard specific styles */
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
            transform: translateY(-5px);
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
        
        .card-info {
            margin-top: 15px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .card-info p {
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
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
            transition: transform 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
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
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .countdown-container {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .countdown-bar {
            width: 100px;
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 3px;
            margin-left: 10px;
            overflow: hidden;
        }
        
        .countdown-progress {
            height: 100%;
            background-color: #4f46e5;
            width: 100%;
            transition: width linear 1s;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .card-content {
                height: 180px;
            }
            
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
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
                <div id="personalStorageInfo" class="card-info"></div>
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
                <div id="diskUsageInfo" class="card-info"></div>
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
                <div id="cpuUsageInfo" class="card-info"></div>
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
                <div id="memoryUsageInfo" class="card-info"></div>
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
                        <div class="metric-value" id="systemUptime">--</div>
                        <div class="metric-label">System Uptime</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="systemStatus">--</div>
                        <div class="metric-label">System Status</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="kernelVersion">--</div>
                        <div class="metric-label">Kernel Version</div>
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
            <div class="countdown-container">
                Auto-refresh: <span id="countdownTimer">10</span>s
                <div class="countdown-bar">
                    <div class="countdown-progress" id="countdownBar"></div>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Charts and data management
    let charts = {};
    let refreshInterval = 10000; // 10 seconds
    let countdownTimer = 10;
    let countdownInterval;
    let isDataLoading = false;
    
    // Initialize the personal storage chart
    function initPersonalStorageChart() {
        const ctx = document.getElementById('personalStorageChart').getContext('2d');
        charts.personalStorage = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Free'],
                datasets: [{
                    data: [0, 100],
                    backgroundColor: [
                        function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            // First segment (Used)
                            if (context.dataIndex === 0) {
                                if (value < 70) return '#22c55e'; // Green - OK
                                if (value < 85) return '#f59e0b'; // Yellow - Warning
                                return '#ef4444'; // Red - Critical
                            }
                            // Second segment (Free)
                            return '#e5e7eb'; // Gray
                        },
                    ],dataset.data[context.dataIndex];
                            // First segment (Used)
                            if (context.dataIndex === 0) {
                                if (value < 70) return '#4f46e5'; // Blue - OK
                                if (value < 90) return '#f59e0b'; // Yellow - Warning
                                return '#ef4444'; // Red - Critical
                            }
                            // Second segment (Free)
                            return '#e5e7eb'; // Gray
                        },
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000
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
                        function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            // First segment (Used)
                            if (context.dataIndex === 0) {
                                if (value < 70) return '#22c55e'; // Green - OK
                                if (value < 85) return '#f59e0b'; // Yellow - Warning
                                return '#ef4444'; // Red - Critical
                            }
                            // Second segment (Free)
                            return '#e5e7eb'; // Gray
                        },
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000
                }
            }
        });
    }
    
    // Initialize the CPU usage chart
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
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw.toFixed(1) + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000
                }
            }
        });
    }
    
    // Initialize the memory usage chart
    function initMemoryUsageChart() {
        const ctx = document.getElementById('memoryUsageChart').getContext('2d');
        charts.memoryUsage = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Cache', 'Free'],
                datasets: [{
                    data: [0, 0, 100],
                    backgroundColor: [
                        '#4f46e5', // Used - Blue
                        '#f59e0b', // Cache - Orange
                        '#e5e7eb'  // Free - Gray
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw.toFixed(0) + ' MB';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000
                }
            }
        });
    }
    
    // Update the temperature gauge
    function updateTemperatureGauge(temperature) {
        const needle = document.getElementById('tempNeedle');
        const value = document.getElementById('tempValue');
        
        if (!needle || !value || temperature === null) {
            if (value) value.textContent = 'N/A';
            return;
        }
        
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
            const used = data.personal.storage.percent;
            const free = 100 - used;
            
            charts.personalStorage.data.datasets[0].data = [used, free];
            charts.personalStorage.data.labels = [`Used (${used.toFixed(1)}%)`, `Free (${free.toFixed(1)}%)`];
            charts.personalStorage.update();
            
            const info = document.getElementById('personalStorageInfo');
            if (info) {
                info.innerHTML = `
                    <p><span>Used:</span> <strong>${data.personal.storage.used.toFixed(2)} MB</strong></p>
                    <p><span>Quota:</span> <strong>${data.personal.storage.quota.toFixed(2)} MB</strong></p>
                    <p><span>Usage:</span> <strong>${data.personal.storage.percent.toFixed(1)}%</strong></p>
                `;
            }
        }
        
        <?php if($isAdmin): ?>
        // Update system disk usage chart
        if (charts.diskUsage && data.system && data.system.disk) {
            // Root disk or primary disk
            const rootDisk = data.system.disk.root;
            if (rootDisk) {
                const usedPercent = parseInt(rootDisk.percent);
                const freePercent = 100 - usedPercent;
                
                charts.diskUsage.data.datasets[0].data = [usedPercent, freePercent];
                charts.diskUsage.data.labels = [`Used (${usedPercent}%)`, `Free (${freePercent}%)`];
                charts.diskUsage.update();
                
                const info = document.getElementById('diskUsageInfo');
                if (info) {
                    info.innerHTML = `
                        <p><span>Mount:</span> <strong>${rootDisk.mount}</strong></p>
                        <p><span>Used:</span> <strong>${rootDisk.used.toFixed(2)} GB</strong></p>
                        <p><span>Free:</span> <strong>${rootDisk.available.toFixed(2)} GB</strong></p>
                        <p><span>Total:</span> <strong>${rootDisk.size.toFixed(2)} GB</strong></p>
                    `;
                }
            } else if (data.system.disk.percent) {
                // Fallback to simple percentage
                const usedPercent = parseInt(data.system.disk.percent);
                const freePercent = 100 - usedPercent;
                
                charts.diskUsage.data.datasets[0].data = [usedPercent, freePercent];
                charts.diskUsage.data.labels = [`Used (${usedPercent}%)`, `Free (${freePercent}%)`];
                charts.diskUsage.update();
            }
        }
        
        // Update CPU temperature
        if (data.system && data.system.temperature !== null) {
            updateTemperatureGauge(data.system.temperature);
        }
        
        // Update CPU usage chart
        if (charts.cpuUsage && data.system && data.system.cpu) {
            // Update doughnut chart with detailed CPU usage breakdown
            charts.cpuUsage.data.datasets[0].data = [
                data.system.cpu.user,                  // User
                data.system.cpu.system,                // System
                data.system.cpu.idle                   // Idle
            ];
            charts.cpuUsage.update();
            
            const info = document.getElementById('cpuUsageInfo');
            if (info) {
                info.innerHTML = `
                    <p><span>Total:</span> <strong>${data.system.cpu.total.toFixed(1)}%</strong></p>
                    <p><span>User:</span> <strong>${data.system.cpu.user.toFixed(1)}%</strong></p>
                    <p><span>System:</span> <strong>${data.system.cpu.system.toFixed(1)}%</strong></p>
                    <p><span>Load (1m):</span> <strong>${data.system.cpu.load_1min.toFixed(1)}%</strong></p>
                `;
            }
        }
        
        // Update memory usage chart
        if (charts.memoryUsage && data.system && data.system.memory) {
            // Get data
            const used = data.system.memory.used;
            const cache = data.system.memory.cache || 0;
            const free = data.system.memory.total - used - cache;
            
            // Update chart
            charts.memoryUsage.data.datasets[0].data = [used, cache, free];
            charts.memoryUsage.update();
            
            const info = document.getElementById('memoryUsageInfo');
            if (info) {
                info.innerHTML = `
                    <p><span>Used:</span> <strong>${used.toFixed(0)} MB</strong> (${data.system.memory.percent.toFixed(1)}%)</p>
                    <p><span>Cache:</span> <strong>${cache.toFixed(0)} MB</strong></p>
                    <p><span>Free:</span> <strong>${free.toFixed(0)} MB</strong></p>
                    <p><span>Total:</span> <strong>${data.system.memory.total.toFixed(0)} MB</strong></p>
                `;
            }
        }
        
        // Update system overview metrics
        if (data.system) {
            // Update user count
            if (data.system.users) {
                document.getElementById('userCount').textContent = data.system.users;
            }
            
            // Update file count
            if (data.system.files) {
                document.getElementById('fileCount').textContent = data.system.files;
            }
            
            // Update total storage
            if (data.system.storage) {
                document.getElementById('totalStorage').textContent = data.system.storage.used.toFixed(2) + ' MB';
            }
            
            // Update uptime
            if (data.system.uptime) {
                document.getElementById('systemUptime').textContent = data.system.uptime;
            }
            
            // Update kernel version
            if (data.system.kernel) {
                document.getElementById('kernelVersion').textContent = data.system.kernel;
            }
            
            // Determine system status based on various metrics
            let status = 'Good';
            let statusClass = 'status-good';
            
            if ((data.system.cpu && data.system.cpu.total > 90) || 
                (data.system.memory && data.system.memory.percent > 90) || 
                (data.system.disk && data.system.disk.percent > 90) || 
                (data.system.temperature && data.system.temperature > 80)) {
                status = 'Critical';
                statusClass = 'status-critical';
            } else if ((data.system.cpu && data.system.cpu.total > 70) || 
                      (data.system.memory && data.system.memory.percent > 70) || 
                      (data.system.disk && data.system.disk.percent > 80) || 
                      (data.system.temperature && data.system.temperature > 60)) {
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
        
        // Reset countdown
        resetCountdown();
    }
    
    // Fetch data from the server
    function fetchData() {
        if (isDataLoading) return; // Prevent multiple simultaneous requests
        
        isDataLoading = true;
        fetch('system_data.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                isDataLoading = false;
                updateCharts(data);
            })
            .catch(error => {
                isDataLoading = false;
                console.error('Error fetching data:', error);
            });
    }
    
    // Start countdown timer
    function startCountdown() {
        countdownTimer = 10;
        const bar = document.getElementById('countdownBar');
        if (bar) bar.style.width = '100%';
        
        // Update countdown display
        updateCountdownDisplay();
        
        // Use requestAnimationFrame for smoother countdown
        let lastTime = Date.now();
        let elapsed = 0;
        
        function animate() {
            const now = Date.now();
            const delta = now - lastTime;
            lastTime = now;
            
            elapsed += delta;
            const progress = Math.min(1, elapsed / refreshInterval);
            
            // Update the countdown bar
            const bar = document.getElementById('countdownBar');
            if (bar) bar.style.width = (100 - progress * 100) + '%';
            
            // Update the countdown timer every second
            const remainingSeconds = Math.ceil((refreshInterval - elapsed) / 1000);
            if (remainingSeconds !== countdownTimer) {
                countdownTimer = remainingSeconds;
                updateCountdownDisplay();
            }
            
            // Continue the animation or fetch new data
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                fetchData();
            }
        }
        
        requestAnimationFrame(animate);
    }
    
    // Update countdown display
    function updateCountdownDisplay() {
        const display = document.getElementById('countdownTimer');
        if (display) display.textContent = countdownTimer;
    }
    
    // Reset countdown
    function resetCountdown() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        startCountdown();
    }
    
    // Initialize charts and start data refresh
    window.addEventListener('DOMContentLoaded', () => {
        // Initialize charts
        initPersonalStorageChart();
        
        <?php if($isAdmin): ?>
        initDiskUsageChart();
        initCpuUsageChart();
        initMemoryUsageChart();
        <?php endif; ?>
        
        // Initial data fetch
        fetchData();
    });
    <?php endif; ?>
    </script>
</body>
</html>
        charts.diskUsage = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Used', 'Free'],
                datasets: [{
                    data: [0, 100],
                    backgroundColor: [
                        function(context) {
                            const value = context.