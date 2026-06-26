<?php

$outDir = __DIR__ . '/../outputs';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$outFile = $outDir . '/EBIMS_Client_Loan_Flow_Validation.docx';

function xesc(string $text): string
{
    return htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function text_runs(string $text, array $opts = []): string
{
    $bold = !empty($opts['bold']) ? '<w:b/>' : '';
    $italic = !empty($opts['italic']) ? '<w:i/>' : '';
    $color = isset($opts['color']) ? '<w:color w:val="' . xesc($opts['color']) . '"/>' : '';
    $size = isset($opts['size']) ? '<w:sz w:val="' . ((int) $opts['size'] * 2) . '"/>' : '';
    $font = isset($opts['font'])
        ? '<w:rFonts w:ascii="' . xesc($opts['font']) . '" w:hAnsi="' . xesc($opts['font']) . '"/>'
        : '';
    $rPr = ($bold || $italic || $color || $size || $font) ? '<w:rPr>' . $font . $bold . $italic . $color . $size . '</w:rPr>' : '';

    $parts = preg_split("/\r\n|\n|\r/", $text);
    $xml = '';
    foreach ($parts as $i => $part) {
        if ($i > 0) {
            $xml .= '<w:r><w:br/></w:r>';
        }
        $xml .= '<w:r>' . $rPr . '<w:t xml:space="preserve">' . xesc($part) . '</w:t></w:r>';
    }
    return $xml;
}

function p(string $text, string $style = 'Normal', array $opts = []): string
{
    $pPr = '<w:pStyle w:val="' . xesc($style) . '"/>';
    if (isset($opts['numId'])) {
        $level = (int) ($opts['level'] ?? 0);
        $pPr .= '<w:numPr><w:ilvl w:val="' . $level . '"/><w:numId w:val="' . (int) $opts['numId'] . '"/></w:numPr>';
    }
    if (isset($opts['shade'])) {
        $pPr .= '<w:shd w:val="clear" w:color="auto" w:fill="' . xesc($opts['shade']) . '"/>';
    }
    if (isset($opts['keepNext']) && $opts['keepNext']) {
        $pPr .= '<w:keepNext/>';
    }
    return '<w:p><w:pPr>' . $pPr . '</w:pPr>' . text_runs($text, $opts) . '</w:p>';
}

function page_break(): string
{
    return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
}

function table_xml(array $headers, array $rows, array $widths, array $opts = []): string
{
    $total = array_sum($widths);
    $indent = $opts['indent'] ?? 120;
    $headerFill = $opts['headerFill'] ?? 'F2F4F7';
    $headerColor = $opts['headerColor'] ?? '0B2545';
    $xml = '<w:tbl><w:tblPr>'
        . '<w:tblW w:w="' . $total . '" w:type="dxa"/>'
        . '<w:tblInd w:w="' . $indent . '" w:type="dxa"/>'
        . '<w:tblLayout w:type="fixed"/>'
        . '<w:tblCellMar><w:top w:w="100" w:type="dxa"/><w:left w:w="120" w:type="dxa"/><w:bottom w:w="100" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tblCellMar>'
        . '<w:tblBorders><w:top w:val="single" w:sz="4" w:color="D9E2EC"/><w:left w:val="single" w:sz="4" w:color="D9E2EC"/><w:bottom w:val="single" w:sz="4" w:color="D9E2EC"/><w:right w:val="single" w:sz="4" w:color="D9E2EC"/><w:insideH w:val="single" w:sz="4" w:color="D9E2EC"/><w:insideV w:val="single" w:sz="4" w:color="D9E2EC"/></w:tblBorders>'
        . '</w:tblPr><w:tblGrid>';
    foreach ($widths as $w) {
        $xml .= '<w:gridCol w:w="' . (int) $w . '"/>';
    }
    $xml .= '</w:tblGrid>';

    $allRows = [$headers];
    foreach ($rows as $row) {
        $allRows[] = $row;
    }

    foreach ($allRows as $ri => $row) {
        $xml .= '<w:tr>';
        if ($ri === 0) {
            $xml .= '<w:trPr><w:tblHeader/></w:trPr>';
        }
        foreach ($widths as $ci => $w) {
            $fill = $ri === 0 ? $headerFill : (($opts['zebra'] ?? false) && $ri % 2 === 0 ? 'FAFBFC' : null);
            $tcPr = '<w:tcW w:w="' . (int) $w . '" w:type="dxa"/>';
            if ($fill) {
                $tcPr .= '<w:shd w:val="clear" w:color="auto" w:fill="' . xesc($fill) . '"/>';
            }
            $text = isset($row[$ci]) ? (string) $row[$ci] : '';
            $cellOpts = $ri === 0
                ? ['bold' => true, 'color' => $headerColor, 'size' => 9]
                : ['size' => $opts['bodySize'] ?? 9];
            $xml .= '<w:tc><w:tcPr>' . $tcPr . '</w:tcPr>'
                . '<w:p><w:pPr><w:spacing w:after="40" w:line="260" w:lineRule="auto"/></w:pPr>'
                . text_runs($text, $cellOpts)
                . '</w:p></w:tc>';
        }
        $xml .= '</w:tr>';
    }
    return $xml . '</w:tbl>' . p('', 'Normal');
}

$body = '';

$body .= p('EBIMS Client Self-Application to Loan Listing', 'Title');
$body .= p('Flow and Excel Scoring Validation Memo', 'Subtitle');
$body .= p('Prepared: June 13, 2026 | Workbook: D:/bims/EBIMS RIVISED UNDERWRITING.xlsx | System: EBIMS Laravel application', 'Meta');
$body .= p('Validation verdict: the main path is valid when field verification is submitted and the system does not decline. The application is scored using the workbook-style calculation, then converted into a pending PersonalLoan record that appears in the normal loan list and approvals queue. The main control gap is the manual client-application approval route, which can still convert a pending application without the field-verification scoring path.', 'Callout');

$body .= p('1. Source Trail', 'Heading1');
$body .= table_xml(
    ['Area', 'Evidence reviewed', 'Validation result'],
    [
        ['Public client application', 'routes/web.php:41-44; app/Http/Controllers/ClientLoanApplicationController.php:34 and :258', 'Client submits at /apply. The saved application status is pending_fo_verification.'],
        ['Admin FO verification', 'routes/web.php:112-119; app/Http/Controllers/Admin/ClientApplicationController.php:113, :291, :304', 'FO verification saves FieldVerification, rejects immediately if FO selects reject, otherwise calls scoring.'],
        ['Workbook scoring service', 'app/Services/ClientLoanScoringService.php:112, :471, :511-555, :762-839', 'score() now enters the workbook-style scorer and evaluates evidence, VSS, DSCR, collateral, guarantor, gates, and decision.'],
        ['Loan conversion', 'app/Http/Controllers/Admin/ClientApplicationController.php:324, :414', 'Non-declined scoring result creates or finds a member and creates a PersonalLoan.'],
        ['Loan listing and approval', 'app/Http/Controllers/Admin/LoanController.php:34, :645', 'Converted PersonalLoan status 0 appears in the all-loans list and approvals queue, then loan approval changes status to 1.'],
    ],
    [2200, 3300, 3860],
    ['zebra' => true]
);

$body .= p('2. End-to-End Operational Flow', 'Heading1');
$flow = [
    'Client opens /apply and enters applicant, business, residence, community, financial, collateral, consent, and guarantor details.',
    'The public controller validates required fields, uploads documents, checks duplicate active applications, checks existing member active loans, generates an application code, and saves the record as pending_fo_verification.',
    'Branch staff see the record under Admin -> Client Applications, scoped through LoanAccessService by branch access.',
    'A field officer opens /admin/client-applications/{id}/verify while the application is still pending_fo_verification.',
    'The FO captures visit dates, KYC checks, business checks, verified monthly financials, CRB/arreas information, collateral inspection/legal details, community checks, guarantor confirmations, policy flags, and recommendation.',
    'If field_recommendation is reject, the application is rejected immediately and system scoring is skipped.',
    'If the FO recommendation is proceed or flag, FieldVerification is saved and ClientLoanScoringService::score() computes the workbook-style System Decision Layer.',
    'If final_decision is DECLINE, the client application is rejected with a system-decline reason.',
    'If final_decision is APPROVE, CONDITIONAL_APPROVAL, or APPROVE_WITH_MODIFICATION, the system creates or locates a Member and creates a PersonalLoan with status 0.',
    'The application is marked converted, member_id and loan_id are written, and staff are redirected to the new loan show page.',
    'The loan appears in the normal loans list and in the approvals queue. A loan approver can approve it after mandatory fee checks; status then becomes 1, ready for disbursement.',
];
foreach ($flow as $item) {
    $body .= p($item, 'ListParagraph', ['numId' => 2]);
}

$body .= p('3. Status and Ownership Map', 'Heading1');
$body .= table_xml(
    ['Stage', 'Owner', 'Record/status', 'What must be true'],
    [
        ['Self application', 'Client', 'ClientLoanApplication: pending_fo_verification', 'Application validates, duplicate guard passes, required documents and consents are captured.'],
        ['FO verification', 'Field officer', 'FieldVerification created', 'Visit and verification fields are submitted for KYC, business, cash flow, CRB, collateral, community, guarantors, and policy controls.'],
        ['System scoring', 'System', 'Scores written to ClientLoanApplication', 'Workbook formulas are applied. Gates determine BLOCK, REVIEW, or PASS.'],
        ['Decision handling', 'System', 'rejected or converted', 'DECLINE rejects. Any non-decline creates member/loan but does not disburse money.'],
        ['Loan queue', 'Credit/branch approver', 'PersonalLoan: status 0', 'Loan appears in admin loans and approvals for final loan approval.'],
        ['Loan approval', 'Approver', 'PersonalLoan: status 1', 'Fees and access controls pass; loan is ready for disbursement.'],
    ],
    [1800, 1700, 2500, 3360],
    ['zebra' => true]
);

$body .= page_break();
$body .= p('4. Excel Calculation Model Used for System Scoring', 'Heading1');
$body .= p('Workbook sheets reviewed: README, Controls, Lookups, Client_Intake, Field_Verification, System_Decision, and Executive_Decision. The scoring driver is System_Decision, with assumptions and thresholds in Controls and inputs coming from Client_Intake plus Field_Verification.', 'Normal');

$body .= p('4.1 Policy controls from the workbook', 'Heading2');
$body .= table_xml(
    ['Workbook cell', 'Control', 'Workbook value', 'System mapping'],
    [
        ['Controls!B4', 'Minimum collateral coverage', '3.0x', 'ClientLoanScoringService COL_MULT = 3.00 / policy key COL_MULT.'],
        ['Controls!B6', 'Target DSCR', '1.2x', 'policy key MIN_DSCR.'],
        ['Controls!B7', 'Weekly minimum VSS', '65', 'Workbook B37 references B7; workbook scoring path uses MIN_VSS = 65.'],
        ['Controls!B8', 'Monthly minimum VSS', '75', 'Available in controls; note System_Decision!B37 uses B7 in this workbook.'],
        ['Controls!B9', 'Evidence strong threshold', '70', 'policy key EVIDENCE_STRONG_THRESHOLD with fallback 70.'],
        ['Controls!B10', 'Arrears gate maximum days', '30', 'policy key MAX_ARREARS_DAYS.'],
    ],
    [1600, 2600, 1600, 3560],
    ['zebra' => true]
);

$body .= p('4.2 Score components', 'Heading2');
$body .= table_xml(
    ['Component', 'Workbook cell(s)', 'Calculation logic', 'System mapping'],
    [
        ['Evidence Score (ES)', 'System_Decision!B15', 'Sums client evidence from Client_Intake B40:B46 plus FO evidence from Field_Verification B28, B30, and B31. Strong threshold is 70.', 'excelEvidenceScore().'],
        ['Verification Strength Score (VSS)', 'System_Decision!B16', 'Ten FO checks worth 10 points each: KYC, residence, business, photos, sales record, and mobile money evidence.', 'excelVerificationStrengthScore().'],
        ['Verified disposable income', 'System_Decision!B17', 'MAX(0, sales + other income - COGS - expenses). Workbook is daily-shaped; app captures monthly and normalizes into the requested repayment period.', 'scoreUsingWorkbook() computes monthly disposable income, then period income.'],
        ['Installment and obligations', 'System_Decision!B18:B20', 'Flat installment = principal * (1 + interest * periods) / periods. DSCR = period income / (new installment + external installment).', 'scoreUsingWorkbook() uses product interest and requested/approved tenor.'],
        ['Collateral coverage', 'System_Decision!B21:B23', 'Total FSV / requested amount. Maximum collateral limit is total FSV / 3.0.', 'excelCollateralCoverageComponent(); COL_MULT = 3.0.'],
        ['Collateral saleability', 'System_Decision!B24:B26', 'Asset-type score adjusted by legal enforceability, then weighted by FSV.', 'excelCollateralSaleability().'],
        ['Guarantor support', 'System_Decision!B28:B31', 'Identity, pledge, consent, commitment, and guarantor security value produce the guarantor strength score.', 'excelGuarantorStrengthScore().'],
        ['Composite SDL', 'System_Decision!B32', '30% DSCR component + 20% VSS + 25% collateral coverage + 15% saleability + 10% guarantor score.', 'scoreUsingWorkbook() creates SDL and supporting metric fields.'],
    ],
    [1700, 1700, 3600, 2360],
    ['zebra' => true, 'bodySize' => 8]
);

$body .= p('4.3 Gates and final decision', 'Heading2');
$body .= table_xml(
    ['Workbook cell', 'Gate/decision', 'Logic', 'Expected system behavior'],
    [
        ['B35', 'Consents', 'Client verification consent, CRB consent, declaration truth, and pledge accepted must all pass.', 'BLOCK if missing.'],
        ['B36', 'Business evidence', 'ES >= Controls!B9 passes; otherwise REVIEW.', 'REVIEW if ES < 70.'],
        ['B37', 'VSS', 'VSS >= Controls!B7 passes.', 'BLOCK if VSS < 65 in current workbook path.'],
        ['B38', 'Arrears', 'Max arrears days <= Controls!B10.', 'BLOCK if > 30 days.'],
        ['B39', 'Legal proof and pledge', 'Collateral ownership/document checks and pledge explanation/understanding must pass.', 'BLOCK if missing.'],
        ['B40', 'FSV 3x', 'Collateral coverage >= Controls!B4.', 'BLOCK if FSV/requested < 3.0.'],
        ['B41', 'DSCR', 'DSCR >= Controls!B6.', 'BLOCK if DSCR < 1.2.'],
        ['B42', 'Saleability', 'Saleability >= 60 passes; otherwise REVIEW.', 'REVIEW if weak saleability.'],
        ['B43', 'Guarantor', 'No guarantor required passes; otherwise guarantor score >= 60.', 'REVIEW if weak guarantor support.'],
        ['B44', 'Overall gate', 'Any hard BLOCK blocks. If no block but any review gate exists, status is REVIEW. Otherwise PASS.', 'Controls final decision.'],
        ['B47:B51', 'Risk and recommendation', 'BLOCK => Decline. Else SDL >=80 Low, >=65 Moderate, >=50 High, below Reject. Recommendation maps to Approve, Conditional Approval, Approve with Modification, or Decline. Traffic is GREEN, AMBER, or RED.', 'System stores GREEN/YELLOW/RED, mapping workbook AMBER to YELLOW.'],
    ],
    [1400, 1800, 3900, 2260],
    ['zebra' => true, 'bodySize' => 8]
);

$body .= page_break();
$body .= p('5. Validation Findings', 'Heading1');
$body .= table_xml(
    ['Severity', 'Finding', 'Why it matters', 'Recommendation'],
    [
        ['Pass', 'Main flow is valid end-to-end.', 'A non-declined, FO-verified self-application becomes a pending PersonalLoan and appears in the normal loan workflow.', 'Keep this as the standard operating path.'],
        ['Pass', 'Workbook scoring is represented in the service.', 'ES, VSS, DSCR, collateral, saleability, guarantor score, gates, recommendation, and traffic light are all computed in the workbook-style path.', 'Use the workbook path as the source for system verification.'],
        ['Important', 'Manual client-application approve can bypass scoring.', 'ClientApplicationController::approve accepts pending_fo_verification and converts through doConvert without requiring FieldVerification or SDL scores.', 'Restrict manual approval to scored applications, or require an existing FieldVerification and non-decline final decision.'],
        ['Important', 'FO rejection skips system scoring.', 'This is acceptable if FO reject is intended as a hard stop, but it means no SDL metrics are recorded for those cases.', 'Decide whether rejected field cases should still receive a system score for audit records.'],
        ['Medium', 'Monthly app financial capture differs from workbook daily shape.', 'Workbook B17/B20 are daily-period formulas. The app captures monthly verified financials and normalizes to daily/weekly/monthly repayment periods.', 'Document this mapping in SOPs; keep field labels clear that app financials are monthly.'],
        ['Medium', 'Workbook AMBER is stored as YELLOW.', 'The DB enum supports GREEN/YELLOW/RED, while Excel says GREEN/AMBER/RED.', 'Keep mapping documented or extend enum/UI to AMBER if exact workbook wording is required.'],
        ['Medium', 'Risk band labels differ.', 'Excel has Low, Moderate, High, Reject. The system storage has Low, Medium, High; Moderate maps to Medium and Decline stores as a rejected decision.', 'Document the mapping or add exact workbook labels to the DB enum.'],
        ['Medium', 'Loan list quick tab filter mismatch observed.', 'LoanController filters by status=, while some quick tabs use filter=. The status select still works.', 'Change quick tabs to status=0/1/2 or add controller support for filter=.'],
        ['Note', 'Existing records are not automatically rescored.', 'Changing the scoring service affects new FO submissions or explicit rescoring only.', 'Run a controlled rescore job if historical applications must be updated.'],
    ],
    [1100, 2600, 3100, 2560],
    ['zebra' => true, 'bodySize' => 8]
);

$body .= p('6. Recommended Test Pack', 'Heading1');
$tests = [
    'Self-application happy path: submit /apply with all required applicant, business, collateral, consent, and guarantor fields. Confirm the created application status is pending_fo_verification.',
    'Duplicate guard: submit a second application with the same phone, NIN, or email while the first application is active. Confirm it is blocked.',
    'FO happy path: submit verification with VSS >= 65, ES >= 70, DSCR >= 1.2, collateral coverage >= 3.0x, arrears <= 30, legal proof complete, and saleability >= 60. Confirm non-decline decision and PersonalLoan status 0.',
    'DSCR block: set verified disposable cash flow too low so DSCR < 1.2. Confirm final_decision is DECLINE and application is rejected.',
    'Collateral block: set FSV/requested < 3.0 or missing legal proof. Confirm BLOCK and rejection.',
    'Evidence review: keep all hard gates passing but ES < 70. Confirm overall gate REVIEW and recommendation becomes Conditional Approval or related non-green outcome.',
    'Guarantor review: keep hard gates passing but guarantor score < 60 when a guarantor exists. Confirm REVIEW behavior.',
    'CRB/arrears block: set verified arrears days > 30. Confirm BLOCK and rejection.',
    'Loan list handoff: after a non-decline application, confirm the loan appears in /admin/loans with status 0 and in /admin/loans/approvals.',
    'Final loan approval: approve the pending loan with required fees satisfied. Confirm status moves from 0 to 1 and verified is set to 1.',
    'Manual bypass test: try ClientApplicationController manual approve on a pending_fo_verification app. Current behavior allows conversion for authorized users; this should fail after the recommended control is implemented.',
];
foreach ($tests as $test) {
    $body .= p($test, 'ListParagraph', ['numId' => 2]);
}

$body .= p('7. Bottom Line', 'Heading1');
$body .= p('The self-application, FO verification, system verification, and loan-listing flow is structurally correct and now follows the Excel scoring model. The borrower does not jump straight to disbursement: a non-decline decision creates a pending loan that still needs normal loan approval. To make the process fully controlled, close the manual approval bypass, decide how to audit FO rejections, and clean up the loan-list quick filter links.', 'Callout');

$contentTypes = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>
  <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
</Types>
XML;

$rels = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;

$docRels = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>
</Relationships>
XML;

$styles = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault>
    <w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="264" w:lineRule="auto"/></w:pPr></w:pPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:qFormat/>
    <w:pPr><w:spacing w:after="120" w:line="264" w:lineRule="auto"/></w:pPr>
    <w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/><w:color w:val="1F2937"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Title">
    <w:name w:val="Title"/>
    <w:basedOn w:val="Normal"/>
    <w:next w:val="Subtitle"/>
    <w:qFormat/>
    <w:pPr><w:spacing w:before="0" w:after="80"/></w:pPr>
    <w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:b/><w:sz w:val="44"/><w:color w:val="0B2545"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Subtitle">
    <w:name w:val="Subtitle"/>
    <w:basedOn w:val="Normal"/>
    <w:next w:val="Meta"/>
    <w:qFormat/>
    <w:pPr><w:spacing w:after="80"/></w:pPr>
    <w:rPr><w:i/><w:sz w:val="24"/><w:color w:val="4B5563"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Meta">
    <w:name w:val="Meta"/>
    <w:basedOn w:val="Normal"/>
    <w:pPr><w:spacing w:after="200"/></w:pPr>
    <w:rPr><w:sz w:val="19"/><w:color w:val="4B5563"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading1">
    <w:name w:val="heading 1"/>
    <w:basedOn w:val="Normal"/>
    <w:next w:val="Normal"/>
    <w:qFormat/>
    <w:pPr><w:keepNext/><w:outlineLvl w:val="0"/><w:spacing w:before="320" w:after="160"/></w:pPr>
    <w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="2E74B5"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading2">
    <w:name w:val="heading 2"/>
    <w:basedOn w:val="Normal"/>
    <w:next w:val="Normal"/>
    <w:qFormat/>
    <w:pPr><w:keepNext/><w:outlineLvl w:val="1"/><w:spacing w:before="240" w:after="120"/></w:pPr>
    <w:rPr><w:b/><w:sz w:val="26"/><w:color w:val="2E74B5"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Heading3">
    <w:name w:val="heading 3"/>
    <w:basedOn w:val="Normal"/>
    <w:next w:val="Normal"/>
    <w:qFormat/>
    <w:pPr><w:keepNext/><w:outlineLvl w:val="2"/><w:spacing w:before="160" w:after="80"/></w:pPr>
    <w:rPr><w:b/><w:sz w:val="24"/><w:color w:val="1F4D78"/></w:rPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="ListParagraph">
    <w:name w:val="List Paragraph"/>
    <w:basedOn w:val="Normal"/>
    <w:pPr><w:ind w:left="720" w:hanging="360"/><w:spacing w:after="120" w:line="280" w:lineRule="auto"/></w:pPr>
  </w:style>
  <w:style w:type="paragraph" w:styleId="Callout">
    <w:name w:val="Callout"/>
    <w:basedOn w:val="Normal"/>
    <w:pPr><w:spacing w:before="80" w:after="180" w:line="280" w:lineRule="auto"/><w:shd w:val="clear" w:color="auto" w:fill="F4F6F9"/><w:pBdr><w:left w:val="single" w:sz="18" w:space="6" w:color="2E74B5"/></w:pBdr></w:pPr>
    <w:rPr><w:sz w:val="22"/><w:color w:val="0B2545"/></w:rPr>
  </w:style>
</w:styles>
XML;

$numbering = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:abstractNum w:abstractNumId="1">
    <w:multiLevelType w:val="singleLevel"/>
    <w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="&#8226;"/><w:lvlJc w:val="left"/><w:pPr><w:tabs><w:tab w:val="num" w:pos="720"/></w:tabs><w:ind w:left="720" w:hanging="360"/></w:pPr></w:lvl>
  </w:abstractNum>
  <w:abstractNum w:abstractNumId="2">
    <w:multiLevelType w:val="singleLevel"/>
    <w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%1."/><w:lvlJc w:val="left"/><w:pPr><w:tabs><w:tab w:val="num" w:pos="720"/></w:tabs><w:ind w:left="720" w:hanging="360"/></w:pPr></w:lvl>
  </w:abstractNum>
  <w:num w:numId="1"><w:abstractNumId w:val="1"/></w:num>
  <w:num w:numId="2"><w:abstractNumId w:val="2"/></w:num>
</w:numbering>
XML;

$settings = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:zoom w:percent="100"/>
  <w:defaultTabStop w:val="720"/>
  <w:compat/>
</w:settings>
XML;

$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 wp14">'
    . '<w:body>' . $body
    . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/><w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr>'
    . '</w:body></w:document>';

$zip = new ZipArchive();
if ($zip->open($outFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Unable to create DOCX: {$outFile}\n");
    exit(1);
}

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('word/document.xml', $document);
$zip->addFromString('word/_rels/document.xml.rels', $docRels);
$zip->addFromString('word/styles.xml', $styles);
$zip->addFromString('word/numbering.xml', $numbering);
$zip->addFromString('word/settings.xml', $settings);
$zip->close();

echo $outFile . PHP_EOL;
