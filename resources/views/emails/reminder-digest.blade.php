<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salary Reminders</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border:1px solid #e5e7eb; border-radius:8px;">
                    <tr>
                        <td style="padding:24px 28px 8px 28px;">
                            <p style="margin:0; font-size:12px; letter-spacing:2px; text-transform:uppercase; color:#b45309; font-weight:bold;">Smart Bill Reminder</p>
                            <h1 style="margin:8px 0 0 0; font-size:22px; line-height:28px;">Your salary reminders</h1>
                            <p style="margin:10px 0 0 0; font-size:14px; line-height:21px; color:#374151;">
                                Hello {{ $user->name }}, the following salary obligations need your attention.
                            </p>
                        </td>
                    </tr>

                    @if ($overdue->isNotEmpty())
                        <tr>
                            <td style="padding:18px 28px 0 28px;">
                                <h2 style="margin:0 0 8px 0; font-size:15px; color:#b91c1c;">Overdue payments ({{ $overdue->count() }})</h2>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    @foreach ($overdue as $reminder)
                                        @php $obligation = $reminder->salaryObligation; @endphp
                                        <tr>
                                            <td style="padding:10px 12px; border:1px solid #fecaca; background-color:#fef2f2; border-radius:6px;">
                                                <p style="margin:0; font-size:14px; font-weight:bold;">{{ $obligation->employee->name }} &mdash; {{ $obligation->formatted_amount }}</p>
                                                <p style="margin:4px 0 0 0; font-size:13px; color:#374151;">
                                                    Due {{ $obligation->due_date->format('M d, Y') }} ({{ $obligation->due_date->diffForHumans() }})
                                                    @if ($obligation->latestRiskAssessment)
                                                        &middot; {{ $obligation->latestRiskAssessment->risk_level->label() }} ({{ $obligation->latestRiskAssessment->score }}/85)
                                                    @endif
                                                </p>
                                            </td>
                                        </tr>
                                        <tr><td style="height:8px;"></td></tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if ($upcoming->isNotEmpty())
                        <tr>
                            <td style="padding:18px 28px 0 28px;">
                                <h2 style="margin:0 0 8px 0; font-size:15px; color:#b45309;">Due soon ({{ $upcoming->count() }})</h2>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    @foreach ($upcoming as $reminder)
                                        @php $obligation = $reminder->salaryObligation; @endphp
                                        <tr>
                                            <td style="padding:10px 12px; border:1px solid #fde68a; background-color:#fffbeb; border-radius:6px;">
                                                <p style="margin:0; font-size:14px; font-weight:bold;">{{ $obligation->employee->name }} &mdash; {{ $obligation->formatted_amount }}</p>
                                                <p style="margin:4px 0 0 0; font-size:13px; color:#374151;">
                                                    Due {{ $obligation->due_date->format('M d, Y') }} ({{ $obligation->due_date->diffForHumans() }})
                                                    @if ($obligation->latestRiskAssessment)
                                                        &middot; {{ $obligation->latestRiskAssessment->risk_level->label() }} ({{ $obligation->latestRiskAssessment->score }}/85)
                                                    @endif
                                                </p>
                                            </td>
                                        </tr>
                                        <tr><td style="height:8px;"></td></tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:20px 28px 26px 28px;">
                            <p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">
                                Open Smart Bill Reminder to review these obligations and record payments.
                                Preparing for the high-risk items early helps you avoid a missed salary payment.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
