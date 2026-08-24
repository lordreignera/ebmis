@php
    $imageDataUri = function (string $relativePath): ?string {
        $path = public_path($relativePath);

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    };

    $selfApplicationUrl = 'https://ebmis.emuria.net/apply';

    $sections = [
        [
            'title' => 'Getting Started',
            'image' => 'userguidelineimages/maindashboard.png',
            'points' => [
                'Start from the main dashboard to review total members, active loans, overdue loans, collections, investments, and cash securities.',
                'Use Quick Actions for fast access to Active Loans, Self Applications, UMRA Reports, Ledgers, and Expenditures.',
                'Use the Help card or sidebar help panel whenever a staff member needs this manual.',
            ],
        ],
        [
            'title' => 'Find Menu Items',
            'image' => 'userguidelineimages/sidebarsearchbar.png',
            'points' => [
                'Click the Find menu item field in the sidebar.',
                'Search by words such as loans, members, repayments, reports, settings, SMS, or roles.',
                'Press Enter to open the first result or click the matching menu link.',
            ],
        ],
        [
            'title' => 'Loan Portfolio',
            'image' => 'userguidelineimages/loan potifolia.png',
            'points' => [
                'Use Loan Portfolio to create loans, review self-applied applications, approve loans, disburse loans, and monitor portfolio work.',
                'After a loan is disbursed, repayment work moves into Active Loans.',
                'Staff can open and share the public self-application link: ' . $selfApplicationUrl,
            ],
        ],
        [
            'title' => 'Active Loans',
            'image' => 'userguidelineimages/activeloans.png',
            'points' => [
                'Active Loans lists running loans with unpaid schedules and shows principal due, interest due, late fees due, overdue loans, and today\'s collections.',
                'Use the tabs for Active Personal Loans, Collections Queue, Risk Follow-up, Security Gaps, and Loan Operations where allowed.',
                'Use Search, Branch, Product, and Status filters before opening schedules or exporting records.',
                'Open a schedule to review installments, pending mobile money requests, previous payments, and the next amount due.',
                'Record follow-up notes and collateral details from this workspace so collection history remains attached to the loan.',
            ],
            'warning' => 'Loan Officers, Field Officers, Branch Managers, and other non-administrator roles only see active loans assigned to their user account. Super Administrators and Administrators can view and manage active loans across branches for supervision, reassignment, and operations. The same rule protects details, schedules, repayments, polling, receipts, collateral, follow-ups, and exports.',
        ],
        [
            'title' => 'Payments & Collections',
            'image' => 'userguidelineimages/payments.png',
            'points' => [
                'Use Payments & Collections for repayment follow-up, repayment history, fees, savings, cash securities, and mobile money tracking.',
                'Open loan schedules to check expected, paid, pending, and outstanding repayment amounts.',
                'Use reports when exported repayment or payment transaction records are required.',
            ],
        ],
        [
            'title' => 'Reports & Accounting',
            'image' => 'userguidelineimages/reports.png',
            'points' => [
                'Use Reports & Accounting for operational review, accounting checks, regulatory records, and management follow-up.',
                'Apply filters before exporting to keep reports focused.',
                'Use UMRA reports for regulatory reporting workflows.',
            ],
        ],
        [
            'title' => 'School Management',
            'image' => 'userguidelineimages/schoolmanagment.png',
            'points' => [
                'Use School Management to administer school records and school, student, or staff loan workflows.',
                'Review registered schools, pending approvals, active loans, disbursements, repayments, and portfolio information.',
            ],
        ],
        [
            'title' => 'Clients',
            'image' => 'userguidelineimages/client module.png',
            'points' => [
                'Use the Clients Module for member registration, approvals, groups, member details, SMS records, and communication.',
                'Use Pending Members to review clients waiting for approval.',
            ],
        ],
    ];
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>EBIMS User Manual</title>
    <style>
        @page { margin: 28px 28px 36px; }
        body {
            color: #172033;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }
        h1 {
            color: #111827;
            font-size: 24px;
            margin: 0 0 4px;
        }
        h2 {
            border-bottom: 1px solid #d7dee8;
            color: #111827;
            font-size: 16px;
            margin: 24px 0 10px;
            padding-bottom: 6px;
        }
        p {
            margin: 0 0 8px;
        }
        .meta {
            color: #64748b;
            font-size: 11px;
            margin-bottom: 18px;
        }
        .cover {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            margin-bottom: 18px;
            padding: 14px;
        }
        .section {
            page-break-inside: avoid;
        }
        ol {
            margin: 8px 0 10px 18px;
            padding: 0;
        }
        li {
            margin-bottom: 5px;
        }
        .shot {
            border: 1px solid #d7dee8;
            border-radius: 4px;
            margin: 8px 0 12px;
            padding: 6px;
            text-align: center;
        }
        .shot img {
            max-height: 260px;
            max-width: 100%;
        }
        .warning {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 4px;
            color: #7c2d12;
            margin-top: 8px;
            padding: 9px;
        }
        .footer-note {
            border-top: 1px solid #d7dee8;
            color: #64748b;
            font-size: 10px;
            margin-top: 18px;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="cover">
        <h1>EBIMS User Manual</h1>
        <p>Operational guide for dashboard navigation, modules, active loans, collections, reports, and access-controlled workflows.</p>
        <div class="meta">Generated {{ $generatedAt->format('Y-m-d H:i') }}</div>
    </div>

    @foreach($sections as $section)
        <div class="section">
            <h2>{{ $section['title'] }}</h2>
            @if($src = $imageDataUri($section['image']))
                <div class="shot">
                    <img src="{{ $src }}" alt="{{ $section['title'] }}">
                </div>
            @endif
            <ol>
                @foreach($section['points'] as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ol>
            @if(!empty($section['warning']))
                <div class="warning">
                    <strong>Important access rule:</strong> {{ $section['warning'] }}
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer-note">
        This manual reflects the current EBIMS user guideline and Active Loans access behavior.
    </div>
</body>
</html>
