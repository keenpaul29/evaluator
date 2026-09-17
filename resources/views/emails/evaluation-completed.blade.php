<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #111827; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }
        .score { font-size: 32px; font-weight: bold; }
        .verdict { display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: 600; font-size: 14px; }
        .hire { background: #d1fae5; color: #065f46; }
        .maybe { background: #fef3c7; color: #92400e; }
        .no_hire { background: #fee2e2; color: #991b1b; }
        .dim { margin: 8px 0; }
        .dim-bar { height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; }
        .dim-fill { height: 100%; background: #111827; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin:0;font-size:18px;">Evaluation Complete</h1>
        <p style="margin:4px 0 0;opacity:0.8;font-size:14px;">{{ $candidate->name }} ({{ $candidate->github_username }})</p>
    </div>
    <div class="content">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
            <div class="score">{{ $evaluation->overall_score }}/10</div>
            <div>
                <span class="verdict {{ str_replace('_', '-', $evaluation->verdict) }}">
                    {{ $evaluation->getVerdictLabel() }}
                </span>
            </div>
        </div>

        <h3 style="font-size:14px;color:#6b7280;margin-bottom:8px;">Dimensions</h3>
        @foreach($evaluation->dimensions as $dim)
            <div class="dim">
                <div style="display:flex;justify-content:space-between;font-size:13px;">
                    <span>{{ $dim->getDimensionLabel() }}</span>
                    <span>{{ $dim->score }}/10</span>
                </div>
                <div class="dim-bar">
                    <div class="dim-fill" style="width:{{ ($dim->score / 10) * 100 }}%"></div>
                </div>
            </div>
        @endforeach

        @if($evaluation->strengths)
            <h3 style="font-size:14px;color:#6b7280;margin:20px 0 8px;">Strengths</h3>
            <ul style="margin:0;padding-left:20px;font-size:13px;">
                @foreach($evaluation->strengths as $s)
                    <li>{{ $s }}</li>
                @endforeach
            </ul>
        @endif

        @if($evaluation->concerns)
            <h3 style="font-size:14px;color:#6b7280;margin:20px 0 8px;">Concerns</h3>
            <ul style="margin:0;padding-left:20px;font-size:13px;">
                @foreach($evaluation->concerns as $c)
                    <li>{{ $c }}</li>
                @endforeach
            </ul>
        @endif

        <p style="margin-top:20px;font-size:13px;color:#6b7280;">
            <a href="{{ route('candidates.show', $candidate) }}" style="color:#111827;">View Full Report →</a>
        </p>
    </div>
</body>
</html>
