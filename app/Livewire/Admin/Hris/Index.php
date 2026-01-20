<?php

namespace App\Livewire\Admin\Hris;

use App\Http\Controllers\Admin\Services\EmployeeUploadService;
use App\Imports\EmployeeImports;
use App\Models\EmployeeAccount;
use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeUpdatePersonal;
use App\Models\EmployementTypes;
use App\Models\ShiftSchedule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Bus\Batch;
use App\Notifications\Notifications;
use App\Jobs\EmployeeUpload;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;


class Index extends Component
{

    use WithFileUploads;
    use WithPagination;

    public $employee_no;
    public $isParsing;
    public $isUploading = false;
    public $file;
    public $upload_preview;
    public $resultMessage;
    public $countries;
    public $selected_id;
    public $isLinkSchedule = false;
    public $shifts;
    public $schedules;
    public $roles;
    public $shift_id;
    public $schedule_id;
    public $employmentTypes;
    public $selectedType;
    public $actionBy;
    public $isTransferingEmp;

    public bool $lazy = true;

    protected $listeners = ['remove', 'unlock', 'restore', 'loading', 'loadRecords'];

    protected $paginationTheme = 'bootstrap';
    public $entries = 10;
    public $search = '';

    public function mount() {
        $this->loadRecords();
    }

    public function loading() {
        $this->lazy = false;
    }

    public function loadRecords() {
        $this->actionBy = Auth::user();
        $this->shifts = ShiftSchedule::all();
        $this->schedules = EmployeeSchedule::all();
        $this->roles = EmployementTypes::all();
        $this->employmentTypes = EmployementTypes::all();

        if(session('dispatch') == 'isTransfering') {
            $this->dispatch('alert', [
                'status' => 'info',
                'title' => 'Please be informed', 
                'showAlert' => true,
                'message' => 'This employee account is currently undergoing data migration to the newly assigned employee number. The process will be completed shortly. Thank you for your patience and understanding.',
            ]);
        }

    }

    public function close_upload_employee() {
        $this->reset('upload_preview', 'file', 'isLinkSchedule');
    }

    public function select_change($property) {
        if($property === 'linkSchedule') {
            $this->isLinkSchedule = !$this->isLinkSchedule ? false : true;
        }
    }

    // public function updatedFile() {

    //     if (Gate::denies('write hris')) {
    //         $this->dispatch('alert', [
    //             'status' => 'error',
    //             'title' => 'Access Denied!',
    //             'showAlert' => true,
    //             'message' => 'You do not have permission to perform this action.',
    //         ]);
    //         return;
    //     }

    //     if ($this->file) {

    //         $this->upload_preview;

    //         $file = $this->file;

    //         if ($file instanceof \Illuminate\Http\UploadedFile) {
    //             $extension = strtolower($file->getClientOriginalExtension());

    //             if (in_array($extension, ['xls', 'xlsx'])) {
    //                 try {

    //                     $files = Storage::files('public/temp/files');

    //                     Storage::delete($files);

    //                     $fileName = uniqid() . '.' . $extension;

    //                     $file->storeAs('public/temp/files', $fileName);

    //                     $this->upload_preview = asset('storage/temp/files/' . $fileName);

    //                     $this->isParsing = false;

    //                 } catch (\Exception $e) {
    //                     $this->addError('file', 'There was an error saving the file to temporary storage.');
    //                     $this->isParsing = false;
    //                 }
    //             } else {
    //                 $this->addError('file', 'The file must be an Excel file (.xls or .xlsx).');
    //             }
    //         }

    //         $this->file = null;
    //     } else {
    //         $this->isParsing = true;
    //     }

    // }

    public function updatedFile()
    {
        if (Gate::denies('write hris')) {
            $this->addError('file', 'You do not have permission to perform this action.');
            return;
        }

        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        // Just mark preview-ready
        $this->upload_preview = $this->file->getClientOriginalName();
    }


    // public function upload_file()
    // {
    //     if (Gate::denies('write hris')) {
    //         $this->dispatch('alert', [
    //             'status' => 'error',
    //             'title' => 'Access Denied!',
    //             'showAlert' => true,
    //             'message' => 'You do not have permission to perform this action.',
    //         ]);
    //         return;
    //     }

    //     $this->isUploading = true;

    //     try {
    //         $relativePath = str_replace(asset('storage/'), '', $this->upload_preview);
    //         $absolutePath = storage_path('app/public/' . $relativePath);

    //         if (!Storage::exists('public/' . $relativePath)) {
    //             throw new \Exception('File does not exist in storage.');
    //         }

    //         $spreadsheet = IOFactory::load($absolutePath);
    //         $sheetNames = $spreadsheet->getSheetNames();

    //         $sheetsData = Excel::toArray(new EmployeeImports, $absolutePath);

    //         $this->validateUploaded($spreadsheet, $sheetNames);

    //         $schedules = [
    //             'shift' => $this->shift_id,
    //             'schedule' => $this->schedule_id
    //         ];

    //         $jobs = [];

    //         foreach ($sheetsData as $index => $sheet) {
    //             $sheetName = $sheetNames[$index];

    //             $sheet = array_slice($sheet, 1);
    //             $sheet = array_filter($sheet, fn($row) =>
    //                 isset($row[0]) && !empty($row[0]) &&
    //                 !empty(array_filter($row, fn($v) => $v !== null && $v !== ''))
    //             );
    //             $sheet = array_values($sheet);

    //             $chunks = array_chunk($sheet, 100);

    //             foreach ($chunks as $chunk) {
    //                 $jobs[] = new EmployeeUpload($chunk, $sheetName, $schedules);
    //             }
    //         }
            
    //         if(!empty($jobs)) {
    //             Bus::batch($jobs)
    //                 ->withOption('actionBy', [
    //                     'id' => $this->actionBy->id,
    //                     'name' => $this->actionBy->name
    //                 ])
    //                 ->name('Employee Uploading')
    //                 ->catch(function (Batch $batch, \Throwable $e) {
    //                     \Log::error('Error: ' . $e->getMessage());
    //                     $this->actionBy?->notify(new Notifications(
    //                         'error',
    //                         'An error occurred during the uploading of employee informations.',
    //                         route('system.jobs', ['id' => $batch->id]),
    //                         'admin'
    //                     ));
    //                 })
    //                 ->then(function (Batch $batch) { 
    //                     $this->actionBy?->notify(new Notifications(
    //                         'success',
    //                         'The uploading of employee informations has been successful.',
    //                         route('system.jobs', ['id' => $batch->id]),
    //                         'admin'
    //                     ));
    //                 })
    //                 ->dispatch();


    //             $this->dispatch('hideModal', [
    //                 'modal' => 'upload_employee'
    //             ]);

    //             $this->dispatch('alert', [
    //                 'status' => 'info',
    //                 'title' => 'Please be informed',
    //                 'showAlert' => true,
    //                 'message' => 'The uploading of employee has been started. We are currently processing the data. You will receive another notification once the upload is complete. Thank you for your patience.',
    //             ]);

    //             $this->reset(['shift_id', 'schedule_id', 'isLinkSchedule']);
    //             $this->loadRecords();
    //         } else {
    //             return $this->dispatch('alert', [
    //                 'showAlert' => true,
    //                 'status' => 'error',
    //                 'title' => 'Oops',
    //                 'message' => 'No jobs were processed'
    //             ]);

    //         }

    //     } catch (\Exception $e) {

    //         logger()->error('Error uploading file: ' . $e->getMessage());

    //         $this->dispatch('alert', [
    //             'status' => 'error',
    //             'title' => 'Oops!',
    //             'isRemoveRowDT' => true,
    //             'showAlert' => true,
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ]);
    //     } finally {
    //         $this->isUploading = false;
    //     }
    // }

    public function upload_file()
    {
        if (Gate::denies('write hris')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        $this->isUploading = true;

        try {

            $this->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:5120',
            ]);

            // Store file ONCE here (correct Livewire flow)
            $path = $this->file->storeAs(
                'public/temp/files',
                uniqid() . '.' . $this->file->getClientOriginalExtension()
            );

            $absolutePath = storage_path('app/' . $path);

            if (!file_exists($absolutePath)) {
                throw new \Exception('Uploaded file not found on server.');
            }

            $spreadsheet = IOFactory::load($absolutePath);
            $sheetNames  = $spreadsheet->getSheetNames();
            $sheetsData  = Excel::toArray(new EmployeeImports, $absolutePath);

            $this->validateUploaded($spreadsheet, $sheetNames);

            $schedules = [
                'shift' => $this->shift_id,
                'schedule' => $this->schedule_id
            ];

            $jobs = [];

            foreach ($sheetsData as $index => $sheet) {
                $sheetName = $sheetNames[$index];

                $sheet = array_slice($sheet, 1);
                $sheet = array_filter($sheet, fn ($row) =>
                    isset($row[0]) && !empty($row[0]) &&
                    !empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))
                );

                $chunks = array_chunk(array_values($sheet), 100);

                \Log::debug("Preparing jobs for sheet '{$sheetName}' with " . count($sheet) . " rows in " . count($chunks) . " chunks.");

                foreach ($chunks as $chunk) {
                    $jobs[] = new EmployeeUpload($chunk, $sheetName, $schedules);
                }
            }

            if (empty($jobs)) {
                throw new \Exception('No valid data found in the uploaded file.');
            }

            Bus::batch($jobs)
                ->name('Employee Uploading')
                ->dispatch();

            $this->dispatch('hideModal', ['modal' => 'upload_employee']);

            $this->dispatch('alert', [
                'status' => 'info',
                'title' => 'Please be informed',
                'showAlert' => true,
                'message' => 'Employee upload has started. You will be notified once completed.',
            ]);

            $this->reset(['file', 'upload_preview', 'shift_id', 'schedule_id', 'isLinkSchedule']);
            $this->loadRecords();

        } catch (\Throwable $e) {

            logger()->error('Employee upload failed', [
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Upload Failedsss',
                'showAlert' => true,
                'message' => $e->getMessage(),
            ]);

        } finally {
            $this->isUploading = false;
        }
    }


    public function validateUploaded($spreadsheet, $sheetNames) {

        $product = env('APP_PRODUCT');

        if($product == 'government') {
            $emp_info_req = [
                'employee no.', 'bsd no.', 'lastname', 'firstname', 'middlename',
                'address', 'email', 'sex', 'civil status', 'birthday', 'age',
                'gsis id', 'pagibig id', 'philhealth id', 'tin id', 'bank account no.',
                'date hired', 'job category', 'position', 'unit', 'monthly salary'
            ];
            // $opt_req = ['job categories', 'bool', 'civil status', 'sex', 'departments', 'positions', 'units'];
            $opt_req = ['job categories', 'bool', 'civil status', 'sex', 'departments'];
        } else {
            $emp_info_req = [
                'employee no.', 'bsd no.', 'lastname', 'firstname', 'middlename',
                'address', 'email', 'sex', 'civil status', 'birthday',
                'pagibig id', 'sss id','philhealth id', 'tin id', 'payroll account no.',
                'date hired', 'job category', 'position', 'monthly salary','department', 'email'
            ];
            $opt_req = ['job categories', 'bool', 'civil status', 'sex', 'departments'];
        }

        $expectedSheets = [
            'employee information' => $emp_info_req,
            'family background' => [
                'employee no.', 'spouse surname', 'spouse firstname', 'spouse middlename',
                'spouse suffix', 'spouse occupation', 'spouse business name',
                'spouse business address', 'spouse contact no', "father surname",
                "father firstname", "father middlename", "father suffix",
                "mother surname", "mother firstname", "mother middlename"
            ],
            'children' => [
                'employee no.', 'firstname', 'middlename', 'lastname', 'birthdate'
            ],
            'education' => [
                'employee no.', 'level', 'school name', 'course', 'from year', 'to year'
            ],
            'employment history' => [
                'employee no.', 'position', 'department', 'company name', 
                'monthly salary', 'employment status', 'is government?', 
                'from year', 'to year'
            ],
            'civil service' => [
                'employee no.', 'certification', 'rating', 'date exam', 'place exam', 
                'license no', 'date validity'
            ],
            'trainings' => [
                'employee no.', 'type', 'name', 'date from', 'date to', 
                'consumed hours', 'sponsored by'
            ],
            'other works' => [
                'employee no.', 'organization', 'address', 'date from', 
                'date to', 'consumed hours', 'position'
            ],
            'skills' => [
                'employee no.', 'skill / hobbies name', 
                'recognition', 'organization'
            ],
            'options' => $opt_req
        ];

        foreach ($sheetNames as $sheetName) {

            $sheetNameLower = strtolower($sheetName);

            if (array_key_exists($sheetNameLower, $expectedSheets)) {
                $sheetData = $spreadsheet->getSheetByName($sheetName)->toArray();

                $sheetData = array_map(function ($row) {
                    return array_filter($row, function ($value) {
                        return $value !== null;  
                    });
                }, $sheetData);

                if (empty($sheetData)) {
                    throw new \Exception("Sheet '{$sheetName}' is empty.");
                }

                $header = $sheetData[0];

                $headerLower = array_map(function ($item) {
                    return strtolower(trim($item));
                }, $header);

                $expectedHeader = array_map('strtolower', array_map('trim', $expectedSheets[$sheetNameLower]));

                $missingHeaders = array_diff($expectedHeader, $headerLower);

                if (!empty($missingHeaders)) {
                    $missingList = implode(', ', $missingHeaders);
                    Log::error("Sheet '{$sheetName}' is missing required headers: {$missingList}");
                    throw new \Exception("Missing column(s): {$missingList} at sheet {$sheetName}");
                }
            } else {
                Log::error("Unexpected sheet '{$sheetName}' found in the file.");
                throw new \Exception("Uploaded file contains invalid format");
            }
        }


        return true;
    }

    public function remove(bool $isNotify = true, ? string $employee_no = null) {

        if (Gate::denies('write hris')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you are about to delete employee <b>' . strtoupper($employee_no) . '</b>. Once this action is completed, it cannot be undone or reversed!';
            $action = 'remove';

            $this->selected_id = $employee_no;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeInformation::where('employee_no', $this->selected_id)->first();

            if($record) {

                $record->isDeleted = true;
                $record->status = 'inactive';
                $record->save();

                $record = EmployeeUpdatePersonal::where('employee_no', $this->employee_no)->first();

                $this->loadRecords();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!',
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Employee ' . strtoupper($this->selected_id) . ' was deleted successfully.'
                ]);

            } else {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!',
                    'isRemoveRowDT' => false,
                    'message' => 'Error: ID does not exists'
                ]);
            }
        }
    }

    public function unlock(bool $isNotify = true, ? string $employee_no = null) {

        if (Gate::denies('write hris')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that this account has been locked due to multiple login attempts. Are you sure to unlock account  <b>' . strtoupper($employee_no) . '?</b>. Once this action is completed, it cannot be undone or reversed!';
            $action = 'unlock';

            $this->selected_id = $employee_no;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeAccount::where('employee_no', $this->selected_id)->first();

            if($record) {

                $record->isLocked = false;
                $record->login_attempts = 0;
                $record->save();

                $this->loadRecords();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!',
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Employee ' . strtoupper($this->selected_id) . ' account has been unlocked.'
                ]);

            } else {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!',
                    'isRemoveRowDT' => false,
                    'message' => 'Error: ID does not exists'
                ]);
            }
        }
    }

    public function restore(bool $isNotify = true, ? string $employee_no = null) {

        if (Gate::denies('write hris')) {
            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Access Denied!',
                'showAlert' => true,
                'message' => 'You do not have permission to perform this action.',
            ]);
            return;
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Please be informed that this archived account will be restored. Once this action is completed, it cannot be undone or reversed!';
            $action = 'restore';

            $this->selected_id = $employee_no;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $record = EmployeeInformation::where('employee_no', $this->selected_id)
                ->first();

            if($record) {

                $record->isDeleted = false;
                $record->status = 'active';
                $record->save();

                $this->loadRecords();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!',
                    'id' => $this->selected_id,
                    'isRemoveRowDT' => true,
                    'message' => 'Employee ' . strtoupper($this->selected_id) . ' account has been restored.'
                ]);

            } else {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!',
                    'isRemoveRowDT' => false,
                    'message' => 'Error: ID does not exists'
                ]);
            }
        }
    }

    public function changeEmployeeNo($employee_no) {
        $this->dispatch('showModal', [
            'modal' => 'change_employee_no',
        ]);

        $this->dispatch('setEmployeeNo', employee_no: $employee_no);

    }

    public function render()
    {
        $query = EmployeeInformation::with('account', 'personal');

        if ($this->selectedType !== null) {
            if ($this->selectedType === 'unassigned') {
                $query->whereNull('employment_type_id')
                    ->where('isDeleted', false);
            } else if($this->selectedType === 'archived') {
                $query->where('isDeleted', true);
            } else {
                $query->where('employment_type_id', $this->selectedType)
                    ->where('isDeleted', false);
            }
        } else {
             $query->where('isDeleted', false);
        }

        if (!empty($this->search)) {
            $this->resetPage();

            $query->where(function ($q) {
                $q->where('employee_no', 'like', '%' . $this->search . '%')
                ->orWhereHas('personal', function ($subQuery) {
                    $subQuery->whereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ['%' . $this->search . '%']);
                });
            });
        }

        $employees = $query->latest()->paginate($this->entries);

        return view('livewire.admin.hris.index', [
            'employees' => $employees
        ]);
    }


}
