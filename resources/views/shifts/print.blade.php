<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shifts - Print View</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-top: 5px;
        }
        
        .day-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .day-title {
            background-color: #f3f4f6;
            padding: 10px;
            margin-bottom: 15px;
            font-weight: bold;
            border-left: 4px solid #2563eb;
        }
        
        .shift {
            border: 1px solid #ddd;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
        }
        
        .shift-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .shift-name {
            font-weight: bold;
            font-size: 16px;
        }
        
        .shift-time {
            color: #666;
            font-weight: bold;
        }
        
        .shift-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .assigned-users {
            margin-top: 10px;
        }
        
        .assigned-users h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #333;
        }
        
        .user-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .user-badge {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .no-users {
            color: #999;
            font-style: italic;
            font-size: 12px;
        }
        
        .description {
            margin-top: 8px;
            color: #555;
            font-size: 14px;
        }
        
        @media print {
            body {
                margin: 0;
            }
            
            .shift {
                page-break-inside: avoid;
            }
            
            .day-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Eurofurence Badge Distribution Team</div>
        <div class="subtitle">Shifts Schedule</div>
        <div class="subtitle">Generated on {{ now()->format('F j, Y \a\t g:i A') }}</div>
    </div>

    @forelse($shifts as $date => $dayShifts)
        <div class="day-section">
            <div class="day-title">
                {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
            </div>
            
            @foreach($dayShifts as $shift)
                <div class="shift">
                    <div class="shift-header">
                        <div class="shift-name">{{ $shift->name }}</div>
                        <div class="shift-time">{{ $shift->start_time }} - {{ $shift->end_time }}</div>
                    </div>
                    
                    <div class="shift-meta">
                        <div>Required: {{ $shift->required_people }} people</div>
                        <div>Assigned: {{ $shift->users->count() }} people</div>
                        <div>Status: 
                            @if($shift->users->count() >= $shift->required_people)
                                <span style="color: #16a34a; font-weight: bold;">FULL</span>
                            @elseif($shift->users->count() > 0)
                                <span style="color: #ea580c; font-weight: bold;">PARTIAL</span>
                            @else
                                <span style="color: #dc2626; font-weight: bold;">EMPTY</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="assigned-users">
                        <h4>Assigned Volunteers:</h4>
                        @if($shift->users->count() > 0)
                            <div class="user-list">
                                @foreach($shift->users as $user)
                                    <span class="user-badge">{{ $user->name }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="no-users">No volunteers assigned yet</div>
                        @endif
                    </div>
                    
                    @if($shift->description)
                        <div class="description">
                            <strong>Description:</strong> {{ $shift->description }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @empty
        <div style="text-align: center; padding: 50px; color: #666;">
            <h3>No shifts scheduled</h3>
            <p>There are currently no shifts in the system.</p>
        </div>
    @endforelse
    
    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
