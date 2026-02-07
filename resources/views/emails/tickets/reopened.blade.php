<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Reopened</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #FFC107; color: #333; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; margin: 20px 0; }
        .ticket-info { background-color: white; padding: 15px; margin: 10px 0; border-left: 4px solid #FFC107; }
        .info-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .info-table td { padding: 8px; border-bottom: 1px solid #e0e0e0; }
        .info-table td:first-child { font-weight: bold; width: 40%; color: #555; }
        .warning-message { background-color: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .button { background-color: #FFC107; color: #333; padding: 12px 24px; text-decoration: none; display: inline-block; margin: 10px 0; border-radius: 4px; }
        .footer { text-align: center; color: #777; font-size: 12px; padding: 20px; border-top: 2px solid #e0e0e0; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔄 Ticket Reopened</h1>
    </div>

    <div class="content">
        <p>Dear {{ $user->first_name }},</p>

        <div class="warning-message">
            <strong>Note:</strong> This ticket has been reopened and requires further attention.
        </div>

        <div class="ticket-info">
            <h2>Ticket #{{ $ticket->id }}</h2>
            <table class="info-table">
                <tr>
                    <td>Subject:</td>
                    <td>{{ $ticket->subject }}</td>
                </tr>
                <tr>
                    <td>Priority:</td>
                    <td>{{ ucfirst($ticket->priority->value) }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td><strong style="color: #FFC107;">{{ ucfirst($ticket->status->value) }}</strong></td>
                </tr>
                @if($ticket->assignedTo)
                    <tr>
                        <td>Assigned To:</td>
                        <td>{{ $ticket->assignedTo->first_name }} {{ $ticket->assignedTo->last_name }}</td>
                    </tr>
                @endif
                @if($ticket->device)
                    <tr>
                        <td>Related Device:</td>
                        <td>{{ $ticket->device->device_code }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <p><strong>Original Description:</strong></p>
        <div style="background-color: #e9ecef; padding: 15px; border-radius: 4px; margin: 10px 0;">
            {{ $ticket->description }}
        </div>

        <p>Our support team will review this ticket and provide assistance as soon as possible.</p>

        <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}" class="button">View Ticket</a>
    </div>

    <div class="footer">
        <p>This is an automated message from {{ config('app.name') }}</p>
        <p>&copy; {{ date('Y') }} VEGA ENTERPRISES. All rights reserved.</p>
    </div>
</div>
</body>
</html>
