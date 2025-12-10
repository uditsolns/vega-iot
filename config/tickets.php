<?php

return [
    'max_attachments' => env('TICKET_MAX_ATTACHMENTS', 5),
    'max_file_size' => env('TICKET_MAX_FILE_SIZE', 10240), // KB
    'allowed_mime_types' => explode(',', env('TICKET_ALLOWED_MIME_TYPES', 'jpg,jpeg,png,pdf,doc,docx,txt')),

    'status_colors' => [
        'open' => '#17A2B8',
        'in_progress' => '#FFC107',
        'waiting_on_customer' => '#6C757D',
        'resolved' => '#28A745',
        'closed' => '#343A40',
    ],

    'priority_colors' => [
        'low' => '#6C757D',
        'medium' => '#FFC107',
        'high' => '#FD7E14',
        'critical' => '#DC3545',
    ],
];
