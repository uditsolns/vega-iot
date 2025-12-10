<?php

return [
    'retention_days' => env('REPORT_RETENTION_DAYS', 7),
    'max_records' => env('REPORT_MAX_RECORDS', 100000),
    'timeout' => env('REPORT_TIMEOUT_SECONDS', 300),

    'intervals' => [
        'hourly' => '1 hour',
        'daily' => '1 day',
        'weekly' => '1 week',
    ],

    'formats' => [
        'json' => 'application/json',
        'csv' => 'text/csv',
        'pdf' => 'application/pdf',
    ],
];
