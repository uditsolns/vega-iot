<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Queue
    |--------------------------------------------------------------------------
    */
    'queue' => env('NOTIFICATION_QUEUE', 'notifications'),

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    */
    'channels' => [
        'email' => [
            'enabled' => env('NOTIFICATION_EMAIL_ENABLED', true),
        ],
        'sms' => [
            'enabled' => env('NOTIFICATION_SMS_ENABLED', true),
        ],
        'voice' => [
            'enabled' => env('NOTIFICATION_VOICE_ENABLED', false),
        ],
        'push' => [
            'enabled' => env('NOTIFICATION_PUSH_ENABLED', false),
        ],
        'database' => [
            'enabled' => env('NOTIFICATION_DATABASE_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MsgClub Configuration
    |--------------------------------------------------------------------------
    */
    'msgclub' => [
        'auth_key' => env('MSGCLUB_AUTH_KEY', ''),
        'base_url' => env('MSGCLUB_BASE_URL', 'https://api.msgclub.net'),

        'sms' => [
            'sender_id'    => env('MSGCLUB_SMS_SENDER_ID', 'VEGAIO'),
            'route_id'     => env('MSGCLUB_SMS_ROUTE_ID', '1'),
            'content_type' => env('MSGCLUB_SMS_CONTENT_TYPE', 'unicode'),
            'timeout'      => 30,
        ],

        'email' => [
            'route_id'     => env('MSGCLUB_EMAIL_ROUTE_ID', '1'),
            'from_email'   => env('MSGCLUB_FROM_EMAIL', 'noreply@vegaiot.com'),
            'from_name'    => env('MSGCLUB_FROM_NAME', 'VEGA IoT'),
            'display_name' => env('MSGCLUB_DISPLAY_NAME', 'VEGA IoT Monitoring System'),
            'timeout'      => 30,
        ],

        'voice' => [
            'timeout' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Templates
    |--------------------------------------------------------------------------
    |
    | Available placeholders:
    |   {severity}   — warning | critical
    |   {code}       — device code e.g. ACME-DEV-0001
    |   {sensor}     — sensor label e.g. "Temp Internal"
    |   {value}      — current reading e.g. 36.4
    |   {threshold}  — threshold value that was breached e.g. 35.0
    |   {location}   — Location > Hub > Area path
    |   {user}       — name of the user who acted (ack/resolve)
    */
    'templates' => [
        'sms' => [
            'alert_triggered' => [
                'id'      => env('MSGCLUB_TEMPLATE_ALERT_TRIGGERED'),
                'content' => '{severity} ALERT: Device {code}, Sensor: {sensor}. Value: {value}, Threshold: {threshold}. Location: {location}.',
            ],
            'alert_acknowledged' => [
                'id'      => env('MSGCLUB_TEMPLATE_ALERT_ACKNOWLEDGED'),
                'content' => 'Alert acknowledged by {user} for device {code}, Sensor: {sensor}. Location: {location}.',
            ],
            'alert_resolved' => [
                'id'      => env('MSGCLUB_TEMPLATE_ALERT_RESOLVED'),
                'content' => 'Alert resolved by {user} for device {code}, Sensor: {sensor}. Location: {location}.',
            ],
            'alert_back_in_range' => [
                'id'      => env('MSGCLUB_TEMPLATE_ALERT_BACK_IN_RANGE'),
                'content' => 'Device {code}, Sensor: {sensor} is back in normal range (current: {value}). Location: {location}.',
            ],
        ],

        'voice' => [
            'alert_triggered' => 'Critical Alert. Device {code} at {location}. Sensor {sensor} reads {value}. Immediate action required.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Preferences
    |--------------------------------------------------------------------------
    */
    'preferences' => [
        'default' => [
            'email'    => true,
            'sms'      => false,
            'voice'    => false,
            'push'     => false,
            'database' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'sms' => [
            'max_per_user_per_hour'  => 10,
            'max_per_alert_per_hour' => 5,
        ],
        'voice' => [
            'max_per_user_per_hour'  => 3,
            'max_per_alert_per_hour' => 2,
        ],
        'email' => [
            'max_per_user_per_hour' => 50,
        ],
    ],
];
