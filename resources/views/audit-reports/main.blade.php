{{-- resources/views/reports/audit/main.blade.php --}}
    <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 20mm 10mm 20mm 10mm;
            header: page-header;
            footer: page-footer;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #000;
        }

        .header {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #666;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-logo {
            width: 50px;
            height: auto;
            margin-right: 15px;
        }

        .header-text {
            flex: 1;
            text-align: center;
        }

        .header h1 {
            color: #003366;
            font-size: 13pt;
            margin: 0 0 5px 0;
        }

        .header h2 {
            font-size: 10pt;
            margin: 0;
            font-weight: normal;
        }

        .resource-info {
            margin-bottom: 15px;
        }

        .resource-info h3 {
            color: #003366;
            font-size: 10pt;
            margin: 0 0 10px 0;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 3px 0;
            font-size: 8.5pt;
        }

        .info-table td:nth-child(odd) {
            font-weight: bold;
            width: 20%;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .audit-table th {
            background-color: #003366;
            color: #fff;
            padding: 8px;
            text-align: left;
            font-size: 8.5pt;
            font-weight: bold;
        }

        .audit-table td {
            padding: 6px 8px;
            border: 0.5px solid #ccc;
            font-size: 8.5pt;
            word-wrap: break-word;
        }

        .audit-table th,
        .audit-table td {
            vertical-align: top;
        }

        .audit-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 8pt;
            color: #000;
            border-top: 1px solid #666;
            padding-top: 5px;
        }
    </style>
</head>
<body>
<htmlpageheader name="page-header">
    <table width="100%" style="border-bottom: 0.5px solid #666666; margin-bottom: 5px;">
        <tr>
            <td width="15%" style="text-align: left;">
                @if(file_exists(public_path('vega-logo.png')))
                    <img src="{{ public_path('vega-logo.png') }}" style="height: 11mm; width: 22mm;" alt="Logo">
                @endif
            </td>
            <td width="70%" style="text-align: center; vertical-align: middle;">
                <div style="color: #003366; font-weight: bold; font-size: 11pt;">Audit Report</div>
                <div style="color: #000000; font-size: 8pt; margin-top: 2px;">{{ $report->name ?? 'Report' }}</div>
            </td>
            <td width="15%"></td>
        </tr>
    </table>
</htmlpageheader>

<div class="resource-info">
    @if($resource['type'] === 'user')
        @include('audit-reports.user-info', ['resource' => $resource])
    @else
        @include('audit-reports.device-info', ['resource' => $resource])
    @endif
</div>

@if($resource['type'] === 'user')
    <table class="audit-table">
        <thead>
        <tr>
            <th style="width: 5%;">Sr No</th>
            <th style="width: 12%;">Date & Time</th>
            <th style="width: 10%;">Module</th>
            <th style="width: 10%;">Action</th>
            <th style="width: 28%;">Description</th>
            <th style="width: 35%;">Properties</th>
        </tr>
        </thead>
        <tbody>
        @forelse($activities as $activity)
            <tr>
                <td>{{ $activity['sr_no'] }}</td>
                <td>{{ \Carbon\Carbon::parse($activity['date_time'])->format('d-m-Y H:i:s') }}</td>
                <td>{{ $activity['module'] }}</td>
                <td>{{ $activity['action'] }}</td>
                <td>{{ $activity['description'] }}</td>
                <td>{{ $activity['properties'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No activities found for this period</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@else
    <table class="audit-table">
        <thead>
        <tr>
            <th style="width: 5%;">Sr No</th>
            <th style="width: 10%;">Date & Time</th>
            <th style="width: 12%;">User</th>
            <th style="width: 10%;">Action</th>
            <th style="width: 28%;">Description</th>
            <th style="width: 35%;">Properties</th>
        </tr>
        </thead>
        <tbody>
        @forelse($activities as $activity)
            <tr>
                <td>{{ $activity['sr_no'] }}</td>
                <td>{{ \Carbon\Carbon::parse($activity['date_time'])->format('d-m-Y H:i:s') }}</td>
                <td>{{ $activity['user'] }}</td>
                <td>{{ $activity['action'] }}</td>
                <td>{{ $activity['description'] }}</td>
                <td>{{ $activity['properties'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No activities found for this period</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endif

<htmlpagefooter name="page-footer">
    <table width="100%" style="border-top: 0.5px solid #666666; padding-top: 5px; font-size: 8pt;">
        <tr>
            <td width="60%" style="vertical-align: top;">
                <div>
                    <span style="font-weight: normal;">Report Generated by: </span>
                    <span style="color: #003366; font-weight: normal;">{{ $generated_by ?? 'System' }}</span>
                    <span style="font-weight: normal;"> @ {{ now()->format('d-m-Y H:i:s') }}</span>
                </div>
                <div style="margin-top: 3px;">
                    This is a computer-generated report, no signature is required ***
                </div>
            </td>
            <td width="40%" style="text-align: right; vertical-align: top;">
                <div>Page {PAGENO} of {nb}</div>
                <div style="margin-top: 3px;">Copyright © Vega™</div>
            </td>
        </tr>
    </table>
</htmlpagefooter>
</body>
</html>
