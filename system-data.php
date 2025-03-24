<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Database Connection
$host = '91.216.107.164';
$user = 'amzz2427862';
$pass = '37qB5xqen4prX8@';
$dbname = 'amzz2427862';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Data collection
$data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'system' => [],
    'personal' => []
];

// System information (only available to admins)
if ($isAdmin) {
    /**
     * CPU Usage Collection
     */
    $cpuData = [];
    
    // Try to get CPU usage from /proc/stat
    if (file_exists('/proc/stat')) {
        $stat1 = file('/proc/stat');
        // Sleep briefly to measure CPU over time
        usleep(100000); // 100ms
        $stat2 = file('/proc/stat');
        
        if ($stat1 && $stat2) {
            // Get CPU line
            $cpu1 = explode(' ', preg_replace('/\s+/', ' ', $stat1[0]));
            $cpu2 = explode(' ', preg_replace('/\s+/', ' ', $stat2[0]));
            
            // Calculate jiffies
            $user1 = $cpu1[1] + $cpu1[2]; // user + nice
            $system1 = $cpu1[3]; // system
            $idle1 = $cpu1[4]; // idle
            $iowait1 = isset($cpu1[5]) ? $cpu1[5] : 0; // iowait
            $total1 = $user1 + $system1 + $idle1 + $iowait1;
            
            $user2 = $cpu2[1] + $cpu2[2]; // user + nice
            $system2 = $cpu2[3]; // system
            $idle2 = $cpu2[4]; // idle
            $iowait2 = isset($cpu2[5]) ? $cpu2[5] : 0; // iowait
            $total2 = $user2 + $system2 + $idle2 + $iowait2;
            
            // Calculate difference
            $totalDiff = $total2 - $total1;
            if ($totalDiff > 0) {
                $userPercent = round(($user2 - $user1) * 100 / $totalDiff, 1);
                $systemPercent = round(($system2 - $system1) * 100 / $totalDiff, 1);
                $ioWaitPercent = round(($iowait2 - $iowait1) * 100 / $totalDiff, 1);
                $idlePercent = round(($idle2 - $idle1) * 100 / $totalDiff, 1);
                
                $cpuData = [
                    'user' => $userPercent,
                    'system' => $systemPercent,
                    'iowait' => $ioWaitPercent,
                    'idle' => $idlePercent,
                    'total' => $userPercent + $systemPercent + $ioWaitPercent
                ];
            }
        }
    }
    
    // Fallback to load average
    if (empty($cpuData)) {
        $load = sys_getloadavg();
        $cpuCores = intval(shell_exec('nproc')) ?: 1;
        $cpuLoad = round($load[0] * 100 / $cpuCores, 1);
        
        $cpuData = [
            'user' => round($cpuLoad * 0.7, 1),     // Estimate user CPU usage
            'system' => round($cpuLoad * 0.3, 1),   // Estimate system CPU usage
            'iowait' => 0,
            'idle' => max(0, 100 - $cpuLoad),
            'total' => $cpuLoad
        ];
    }
    
    // Add load averages
    $load = sys_getloadavg();
    $cpuCores = intval(shell_exec('nproc')) ?: 1;
    $cpuData['load_1min'] = round($load[0] * 100 / $cpuCores, 1);
    $cpuData['load_5min'] = round($load[1] * 100 / $cpuCores, 1);
    $cpuData['load_15min'] = round($load[2] * 100 / $cpuCores, 1);
    
    $data['system']['cpu'] = $cpuData;
    
    /**
     * Memory Usage Collection
     */
    $memData = [];
    
    // Try to get memory info from /proc/meminfo
    if (file_exists('/proc/meminfo')) {
        $meminfo = file_get_contents('/proc/meminfo');
        if ($meminfo) {
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $totalMatches);
            preg_match('/MemFree:\s+(\d+)/', $meminfo, $freeMatches);
            preg_match('/Buffers:\s+(\d+)/', $meminfo, $buffersMatches);
            preg_match('/Cached:\s+(\d+)/', $meminfo, $cachedMatches);
            preg_match('/SReclaimable:\s+(\d+)/', $meminfo, $reclaimableMatches);
            
            if (!empty($totalMatches)) {
                $totalKb = intval($totalMatches[1]);
                $freeKb = intval($freeMatches[1] ?? 0);
                $buffersKb = intval($buffersMatches[1] ?? 0);
                $cachedKb = intval($cachedMatches[1] ?? 0);
                $reclaimableKb = intval($reclaimableMatches[1] ?? 0);
                
                // Calculate used memory (excluding cache & buffers)
                $usedKb = $totalKb - $freeKb - $buffersKb - $cachedKb - $reclaimableKb;
                $cacheKb = $buffersKb + $cachedKb + $reclaimableKb;
                
                $memData = [
                    'total' => round($totalKb / 1024, 0),       // MB
                    'used' => round($usedKb / 1024, 0),         // MB
                    'cache' => round($cacheKb / 1024, 0),       // MB
                    'free' => round($freeKb / 1024, 0),         // MB
                    'percent' => round($usedKb * 100 / $totalKb, 1)
                ];
            }
        }
    }
    
    // Fallback to free command
    if (empty($memData)) {
        $meminfo = shell_exec('free -m');
        if ($meminfo) {
            preg_match('/^Mem:\s+(\d+)\s+(\d+)\s+(\d+)/m', $meminfo, $matches);
            if (!empty($matches)) {
                $totalMem = intval($matches[1]);
                $usedMem = intval($matches[2]);
                $freeMem = intval($matches[3]);
                
                // Estimate cache (typically around 30% of used memory on average systems)
                $cacheMem = round($usedMem * 0.3);
                $realUsedMem = $usedMem - $cacheMem;
                
                $memData = [
                    'total' => $totalMem,
                    'used' => $realUsedMem,
                    'cache' => $cacheMem,
                    'free' => $freeMem,
                    'percent' => round($realUsedMem * 100 / $totalMem, 1)
                ];
            }
        }
    }
    
    $data['system']['memory'] = $memData;
    
    /**
     * Disk Usage Collection
     */
    $diskData = ['disks' => []];
    
    // Get disk usage with df
    $dfOutput = shell_exec('df -B1');
    if ($dfOutput) {
        preg_match_all('/^(\/dev\/\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%\s+(.+)$/m', $dfOutput, $matches, PREG_SET_ORDER);
        
        $totalSize = 0;
        $totalUsed = 0;
        $rootDisk = null;
        
        foreach ($matches as $match) {
            if (strpos($match[6], '/') === 0) { // Only include actual mounted filesystems
                $mountPoint = $match[6];
                $size = round($match[2] / (1024 * 1024 * 1024), 2); // GB
                $used = round($match[3] / (1024 * 1024 * 1024), 2); // GB
                $available = round($match[4] / (1024 * 1024 * 1024), 2); // GB
                $percent = $match[5];
                
                $disk = [
                    'mount' => $mountPoint,
                    'size' => $size,
                    'used' => $used,
                    'available' => $available,
                    'percent' => $percent
                ];
                
                $diskData['disks'][] = $disk;
                
                // Track root filesystem
                if ($mountPoint === '/') {
                    $rootDisk = $disk;
                }
                
                $totalSize += $size;
                $totalUsed += $used;
            }
        }
        
        $diskData['total_size'] = $totalSize;
        $diskData['total_used'] = $totalUsed;
        $diskData['total_percent'] = $totalSize > 0 ? round(($totalUsed / $totalSize) * 100, 1) : 0;
        
        // If no root disk found, use the first one
        if ($rootDisk === null && !empty($diskData['disks'])) {
            $rootDisk = $diskData['disks'][0];
        }
        
        if ($rootDisk) {
            $diskData['root'] = $rootDisk;
            $diskData['percent'] = $rootDisk['percent']; // For backwards compatibility
        }
    }
    
    $data['system']['disk'] = $diskData;
    
    /**
     * CPU Temperature Collection
     */
    $temperature = null;
    
    // Try Raspberry Pi temperature sensor
    if (file_exists('/sys/class/thermal/thermal_zone0/temp')) {
        $temp = intval(file_get_contents('/sys/class/thermal/thermal_zone0/temp'));
        $temperature = round($temp / 1000, 1);
    }
    
    // Try sensors command (for general Linux systems)
    if ($temperature === null) {
        $sensorsOutput = shell_exec('sensors 2>/dev/null');
        if ($sensorsOutput) {
            // Look for CPU temperature in output
            if (preg_match('/Core 0.*?\+(\d+\.\d+)°C/', $sensorsOutput, $matches)) {
                $temperature = floatval($matches[1]);
            } elseif (preg_match('/CPU Temperature.*?\+(\d+\.\d+)°C/', $sensorsOutput, $matches)) {
                $temperature = floatval($matches[1]);
            } elseif (preg_match('/temp1.*?\+(\d+\.\d+)°C/', $sensorsOutput, $matches)) {
                $temperature = floatval($matches[1]);
            }
        }
    }
    
    $data['system']['temperature'] = $temperature;
    
    /**
     * System Information
     */
    // Get uptime
    $uptime = shell_exec('uptime -p');
    $data['system']['uptime'] = $uptime ? trim($uptime) : null;
    
    // Get kernel version
    $kernel = shell_exec('uname -r');
    $data['system']['kernel'] = $kernel ? trim($kernel) : null;
    
    // Get hostname
    $hostname = shell_exec('hostname');
    $data['system']['hostname'] = $hostname ? trim($hostname) : null;
    
    // Database stats
    $userCountQuery = "SELECT COUNT(*) as count FROM users";
    $result = $conn->query($userCountQuery);
    $row = $result->fetch_assoc();
    $data['system']['users'] = $row['count'];
    
    $fileCountQuery = "SELECT COUNT(*) as count FROM files";
    $result = $conn->query($fileCountQuery);
    $row = $result->fetch_assoc();
    $data['system']['files'] = $row['count'];
    
    $totalStorageQuery = "SELECT SUM(file_size) as total_size FROM files";
    $result = $conn->query($totalStorageQuery);
    $row = $result->fetch_assoc();
    $totalStorageUsed = $row['total_size'] ?: 0;
    $data['system']['storage'] = [
        'used' => round($totalStorageUsed / (1024 * 1024), 2) // MB
    ];
}

/**
 * Personal Storage Usage (available to all users)
 */
$userStorageQuery = $conn->prepare("SELECT SUM(file_size) as total_size FROM files WHERE user_id = ?");
$userStorageQuery->bind_param("i", $userId);
$userStorageQuery->execute();
$result = $userStorageQuery->get_result();
$row = $result->fetch_assoc();
$userStorageUsed = $row['total_size'] ?: 0;

// Get user's quota
$quotaQuery = $conn->prepare("SELECT storage_quota FROM users WHERE id = ?");
$quotaQuery->bind_param("i", $userId);
$quotaQuery->execute();
$result = $quotaQuery->get_result();
$row = $result->fetch_assoc();
$userQuota = $row['storage_quota'] ?: 104857600; // 100MB default

$data['personal']['storage'] = [
    'used' => round($userStorageUsed / (1024 * 1024), 2), // MB
    'quota' => round($userQuota / (1024 * 1024), 2), // MB
    'percent' => round(($userStorageUsed / $userQuota) * 100, 2)
];

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($data);
