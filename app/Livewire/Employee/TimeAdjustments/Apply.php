<?php

namespace App\Livewire\Employee\TimeAdjustments;

use App\Models\EmployeeAccount;
use App\Models\EmployeePersonal;
use App\Models\EmployeeTimeAdjustments;
use App\Models\EmployeeTimeAdjustmentsAttachments;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Apply extends Component
{

    use WithFileUploads;

    public $record_id;
    public $employee_no;
    public $employee_id;
    public $date;
    public $clock_in;
    public $clock_out;
    public $reason;
    public $attachments = [];
    public $preview_attachments = [];


    protected $listeners = ['save'];

    public function mount() {
        $this->loadRecords();
    }

    public function loadRecords() {


        $employee_no = Auth::user()->employee_no;
        $employee_id = Auth::user()->id;

        $this->employee_no = $employee_no;
        $this->employee_id = $employee_id;
 
        if(!is_null($this->record_id)) {
            $records = EmployeeTimeAdjustments::where('id', $this->record_id)
                ->where('employee_no', $employee_no)
                ->first();
        
            if(!$records) {
                return redirect()
                    ->route('employee.request-timelog');
            }

            $clock_in = $records->clock_in ? (str_contains($records->clock_in, 'M') ? Carbon::createFromFormat('h:i A', $records->clock_in)->format('H:i:s') : $records->clock_in) : null;
            $clock_out = $records->clock_out ? (str_contains($records->clock_out, 'M') ? Carbon::createFromFormat('h:i A', $records->clock_out)->format('H:i:s') : $records->clock_out) : null;

            $this->date = $records->date ? Carbon::parse($records->date)->format('Y-m-d') : null;
            $this->clock_in = $clock_in;
            $this->clock_out = $clock_out;
            $this->reason = $records->reason;
            $this->preview_attachments = $records->attachments ? $records->attachments->toArray() : [];
        }

    }


    protected function normalizeDate(): void
    {
        Log::channel('single')->info('Time adjustment apply: normalizeDate', [
            'employee_no' => $this->employee_no,
            'date_before' => $this->date,
            'date_type' => $this->date !== null ? gettype($this->date) : 'null',
        ]);
        if (empty($this->date)) {
            return;
        }
        if ($this->date instanceof \DateTimeInterface) {
            $this->date = $this->date->format('Y-m-d');
            return;
        }
        if (is_string($this->date) && trim($this->date) !== '') {
            try {
                $this->date = Carbon::parse($this->date)->format('Y-m-d');
            } catch (\Exception $e) {
                // leave as-is so validation can report the error
            }
        }
        Log::channel('single')->info('Time adjustment apply: normalizeDate after', [
            'employee_no' => $this->employee_no,
            'date_after' => $this->date,
        ]);
    }

    public function rules() {
        return [
            'date' => 'required|date',
            'clock_in' => 'required',
            'clock_out' => 'required',
            'reason' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,pdf|max:5120',
        ];
    }

    public function removeAttachment(int $id)
    {
        $record = EmployeeTimeAdjustmentsAttachments::find($id);

        if ($record) {
            $disk = env('USE_S3_STORAGE', false) ? 's3' : 'public';
            Storage::disk($disk)->delete($record->attachment);
            $record->delete();

            $this->preview_attachments = array_values(
                array_filter($this->preview_attachments, fn ($item) => $item['id'] != $id)
            );
        }
    }



    public function save(bool $isNotify = true) {
        Log::channel('single')->info('Time adjustment apply: save called', [
            'employee_no' => $this->employee_no,
            'isNotify' => $isNotify,
            'record_id' => $this->record_id,
            'date' => $this->date,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
        ]);
        $this->normalizeDate();
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('single')->warning('Time adjustment apply: validation failed', [
                'employee_no' => $this->employee_no,
                'errors' => $e->errors(),
                'date' => $this->date,
            ]);
            throw $e;
        }
    
        if ($isNotify) {
            $title = 'Are you sure to continue?';
            $message = 'Yes, I am sure that all the information I have provided is accurate and true. This ensures that there will be no issues as we proceed.';
            $action = 'save';
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);
        } else {
            try {
                
    
                $model = EmployeeTimeAdjustments::updateOrCreate([
                    'id' => $this->record_id,
                ], [
                    'employee_no' => $this->employee_no,
                    'date' => $this->date,
                    'clock_in' => $this->clock_in,
                    'break_out' => null,
                    'break_in' => null,
                    'clock_out' => $this->clock_out,
                    'reason' => $this->reason,
                ]);

                if (!empty($this->attachments)) {
                    foreach ($this->attachments as $attachment) {
                        $filename = strtolower(
                            time() . '_' . str_replace(' ', '_', $attachment->getClientOriginalName())
                        );

                    $disk = env('USE_S3_STORAGE', false) ? 's3' : 'public';
                    $path = $attachment->storeAs(
                        'time-adjustments',
                        $filename,
                        $disk
                    );

                        EmployeeTimeAdjustmentsAttachments::create([
                            'employee_requests_id' => $model->id,
                            'attachment' => $path,
                        ]);
                    }
                }



                if (is_null($this->record_id)) {
                    $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!',
                        'message' => 'Your application has been submitted. You will receive an email regarding your application status as soon as we review it. Thank you for your understanding.'
                    ]);
    
                    $user = EmployeeAccount::find($this->employee_id);
                    $personal = $user->personal ?? EmployeePersonal::where('employee_no', $this->employee_no)->first();
                    $name = $personal ? trim($personal->firstname . ' ' . $personal->lastname) : '';
                    $display = $name !== '' ? e($name) . ' (' . e($this->employee_no) . ')' : e($this->employee_no);
                    $message = 'Employee <strong>' . $display . '</strong> has submitted an application for <strong>request timelog</strong>.';
                    $redirect = route('ess.time-adjustments');

                    $user->notify(new Notifications('info', $message, $redirect, 'admin'));
    
                    //$this->resetExcept('employee_no', 'employee_id');

                    $this->reset([
                    'date',
                    'clock_in',
                    'clock_out',
                    'reason',
                    'attachments',
                    'preview_attachments',
                ]);

                $this->dispatch('form-reset');

                    return;
                } else {
                    return $this->dispatch('alert', [
                        'showAlert' => true,
                        'status' => 'success',
                        'title' => 'Yey!',
                        'message' => 'Your application has been updated. You will receive an email regarding your application status as soon as we review it. Thank you for your understanding.',
                        'redirect' => route('employee.time-adjustments.edit', ['id' => $this->record_id])
                    ]);
                }
            } catch (\Exception $e) {
                return $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops',
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
        }
    }
    

    public function render()
    {
        return view('livewire.employee.time-adjustments.apply');
    }
}