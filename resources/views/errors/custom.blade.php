<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Error' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        .error-icon {
            text-align: center;
            font-size: 60px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .message {
            color: #666;
            font-size: 16px;
            margin: 20px 0;
            line-height: 1.6;
        }
        .details {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 13px;
            color: #721c24;
            overflow-x: auto;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-link:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>{{ $title ?? 'Error' }}</h1>
        <div class="message">
            {{ $message ?? 'An error occurred while processing your request.' }}
        </div>
        
        @if(isset($details) && $details)
        <div class="details">
            <strong>Details:</strong><br>
            {{ $details }}
        </div>
        @endif
        
        <a href="{{ url()->previous() }}" class="back-link">← Go Back</a>
        <a href="{{ route('admin.dashboard') }}" class="back-link">Dashboard</a>
    </div>
</body>
</html>
