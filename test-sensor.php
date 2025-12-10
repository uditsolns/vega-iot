#!/usr/bin/env php
<?php

/**
 * Sensor Data Simulator
 * Connects to TCP server and sends sample sensor data
 * Usage: php test-sensor.php <host> <port> [device_id]
 */

if ($argc < 3) {
    echo "Usage: php test-sensor.php <host> <port> [device_id]\n";
    echo "Example: php test-sensor.php 405367.hstgr.cloud 5000 6020250001\n";
    exit(1);
}

$host = $argv[1];
$port = (int)$argv[2];
$deviceId = $argv[3] ?? '6020250001';

// Create socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    die("socket_create() failed: " . socket_strerror(socket_last_error()) . "\n");
}

echo "Connecting to $host:$port...\n";

// Connect to server
$result = socket_connect($socket, $host, $port);
if ($result === false) {
    die("socket_connect() failed: " . socket_strerror(socket_last_error()) . "\n");
}

echo "Connected! Sending sensor data...\n";

// Create sample sensor data matching the protocol
$sensorData = [
    'ID' => $deviceId,
    'TYPE' => 'WIFI-T&H',
    'SN' => '074',
    'TEMP' => 24.55,
    'HUM' => '68.5%',
    'ST' => '5M',
    'STATUS' => '10000001',
    'ALM' => '2',
    'VOL' => '3.74V',
    'TIME' => date('Y-m-d H:i:s'),
];

$json = json_encode($sensorData);

echo "Sending: $json\n";

// Send data
$bytes = socket_write($socket, $json);
if ($bytes === false) {
    die("socket_write() failed: " . socket_strerror(socket_last_error()) . "\n");
}

echo "Sent $bytes bytes\n";

// Wait a bit for server processing
sleep(1);

// Close socket
socket_close($socket);

echo "Connection closed\n";
echo "Check TCP server logs to verify data was received and processed\n";
