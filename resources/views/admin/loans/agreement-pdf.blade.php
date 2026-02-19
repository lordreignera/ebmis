<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Agreement - {{ $loan->code }}</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.4;
            margin: 0;
            padding: 15px;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .address {
            font-size: 11px;
            margin-bottom: 3px;
        }
        .loan-info {
            margin: 15px 0;
            line-height: 1.6;
        }
        .section-title {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            text-decoration: underline;
        }
        .clause-title {
            font-weight: bold;
            margin-top: 10px;
        }
        p {
            margin: 8px 0;
            text-align: justify;
        }
        .signature-section {
            margin-top: 30px;
        }
        .signature-line {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 200px;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Emuria Business Investment and Management Software (E-BIMS) Ltd</div>
        <div class="address">Akisim cell, Central ward, Akore town, Kapelebyong, Uganda</div>
    </div>

    <div class="loan-info">
        <strong>Loan ID:</strong> {{ $loan->code }}<br>
        <strong>Type of Loan:</strong> {{ $loan->product->pname ?? 'Standard Loan' }}<br>
        <strong>Type of Application:</strong> {{ ucfirst($type) }} Loan<br>
        <strong>Branch:</strong> {{ $loan->branch->name ?? 'Main Branch' }}<br>
        <strong>Loan Amount:</strong> {{ number_format($loan->principal, 0) }}<br>
        <strong>Loan Term:</strong> {{ $loan->period }}
        @if($loan->product)
            @if($loan->product->period_type == 1) Weeks
            @elseif($loan->product->period_type == 2) Months
            @elseif($loan->product->period_type == 3) Days
            @endif
        @endif
        <br>
        <strong>Date:</strong> {{ \Carbon\Carbon::parse($loan->datecreated)->format('Y-m-d H:i:s') }}
    </div>

    <p style="text-align: center; font-weight: bold;">The Republic of Uganda</p>
    <p style="text-align: center;">In the matter of the Contracts Act, 2010</p>

    <p>This Loan agreement is made this {{ \Carbon\Carbon::parse($loan->datecreated)->format('Y-m-d H:i:s') }}. Between: 
        @if($type === 'personal')
            <strong>{{ $borrower->fname }} {{ $borrower->lname }}</strong>, of {{ $borrower->village ?? '' }}, {{ $borrower->parish ?? '' }}, {{ $borrower->subcounty ?? '' }} with National Identification Number {{ $borrower->nin ?? 'N/A' }}
        @else
            <strong>{{ $borrower->name }}</strong>, a registered group
        @endif
        , whether as an individual or as a group jointly and severally liable, hereinafter (the "Borrower" including a group borrower) of the one part, and</p>

    <p><strong>Emuria Business Investments and Management Software (E-BIMS) Ltd</strong>, a limited liability company incorporated in Uganda, of Akisim cell, Central ward, Akore town, Kapelebyong, hereinafter (the "Lender") of the other part.</p>

    <p><strong>Now it is agreed by the parties as follows:</strong></p>

    <p class="clause-title">Interpretation:</p>
    <p>In this Agreement, unless the context otherwise requires: Words denoting the singular include the plural and vice versa. References to "Borrower" includes individuals and groups jointly and severally liable.</p>

    <p class="clause-title">Definition:</p>
    <p>"Group Borrower": A group of individuals who collectively borrow funds under this Agreement and are jointly and severally liable for the repayment of the loan, individually and severally referred to as the Borrower.</p>

    <p class="clause-title">Applicability:</p>
    <p>Provisions under Clause 5.9 of this Agreement shall apply exclusively to a Group Borrower.</p>

    <p class="section-title">1. Loan Amount:</p>
    <p>Upon the Borrower's loan application and acceptance of the loan offer, the Lender hereby agrees to advance the Borrower a loan of Uganda shillings <strong>UGX {{ number_format($loan->principal, 0) }}/=</strong> payable in
        @if($loan->product)
            @if($loan->product->period_type == 1) Weeks
            @elseif($loan->product->period_type == 2) Months
            @elseif($loan->product->period_type == 3) Days
            @endif
        @endif
        , and the Borrower individually and collectively accepts responsibility for all obligations and debts under this agreement without protest and acknowledges such indebtedness to the Lender, through its {{ $loan->branch->name ?? 'Main' }} Branch.</p>

    <p class="section-title">2. Loan Purpose:</p>
    @if($loan->loan_purpose)
    <p>The loan shall be used for the purpose of <strong>{{ $loan->loan_purpose }}</strong>. Diversion of loan funds to other uses shall not be permitted and will constitute a breach of this agreement on loan use. This breach will cause the Lender to restrict future loans to the Borrower or recall the loan entirely.</p>
    @else
    <p>The loan shall be used for the purpose of investing in ……………………………………………. Diversion of loan funds to other uses shall not be permitted and will constitute a breach of this agreement on loan use. This breach will cause the Lender to restrict future loans to the Borrower or recall the loan entirely.</p>
    @endif

    <p class="section-title">3. Loan Tenure:</p>
    <p>The Loan shall be for a term of {{ $loan->period }}
        @if($loan->product)
            @if($loan->product->period_type == 1) Weeks
            @elseif($loan->product->period_type == 2) Months
            @elseif($loan->product->period_type == 3) Days
            @endif
        @endif
        .</p>

    <p class="section-title">4. Interest, Fees, and other expenses</p>

    <p class="clause-title">4.1 Interest:</p>
    <p>The loan shall attract an interest rate of <strong>{{ $loan->interest }}%</strong> 
    @if($loan->product)
        @if($loan->product->period_type == 1) per week
        @elseif($loan->product->period_type == 2) per month
        @elseif($loan->product->period_type == 3) per day
        @endif
    @else
        per annum
    @endif
    calculated on the reducing balance method.</p>

    <p class="clause-title">4.2 Fees:</p>
    @if($loan->product && $loan->product->charges()->where('isactive', 1)->count() > 0)
        <p>The following fees shall be paid at the time of loan disbursement:</p>
        <ul style="margin-left: 20px;">
        @foreach($loan->product->charges()->where('isactive', 1)->get() as $charge)
            <li><strong>{{ $charge->name }}:</strong> 
                @if($charge->type == 1)
                    UGX {{ number_format($charge->value, 0) }} (Fixed Amount)
                @elseif($charge->type == 2)
                    {{ $charge->value }}% of loan amount (UGX {{ number_format(($loan->principal * $charge->value / 100), 0) }})
                @elseif($charge->type == 3)
                    UGX {{ number_format($charge->value, 0) }} per day
                @elseif($charge->type == 4)
                    UGX {{ number_format($charge->value, 0) }} per month
                @endif
            </li>
        @endforeach
        </ul>
    @else
        <p>Registration fees, administration fees, and processing fees of the loan amount shall be paid at the time of loan disbursement as per the lender's fee schedule.</p>
    @endif

    <p class="clause-title">4.3 Loan Expenses:</p>
    <p><strong>Conveyance and Caveating fees:</strong> The cost of investigations, stamps, seals, fees, and other costs in connection with the collateral associated with the loan shall be borne by the Borrower.</p>

    <p class="clause-title">4.4 Incidental fees:</p>
    <p>The Borrower shall pay all fees incidental to the loan before loan disbursement.</p>

    <p class="section-title">5. Loan Repayment:</p>
    <p>The Borrower agrees to pay the principal to the Lender, in <strong>{{ $loan->period }}</strong> installments.</p>
    
    <p>Repayment Schedule:</p>
    <ul style="margin-left: 20px;">
        <li>Total loan amount: <strong>UGX {{ number_format($loan->principal, 0) }}</strong></li>
        <li>Number of installments: <strong>{{ $loan->period }}</strong></li>
        <li>Installment amount: <strong>UGX {{ number_format($loan->installment, 0) }}</strong></li>
        <li>Payment frequency: 
            @if($loan->product)
                @if($loan->product->period_type == 1) <strong>Weekly (Every 7 days)</strong>
                @elseif($loan->product->period_type == 2) <strong>Monthly (Every 30 days)</strong>
                @elseif($loan->product->period_type == 3) <strong>Daily (Monday to Saturday, excluding Sundays)</strong>
                @endif
            @endif
        </li>
    </ul>

    <p class="clause-title">5.1 Late fees:</p>
    <p>Repayments shall be made on the scheduled dates between the hours of 9 am to 5 pm. If the payment is not received by the due date, the installment due shall attract a late fee of <strong>6 percent per week</strong> of the overdue installments.</p>

    <p class="clause-title">5.2 Pre-Payments:</p>
    <p>The Borrower has the option to pay more than the installment due. By paying ahead, the Borrower pays off the loan quickly.</p>

    <p class="clause-title">5.3 Early complete repayment of the entire outstanding Loan:</p>
    <p>The Borrower has the option to make early repayment of the entire principal loan amount by giving a seven-day notice to the Lender, through its branch manager. The lender's system shall generate a 50 percent rebate on the prepaid Interest if the prepaid period is equal to or greater than two months.</p>

    <p class="clause-title">5.4 Cooling off:</p>
    <p>In line with the Bank of Uganda financial customers protection guidelines, the lender hereby advises the Borrower of the option to revoke or terminate the Loan agreement in the exercise of the Borrower's right to cooling off, within 5 working days from the day of the loan disbursement. The said 5 working days shall exclude Saturdays, Sundays, and public holidays.</p>

    <p>The Borrower is mandated to exercise their right to cool off by delivering a written notice to the lender and further upon the following terms:</p>
    
    <p style="margin-left: 20px;">a) Upon revocation/termination of the loan within the said cooling period, the Borrower shall refund to the lender the entire loan amount disbursed together with all the incidental administrative fees of up to 5 percent of the loan amount.</p>
    
    <p style="margin-left: 20px;">b) To be clear, The Revocation or termination of the loan agreement with the Lender shall only be effective if the Borrower repays the full amount at the time of revocation or termination of the loan and the 5 percent administrative fees referred in a) above, within 5 days from the date of loan disbursement.</p>
    
    <p style="margin-left: 20px;">c) Any further delays in refunding the loan and administration fees shall automatically annul the right of cooling off and the loan and agreement shall subsequently be reinstated upon its original terms.</p>

    <p style="margin-left: 20px;">d) The cooling-off right/option is only available to customers with a minimal loan amount of One Million Uganda Shillings (UGX 1,000,000/=) and shall be limited to loans with an agreed loan tenure of at least one year.</p>

    <p class="clause-title">5.5 Late Repayment:</p>
    <p>Where the Borrower is in breach of the loan agreement and/or is in default and the loan installment remains unpaid for two consecutive payment dates and does not make any further payments until the third installment is due, the Lender, shall recall the entire loan and immediately demand repayment of the entire outstanding loan balance. The Lender will therefore proceed against the Borrower for the recovery of the total principal plus interest, collection fees and all related incidental costs.</p>

    <p class="clause-title">5.6 Loan Recall:</p>
    <p>The Lender reserves the right to recall the loan at any given time, whether there is default, if in the lender's own assessment, the Borrower has become a risky Borrower such that there is a higher likelihood of defaulting on the loan or failing to meet the repayment terms. Where such a loan is recalled, the entire loan amount shall fall due and owing immediately.</p>

    <p class="clause-title">5.7 Outstanding Loan Balance at the end of the loan term:</p>
    <p>Any amount in respect of which the principal remains unpaid at the end of the loan period shall continue to attract interest at the rate indicated in Clause 4.1 of this agreement, until the entire outstanding principal balance is fully paid.</p>

    <p class="section-title">6. Charges for payments in arrears plus Loan Recovery costs</p>
    <p class="clause-title">6.1 Arrears Charges:</p>
    <p>If the loan falls into arrears, the Borrower will incur a <strong>6 percent weekly charge</strong> on the principal amount in arrears. This charge will continue to apply until the arrears, including any late fees and loan recovery costs, are fully paid. Both the principal in arrears and the unpaid late fees will attract this 6 percent weekly charge until the total outstanding amount is cleared.</p>

    @if($type === 'group')
    <p class="clause-title">6.2 Group Banker:</p>
    @if($loan->group_banker_name)
    <p>{{ $borrower->name }}, unanimously elected <strong>{{ $loan->group_banker_name }}</strong> with national identification number <strong>{{ $loan->group_banker_nin }}</strong>, a <strong>{{ $loan->group_banker_occupation }}</strong> and a resident of <strong>{{ $loan->group_banker_residence }}</strong>, to be their Group Banker.</p>
    @else
    <p>[Name of group], unanimously elected [person elected] with national identification number [elected person's NIN], a [occupation of person elected] and a resident of [location/residence of person elected], to be their Group Banker.</p>
    @endif
    
    <p>The Group Banker is responsible for ensuring that the loan disbursed by the Lender to the Group Banker's mobile wallet is distributed to the respective members of the group. It is the role of the banker to ensure that each individual member makes their loan repayment contributions at least one day before the due date. If the loan installment is delayed, the entire group will be charged a late fee of 6 percent per week on the overdue amount.</p>
    @endif

    <p class="section-title">7. Loan Collateral Security</p>
    <p>The Borrower has pledged collateral security to the lender for the entire loan term. This collateral security shall remain in effect until the full repayment of all outstanding amounts, including the principal, interest, late fees, penalties, and any loan recovery costs.</p>

    <p>The Borrower and the lender hereby agree that the Collateral security pledged shall be held as continuing security for all financial obligations the Borrower may subsequently apply for in addition to those under this agreement.</p>

    <p class="clause-title">7.1 Pledged Collateral:</p>
    <p>The following Collateral has been pledged by the Borrower to secure the loan:</p>
    
    @php
        // Priority: 1) E-signature form data, 2) Active savings account, 3) Blank
        $accountNumber = $loan->cash_account_number;
        $accountName = $loan->cash_account_name;
        
        if (!$accountNumber && $type === 'personal' && $borrower->savings && $borrower->savings->count() > 0) {
            $activeSavings = $borrower->savings->where('status', 1)->first();
            if ($activeSavings) {
                $accountNumber = $activeSavings->code;
                $accountName = $borrower->fname . ' ' . $borrower->lname;
            }
        }
        
        if (!$accountName && $type === 'personal') {
            $accountName = $borrower->fname . ' ' . $borrower->lname;
        }
    @endphp
    
    <p style="margin-left: 20px;">All Monies in the following Cash security Accounts: Account number: 
    @if($accountNumber)
        <strong>{{ $accountNumber }}</strong>
    @else
        __________________________
    @endif
    in the name of 
    @if($accountName)
        <strong>{{ $accountName }}</strong>
    @else
        _____________________________
    @endif
    held with the Lender at Akisim cell, Central ward, Akore town, Kapelebyong.</p>

    <p style="margin-left: 20px;"><strong>Collateral Security pledged:</strong></p>
    @if($type === 'personal' && $borrower->assets && $borrower->assets->count() > 0)
        <ul style="margin-left: 40px;">
        @foreach($borrower->assets as $asset)
            <li>{{ $asset->assetType->name ?? 'Asset' }}: Quantity {{ $asset->quantity }}, Value UGX {{ number_format($asset->value, 0) }}, Total Value UGX {{ number_format($asset->total_value, 0) }}</li>
        @endforeach
        </ul>
    @endif
    
    @if($loan->immovable_assets || $loan->moveable_assets || $loan->intellectual_property || $loan->stocks_collateral || $loan->livestock_collateral)
        <p style="margin-left: 40px;">
        @if($loan->immovable_assets)<strong>i) Immovable assets:</strong> {{ $loan->immovable_assets }}<br>@endif
        @if($loan->moveable_assets)<strong>ii) Moveable Assets:</strong> {{ $loan->moveable_assets }}<br>@endif
        @if($loan->intellectual_property)<strong>iii) Intellectual property:</strong> {{ $loan->intellectual_property }}<br>@endif
        @if($loan->stocks_collateral)<strong>iv) Stocks:</strong> {{ $loan->stocks_collateral }}<br>@endif
        @if($loan->livestock_collateral)<strong>v) Livestock:</strong> {{ $loan->livestock_collateral }}@endif
        </p>
    @elseif(!($type === 'personal' && $borrower->assets && $borrower->assets->count() > 0))
        <p style="margin-left: 40px;">
        i) Immovable assets: ………………………………………<br>
        ii) Moveable Assets: Motorcycle, vehicles, etc. ………………………………………<br>
        iii) Intellectual property: ………………………………………<br>
        iv) Stocks: ………………………………………<br>
        v) Live stock: ………………………………………</p>
    @endif

    <p class="clause-title">7.2 Loans in arrears recovery Procedure:</p>
    <p>In the event of default, a written demand note will be issued to the client in the first week, stipulating payment of the overdue installment within five business days. Should payment not be received by the second week, a second demand note will be sent, requiring settlement of all outstanding loan installments within five business days. Failure to comply will result in the issuance of a Loan Recall Note in the third week. If the client's account remains in arrears for three consecutive weeks, the entire loan becomes due and payable within 14 business days.</p>

    <p class="clause-title">7.3 Selling of pledged non-cash collateral security:</p>
    <p>Where the Borrower fails to make payments for three consecutive weeks, the Lender shall recall the entire outstanding loan and shall apply the value of all pledged assets to recover the outstanding loan, interest, charges, and any other fees. The Borrower will be given 14 days to sell off the collateral but if the Borrower fails to sell the collateral and pay off the outstanding loan, the Borrower then grants the Lender all the rights to sell off the pledged collateral without protest to recover the total outstanding loan and all the applicable charges and fees. Any excess funds arising from the sale of collateral security shall be paid to the client.</p>

    <p class="section-title">8. Loan Guarantors</p>
    <p>The Borrower presents the following loan guarantors, and the guarantors willingly agree to guarantee the loan to the Borrower.</p>

    @if($loan->guarantors && $loan->guarantors->count() > 0)
        @foreach($loan->guarantors as $index => $guarantor)
        @php
            $guarantorMember = $guarantor->member;
        @endphp
        <p style="margin-top: 15px;"><strong>Guarantor {{ $index + 1 }}:</strong> {{ $guarantorMember->fname ?? '' }} {{ $guarantorMember->lname ?? '' }}, aged {{ $guarantorMember->dob ? \Carbon\Carbon::parse($guarantorMember->dob)->age : '____' }} years, NIN: {{ $guarantorMember->nin ?? '____________________' }}, a resident of {{ $guarantorMember->village ?? '____' }}, {{ $guarantorMember->parish ?? '____' }}, {{ $guarantorMember->subcounty ?? '____' }}.</p>
        <p style="margin-left: 20px;">Name: <strong>{{ $guarantorMember->fname ?? '' }} {{ $guarantorMember->lname ?? '' }}</strong></p>
        @if($guarantor->signature)
            @if($guarantor->signature_type === 'drawn')
            <p style="margin-left: 20px;"><img src="{{ $guarantor->signature }}" style="height: 50px;" alt="Guarantor Signature"></p>
            @else
            <p style="margin-left: 20px;"><img src="{{ \App\Services\FileStorageService::getFileUrl($guarantor->signature) }}" style="height: 50px;" alt="Guarantor Signature"></p>
            @endif
            <p style="margin-left: 20px;">Date: {{ \Carbon\Carbon::parse($guarantor->signature_date)->format('M d, Y \a\t h:i A') }}</p>
        @else
        <p style="margin-left: 20px;">Signature: _______________________________ Date: _______________</p>
        @endif
        @endforeach
    @else
        <p style="margin-top: 15px;"><strong>Guarantor One:</strong> [Name], aged [Age] years, NIN: ____________________, is a resident of [place of residence].</p>
        <p style="margin-left: 20px;">Name: ______________________________</p>
        <p style="margin-left: 20px;">Signature: _______________________________ Date: _______________</p>
        
        <p style="margin-top: 15px;"><strong>Guarantor Two:</strong> [Name], aged [Age] years, NIN: ____________________, is a resident of [place of residence].</p>
        <p style="margin-left: 20px;">Name: ______________________________</p>
        <p style="margin-left: 20px;">Signature: _______________________________ Date: _______________</p>
        
        <p style="margin-top: 15px;"><strong>Guarantor Three:</strong> [Name], aged [Age] years, NIN: ____________________, is a resident of [place of residence].</p>
        <p style="margin-left: 20px;">Name: ______________________________</p>
        <p style="margin-left: 20px;">Signature: _______________________________ Date: _______________</p>
    @endif

    <p style="margin-top: 15px;">It is hereby agreed and understood by all parties that by the said guarantors appending their signatures hereunder, they acknowledge legal liability/responsibility and fully understand that in the case of default of the principal Borrower, the Lender will proceed to recover the said loan balance from the Borrower and/or guarantors.</p>

    <p class="section-title">9. Supervision and Inspection</p>
    <p>The Lender reserves the right to coordinate, either directly or through authorized parties, and the Borrower is obligated to comply with any requests for information, clarification, or inspection issued by the Lender regarding the Borrower and/or their business.</p>

    <p class="section-title">10. Funding restrictions</p>
    <p>The Borrower guarantees that the borrowed sum will not be used for financing terrorists or engaging in any illegal activities, including money laundering. The Borrower acknowledges awareness of all prohibited business activities listed in Clause 12 of this agreement.</p>

    <p class="section-title">11. Declarations</p>
    <p>The Borrower agrees to provide truthful personal and business financial information, as well as business performance data, as requested by the Lender. Additionally, the Borrower commits to complying with all relevant laws of Uganda, including environmental and social regulations, as well as the Lender's product policies. The Borrower acknowledges that providing false or misleading information is unlawful, and any breach of this agreement may result in legal action by the Lender in a court of competent jurisdiction for resolution.</p>

    <p>The Borrower shall utilize the loan for the specified purpose outlined in Clause 2 of this agreement, and the Lender retains the ongoing authority to verify that the loan funds were indeed utilized for the intended purpose.</p>

    <p>The Borrower's failure to repay the loan as per the agreed repayment schedule will constitute an event of breach and default. In such a scenario, the Lender reserves the right to offset the outstanding loan amount against funds available in any savings account held by the Borrower or guarantors at any branch of the Lender, and may also utilize pledged cash collateral.</p>

    <p>In the event of the Borrower's death, bankruptcy, or liquidation, any outstanding sums owed to the Lender by the Borrower shall be recovered from the Borrower's estate. The Lender holds priority in settling the debts of the deceased or bankrupt Borrower. However, should the outstanding loan amount, along with associated costs, be covered by an insurance company, the Borrower's estate shall be exempt from such recourse.</p>

    <p>The Borrower must ensure the presence of valid and consistent insurance coverage for its business and assets, safeguarding against risks typical for companies engaged in similar business activities.</p>

    <p class="section-title">12. Credit Reference Bureau (CRB)</p>
    <p>The Borrower hereby agrees and authorizes the Lender to: Make inquiries from any bank, financial institution or association or any approved credit reference bureau to confirm any information provided by the Borrower. Provide and share all statutory information in respect of the loan facility and all subsequent facilities as shall be advanced to the Borrower from time to time and in line with the set Bank of Uganda credit reference bureau regulations.</p>

    <p class="section-title">13. Permitted Disclosures</p>
    <p>The Borrower consents to the Lender disclosing confidential information to any of its affiliates, and any of their current or bona fide prospective investors, directors, officers, employees, shareholders, Investment bankers, lenders, accountants, auditors, insurers, credit reference bureau business or financial advisors, and attorneys, in each case only where such persons are under appropriate nondisclosure obligation imposed by professional ethics, law or otherwise.</p>

    <p class="section-title">14. Exclusion List</p>
    <p>The Borrower hereby certifies that he/she is totally compliant with the Lender's business exclusion list. The Borrower shall not at any time anywhere in the world, perform or finance any activities immediately listed below:</p>

    <p>The Lender will not make loans or provide other financial services to individuals engaged in the following activities: Drift net fishing in the marine environment using nets more than 1 km in length; Significant conversion or degradation of critical habitat; Production, trade, storage, or transport of significant volumes of hazardous chemicals; Production or trade in radioactive materials; Production or trade in unbonded asbestos fibres; Production or activities involving harmful or exploitative forms of forced labour/harmful labour, child labour, Discriminatory practices; Relocation of Indigenous people from traditional or customary land; Production or trade in weapons and munitions as primary business activity; Production or trade in alcoholic beverages as a primary source of business activity; Production or trade in tobacco as primary business activity; Gambling, betting, casinos, and equivalent enterprises as a primary business activity; Any business related to pornography or prostitution; Cross-border trade in waste and waste products unless compliant to the Basel Convention; Production or trade in any activity deemed illegal under the Uganda laws or regulations or international conventions and agreements.</p>

    <p><strong>The Borrower has complied with the requirements of this exclusion list in clause 14 above.</strong></p>
    @if($loan->borrower_signature)
        @if($loan->borrower_signature_type === 'drawn')
        <p><img src="{{ $loan->borrower_signature }}" style="height: 50px;" alt="Borrower Signature"></p>
        @else
        <p><img src="{{ \App\Services\FileStorageService::getFileUrl($loan->borrower_signature) }}" style="height: 50px;" alt="Borrower Signature"></p>
        @endif
    @else
    <p>Signature: <span class="signature-line"></span></p>
    @endif
    <p>Name of Borrower: @if($type === 'personal') {{ $borrower->fname }} {{ $borrower->lname }} @else {{ $borrower->name }} @endif</p>

    <p class="section-title">15. Sanction List</p>
    <p>The Borrower hereby certifies that he/she is totally compliant with the Lender's business sanctions list outlined in this agreement under this clause. The Borrower shall not at any time engage in, authorize, or permit any person acting on its behalf to engage in any activity stipulated in the sanction list.</p>

    <p class="clause-title">15.1 Corruption and corrupt practices:</p>
    <p>A corrupt practice is the offering, giving, receiving, or soliciting, directly or indirectly, of anything of value to improperly influence the actions of another party. Corrupt practices are understood as kickbacks and bribery.</p>

    <p class="clause-title">15.2 Facilitation:</p>
    <p>The Lender does not condone facilitation payments.</p>

    <p class="clause-title">15.3 Fraudulent Practices:</p>
    <p>Fraudulent practice is any action or omission, including misrepresentation, that knowingly or recklessly misleads, or attempts to mislead, a party to obtain a financial benefit or to avoid an obligation.</p>

    <p class="clause-title">15.4 Coercive Practices:</p>
    <p>A Coercive Practice is impairing or harming, or threatening to impair, or harm, directly or indirectly, any party or property of the party to improperly influence the actions of a party, this also includes passive aggression.</p>

    <p class="clause-title">15.5 Collusive Practices:</p>
    <p>Collusive practice is an arrangement between two or more parties designed to achieve an improper purpose including to influence improperly the actions of another party.</p>

    <p class="clause-title">15.6 Obtrusive Practice:</p>
    <p>Deliberately destroying, falsifying, altering, or concealing of evidence material to the investigation or making of false statements to investigators, in order to materially impede the Lender's investigation into allegations of a corrupt, fraudulent, Coercive or collusive practice.</p>

    <p><strong>The Borrower has complied with the requirements of this sanction list.</strong></p>
    @if($loan->borrower_signature)
        @if($loan->borrower_signature_type === 'drawn')
        <p><img src="{{ $loan->borrower_signature }}" style="height: 50px;" alt="Borrower Signature"></p>
        @else
        <p><img src="{{ \App\Services\FileStorageService::getFileUrl($loan->borrower_signature) }}" style="height: 50px;" alt="Borrower Signature"></p>
        @endif
    @else
    <p>Signed by: <span class="signature-line"></span></p>
    @endif
    <p>Name of Borrower: @if($type === 'personal') {{ $borrower->fname }} {{ $borrower->lname }} @else {{ $borrower->name }} @endif</p>

    <p class="section-title">16. Applicable Law</p>
    <p>This agreement and any dispute or claim arising out of or in connection with it or its subject matter (including non-contractual disputes or claims) shall be governed by and construed in accordance with the law of Uganda.</p>

    <p class="section-title">17. Severability</p>
    <p>If any provision of this agreement or the application thereof shall, for any reason and to any extent, be invalid or unenforceable, neither the remainder of this agreement nor the application of the provision to other persons, entities or circumstances shall be affected thereby, but instead shall be enforced to the maximum extent permitted by law.</p>

    <p class="section-title">18. Non-Waiver</p>
    <p>No indulgence, waiver, election, or non-election by either party under this agreement shall affect the other party's duties and liability hereunder.</p>

    <p class="section-title">19. Modifications</p>
    <p>The parties hereby agree that this document contains the entire agreement between parties and this agreement shall not be modified, changed, altered, or amended in any way except through a written amendment signed by both parties.</p>

    <p class="section-title">20. Dispute Resolution</p>
    <p>The Parties shall use their best endeavours to resolve any dispute among them amicably. If the dispute is not resolved amicably, either party shall be free to commence court proceedings.</p>

    <p class="section-title">21. Force Majeure</p>
    <p>If at any time during this agreement it becomes impossible for one of the parties to fulfill its obligations for reasons beyond its control (hereinafter, "Force Majeure"), then the party must immediately notify the other party of the existence of the force majeure and take all reasonable measures to mitigate the effect of the force majeure. The occurrence of an event of force majeure shall not be abused by the Borrower to default on repayments and shall not apply accordingly.</p>

    <p>Notwithstanding the occurrence of a Force Majeure event, the Borrower's obligation to repay the principal amount, interest, and any other amounts due under this Agreement in accordance with the original terms and schedule herein agreed shall not be extended or altered, except as expressly agreed in writing by the Lender.</p>

    <p class="section-title">22. Cashless System</p>
    <p>All Savings or loan repayments must be made by the Borrower directly to the Lender's Bank Account through its Emuria Business Investment and Management Software (E-BIMS) Ltd payment system or through its E-BIMS APP.</p>

    <p>The Borrower is hereby advised that all cash payments to the Lender are strictly prohibited. The Borrower shall indemnify and hold the Lender harmless for any losses, damages, or claims arising from a breach of this condition. The Lender shall not be liable for any cash payments, and or cash payments to any staff member, or to any mobile payments to any staff members personal mobile money account.</p>

    <p style="text-align: center; margin-top: 40px;"><strong>IN WITNESS WHEREOF, the parties hereto have executed this Agreement on the date first above written.</strong></p>

    <div class="signature-section">
        <p><strong>Signed by:</strong></p>
        
        <p><strong>Borrower</strong></p>
        @if($type === 'personal')
        <p>Borrower One:</p>
        @if($loan->borrower_signature)
            @if($loan->borrower_signature_type === 'drawn')
            <p><img src="{{ $loan->borrower_signature }}" style="height: 50px;" alt="Borrower Signature"></p>
            @else
            <p><img src="{{ \App\Services\FileStorageService::getFileUrl($loan->borrower_signature) }}" style="height: 50px;" alt="Borrower Signature"></p>
            @endif
            <p>Signed on: {{ \Carbon\Carbon::parse($loan->borrower_signature_date)->format('M d, Y \a\t h:i A') }}</p>
        @else
        <p>Signature: <span class="signature-line"></span></p>
        @endif
        <p>Name: {{ $borrower->fname }} {{ $borrower->lname }}</p>
        <p>Tel: {{ $borrower->contact }}</p>
        @else
        <p>Group Representative:</p>
        @if($loan->borrower_signature)
            @if($loan->borrower_signature_type === 'drawn')
            <p><img src="{{ $loan->borrower_signature }}" style="height: 50px;" alt="Borrower Signature"></p>
            @else
            <p><img src="{{ \App\Services\FileStorageService::getFileUrl($loan->borrower_signature) }}" style="height: 50px;" alt="Borrower Signature"></p>
            @endif
            <p>Signed on: {{ \Carbon\Carbon::parse($loan->borrower_signature_date)->format('M d, Y \a\t h:i A') }}</p>
        @else
        <p>Signature: <span class="signature-line"></span></p>
        @endif
        @if($loan->group_representative_name)
        <p>Name: {{ $loan->group_representative_name }}</p>
        <p>Tel: {{ $loan->group_representative_phone }}</p>
        @else
        <p>Name: ___________________________</p>
        <p>Tel: ___________________________</p>
        @endif
        @endif

        <p style="margin-top: 20px;"><strong>Lender</strong></p>
        <p>For and on behalf of Emuria Business Investment and Management Software (E-BIMS) Ltd,</p>
        @if($loan->lender_signature)
            @if($loan->lender_signature_type === 'drawn')
            <p><img src="{{ $loan->lender_signature }}" style="height: 50px;" alt="Lender Signature"></p>
            @else
            <p><img src="{{ \App\Services\FileStorageService::getFileUrl($loan->lender_signature) }}" style="height: 50px;" alt="Lender Signature"></p>
            @endif
            @php
                $signer = $loan->lender_signed_by ? \App\Models\User::find($loan->lender_signed_by) : null;
            @endphp
            <p>Signed by: {{ $signer ? $signer->name : 'Branch Manager' }}</p>
            <p>Title: {{ $loan->lender_title ?? 'Branch Manager' }}</p>
            <p>Date: {{ \Carbon\Carbon::parse($loan->lender_signature_date)->format('M d, Y \a\t h:i A') }}</p>
        @else
        <p>Signature: <span class="signature-line"></span></p>
        <p>Title: Branch Manager</p>
        @endif

        <p style="margin-top: 20px;"><strong>Witnessed by:</strong></p>
        @if($loan->witness_signature)
            @if($loan->witness_signature_type === 'drawn')
            <p><img src="{{ $loan->witness_signature }}" style="height: 50px;" alt="Witness Signature"></p>
            @else
            <p><img src="{{ \App\Services\FileStorageService::getFileUrl($loan->witness_signature) }}" style="height: 50px;" alt="Witness Signature"></p>
            @endif
        @else
        <p>Signature: <span class="signature-line"></span></p>
        @endif
        @if($loan->witness_name)
        <p>Name: {{ $loan->witness_name }} &nbsp;&nbsp;&nbsp; NIN: {{ $loan->witness_nin }}</p>
        @else
        <p>Name: ___________________________ &nbsp;&nbsp;&nbsp; NIN: ___________________________</p>
        @endif
    </div>

</body>
</html>
