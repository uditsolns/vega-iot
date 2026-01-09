<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Defines which notification channels are enabled and their configuration.
    |
    */

    "channels" => [
        "email" => [
            "enabled" => true,
            "provider" => "msgclub",
        ],
        "sms" => [
            "enabled" => true,
            "provider" => "msgclub",
        ],
        "voice" => [
            "enabled" => false, // TODO: Enable when voice API is implemented
            "provider" => "msgclub",
        ],
        "push" => [
            "enabled" => false, // TODO: Implement push notifications
            "provider" => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MsgClub Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MsgClub SMS, Email, and Voice services.
    |
    */

    "msgclub" => [
        "auth_key" => env("MSGCLUB_AUTH_KEY"),
        "base_url" => env(
            "MSGCLUB_BASE_URL",
            "http://msg.msgclub.net/rest/services",
        ),

        "sms" => [
            "sender_id" => env("MSGCLUB_SMS_SENDER_ID", "VEGADL"),
            "route_id" => env("MSGCLUB_SMS_ROUTE_ID", "1"),
            "content_type" => "english",
            "timeout" => 30, // seconds
        ],

        "email" => [
            "route_id" => env("MSGCLUB_EMAIL_ROUTE_ID", 15),
            "from_email" => env(
                "MSGCLUB_EMAIL_FROM",
                "support@vegadataloggers.online",
            ),
            "from_name" => env("MSGCLUB_EMAIL_FROM_NAME", "VEGA ENTERPRISES"),
            "display_name" => "VEGA",
            "timeout" => 30, // seconds
        ],

        "voice" => [
            "enabled" => false,
            "timeout" => 30, // seconds
            // TODO: Add voice configuration when API details available
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Queue Settings
    |--------------------------------------------------------------------------
    |
    | Queue names and retry configuration for notification jobs.
    |
    */

    "queue" => [
        "connection" => env("QUEUE_CONNECTION", "database"),
        "alerts" => "alerts",
        "notifications" => "notifications",
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of attempts and backoff times for failed notifications.
    |
    */

    "retry" => [
        "attempts" => 3,
        "backoff" => [10, 30, 60], // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Templates
    |--------------------------------------------------------------------------
    |
    | Templates for different notification types and channels.
    |
    */

    "templates" => [
        "sms" => [
            // Template: "Low and High with Hierarchy Device"
            // Format: Dear {#var#}, {#var#} Alert for {#var#} {#var#}: Device {#var#} detected in {#var#} -> {#var#} -> {#var#} at {#var#}, current value {#var#}, Threshold Limit {#var#}. Immediate action required -VEGA ENTERPRISES
            "alert_triggered" => [
                "id" => "1707175586027788471",
                "content" =>
                    "Dear {name}, {severity} Alert for {sensor_type} {threshold_type}: Device {device_code} detected in {location} -> {hub} -> {area} at {datetime}, current value {value}, Threshold Limit {threshold}. Immediate action required -VEGA ENTERPRISES",
            ],

            // Template: "Back range with hierarchy"
            // Format: Dear {#var#}, Device Back in Range: Device {#var#} detected in {#var#} -> {#var#} -> {#var#} at {#var#}, current value {#var#}, Threshold Limit {#var#}. No action required. -VEGA ENTERPRISES
            "alert_back_in_range" => [
                "id" => "1707175584686154584",
                "content" =>
                    "Dear {name}, Device Back in Range: Device {device_code} detected in {location} -> {hub} -> {area} at {datetime}, current value {value}, Threshold Limit {threshold}. No action required. -VEGA ENTERPRISES",
            ],

            // Using same template as triggered since no specific acknowledged template exists
            "alert_acknowledged" => [
                "id" => "1707175586027788471",
                "content" =>
                    "Dear {name}, {severity} Alert for {sensor_type} {threshold_type}: Device {device_code} detected in {location} -> {hub} -> {area} at {datetime}, current value {value}, Threshold Limit {threshold}. Immediate action required -VEGA ENTERPRISES",
            ],

            // Using same template as triggered since no specific resolved template exists
            "alert_resolved" => [
                "id" => "1707175586027788471",
                "content" =>
                    "Dear {name}, {severity} Alert for {sensor_type} {threshold_type}: Device {device_code} detected in {location} -> {hub} -> {area} at {datetime}, current value {value}, Threshold Limit {threshold}. Immediate action required -VEGA ENTERPRISES",
            ],
        ],

        "voice" => [
            "alert_triggered" =>
                "Dear {name}, Critical Alert: Device {device_code} in {location} at {datetime}. Current value: {value}. Immediate action required.",
        ],
    ],
];
