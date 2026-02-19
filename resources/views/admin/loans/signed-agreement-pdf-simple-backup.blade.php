<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signed Loan Agreement - {{ $loan->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            font-size: 12px;
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
            color: #333;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .agreement-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 30px 0;
            text-transform: uppercase;
        }
        .signature-notice {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-table {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 30%;
            color: #555;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .digital-signature {
            background-color: #fff;
            border: 2px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .signature-info {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }
        .signature-stamp {
            text-align: center;
            color: #28a745;
            font-weight: bold;
            font-size: 16px;
            border: 2px solid #28a745;
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
            background-color: #d4edda;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        .highlight {
            background-color: #fff2cc;
            padding: 2px 4px;
            border-radius: 2px;
        }
        .verified-stamp {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            text-align: center;
            padding: 15px;
            margin: 20px auto;
            border-radius: 50px;
            width: 200px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ config('app.name', 'EBIMS') }}</div>
        <div class="subtitle">Electronic Banking and Information Management System</div>
        @if($loan->branch)
            <div class="subtitle">{{ $loan->branch->name }} Branch</div>
        @endif
    </div>

    <div class="agreement-title">Electronically Signed Loan Agreement</div>

    <div class="signature-notice">
        <strong>✓ DIGITALLY SIGNED AGREEMENT</strong><br>
        This loan agreement has been electronically signed and is legally binding.
    </div>

    <div class="verified-stamp">
        VERIFIED & SIGNED
    </div>

    <div class="section">
        <div class="section-title">Digital Signature Information</div>
        <div class="digital-signature">
            <table class="info-table">
                <tr>
                    <td>Signature Status:</td>
                    <td><strong style="color: #28a745;">{{ ucfirst($loan->signature_status ?? 'Signed') }}</strong></td>
                </tr>
                <tr>
                    <td>Signature Date:</td>
                    <td><strong>{{ $loan->signature_date ? \Carbon\Carbon::parse($loan->signature_date)->format('F d, Y \a\t H:i:s') : 'N/A' }}</strong></td>
                </tr>
                <tr>
                    <td>Signature Method:</td>
                    <td>OTP (One Time Password) Verification</td>
                </tr>
                @if($loan->signature_comments)
                <tr>
                    <td>Signature Comments:</td>
                    <td>{{ $loan->signature_comments }}</td>
                </tr>
                @endif
                <tr>
                    <td>Verification System:</td>
                    <td>{{ config('app.name', 'EBIMS') }} Electronic Signature System</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Loan Information</div>
        <table class="info-table">
            <tr>
                <td>Loan Code:</td>
                <td><strong>{{ $loan->code }}</strong></td>
            </tr>
            <tr>
                <td>Loan Type:</td>
                <td>{{ ucfirst($type) }} Loan</td>
            </tr>
            <tr>
                <td>Principal Amount:</td>
                <td><strong>UGX {{ number_format($loan->principal, 2) }}</strong></td>
            </tr>
            <tr>
                <td>Interest Rate:</td>
                <td>{{ $loan->interest }}% per annum</td>
            </tr>
            <tr>
                <td>Loan Period:</td>
                <td>{{ $loan->period }} 
                    @if($loan->product)
                        @if($loan->product->period_type == 1) Weeks
                        @elseif($loan->product->period_type == 2) Months  
                        @elseif($loan->product->period_type == 3) Days
                        @else Periods
                        @endif
                    @else
                        Periods
                    @endif
                </td>
            </tr>
            <tr>
                <td>Repayment Frequency:</td>
                <td>
                    @if($loan->product)
                        @if($loan->product->period_type == 1) Weekly (Every 7 days)
                        @elseif($loan->product->period_type == 2) Monthly (Every 30 days)
                        @elseif($loan->product->period_type == 3) Daily (Monday to Saturday, excluding Sundays)
                        @else As per schedule
                        @endif
                    @else
                        As per schedule
                    @endif
                </td>
            </tr>
            @if(isset($loan->installment))
            <tr>
                <td>Installment Amount:</td>
                <td>UGX {{ number_format($loan->installment, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td>Agreement Date:</td>
                <td>{{ \Carbon\Carbon::parse($loan->datecreated)->format('F d, Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Borrower Information</div>
        <table class="info-table">
            @if($type === 'personal')
                <tr>
                    <td>Full Name:</td>
                    <td><strong>{{ $borrower->fname }} {{ $borrower->lname }}</strong></td>
                </tr>
                <tr>
                    <td>Member ID:</td>
                    <td>{{ $borrower->code ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>National ID:</td>
                    <td>{{ $borrower->nin ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Contact:</td>
                    <td>{{ $borrower->contact }}</td>
                </tr>
                <tr>
                    <td>Email:</td>
                    <td>{{ $borrower->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Signature Verification:</td>
                    <td><strong style="color: #28a745;">Verified via SMS OTP to {{ $borrower->contact }}</strong></td>
                </tr>
            @else
                <tr>
                    <td>Group Name:</td>
                    <td><strong>{{ $borrower->name }}</strong></td>
                </tr>
                <tr>
                    <td>Group Code:</td>
                    <td>{{ $borrower->code ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Registration Date:</td>
                    <td>{{ $borrower->inception_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Address:</td>
                    <td>{{ $borrower->address ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Total Members:</td>
                    <td>{{ $borrower->members ? $borrower->members->count() : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Signature Verification:</td>
                    <td><strong style="color: #28a745;">Verified via Group Representative</strong></td>
                </tr>
            @endif
        </table>
    </div>

    <div class="signature-section">
        <div class="section-title">Electronic Signature Confirmation</div>
        
        <div class="signature-stamp">
            ✓ ELECTRONICALLY SIGNED
        </div>

        <p style="text-align: center; margin: 20px 0; font-style: italic;">
            "By providing the OTP verification code via SMS, the borrower has electronically signed this loan agreement and confirmed acceptance of all terms and conditions outlined herein. This electronic signature is legally equivalent to a handwritten signature."
        </p>

        <table class="info-table" style="margin-top: 30px;">
            <tr>
                <td>Electronic Signature By:</td>
                <td>
                    @if($type === 'personal')
                        {{ $borrower->fname }} {{ $borrower->lname }}
                    @else
                        {{ $borrower->name }} (Group Representative)
                    @endif
                </td>
            </tr>
            <tr>
                <td>Signature Date & Time:</td>
                <td>{{ $loan->signature_date ? \Carbon\Carbon::parse($loan->signature_date)->format('l, F d, Y \a\t H:i:s T') : 'N/A' }}</td>
            </tr>
            <tr>
                <td>Verification Method:</td>
                <td>SMS OTP Verification</td>
            </tr>
            <tr>
                <td>System IP Address:</td>
                <td>{{ request()->ip() ?? 'System Generated' }}</td>
            </tr>
            <tr>
                <td>Document Hash:</td>
                <td style="font-family: monospace; font-size: 10px;">{{ hash('sha256', $loan->code . $loan->signature_date) }}</td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 50px; padding: 20px; background-color: #f8f9fa; border-radius: 5px;">
        <h4 style="margin-top: 0; color: #333;">Legal Validity Notice</h4>
        <p style="font-size: 11px; text-align: justify;">
            This electronically signed document is legally binding under the Electronic Transactions Act and relevant financial regulations. 
            The SMS OTP verification process ensures the authenticity of the borrower's consent. This document serves as proof of 
            the loan agreement and can be used for all legal and business purposes.
        </p>
    </div>

    <div class="footer">
        <p>This signed agreement was generated by {{ config('app.name', 'EBIMS') }} on {{ now()->format('F d, Y \a\t H:i:s') }}</p>
        <p>Digital Signature Certificate | Loan Code: {{ $loan->code }} | Status: Verified ✓</p>
    </div>
</body>
</html>