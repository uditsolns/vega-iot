<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Comment on Ticket</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #6C757D; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin: 20px 0; }
        .ticket-info { background-color: white; padding: 15px; margin: 10px 0; border-left: 4px solid #6C757D; }
        .comment-box { background-color: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .button { background-color: #6C757D; color: white; padding: 12px 24px; text-decoration: none; display: inline-block; margin: 10px 0; }
        .footer { text-align: center; color: #777; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Comment on Ticket</h1>
        </div>

        <div class="content">
            <p>A new comment has been added to ticket #{{ $ticket->id }}:</p>

            <div class="comment-box">
                <p><strong>{{ $comment->user->name ?? 'Unknown' }}</strong> commented:</p>
                <p>{{ $comment->comment }}</p>
                <p style="font-size: 12px; color: #777;">{{ $comment->created_at->format('M d, Y h:i A') }}</p>
            </div>

            <div class="ticket-info">
                <h2>Ticket #{{ $ticket->id }}</h2>
                <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
                <p><strong>Status:</strong> {{ ucfirst($ticket->status->value) }}</p>
                <p><strong>Priority:</strong> {{ ucfirst($ticket->priority->value) }}</p>
            </div>

            <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}" class="button">View Ticket</a>
        </div>

        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}</p>
            <p>Please do not reply to this email</p>
        </div>
    </div>
</body>
</html>
