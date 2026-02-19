<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        /* Novu blue: #005668 */
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9.6px;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }

        .inner-content {
            width: 100%;
            max-width: 100%;
            margin: 0;
            position: relative;
            padding: 8px 10px;
            box-sizing: border-box;
        }

        .company-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
            text-align: left;
            color: #005668;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .header-table td {
            padding: 3px 8px 3px 0;
            vertical-align: top;
        }

        .header-table .label {
            font-weight: bold;
            width: 1%;
            white-space: nowrap;
            color: #005668;
        }

        .header-table .value {
            width: 38%;
        }

        .header-table .col-spacer {
            width: 4%;
        }

        .section-header {
            background-color: #005668;
            color: #fff;
            font-weight: bold;
            font-size: 10.6px;
            padding: 4px 6px;
            margin: 0;
        }

        .total-row {
            background-color: #005668;
            color: #fff;
            font-weight: bold;
            padding: 4px 6px;
        }

        .two-col-wrap {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 8px;
            table-layout: fixed;
        }

        .two-col-wrap td {
            vertical-align: top;
            padding: 0 6px 0 0;
            width: 50%;
            box-sizing: border-box;
        }

        .two-col-wrap td:last-child {
            padding: 0 0 0 6px;
        }

        .two-col-wrap td .payslip-table {
            width: 100%;
            table-layout: fixed;
        }

        .payslip-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #005668;
            table-layout: fixed;
        }

        .payslip-table td {
            padding: 3px 6px;
            border: 1px solid #4a9fb5;
            overflow: hidden;
            word-wrap: break-word;
        }

        .payslip-table .item-label {
            width: 58%;
        }

        .payslip-table .item-amount {
            width: 42%;
            text-align: right;
            white-space: nowrap;
        }

        .payslip-table .sub-label {
            padding-left: 12px;
        }

        .net-pay-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }

        .net-pay-table td {
            background-color: #005668;
            color: #fff;
            font-weight: bold;
            padding: 5px 6px;
            border: 1px solid #005668;
        }

        .net-pay-table .amount {
            text-align: right;
        }

        .disclaimer {
            margin-top: 8px;
            font-size: 8.6px;
            color: #333;
            line-height: 1.25;
            border: 1px solid #005668;
            padding: 6px 8px;
            background: #f0f8f9;
        }
        .disclaimer strong { color: #005668; }

        .received-by {
            margin-top: 8px;
            padding-top: 2px;
            text-align: center;
        }

        .received-by .line {
            margin-bottom: 2px;
        }

        .received-by .signature-line {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 180px;
            margin-left: 4px;
        }

        .inner-content, .header-table, .two-col-wrap, .payslip-table, .net-pay-table, .received-by, .disclaimer {
            page-break-inside: avoid;
        }

    </style>
</head>
<body>
<div class="inner-content">
    @php
        $provider = $provider ?? [];
        $companyName = strtoupper($provider['company'] ?? 'NOVULUTIONS, INC.');
        $payroll = $payslip->payroll ?? null;
        $cutOffRaw = $payroll && is_string($payroll->cut_off_period) ? $payroll->cut_off_period : '';
        $cutOff = $cutOffRaw;
        if ($cutOff !== '' && str_contains($cutOff, ' to ')) {
            $parts = array_map('trim', explode(' to ', $cutOff));
            try {
                $cutOff = \Carbon\Carbon::parse($parts[0] ?? '')->format('F j') . ' to ' . \Carbon\Carbon::parse($parts[1] ?? $parts[0])->format('F j, Y');
            } catch (\Throwable $e) {
                $cutOff = $cutOffRaw;
            }
        }
        $payrollDate = $payroll && $payroll->payroll_date
            ? \Carbon\Carbon::parse($payroll->payroll_date)->format('F j, Y')
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

    @if(!empty($provider['client_logo']) && file_exists(public_path('img/' . $provider['client_logo'])))
        <div style="margin-bottom: 8px;"><img src="{{ public_path('img/' . $provider['client_logo']) }}" alt="Logo" style="max-height: 40px;"></div>
    @endif
    <div class="company-name">{{ $companyName }}</div>

    <table class="header-table">
        <tr>
            <td class="label">Employee No.</td>
            <td class="value">{{ $item->employee_no ?? '' }}</td>
            <td class="col-spacer"></td>
            <td class="label">Payroll Date</td>
            <td class="value">{{ $payrollDate }}</td>
        </tr>
        <tr>
            <td class="label">Employee Name</td>
            <td class="value">{{ $item->name ?? '' }}</td>
            <td class="col-spacer"></td>
            <td class="label">Cut-off Period</td>
            <td class="value">{{ $cutOff }}</td>
        </tr>
        <tr>
            <td class="label">Position</td>
            <td class="value" colspan="3">{{ $item->position ?? '' }}</td>
        </tr>
    </table>

    <table class="two-col-wrap">
        <tr>
            <td>
                <div class="section-header">EARNINGS</div>
                <table class="payslip-table">
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
                    <tr><td class="total-row item-label">GROSS EARNINGS</td><td class="total-row item-amount">PHP {{ $num($item->gross_amount_earned) }}</td></tr>
                </table>
            </td>
            <td>
                <div class="section-header">DEDUCTIONS</div>
                <table class="payslip-table">
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
                    <tr><td class="total-row item-label">Total Deduction</td><td class="total-row item-amount">PHP {{ $num($item->total_deductions) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="net-pay-table">
        <tr>
            <td>NET PAY</td>
            <td class="amount">PHP {{ $num($item->net_amount) }}</td>
        </tr>
    </table>

    <div class="received-by">
        <div class="line">Received By: <span class="signature-line"></span></div>
        <div class="line" style="margin-top: 6px;">{{ $item->name ?? 'Employee Name' }}</div>
    </div>

    <div class="disclaimer" style="margin-top: 6px;">
        <div style="text-align: center; line-height: 1.45;">
            <strong>Novulutions Inc</strong><br>
            <span>35th Floor, Ecotower Building, 9th Ave corner 32nd Street, BGC, Taguig City</span><br><br>
            <strong>HR Details</strong><br>
            <strong>{{ $hrDetails['name'] }}</strong><br>
            <span>Human Resource and Admin Officer</span><br>
            <span>{{ $hrDetails['email'] }}</span><br>
            <span>{{ $hrDetails['phone'] }}</span>
        </div>
    </div>

    <div class="disclaimer">
        <strong>Disclaimer:</strong><br>
        This payslip is confidential and intended solely for the authorized employee. By accessing or downloading this document through the HR Information System, the employee acknowledges responsibility for safeguarding its contents. This payslip is valid and official as of the date of issuance unless formally corrected by the Company. Any unauthorized disclosure or misuse may be subject to disciplinary action.
    </div>
</div>
</body>
</html>
