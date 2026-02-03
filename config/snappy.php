<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Snappy PDF / Image Configuration
    |--------------------------------------------------------------------------
    |
    | This option contains settings for PDF generation.
    |
    | Enabled:
    |
    |    Whether to load PDF / Image generation.
    |
    | Binary:
    |
    |    The file path of the wkhtmltopdf / wkhtmltoimage executable.
    |
    | Timeout:
    |
    |    The amount of time to wait (in seconds) before PDF / Image generation is stopped.
    |    Setting this to false disables the timeout (unlimited processing time).
    |
    | Options:
    |
    |    The wkhtmltopdf command options. These are passed directly to wkhtmltopdf.
    |    See https://wkhtmltopdf.org/usage/wkhtmltopdf.txt for all options.
    |
    | Env:
    |
    |    The environment variables to set while running the wkhtmltopdf process.
    |
    */

    'pdf' => [
        'enabled' => true,
        'binary'  => env('WKHTML_PDF_BINARY'),
        'timeout' => false,

        // Default options for all PDFs
        'options' => [
            // Security
            'enable-local-file-access' => true,

            // Encoding
            'encoding' => 'UTF-8',

            // Quality settings
            'lowquality' => false,
            'dpi' => 96,
            'image-dpi' => 96,
            'image-quality' => 94,

            // Print media type (important for charts)
            'print-media-type' => true,

            // JavaScript (disable for performance unless needed)
            'enable-javascript' => false,
            'javascript-delay' => 0,

            // Disable smart shrinking (better for data tables)
            'disable-smart-shrinking' => false,

            // Quiet mode (reduce console output)
            'quiet' => true,
        ],

        // Environment variables
        'env' => [
            // Prevent Qt warnings
            'QT_QPA_PLATFORM' => 'offscreen',
        ],
//        'env'     => [],
    ],

    'image' => [
        'enabled' => true,
        'binary'  => env('WKHTML_IMG_BINARY'),
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],

];
