<?php

namespace App\Livewire\Employee\Profile;

use App\Models\EmployeeAccount;
use App\Models\EmployeeCivilService;
use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\EmployeeUpdateCivilService;
use App\Notifications\Notifications;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CivilService extends Component
{

    use WithFileUploads;

    public $employee_id;
    public $employee_no;
    public $originalData;
    public $isFromUpdate = false;
    public $recordIndex;
    public $records;

    protected $listeners = ['save', 'removeRecord'];

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords() {

        $user = Auth::guard('employee')->user() ?? Auth::user();
        if (!$user) {
            // Session expired / wrong guard - redirect to employee login.
            return redirect()->route('employee.login');
        }

        $this->employee_no = $user->employee_no;
        $this->employee_id = $user->id;

        $updated = EmployeeUpdateCivilService::where('employee_no', $this->employee_no)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray() ?? [];
        $stored = EmployeeCivilService::where('employee_no', $this->employee_no)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray() ?? [];

        if(!empty($updated)) {
            $data = $updated;
            $this->isFromUpdate = true;
        } else {
            $data = $stored;
            $this->isFromUpdate = false;
        }

        $this->originalData = $data;
        $this->records = $data;

    }

      public function addRecord() {
        if (isset($this->defaultFields)) {
            $this->records[] = $this->defaultFields;
        }
    }

    public function removeRecord(bool $isNotify, $index = null) {
        
        if($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'Please be informed that you will be deleting this record and cannot be undone.';
            $action = 'removeRecord';
            $this->recordIndex = $index;
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);
            return;
        }

        // If called from confirmation without a valid index, bail safely.
        if ($this->recordIndex === null) {
            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Unable to delete',
                'message' => 'No record selected for deletion.',
            ]);
            return;
        }
        
        // Support direct calls like removeRecord(false, index)
        if ($index !== null) {
            $this->recordIndex = $index;
        }

        $updatedRecords = EmployeeUpdateCivilService::where('employee_no', $this->employee_no)
            ->orderBy('created_at', 'asc')
            ->get();

        $storedRecords = EmployeeCivilService::where('employee_no', $this->employee_no)
            ->orderBy('created_at', 'asc')
            ->get();

        $records = $updatedRecords->isNotEmpty() ? $updatedRecords : $storedRecords;

        $record = $records[$this->recordIndex] ?? null;

        if (!$record) {
            // If it's a newly-added (unsaved) row, just remove from UI state.
            if (isset($this->records[$this->recordIndex])) {
                unset($this->records[$this->recordIndex]);
                $this->records = array_values($this->records);
                return;
            }

            $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Record not found',
                'message' => 'This record may have already been deleted. Please refresh the page.',
            ]);
            $this->loadRecords();
            return;
        }

        if ($record && $record->documents) {
            $path = 'documents/' . $this->employee_no . '/' . $record->documents;

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

        }

        $record->delete();

        unset($this->records[$this->recordIndex]);
        $this->records = array_values($this->records);
        
    }

    private $defaultFields = [
        'certification' => '',
        'rating' => '',
        'date_exam' => '',
        'place_exam' => '',
        'license_no' => '',
        'date_validity' => '',
        'documents' => ''
    ];

    protected function rules(?string $employee_no = null) {
        return [
            'records.*.certification' => 'required|string|max:255',
            'records.*.rating' => 'required|string|max:255',
            'records.*.date_exam' => 'required|string|max:255',
            'records.*.place_exam' => 'required|string|max:255',
            'records.*.license_no' => 'nullable|string|max:255',
            'records.*.date_validity' => 'nullable|date',
            'records.*.documents' => 'nullable|mimes:jpg,png,jpeg,pdf',
            'records.*.documents' => function ($attribute, $value, $fail) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                $files = is_array($value) ? $value : [$value];
                foreach ($files as $file) {
                    if ($file instanceof TemporaryUploadedFile) {
                        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                            $fail("The document must be a JPEG, PNG, or PDF file.");
                        }
                    } 
                }
            },
        ];
    }

    protected function messages() {
        return [
            'records.*.certification.required' => '* required',
            'records.*.rating.required' => '* required',
            'records.*.date_exam.required' => '* required',
            'records.*.place_exam.required' => '* required',
            'records.*.license_no.required' => '* required',
            'records.*.date_validity.required' => '* required',
            'records.*.date_validity.date' => '* required',
        ];
    }

    public function hasChanges()
    {
        $originalData = $this->originalData ?? [];
        $records = $this->records;

        foreach ($records as $key => $newValue) {
            if (!array_key_exists($key, $originalData)) {
                return true;
            }

            $oldValue = $originalData[$key];

            if ($newValue !== $oldValue) {
                return true;
            }
        }

        return false;
    }

    public function setErrorActiveTabAccordions(array $errorKeys) {
        $this->dispatch('scrollToError', $errorKeys);
    }

    private function uploadFile($employee_no, $identifier, $path, $file)
    {
        if ($file instanceof TemporaryUploadedFile) {
            $record = EmployeePersonal::with([
                'children', 'employment_history', 'civil_service', 'trainings', 'others', 'skills'
            ])->where('employee_no', $employee_no)->first();
        
            if ($record) {
                if (!empty($record->$identifier)) {
                    Storage::disk('public')->delete("$path/{$record->$identifier}");
                }
        
                if ($identifier === 'documents') {
                    foreach (['children', 'employment_history', 'civil_service', 'trainings', 'others', 'skills'] as $relation) {
                        if ($record->$relation && !empty($record->$relation->$identifier)) {
                            Storage::disk('public')->delete("$path/{$record->$relation->$identifier}");
                        }
                    }
                }
            }
            
            $filename = uniqid(time()) . '.' . $file->getClientOriginalExtension();
            $file->storeAs($path, $filename, 'public');
        
            return $filename;
        }
        
        $record = EmployeePersonal::where('employee_no', $employee_no)->first();
        return $record->$identifier ?? null;
        
    }

    public function download(int $index) {
        
        $files = EmployeeUpdateCivilService::where('employee_no', $this->employee_no)
            ->orderBy('created_at', 'asc')
            ->pluck('documents');

        $files = $files->isNotEmpty()
            ? $files
            : EmployeeCivilService::where('employee_no', $this->employee_no)
                ->orderBy('created_at', 'asc')
                ->pluck('documents');

        $file = $files[$index] ?? null;

        $path = 'documents/' . $this->employee_no . '/' . $file;

        if ($file && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path);
        }

        return $this->dispatch('alert', [
            'status' => 'error',
            'title' => 'Oops',
            'isRemoveRowDT' => false,
            'showAlert' => true,
            'message' => 'The file you\'re trying to download could not be located. It may have been moved, renamed, or deleted from the server. Please verify that the file still exists or contact the administrator for further assistance.'
        ]);
    }

    public function save(bool $isNotify = true) {

        if(!$this->hasChanges()) {
            return $this->dispatch('alert', [
                'status' => 'info',
                'title' => 'Please be informed!',
                'isRemoveRowDT' => false,
                'showAlert' => true,
                'message' => 'Unable to save because no changes were made, feel free to edit or update your informations first before saving. Thank you!'
            ]);
        }

        $employee_no = $this->employee_no;
        $record = EmployeeInformation::where('employee_no', $employee_no)->first();
        
        if (!$record) {
            return $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops',
                'isRemoveRowDT' => false,
                'showAlert' => true,
                'message' => 'Error: Saving a non-existent employee!'
            ]);
        }

        try {
            $this->validate($this->rules($employee_no));
        } catch (ValidationException $e) {
            $this->setErrorActiveTabAccordions($e->validator->errors()->keys());
            throw $e;
        }

        if($isNotify) {

            $title = 'Are you sure to continue?';
            $message = 'Yes, I am sure that all the information I have provided is accurate and true. This ensures that there will be no issues as we proceed.';
            $action = 'save';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

            return;

        } else {
            
            DB::beginTransaction();

            try {

                $data = $this->records;

                $path = 'documents/' . $employee_no;

                $existingIds = EmployeeUpdateCivilService::where('employee_no', $employee_no)
                    ->pluck('id')
                    ->toArray();

                $dataIds = array_column($data, 'id');
                $missingIds = array_diff($existingIds, $dataIds);

                if (!empty($missingIds)) {
                    EmployeeUpdateCivilService::whereIn('id', $missingIds)->delete();
                }

                foreach ($data as $item) {
                    if (isset($item['id'])) {
                        if($item['documents'] instanceof TemporaryUploadedFile) {
                            $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                            $record = EmployeeUpdateCivilService::where('id', $item['id'])
                                ->where('employee_no', $employee_no)
                                ->first();
                            if ($record) {
                                $record->fill([
                                    'employee_no' => $employee_no,
                                    'certification' => $item['certification'],
                                    'rating' => $item['rating'],
                                    'date_exam' => $item['date_exam'],
                                    'place_exam' => $item['place_exam'],
                                    'license_no' => $item['license_no'],
                                    'date_validity' => $item['date_validity'],
                                    'documents' => $documents
                                ])->save();
                            } else {
                                EmployeeUpdateCivilService::create([
                                    'employee_no' => $employee_no,
                                    'certification' => $item['certification'],
                                    'rating' => $item['rating'],
                                    'date_exam' => $item['date_exam'],
                                    'place_exam' => $item['place_exam'],
                                    'license_no' => $item['license_no'],
                                    'date_validity' => $item['date_validity'],
                                    'documents' => $documents
                                ]);
                            }
                        } else {
                            $record = EmployeeUpdateCivilService::where('id', $item['id'])
                                ->where('employee_no', $employee_no)
                                ->first();
                            if ($record) {
                                $record->fill([
                                    'employee_no' => $employee_no,
                                    'certification' => $item['certification'],
                                    'rating' => $item['rating'],
                                    'date_exam' => $item['date_exam'],
                                    'place_exam' => $item['place_exam'],
                                    'license_no' => $item['license_no'],
                                    'date_validity' => $item['date_validity'],
                                ])->save();
                            } else {
                                $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                                EmployeeUpdateCivilService::create([
                                    'employee_no' => $employee_no,
                                    'certification' => $item['certification'],
                                    'rating' => $item['rating'],
                                    'date_exam' => $item['date_exam'],
                                    'place_exam' => $item['place_exam'],
                                    'license_no' => $item['license_no'],
                                    'date_validity' => $item['date_validity'],
                                    'documents' => $documents
                                ]);
                            }
                        }
                    } else {
                        $documents = $this->uploadFile($employee_no, 'documents', $path, $item['documents'] ?? null);
                        EmployeeUpdateCivilService::create([
                            'employee_no' => $employee_no,
                            'certification' => $item['certification'],
                            'rating' => $item['rating'],
                            'date_exam' => $item['date_exam'],
                            'place_exam' => $item['place_exam'],
                            'license_no' => $item['license_no'],
                            'date_validity' => $item['date_validity'],
                            'documents' => $documents
                        ]);
                    }
                }
                        
                DB::commit();

                $this->dispatch('alert', [
                    'status' => 'success',
                    'title' => 'Success!', 
                    'isRemoveRowDT' => false,
                    'isReloadDT' => false,
                    'message' => 'You\'re profile is now in pending for HR\'s approval. We\'ll sent you a notification once approved. Thank you!',
                ]);

                $user = EmployeeAccount::find($this->employee_id);
                $message = 'Employee <strong>' . $this->employee_no . '</strong> has submitted his/her updated <strong>profile information</strong>.';
                $redirect = route('ess.approval-profile.show', ['employee_no' => $user->employee_no, 'form' => 'civil-service']);
                $user->notify(new Notifications('info', $message, $redirect, 'admin'));

                return;
                
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->dispatch('alert', [
                    'status' => 'error',
                    'title' => 'Oops!',
                    'isRemoveRowDT' => true,
                    'showAlert' => true,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
        }

    }

    public function render()
    {
        return view('livewire.employee.profile.civil-service');
    }
}
