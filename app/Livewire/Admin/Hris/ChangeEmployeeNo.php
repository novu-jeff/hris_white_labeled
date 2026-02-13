<?php

namespace App\Livewire\Admin\Hris;

use App\Notifications\Notifications;
use DB;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use App\Models\EmployeeInformation;
use App\Jobs\ChangeEmployeeNoJob;


class ChangeEmployeeNo extends Component
{


    public $actionBy;
    public string $current_employee_no = '';
    public string $new_employee_no = '';

    public int $progress = 0; // 0 - 100
    public int $totalJobs = 0;
    public int $completedJobs = 0;

    public bool $isMigrating = false;

    protected $listeners = ['setEmployeeNo', 'save', 'employeeMigrationProgress' => 'incrementProgress'];

    public function incrementProgress()
    {
        $this->completedJobs++;
        if ($this->totalJobs > 0) {
            $this->progress = intval(($this->completedJobs / $this->totalJobs) * 100);
        }
    }

    public function mount() {
        $this->actionBy = Auth::user();
    }

    public function setEmployeeNo($employee_no)
    {
        $this->current_employee_no = $employee_no;
    }

    public function save(bool $isNotify = true) {

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
            $message = 'Please be informed that there are changes made to the employee number. 
                        This will affect the employee\'s records and access.';
            
            $action = 'save';
            
            $this->dispatch('showConfirmation', [
                'title' => $title,
                'message' => $message,
                'action' => $action
            ]);

        }  else {

            $validator = \Validator::make([
                'current_employee_no' => $this->current_employee_no,
                'new_employee_no' => $this->new_employee_no,
            ], [
                'current_employee_no' => 'required|exists:employee_information,employee_no',
                'new_employee_no' => 'required|unique:employee_information,employee_no',
            ]);
            
            if($validator->fails()) {
                $this->dispatch('alert', [
                    'showAlert' => true,
                    'status' => 'error',
                    'title' => 'Oops!', 
                    'isRemoveRowDT' => false,
                    'message' => $validator->errors()->first(),
                ]);
                return;
            }

            $this->changeEmployeeNo($this->current_employee_no, $this->new_employee_no);

        }
    }

    public function changeEmployeeNo(string $oldEmployeeNo, string $newEmployeeNo)
    {
        
        try {

             \Log::info("STEP 1: Starting migration");

            $newEmployeeNo = strtoupper($newEmployeeNo);

            $employeeModel = EmployeeInformation::where('employee_no', $oldEmployeeNo)->firstOrFail();
             \Log::info("STEP 2: EmployeeInformation loaded");

            $this->current_employee_no = strtoupper($newEmployeeNo);
            $this->isMigrating = true;

            $employeeModel->employee_no = strtoupper($newEmployeeNo);
            $employeeModel->isTransferingEmp = true; 

            $employeeModel->save();

             \Log::info("STEP 3: EmployeeInformation updated");

            $relations = [
                \App\Models\EmployeeAccount::class => 'employee_no',
                \App\Models\EmployeePersonal::class => 'employee_no',
                \App\Models\EmployeeEducation::class => 'employee_no',
                \App\Models\EmployeeParents::class => 'employee_no',
                \App\Models\EmployeeChildren::class => 'employee_no',
                \App\Models\EmployeeEmploymentHistory::class => 'employee_no',
                \App\Models\EmployeeCivilService::class => 'employee_no',
                \App\Models\EmployeeTrainings::class => 'employee_no',
                \App\Models\EmployeeOtherWorks::class => 'employee_no',
                \App\Models\EmployeeSkillsHobbies::class => 'employee_no',
                \App\Models\LeaveCredits::class => 'employee_no',
                \App\Models\OffsetCredits::class => 'employee_no',
                \App\Models\EmployeeLeave::class => 'employee_no',
                \App\Models\EmployeeLeaveDates::class => 'employee_no',
                \App\Models\EmployeeBusinessSlip::class => 'employee_no',
                \App\Models\EmployeeAtro::class => 'employee_no',
                \App\Models\EmployeeAtroRelative::class => 'employee_no',
                \App\Models\EmployeeTimelogs::class => 'employee_id',
                \App\Models\EmployeeDeductions::class => 'employee_no',
                \App\Models\EmployeeEarnings::class => 'employee_no',
                \App\Models\EmployeeLeaveCard::class => 'employee_no',
                \App\Models\EmployeeTimeAdjustments::class => 'employee_no',
                \App\Models\EmployeeUpdateChildren::class => 'employee_no',
                \App\Models\EmployeeUpdateCivilService::class => 'employee_no',
                \App\Models\EmployeeUpdateEducation::class => 'employee_no',
                \App\Models\EmployeeUpdateEmploymentHistory::class => 'employee_no',
                \App\Models\EmployeeUpdateOtherWorks::class => 'employee_no',
                \App\Models\EmployeeUpdateParents::class => 'employee_no',
                \App\Models\EmployeeUpdatePersonal::class => 'employee_no',
                \App\Models\EmployeeUpdateSkillsHobbies::class => 'employee_no',
                \App\Models\EmployeeUpdateTrainings::class => 'employee_no',
                \App\Models\Loan::class => 'employee_no',
            ];
             \Log::info("STEP 4: Relations array built", ['count' => count($relations)]);
            $jobs = [];
            
            foreach ($relations as $model => $column) {
                $jobs[] = new ChangeEmployeeNoJob($model, $oldEmployeeNo, $newEmployeeNo, $column);
            }

            // store total jobs count
            $this->totalJobs = count($jobs);
            $this->completedJobs = 0;
            $this->progress = 0;

             \Log::info("STEP 5: Jobs created", ['count' => count($jobs)]);

            if (!empty($jobs)) {
                $batch = Bus::batch($jobs)
                    ->withOption('actionBy', [
                        'id' => $this->actionBy->id,
                        'name' => $this->actionBy->name
                    ])
                    ->name('Migration: ' . $oldEmployeeNo . ' to ' . $newEmployeeNo)
                    ->catch(function (Batch $batch, \Throwable $e) {
                         \Log::error("STEP 6: Batch catch hit: ".$e->getMessage());
                        $this->actionBy?->notify(new Notifications(
                            'error',
                            'An error occurred during migration of employee.',
                            route('system.jobs', ['id' => $batch->id]),
                            'admin'
                        ));
                    })
                    ->then(function (Batch $batch) { 
                        \Log::info("STEP 7: Batch completed");
                        $this->progress = 100;
                        $this->actionBy?->notify(new Notifications(
                            'success',
                            'Migration of employee has been finished',
                                route('system.jobs', ['id' => $batch->id]),
                            'admin'
                        ));
                    })
                    ->finally(function() use ($employeeModel) {
                         \Log::info("STEP 8: Finally executing");
                          $employeeModel->refresh();
                        $employeeModel->isTransferingEmp = false;
                        $employeeModel->save();

                        $this->isMigrating = false;
                        $this->progress = 100;
                        $this->completedJobs = $this->totalJobs;

                    })
                    ->dispatch();

                    \Log::info("STEP 9: Batch dispatched");
            }

            $this->reset(['new_employee_no']);
            $this->dispatch('loadRecords');
            $this->dispatch('hideModal', [
                'modal' => 'change_employee_no'
            ]);

            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'info',
                'showAlert' => true,
                'message' => 'Migration of employee ' . $oldEmployeeNo . ' to ' . $newEmployeeNo . ' has been started. We\'ll notify you once it\'s done. Thank you!',
            ]);

            return;

        } catch (\Exception $e) {

            $this->reset(['new_employee_no']);
            $this->dispatch('hideModal', [
                'modal' => 'change_employee_no'
            ]);

            report($e);

            $this->dispatch('alert', [
                'status' => 'error',
                'title' => 'Oops',
                'showAlert' => true,
                'message' => 'An error occured: ' . $e->getMessage(),
            ]);

            return;
        }
    }



     public function closeModal()
    {
        $this->reset(['current_employee_no', 'new_employee_no', 'isMigrating']);
        $this->dispatch('hideModal', ['modal' => 'change_employee_no']);
    }

    public function checkMigrationStatus()
    {
            if (!$this->isMigrating || !$this->current_employee_no) {
                return;
            }

            $employee = EmployeeInformation::where('employee_no', $this->current_employee_no)->first();

            if (!$employee || $employee->isTransferingEmp) {
                return; // still running
            }

            // ✅ FINISHED (RUNS ONCE)
            $this->dispatch('alert', [
                'status' => 'success',
                'title' => 'Completed',
                'showAlert' => true,
                'message' => 'Employee number migration completed successfully.',
            ]);

            $this->dispatch('loadRecords');

            // 🛑 STOP POLLING
            $this->isMigrating = false;
            $this->reset('current_employee_no');
    }

    public function render()
    {
        return view('livewire.admin.hris.change-employee-no');
    }
}
