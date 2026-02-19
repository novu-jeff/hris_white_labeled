@php
    $provider = $provider ?? [];
    $companyName = strtoupper($provider['company'] ?? 'NOVULUTIONS, INC.');
    $payrollHeader = $payslip->payroll ?? null;
    $cutOffRaw = $payrollHeader && is_string($payrollHeader->cut_off_period) ? $payrollHeader->cut_off_period : '';
    $cutOff = $cutOffRaw;
    if ($cutOff !== '' && str_contains($cutOff, ' to ')) {
        $parts = array_map('trim', explode(' to ', $cutOff));
        try {
            $cutOff = \Carbon\Carbon::parse($parts[0] ?? '')->format('F j') . ' to ' . \Carbon\Carbon::parse($parts[1] ?? $parts[0])->format('F j, Y');
        } catch (\Throwable $e) {
            $cutOff = $cutOffRaw;
        }
    }
    $payrollDate = $payrollHeader && $payrollHeader->payroll_date
        ? \Carbon\Carbon::parse($payrollHeader->payroll_date)->format('F j, Y')
        : '';
    $product = config('app.product');
    $item = $payslip;
    $num = fn($v) => number_format((float)($v ?? 0), 2);
    $hrDetails = [
        'name' => 'Josephine Garcia',
        'email' => 'jo@novulutions.com',
        'phone' => '+63 920 923 5212',
    ];
@endphp
<div class="payslip-wrapper">
    <div class="payslip-container" id="payslipProtected">
        <div class="inner-content payslip-novu-template">
            {{-- HEADER: Company name + logo --}}
            @if(!empty($provider['client_logo']))
                <div class="mb-3"><img src="{{ asset('img/' . $provider['client_logo']) }}" alt="Logo" style="max-height: 50px;"></div>
            @endif
            <div class="company-name-novu">{{ $companyName }}</div>

            {{-- Employee & Payroll info (same layout as PDF) --}}
            <table class="header-table-novu mb-3">
                <tr>
                    <td class="label">Employee No.</td>
                    <td class="value">{{ $item->employee_no ?? '' }}</td>
                    <td class="col-spacer"></td>
                    <td class="label">Payroll Date</td>
                    <td class="value text-end">{{ $payrollDate }}</td>
                </tr>
                <tr>
                    <td class="label">Employee Name</td>
                    <td class="value">{{ $item->name ?? '' }}</td>
                    <td class="col-spacer"></td>
                    <td class="label">Cut-off Period</td>
                    <td class="value text-end">{{ $cutOff }}</td>
                </tr>
                <tr>
                    <td class="label">Position</td>
                    <td class="value" colspan="3">{{ $item->position ?? '' }}</td>
                </tr>
            </table>

            {{-- Two columns: EARNINGS | DEDUCTIONS --}}
            <div class="row g-4 mb-3 payslip-tables-row">
                <div class="col-lg-6">
                    <div class="section-header-novu">EARNINGS</div>
                    <table class="payslip-table-novu">
                        <tr><td class="item-label">Basic Salary</td><td class="item-amount">PHP {{ $num($item->basic_salary) }}</td></tr>
                        <tr><td class="item-label">Allowance/s</td><td class="item-amount"></td></tr>
                        <tr><td class="item-label sub-label">Communication</td><td class="item-amount">PHP {{ $num($item->communication_allowance ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Transportation</td><td class="item-amount">PHP {{ $num($item->transportation_allowance ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Other</td><td class="item-amount">PHP {{ $num($product === 'private' ? ($item->allowances ?? 0) : ($item->pera ?? 0)) }}</td></tr>
                        <tr><td class="item-label">De Minimis</td><td class="item-amount"></td></tr>
                        <tr><td class="item-label sub-label">Rice Allowance</td><td class="item-amount">PHP {{ $num($item->rice_allowance ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Laundry Allowance</td><td class="item-amount">PHP {{ $num($item->laundry_allowance ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Medical Cash Allowance</td><td class="item-amount">PHP {{ $num($item->medical_cash_allowance ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Uniform Allowance</td><td class="item-amount">PHP {{ $num($item->uniform_allowance ?? 0) }}</td></tr>
                        <tr><td class="item-label">Salary Adjustment</td><td class="item-amount">PHP {{ $num($item->salary_adjustment ?? 0) }}</td></tr>
                        <tr><td class="item-label">Overtime pay</td><td class="item-amount">PHP {{ $num($product === 'private' ? ($item->overtime_pay ?? 0) : 0) }}</td></tr>
                        <tr><td class="item-label">Incentive</td><td class="item-amount">PHP {{ $num($item->incentive ?? 0) }}</td></tr>
                        <tr><td class="item-label">Night differential</td><td class="item-amount">PHP {{ $num($item->night_differential ?? 0) }}</td></tr>
                        <tr><td class="item-label">Leave conversion</td><td class="item-amount">PHP {{ $num($item->leave_conversion ?? 0) }}</td></tr>
                        <tr><td class="total-row-novu item-label">GROSS EARNINGS</td><td class="total-row-novu item-amount">PHP {{ $num($item->gross_amount_earned) }}</td></tr>
                    </table>
                </div>
                <div class="col-lg-6">
                    <div class="section-header-novu">DEDUCTIONS</div>
                    <table class="payslip-table-novu">
                        <tr><td class="item-label">Government</td><td class="item-amount"></td></tr>
                        <tr><td class="item-label sub-label">Withholding Tax</td><td class="item-amount">PHP {{ $num($item->w_tax) }}</td></tr>
                        <tr><td class="item-label sub-label">SSS</td><td class="item-amount">PHP {{ $num($product === 'private' ? ($item->sss ?? 0) : 0) }}</td></tr>
                        <tr><td class="item-label sub-label">SSS - WISP</td><td class="item-amount">PHP {{ $num($item->sss_wisp ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">PhilHealth</td><td class="item-amount">PHP {{ $num($item->philhealth) }}</td></tr>
                        <tr><td class="item-label sub-label">HDMF</td><td class="item-amount">PHP {{ $num($product === 'private' ? ($item->pagibig ?? 0) : ($item->hdmf ?? 0)) }}</td></tr>
                        <tr><td class="item-label">Others</td><td class="item-amount"></td></tr>
                        <tr><td class="item-label sub-label">HDMF Loan</td><td class="item-amount">PHP {{ $num($item->hdmf_loan ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">SSS Loan</td><td class="item-amount">PHP {{ $num($item->sss_loan ?? 0) }}</td></tr>
                        @if($payslip->deductions && $payslip->deductions->where('reference_type', 'loan')->count())
                            @foreach($payslip->deductions->where('reference_type', 'loan') as $deduction)
                                <tr><td class="item-label sub-label">{{ $deduction->loan->loanType->name ?? 'Other Loan' }}</td><td class="item-amount">PHP {{ $num($deduction->amount) }}</td></tr>
                            @endforeach
                        @endif
                        <tr><td class="item-label sub-label">Other Loan</td><td class="item-amount">PHP {{ $num($product === 'private' ? ($item->other_loans ?? 0) : 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Advances</td><td class="item-amount">PHP {{ $num($item->advances ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Excess HMO Coverage</td><td class="item-amount">PHP {{ $num($item->excess_hmo ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Social Responsibility</td><td class="item-amount">PHP {{ $num($item->social_responsibility ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Others</td><td class="item-amount">PHP {{ $num($item->other_deductions ?? 0) }}</td></tr>
                        <tr><td class="item-label sub-label">Undertime / Absent</td><td class="item-amount">PHP {{ $num($item->aut ?? 0) }}</td></tr>
                        @if($product === 'government')
                            <tr><td class="item-label sub-label">GSIS (RLIP)</td><td class="item-amount">PHP {{ $num($item->rlip ?? 0) }}</td></tr>
                        @endif
                        <tr><td class="total-row-novu item-label">Total Deduction</td><td class="total-row-novu item-amount">PHP {{ $num($item->total_deductions) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="net-pay-row-novu py-2 px-3 mb-3">
                <span class="fw-bold">NET PAY</span>
                <span class="float-end">PHP {{ $num($item->net_amount) }}</span>
            </div>

            <div class="received-by-novu mb-3 text-center">
                <div>Received By: <span class="signature-line-novu"></span></div>
                <div class="mt-1">{{ $item->name ?? 'Employee Name' }}</div>
            </div>

            <div class="hr-contact-novu mb-3 text-center">
                <strong class="d-block">Novulutions Inc</strong>
                <span class="d-block">35th Floor, Ecotower Building, 9th Ave corner 32nd Street, BGC, Taguig City</span>
                <br>
                <strong class="d-block">HR Details</strong>
                <strong class="d-block">{{ $hrDetails['name'] }}</strong>
                <span class="d-block">Human Resource and Admin Officer</span>
                <span class="d-block">{{ $hrDetails['email'] }}</span>
                <span class="d-block">{{ $hrDetails['phone'] }}</span>
            </div>

            <div class="disclaimer-novu p-2 small text-secondary">
                <strong>Disclaimer:</strong><br>
                This payslip is confidential and intended solely for the authorized employee. By accessing or downloading this document through the HR Information System, the employee acknowledges responsibility for safeguarding its contents. This payslip is valid and official as of the date of issuance unless formally corrected by the Company. Any unauthorized disclosure or misuse may be subject to disciplinary action.
            </div>
        </div>
    </div>

    <div class="payslip-overlay"></div>
    <div class="payslip-watermark-diagonal-1">CONFIDENTIAL • {{ $item->name ?? '' }} • DO NOT COPY</div>
    <div class="payslip-watermark-diagonal-2">CONFIDENTIAL • DO NOT COPY</div>
    <div class="payslip-watermark-diagonal-3">CONFIDENTIAL • DO NOT COPY</div>
    <div class="payslip-watermark-diagonal-4">CONFIDENTIAL • DO NOT COPY</div>
    <div class="payslip-watermark-diagonal-5">CONFIDENTIAL • DO NOT COPY</div>
    <div class="payslip-watermark-center-large">CONFIDENTIAL</div>
</div>

<style>
.payslip-wrapper { --novu-blue: #005668; position: relative; width: 100%; max-width: 1200px; margin: 0; font-family: Arial, sans-serif; }
.payslip-container { position: relative; z-index: 5; background: #fff; padding: 0; margin: 0; border: 2px solid #005668; box-shadow: 0 0 12px rgba(0, 86, 104, 0.15); width: 100%; box-sizing: border-box; }
.payslip-container .inner-content.payslip-novu-template { padding: 24px 28px; max-width: 700px; }
.payslip-novu-template .company-name-novu { font-weight: bold; font-size: 1.2rem; color: #005668; margin-bottom: 14px; }
.payslip-novu-template .header-table-novu { width: 100%; }
.payslip-novu-template .header-table-novu td { padding: 6px 14px 6px 0; vertical-align: top; }
.payslip-novu-template .header-table-novu .label { font-weight: bold; color: #005668; white-space: nowrap; width: 1%; }
.payslip-novu-template .header-table-novu .value { width: 38%; }
.payslip-novu-template .header-table-novu .col-spacer { width: 4%; }
.payslip-novu-template .section-header-novu { background: #005668; color: #fff; font-weight: bold; padding: 8px 12px; font-size: 13px; }
.payslip-tables-row .col-lg-6 { min-width: 0; overflow: hidden; }
.payslip-novu-template .payslip-table-novu { width: 100%; border: 1px solid #005668; border-collapse: collapse; table-layout: fixed; }
.payslip-novu-template .payslip-table-novu td { padding: 8px 12px; border: 1px solid #4a9fb5; overflow: hidden; }
.payslip-novu-template .payslip-table-novu .item-label { width: 58%; min-width: 0; }
.payslip-novu-template .payslip-table-novu .item-amount { width: 42%; min-width: 110px; text-align: right; padding-left: 16px !important; white-space: nowrap; overflow: hidden; }
.payslip-novu-template .payslip-table-novu .sub-label { padding-left: 20px; }
.payslip-novu-template .total-row-novu { background: #005668; color: #fff; font-weight: bold; padding: 10px 12px !important; }
.payslip-novu-template .net-pay-row-novu { background: #005668; color: #fff; font-weight: bold; border: 1px solid #005668; padding: 12px 16px !important; font-size: 1.05rem; }
.payslip-novu-template .received-by-novu { text-align: center; }
.payslip-novu-template .received-by-novu .signature-line-novu { display: inline-block; border-bottom: 1px solid #000; min-width: 280px; margin-left: 8px; }
.payslip-novu-template .hr-contact-novu { border: 1px solid #ddd; background: #f9f9f9; line-height: 1.6; padding: 10px 14px !important; font-size: 12px; }
.payslip-novu-template .disclaimer-novu { border: 1px solid #ddd; background: #f9f9f9; line-height: 1.5; padding: 12px 16px !important; }

.payslip-overlay { position: absolute; top:0; left:0; width: 100%; height: 100%; background: repeating-linear-gradient(45deg, rgba(0,86,104,0.02) 0, rgba(0,86,104,0.02) 2px, transparent 2px, transparent 5px); pointer-events: none; z-index: 10; }
.payslip-watermark-diagonal-1, .payslip-watermark-diagonal-2, .payslip-watermark-diagonal-3, .payslip-watermark-diagonal-4, .payslip-watermark-diagonal-5 { position: absolute; font-size: 36px; font-weight: 100; color: rgba(0, 86, 104, 0.08); white-space: nowrap; pointer-events: none; z-index: 20; }
.payslip-watermark-diagonal-1 { top: 25%; left: -40%; transform: rotate(25deg); }
.payslip-watermark-diagonal-2 { bottom: 30%; left: -40%; transform: rotate(25deg); }
.payslip-watermark-diagonal-3 { bottom: 10%; left: -40%; transform: rotate(25deg); }
.payslip-watermark-diagonal-4 { top: 10%; left: -40%; transform: rotate(25deg); }
.payslip-watermark-diagonal-5 { top: 40%; left: -40%; transform: rotate(25deg); }
.payslip-watermark-center-large { position: absolute; top: 55%; left: 50%; transform: translate(-50%, -50%); font-size: 90px; font-weight: 900; color: rgba(0, 86, 104, 0.06); pointer-events: none; z-index: 20; white-space: nowrap; }
.payslip-container.blur { filter: blur(25px); transition: filter 0.3s; }
@media print { .payslip-wrapper body * { display: none !important; } }
</style>

<script>
(function() {
    const payslip = document.getElementById('payslipProtected');
    if (!payslip) return;
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') { e.preventDefault(); alert("Printing is disabled on this page."); }
    });
    document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
    document.addEventListener('selectstart', function(e) { e.preventDefault(); });
    document.addEventListener('keyup', function(e) {
        if (e.key === "PrintScreen") { payslip.classList.add('blur'); setTimeout(function() { payslip.classList.remove('blur'); }, 1200); }
    });
    window.addEventListener('blur', function() { payslip.classList.add('blur'); });
    window.addEventListener('focus', function() { payslip.classList.remove('blur'); });
})();
</script>
