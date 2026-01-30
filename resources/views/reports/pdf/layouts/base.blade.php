<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $data['report_name'] ?? 'Report' }}</title>
    <style>
        @page {
            margin: 25mm 10mm 20mm 10mm;
            header: page-header;
            footer: page-footer;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        .section-title {
            color: #003366;
            font-weight: bold;
            font-size: 10pt;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .subsection-title {
            color: #003366;
            font-weight: bold;
            font-size: 11pt;
            margin-top: 8px;
            margin-bottom: 5px;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 10px;
        }

        .info-left {
            width: 38%;
            float: left;
        }

        .info-right {
            width: 58%;
            float: right;
        }

        .info-row {
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .label {
            font-weight: bold;
            display: inline;
        }

        .value {
            font-weight: normal;
            display: inline;
        }

        .divider {
            border-top: 0.5px solid #666666;
            margin: 8px 0;
            clear: both;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Table Styles */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th {
            background-color: #003366;
            color: #ffffff;
            font-weight: bold;
            font-size: 8pt;
            padding: 5px;
            text-align: center;
            border: 0.5px solid #bebebe;
        }

        table.data-table td {
            font-size: 6pt;
            padding: 3px 5px;
            text-align: center;
            border: 0.5px solid #bebebe;
        }

        /* Color coding for values */
        .critical {
            color: #ff0000;
            font-weight: bold;
        }

        .warning {
            color: #ff8c00;
            font-weight: bold;
        }

        .normal {
            color: #000000;
        }

        /* Chart container */
        .chart-container {
            margin-top: 10px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
@yield('content')
</body>
</html>
