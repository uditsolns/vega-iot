<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Queue
    |--------------------------------------------------------------------------
    |
    | Queue name for processing notifications
    |
    */
    'queue' => env('NOTIFICATION_QUEUE', 'notifications'),

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific notification channels
    |
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
    |
    | Configuration for MsgClub SMS and Voice provider
    |
    */
    'msgclub' => [
        'auth_key' => env('MSGCLUB_AUTH_KEY', ''),
        'base_url' => env('MSGCLUB_BASE_URL', 'https://api.msgclub.net'),

        'sms' => [
            'sender_id' => env('MSGCLUB_SMS_SENDER_ID', 'VEGAIO'),
            'route_id' => env('MSGCLUB_SMS_ROUTE_ID', '1'),
            'content_type' => env('MSGCLUB_SMS_CONTENT_TYPE', 'unicode'),
            'timeout' => 30,
        ],

        'email' => [
            'route_id' => env('MSGCLUB_EMAIL_ROUTE_ID', '1'),
            'from_email' => env('MSGCLUB_FROM_EMAIL', 'noreply@vegaiot.com'),
            'from_name' => env('MSGCLUB_FROM_NAME', 'VEGA IoT'),
            'display_name' => env('MSGCLUB_DISPLAY_NAME', 'VEGA IoT Monitoring System'),
            'timeout' => 30,
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
    | SMS message templates with placeholders
    |
    */
    'templates' => [
        'sms' => [
            'alert_triggered' => [
                'id' => env('MSGCLUB_TEMPLATE_ALERT_TRIGGERED'),
                'content' => 'ALERT: {severity} alert for device {code} at {location}. Value: {value}. Threshold: {threshold}. Action required.',
            ],
            'alert_acknowledged' => [
                'id' => env('MSGCLUB_TEMPLATE_ALERT_ACKNOWLEDGED'),
                'content' => 'Alert acknowledged for device {code} at {location}. Alert is being monitored.',
            ],
            'alert_resolved' => [
                'id' => env('MSGCLUB_TEMPLATE_ALERT_RESOLVED'),
                'content' => 'Alert resolved for device {code} at {location}. Issue has been addressed.',
            ],
            'alert_back_in_range' => [
                'id' => env('MSGCLUB_TEMPLATE_ALERT_BACK_IN_RANGE'),
                'content' => 'Device {code} at {location} is back in normal range. Alert auto-resolved.',
            ],
        ],

        'voice' => [
            'alert_triggered' => 'Critical Alert. Device {code} at {location}. Current value {value}. Immediate action required.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Preferences
    |--------------------------------------------------------------------------
    |
    | Default notification preferences for new users
    |
    */
    'preferences' => [
        'default' => [
            'email' => true,
            'sms' => false,
            'voice' => false,
            'push' => false,
            'database' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for notifications per channel
    |
    */
    'rate_limits' => [
        'sms' => [
            'max_per_user_per_hour' => 10,
            'max_per_alert_per_hour' => 5,
        ],
        'voice' => [
            'max_per_user_per_hour' => 3,
            'max_per_alert_per_hour' => 2,
        ],
        'email' => [
            'max_per_user_per_hour' => 50,
        ],
    ],
];
