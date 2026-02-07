<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Resolved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; margin: 20px 0; }
        .ticket-info { background-color: white; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
        .info-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .info-table td { padding: 8px; border-bottom: 1px solid #e0e0e0; }
        .info-table td:first-child { font-weight: bold; width: 40%; color: #555; }
        .success-message { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .button { background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; display: inline-block; margin: 10px 0; border-radius: 4px; }
        .footer { text-align: center; color: #777; font-size: 12px; padding: 20px; border-top: 2px solid #e0e0e0; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✓ Ticket Resolved</h1>
    </div>

    <div class="content">
        <p>Dear {{ $user->first_name }},</p>

        <div class="success-message">
            <strong>Good news!</strong> Your support ticket has been resolved.
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
                    <td><strong style="color: #28a745;">{{ ucfirst($ticket->status->value) }}</strong></td>
                </tr>
                <tr>
                    <td>Resolved By:</td>
                    <td>{{ $ticket->resolvedBy->first_name }} {{ $ticket->resolvedBy->last_name }}</td>
                </tr>
                <tr>
                    <td>Resolved At:</td>
                    <td>{{ $ticket->resolved_at->format('M d, Y h:i A') }}</td>
                </tr>
                @if($ticket->device)
                    <tr>
                        <td>Related Device:</td>
                        <td>{{ $ticket->device->device_code }}</td>
                    </tr>
                @endif
            </table>
        </div>

        @if($ticket->comments()->where('is_internal', false)->latest()->first())
            <p><strong>Latest Update:</strong></p>
            <div style="background-color: #e9ecef; padding: 15px; border-radius: 4px; margin: 10px 0;">
                {{ $ticket->comments()->where('is_internal', false)->latest()->first()->comment }}
            </div>
        @endif

        <p>If this resolves your issue, the ticket will be automatically closed after 48 hours.</p>
        <p>If you need further assistance, you can reopen this ticket by replying or logging into your account.</p>

        <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}" class="button">View Ticket</a>
    </div>

    <div class="footer">
        <p>This is an automated message from {{ config('app.name') }}</p>
        <p>&copy; {{ date('Y') }} VEGA ENTERPRISES. All rights reserved.</p>
    </div>
</div>
</body>
</html>
