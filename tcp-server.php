#!/usr/bin/env php
<?php

/**
 * IoT Sensor TCP Server
 * Listens for sensor data and forwards to Laravel API
 * Usage: php tcp-server.php
 */

// Configuration
$HOST = '0.0.0.0';
$PORT = 5000;
$LARAVEL_API = 'http://405367.hstgr.cloud/api/v1/ingest';
//$LARAVEL_API = 'http://localhost:8000/api/v1/ingest';
$LOG_FILE = '/var/www/vega-iot/storage/logs/tcp-server.log';

// Create socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    die("socket_create() failed: " . socket_strerror(socket_last_error()) . "\n");
}

// Reuse address to avoid "Address already in use" errors
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

// Bind socket
if (!socket_bind($socket, $HOST, $PORT)) {
    die("socket_bind() failed: " . socket_strerror(socket_last_error()) . "\n");
}

// Listen for connections
if (!socket_listen($socket, 5)) {
    die("socket_listen() failed: " . socket_strerror(socket_last_error()) . "\n");
}

logMessage("TCP Server started on $HOST:$PORT");
logMessage("Forwarding to: $LARAVEL_API");
logMessage("---");

// Accept connections in a loop
while (true) {
    $client = socket_accept($socket);

    if ($client === false) {
        logMessage("socket_accept() failed: " . socket_strerror(socket_last_error()));
        continue;
    }

    // Get client info
    socket_getpeername($client, $remote_host, $remote_port);
    logMessage("Client connected from: $remote_host:$remote_port");

    // Read data from socket
    $data = '';
    while (true) {
        $chunk = socket_read($client, 4096);
        if ($chunk === false || $chunk === '') {
            break;
        }
        $data .= $chunk;
    }

    if (empty($data)) {
        logMessage("No data received from client");
        socket_close($client);
        continue;
    }

    logMessage("Raw data received: " . substr($data, 0, 200));

    // Parse JSON
    $json = json_decode(trim($data), true);
    if ($json === null) {
        logMessage("ERROR: Invalid JSON received");
        socket_close($client);
        continue;
    }

    logMessage("Parsed JSON. Device ID: " . ($json['ID'] ?? 'unknown'));

    // Transform data for Laravel API
    $transformed = transformSensorData($json);

    // Send to Laravel API
    $response = sendToLaravel($transformed, $LARAVEL_API);

    if ($response['success']) {
        logMessage("SUCCESS: Data forwarded to Laravel API");
        logMessage("Response: " . json_encode($response['data']));
    } else {
        logMessage("ERROR: Failed to send to Laravel API - " . $response['error']);
    }

    logMessage("---");

    // Close client connection
    socket_close($client);
}

socket_close($socket);

/**
 * Transform sensor data to Laravel API format
 */
function transformSensorData(array $sensorData): array
{
    // Map sensor fields to API fields
    $transformed = [
        'ID' => $sensorData['ID'] ?? null,
        'temperature' => isset($sensorData['TEMP']) ? floatval($sensorData['TEMP']) : null,
        'humidity' => isset($sensorData['HUM']) ? floatval(str_replace('%', '', $sensorData['HUM'])) : null,
        'battery_voltage' => parseBatteryVoltage($sensorData['VOL'] ?? null),
        'recorded_at' => parseTimestamp($sensorData['TIME'] ?? null),
    ];

    // Add raw payload for debugging
    $transformed['raw_payload'] = $sensorData;

    return $transformed;
}

/**
 * Parse battery voltage from format like "3.74V"
 */
function parseBatteryVoltage(?string $voltage): ?float
{
    if (!$voltage) {
        return null;
    }

    // Extract numeric value from "3.74V" or "3.74"
    preg_match('/(\d+\.?\d*)/', $voltage, $matches);
    return isset($matches[1]) ? floatval($matches[1]) : null;
}

/**
 * Parse timestamp from sensor format to ISO 8601
 * Sensor sends: "2024-07-04 10:21:28"
 */
function parseTimestamp(?string $timestamp): ?string
{
    if (!$timestamp) {
        return null;
    }

    try {
        $dt = new DateTime($timestamp);
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        logMessage("ERROR: Failed to parse timestamp '$timestamp': " . $e->getMessage());
        return null;
    }
}

/**
 * Send data to Laravel API
 */
function sendToLaravel(array $data, string $apiUrl): array
{
    $jsonData = json_encode($data);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData),
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'error' => "cURL error: $error",
        ];
    }

    if ($httpCode !== 201 && $httpCode !== 200) {
        return [
            'success' => false,
            'error' => "HTTP $httpCode - $response",
        ];
    }

    return [
        'success' => true,
        'data' => json_decode($response, true),
    ];
}

/**
 * Log message to file and console
 */
function logMessage(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";

    echo $logEntry . "\n";

    $logFile = '/var/www/vega-iot/storage/logs/tcp-server.log';
    @file_put_contents($logFile, $logEntry . "\n", FILE_APPEND);
}
