<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gravity CBC Learner Report</title>
  </head>
  <body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:720px;margin:0 auto;padding:24px;">
      <div style="background:#111827;border-radius:18px;padding:20px 24px;color:#fff;">
        <div style="font-size:18px;font-weight:700;letter-spacing:0.2px;">Gravity CBC Assessments</div>
        <div style="font-size:13px;opacity:0.85;margin-top:6px;">Learner Performance Summary</div>
      </div>

      <div style="background:#ffffff;border-radius:18px;padding:22px 24px;margin-top:16px;box-shadow:0 8px 24px rgba(17,24,39,0.06);">
        <p style="margin:0 0 14px 0;color:#111827;font-size:15px;line-height:1.6;">
          Dear Parent/Guardian,
        </p>

        <p style="margin:0 0 18px 0;color:#374151;font-size:14px;line-height:1.6;">
          Below is the latest assessment summary for your learner. This report is designed to be easy to read on mobile and low data connections.
        </p>

        <div style="background:#f9fafb;border:1px solid #eef2f7;border-radius:14px;padding:14px 16px;margin-bottom:16px;">
          <div style="display:flex;gap:18px;flex-wrap:wrap;">
            <div style="min-width:220px;">
              <div style="font-size:12px;color:#6b7280;">NAME</div>
              <div style="font-size:15px;color:#111827;font-weight:700;">{{ $learner->name }}</div>
            </div>
            <div style="min-width:180px;">
              <div style="font-size:12px;color:#6b7280;">GRADE</div>
              <div style="font-size:15px;color:#111827;font-weight:700;">{{ $report['grade'] ?? ($learner->grade_level ?? '-') }}</div>
            </div>
            <div style="min-width:180px;">
              <div style="font-size:12px;color:#6b7280;">YEAR</div>
              <div style="font-size:15px;color:#111827;font-weight:700;">{{ $report['year'] ?? now()->year }}</div>
            </div>
          </div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
          <div style="flex:1;min-width:220px;background:#ecfeff;border:1px solid #cffafe;border-radius:14px;padding:14px 16px;">
            <div style="font-size:12px;color:#0f766e;">OVERALL CBE LEVEL</div>
            <div style="font-size:18px;font-weight:800;color:#134e4a;margin-top:4px;">
              {{ $report['overall_level'] ?? '—' }}
            </div>
            <div style="font-size:13px;color:#0f766e;margin-top:6px;">
              MN MKS: {{ $report['mn_mks'] ?? '—' }}
            </div>
          </div>
          <div style="flex:1;min-width:220px;background:#fff7ed;border:1px solid #ffedd5;border-radius:14px;padding:14px 16px;">
            <div style="font-size:12px;color:#9a3412;">TOTAL MARKS</div>
            <div style="font-size:18px;font-weight:800;color:#7c2d12;margin-top:4px;">
              TT MKS: {{ $report['tt_mks'] ?? '—' }}
            </div>
            <div style="font-size:13px;color:#9a3412;margin-top:6px;">
              Average: {{ $report['average_percent'] ?? '—' }}
            </div>
          </div>
        </div>

        <h3 style="margin:0 0 10px 0;color:#111827;font-size:15px;">Subject breakdown</h3>

        <div style="border:1px solid #eef2f7;border-radius:14px;overflow:hidden;">
          <table style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="background:#f9fafb;">
                <th align="left" style="padding:10px 12px;font-size:12px;color:#6b7280;">SUBJECT</th>
                <th align="left" style="padding:10px 12px;font-size:12px;color:#6b7280;">SCORE</th>
                <th align="left" style="padding:10px 12px;font-size:12px;color:#6b7280;">LEVEL</th>
              </tr>
            </thead>
            <tbody>
              @foreach(($report['subjects'] ?? []) as $row)
                <tr>
                  <td style="padding:10px 12px;border-top:1px solid #eef2f7;color:#111827;font-size:13px;font-weight:700;">{{ $row['code'] ?? '-' }}</td>
                  <td style="padding:10px 12px;border-top:1px solid #eef2f7;color:#111827;font-size:13px;">{{ $row['percent'] ?? '-' }}</td>
                  <td style="padding:10px 12px;border-top:1px solid #eef2f7;color:#111827;font-size:13px;">{{ $row['level'] ?? '-' }}</td>
                </tr>
              @endforeach
              @if(empty($report['subjects']))
                <tr>
                  <td colspan="3" style="padding:12px;border-top:1px solid #eef2f7;color:#6b7280;font-size:13px;">No subject data available yet.</td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>

        <p style="margin:18px 0 0 0;color:#374151;font-size:13px;line-height:1.6;">
          If you have any questions, please contact your teacher or school administration.
        </p>

        <p style="margin:14px 0 0 0;color:#111827;font-size:13px;font-weight:700;">
          Gravity CBC Assessments
        </p>
      </div>

      <div style="text-align:center;color:#6b7280;font-size:12px;margin-top:14px;">
        Optimized for mobile and low data usage • Designed for low-resource environments
      </div>
    </div>
  </body>
</html>

