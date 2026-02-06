{{-- resources/views/emails/scheduled-reports/report.blade.php --}}
    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4F46E5; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9fafb; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
        .device-list { list-style: none; padding: 0; }
        .device-item { padding: 8px; margin: 4px 0; background: white; border-left: 3px solid #4F46E5; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ $scheduledReport->name }}</h1>
    </div>

    <div class="content">
        <p>Hello {{ $user->name }},</p>

        <p>Please find attached the scheduled reports for the following devices:</p>

        <ul class="device-list">
            @foreach($reports as $report)
                <li class="device-item">
                    <strong>{{ $report['device_code'] }}</strong>
                    @if($report['device_name'] !== $report['device_code'])
                        - {{ $report['device_name'] }}
                    @endif
                </li>
            @endforeach
        </ul>

        <p><strong>Report Details:</strong></p>
        <ul>
            <li>Frequency: {{ $scheduledReport->frequency->label() }}</li>
            <li>Format: {{ $scheduledReport->format->label() }}</li>
            <li>Data Formation: {{ $scheduledReport->data_formation->label() }}</li>
            <li>Generated: {{ now()->format('d M Y, H:i') }}</li>
        </ul>
    </div>

    <div class="footer">
        <p>This is an automated email. Please do not reply.</p>
    </div>
</div>
</body>
</html>
