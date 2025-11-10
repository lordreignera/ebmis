<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Agreement - {{ $loan->code }}</title>
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
        .terms-list {
            margin: 10px 0;
            padding-left: 20px;
        }
        .terms-list li {
            margin: 8px 0;
            text-align: justify;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-box {
            border: 1px solid #ccc;
            height: 80px;
            margin: 20px 0;
            position: relative;
            background-color: #fafafa;
        }
        .signature-label {
            position: absolute;
            bottom: 5px;
            left: 10px;
            font-size: 10px;
            color: #666;
        }
        .date-line {
            border-bottom: 1px solid #333;
            display: inline-block;
            width: 150px;
            margin-left: 10px;
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
        .page-break {
            page-break-before: always;
        }
        .highlight {
            background-color: #fff2cc;
            padding: 2px 4px;
            border-radius: 2px;
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

    <div class="agreement-title">Loan Agreement</div>

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
                    @if($loan->period_type == 1) Weeks
                    @elseif($loan->period_type == 2) Months  
                    @elseif($loan->period_type == 3) Days
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
                <td>{{ now()->format('F d, Y') }}</td>
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
                    <td>Address:</td>
                    <td>{{ $borrower->village }}, {{ $borrower->parish }}, {{ $borrower->subcounty }}</td>
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
            @endif
        </table>
    </div>

    @if($loan->product)
    <div class="section">
        <div class="section-title">Loan Product Details</div>
        <table class="info-table">
            <tr>
                <td>Product Name:</td>
                <td>{{ $loan->product->pname ?? 'Standard Loan' }}</td>
            </tr>
            @if(isset($loan->product->description))
            <tr>
                <td>Description:</td>
                <td>{{ $loan->product->description }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    <div class="page-break"></div>

    <div class="section">
        <div class="section-title">Terms and Conditions</div>
        <ol class="terms-list">
            <li><strong>Loan Purpose:</strong> The borrower acknowledges that this loan is for legitimate business or personal use as declared in the application.</li>
            
            <li><strong>Repayment:</strong> The borrower agrees to repay the loan amount plus interest in 
                @if($loan->period_type == 1) weekly
                @elseif($loan->period_type == 2) monthly
                @elseif($loan->period_type == 3) daily
                @endif
                installments as scheduled.</li>
            
            <li><strong>Interest:</strong> Interest is calculated at {{ $loan->interest }}% per annum on the reducing balance method.</li>
            
            <li><strong>Default:</strong> Failure to make payments on time will result in penalty charges and may lead to loan recall.</li>
            
            <li><strong>Collateral:</strong> This loan is secured by 
                @if($type === 'personal')
                    personal guarantors and/or collateral as declared in the application.
                @else
                    group guarantee and individual member liability.
                @endif
            </li>
            
            <li><strong>Prepayment:</strong> The borrower may prepay the loan in full or in part at any time without penalty.</li>
            
            <li><strong>Insurance:</strong> The borrower is required to maintain adequate insurance coverage for the loan period.</li>
            
            <li><strong>Amendments:</strong> Any changes to this agreement must be made in writing and signed by both parties.</li>
            
            <li><strong>Governing Law:</strong> This agreement is governed by the laws of Uganda.</li>
            
            <li><strong>Dispute Resolution:</strong> Any disputes arising from this agreement shall be resolved through arbitration or the courts of Uganda.</li>
            
            <li><strong>Default Consequences:</strong> In case of default, the lender reserves the right to:
                <ul>
                    <li>Demand immediate payment of the full outstanding amount</li>
                    <li>Charge penalty interest on overdue amounts</li>
                    <li>Take legal action for recovery</li>
                    <li>Report to credit reference bureaus</li>
                </ul>
            </li>
            
            <li><strong>Electronic Signature:</strong> By providing the OTP (One Time Password), the borrower electronically signs this agreement and accepts all terms and conditions.</li>
        </ol>
    </div>

    <div class="signature-section">
        <div class="section-title">Signatures</div>
        
        <div style="width: 48%; float: left;">
            <div class="signature-box">
                <div class="signature-label">
                    Borrower Signature: 
                    @if($type === 'personal')
                        {{ $borrower->fname }} {{ $borrower->lname }}
                    @else
                        Group Representative
                    @endif
                </div>
            </div>
            <div>Date: <span class="date-line"></span></div>
        </div>
        
        <div style="width: 48%; float: right;">
            <div class="signature-box">
                <div class="signature-label">Lender Representative Signature</div>
            </div>
            <div>Date: <span class="date-line"></span></div>
        </div>
        
        <div style="clear: both; margin-top: 40px;">
            <div class="signature-box">
                <div class="signature-label">Witness Signature (if applicable)</div>
            </div>
            <div>Date: <span class="date-line"></span></div>
        </div>
    </div>

    <div class="footer">
        <p>This agreement was generated electronically by {{ config('app.name', 'EBIMS') }} on {{ now()->format('F d, Y \a\t H:i:s') }}</p>
        <p>Page 1 of 1 | Loan Code: {{ $loan->code }}</p>
    </div>
</body>
</html>