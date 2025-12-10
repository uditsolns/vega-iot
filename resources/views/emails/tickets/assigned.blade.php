<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Assigned</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #FFC107; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin: 20px 0; }
        .ticket-info { background-color: white; padding: 15px; margin: 10px 0; border-left: 4px solid #FFC107; }
        .button { background-color: #FFC107; color: white; padding: 12px 24px; text-decoration: none; display: inline-block; margin: 10px 0; }
        .footer { text-align: center; color: #777; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ticket Assigned to You</h1>
        </div>

        <div class="content">
            <p>A support ticket has been assigned to you by {{ $assignedBy->name }}:</p>

            <div class="ticket-info">
                <h2>Ticket #{{ $ticket->id }}</h2>
                <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
                <p><strong>Priority:</strong> <span style="color: {{ config('tickets.priority_colors')[$ticket->priority->value] ?? '#333' }}">{{ ucfirst($ticket->priority->value) }}</span></p>
                <p><strong>Status:</strong> {{ ucfirst($ticket->status->value) }}</p>
                <p><strong>Created by:</strong> {{ $ticket->user->name ?? 'Unknown' }}</p>
                @if($ticket->device)
                    <p><strong>Related Device:</strong> {{ $ticket->device->name }}</p>
                @endif
            </div>

            <p><strong>Description:</strong></p>
            <p>{{ $ticket->description }}</p>

            <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}" class="button">View Ticket</a>
        </div>

        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}</p>
            <p>Please do not reply to this email</p>
        </div>
    </div>
</body>
</html>
