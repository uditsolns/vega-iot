<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCA Report</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 18mm 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.5;
        }

        /* ── Header ─────────────────────────────────────────────────── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #003366;
            padding-bottom: 6px;
            margin-bottom: 14px;
        }

        .header-logo {
            width: 55px;
            height: auto;
        }

        .header-title {
            color: #003366;
            font-weight: bold;
            font-size: 13pt;
            text-align: center;
            flex: 1;
        }

        /* ── Section heading ─────────────────────────────────────────── */
        .section-title {
            color: #003366;
            font-size: 10.5pt;
            font-weight: bold;
            margin-bottom: 8px;
        }

        /* ── Details table ───────────────────────────────────────────── */
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table tr {
            border-bottom: 1px solid #e0e0e0;
        }

        .details-table tr:last-child {
            border-bottom: 1px solid #ccc;
        }

        .details-table td {
            padding: 7px 10px;
            font-size: 9pt;
            vertical-align: top;
        }

        .details-table td.label {
            font-weight: bold;
            width: 38%;
            background-color: #f5f5f5;
            border-right: 1px solid #e0e0e0;
            border-left: 1px solid #e0e0e0;
        }

        .details-table td.value {
            border-right: 1px solid #e0e0e0;
        }

        .details-table tr:first-child td {
            border-top: 1px solid #ccc;
        }

        /* ── Footer ─────────────────────────────────────────────────── */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #666;
            padding: 5px 12mm;
            font-size: 7.5pt;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>

<div class="page-header">
    <div style="width: 55px;">
        @if(file_exists(public_path('vega-logo.png')))
            <img src="{{ public_path('vega-logo.png') }}" class="header-logo" alt="Vega Logo">
        @endif
    </div>
    <div class="header-title">Alert Report</div>
    <div style="width: 55px;"></div>
</div>

@yield('content')

<div class="page-footer">
    <div>This is a computer-generated report, no signature is required.</div>
    <div>© Vega™</div>
</div>

</body>
</html>
