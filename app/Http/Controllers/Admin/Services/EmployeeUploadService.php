<?php

namespace App\Http\Controllers\Admin\Services;

use App\Helper\Generate;
use App\Http\Controllers\Controller;
use App\Models\EmployeeAccount;
use App\Models\EmployeeChildren;
use App\Models\EmployeeCivilService;
use App\Models\EmployeeEducation;
use App\Models\EmployeeEmploymentHistory;
use App\Models\EmployeeInformation;
use App\Models\EmployeeOtherWorks;
use App\Models\EmployeeParents;
use App\Models\EmployeePersonal;
use App\Models\EmployeeSkillsHobbies;
use App\Models\EmployeeTrainings;
use App\Models\EmployementTypes;
use App\Models\Positions;
use App\Models\Sections;
use App\Models\Tranche;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeeUploadService extends Controller
{
    private function normalizeHeader($header)
    {
        return strtolower(trim($header));
    }

    public function transformDate($value)
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                ->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    

    public function uploadEmployeeInformation(array $rows, array $schedules)
{
    if (count($rows) === 0) {
        Log::warning('Employee Information sheet is empty');
        return;
    }

    $skippedRows = [];
    $duplicateRows = [];
    $processedRows = 0;

    // Define expected headers
    $expectedHeaders = [
        'employee no.', 'bsd no.', 'lastname', 'firstname', 'middlename',
        'address', 'sex', 'civil status', 'birthday',
        'pagibig id', 'sss id', 'philhealth id', 'tin id', 'payroll account no',
        'date hired', 'job category', 'position', 'monthly salary', 'email', 'department', 'salary method'
    ];

    // Check if first row is header by matching at least 3 expected headers
    $firstRow = array_map(fn($v) => strtolower(trim((string)$v)), $rows[0]);
    $hasHeaderRow = count(array_intersect($expectedHeaders, $firstRow)) >= 3;

    // Build column map
    $columnMap = [];
    if ($hasHeaderRow) {
        foreach ($expectedHeaders as $field) {
            $index = array_search($field, $firstRow);
            $columnMap[$field] = $index !== false ? $index : null;
        }
    } else {
        // If no header, assume fixed order of columns
        $columnMap = array_combine($expectedHeaders, range(0, count($expectedHeaders) - 1));
    }

    $startRow = $hasHeaderRow ? 1 : 0;

    for ($i = $startRow; $i < count($rows); $i++) {
        $row = $rows[$i];

        // Map data safely
        $data = [];
        foreach ($columnMap as $field => $index) {
            $data[$field] = $index !== null ? $row[$index] : null;
        }

        if (empty($data['employee no.'])) {
            $skippedRows[] = ['row' => $i + 1, 'reason' => 'Missing employee number'];
            Log::warning('Employee upload skippedss', ['row' => $i + 1]);
            continue;
        }

        Log::Info('Processing employee', ['data' => $data]);

        $processedRows++;

        // Resolve job category and position
        $jobCategoryName = ucfirst(strtolower(trim($data['job category'] ?? '')));
        $positionName = trim($data['position'] ?? '');
        $sectionName = trim($data['department'] ?? '');

        $jobCategory = $jobCategoryName
            ? EmployementTypes::firstOrCreate(['name' => $jobCategoryName])
            : null;

        $position = $positionName
            ? Positions::firstOrCreate(['name' => $positionName])
            : null;

        $section = !empty($sectionName)
                ? Sections::firstOrCreate(['name' => $sectionName])
                : null;
            Log::info("Section processed", ['name' => $sectionName, 'id' => $section?->id]);    

        // Transform dates
        $birthday = $this->transformDate($data['birthday']);
        $dateHired = $this->transformDate($data['date hired']);

        $age = $birthday ? Carbon::parse($birthday)->age : null;

        // EmployeeInformation
        $employeeInfo = EmployeeInformation::updateOrCreate(
            ['employee_no' => $data['employee no.']],
            [
                'bsd_no' => $data['bsd no.'],
                'payroll_account_number' => $data['payroll account no'],
                'date_hired' => $data['date hired'],
                'position_id' => $position?->id,
                'section_id' => $section?->id,
                'salary' => $data['monthly salary'],
                'employment_type_id' => $jobCategory?->id,
                'email' => $data['email'],
                'unit' => $data['department'] ?? null,
                'shift_id' => $schedules['shift'] ?? null,
                'schedule_id' => $schedules['schedule'] ?? null,
                'salary_method' => strtolower($data['salary method']) ?? null,
            ]
        );

        if (!$employeeInfo->wasRecentlyCreated) {
            $duplicateRows[] = [
                'row' => $i + 1,
                'employee_no' => $data['employee no.'],
                'table' => 'employee_information'
            ];
            Log::notice('Duplicate employee information detected', [
                'employee_no' => $data['employee no.']
            ]);
        }

        // EmployeePersonal
        EmployeePersonal::updateOrCreate(
            ['employee_no' => $data['employee no.']],
            [
                'lastname' => $data['lastname'],
                'firstname' => $data['firstname'],
                'middlename' => $data['middlename'],
                'present_address' => $data['address'],
                'sex' => strtolower($data['sex'] ?? ''),
                'civil_status' => strtolower($data['civil status'] ?? ''),
                'birthday' => $birthday,
                'age' => $age,
                'pagibig_no' => $data['pagibig id'] ?? null,
                'sss_no' => $data['sss id'] ?? null,
                'philhealth_no' => $data['philhealth id'] ?? null,
                'tin_no' => $data['tin id'] ?? null,
            ]
        );

        // Create employee account
        $this->createAccount(
            $data['employee no.'],
            $data['firstname'],
            $data['lastname'],
            $data['email']
        );
    }

    Log::info('Employee upload completed', [
        'processed' => $processedRows,
        'skipped' => count($skippedRows),
        'duplicates' => count($duplicateRows),
        'skipped_rows' => $skippedRows,
        'duplicate_rows' => $duplicateRows,
    ]);
}


    private function createAccount($employeeNo, $firstName, $lastName, $email)
    {
        $generate = new Generate;
        $email_id = $generate->email($employeeNo, $firstName, $lastName);

        $user = EmployeeAccount::updateOrCreate(
            ['employee_no' => $employeeNo],
            [
                'email' => $email,
                'email_id' => $email_id,
                'password' => bcrypt('password')
            ]
        );

        if (!$user->wasRecentlyCreated) {
            Log::notice('Duplicate employee account', ['employee_no' => $employeeNo]);
        }

        $user->assignRole('employee');
    }
}
