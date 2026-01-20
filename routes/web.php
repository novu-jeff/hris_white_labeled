<?php


use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\OthersController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\HRISController;
use App\Http\Controllers\Admin\Job\ApplicantController;
use App\Http\Controllers\Admin\Job\InterviewController;
use App\Http\Controllers\Admin\Job\PostController;
use App\Http\Controllers\Admin\Job\RequirementsController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\AnnouncementController as ESSAnnouncementController;
use App\Http\Controllers\Admin\ClockInOutController as ESSClockInOutController;
use App\Http\Controllers\Admin\ESSAuthorityToRenderTimeController;
use App\Http\Controllers\Admin\LeaveController as ESSLeaveController;
use App\Http\Controllers\Admin\PayslipRequestController as ESSPayslipRequestController;
use App\Http\Controllers\Admin\ApprovalUpdateProfile as ESSApprovalProfile;
use App\Http\Controllers\Admin\LoanController as ESSLoanController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\ESSFAQController;
use App\Http\Controllers\Admin\TimeAdjustmentsController as ESSTimeAdjustmentsController;
use App\Http\Controllers\Admin\OfficialBusinessSlipController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\Reports\BIR\BIRController;
use App\Http\Controllers\Admin\Reports\DailyTimeRecord\DailyTimeRecordController;
use App\Http\Controllers\Admin\Reports\Pagibig\PagibigController;
use App\Http\Controllers\Admin\Reports\Philhealth\PhilhealthController;
use App\Http\Controllers\Admin\Reports\SSS\SSSController;
use App\Http\Controllers\Admin\RequestStatusController as ESSRequestStatusController;
use App\Http\Controllers\Admin\SchedulerController;
use App\Http\Controllers\Admin\Settings\HRIS\BankInformationController;
use App\Http\Controllers\Admin\Settings\HRIS\BranchController;
use App\Http\Controllers\Admin\Settings\HRIS\CostCenterController;
use App\Http\Controllers\Admin\Settings\HRIS\DeductionController;
use App\Http\Controllers\Admin\Settings\HRIS\DepartmentController;
use App\Http\Controllers\Admin\Settings\HRIS\EmploymentTypeController;
use App\Http\Controllers\Admin\Settings\HRIS\GSISController;
use App\Http\Controllers\Admin\Settings\HRIS\PositionController;
use App\Http\Controllers\Admin\Settings\HRIS\ViolationController;
use App\Http\Controllers\Admin\Settings\HRIS\OtherDeductionsController;
use App\Http\Controllers\Admin\Settings\HRIS\OtherEarningsController;
use App\Http\Controllers\Admin\Settings\HRIS\SectionController;
use App\Http\Controllers\Admin\Settings\HRIS\LoanTypeController;
use App\Http\Controllers\Admin\Settings\HRIS\LeaveController;
use App\Http\Controllers\Admin\Settings\ShiftScheduleController;
use App\Http\Controllers\Admin\Settings\CompanyInformationController;
use App\Http\Controllers\Admin\Settings\EmployeeScheduleController;
use App\Http\Controllers\Admin\Settings\HRIS\EarningsController;
use App\Http\Controllers\Admin\Settings\OrganizationController;
use App\Http\Controllers\Admin\Settings\Payroll\HolidayController;
use App\Http\Controllers\Admin\Settings\RoleController;
use App\Http\Controllers\Admin\TimeKeepingController;
use App\Http\Controllers\Admin\TranchesController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Home\LoginController as HomeLoginController;
use App\Http\Controllers\Home\AppliedController;
use App\Http\Controllers\Home\HomeController;
use App\Http\Controllers\Home\ProfileController;
use App\Http\Controllers\Home\RegisterController;
use App\Http\Controllers\Home\ViewJobController;
use App\Http\Controllers\Home\InterviewController as HomeInterviewController;

use App\Http\Controllers\Employee\LoginController as EmployeeLoginController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\LeaveController as EmployeeLeaveController;
use App\Http\Controllers\Employee\LoanController as EmployeeLoanController;
use App\Http\Controllers\Employee\ClockInOutController as EmployeeClockInOutController;
use App\Http\Controllers\Employee\ATROController as EmployeeATROController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Employee\AnnouncementController as EmployeeAnnouncementController;
use App\Http\Controllers\Employee\BusinessSlipController;
use App\Http\Controllers\Employee\DirectoryController as EmployeeDirectoryController;
use App\Http\Controllers\Employee\EmployeeDailyTimeRecordController;
use App\Http\Controllers\Employee\TimeAdjustmentsController;
use App\Http\Controllers\Employee\PayslipController;
use App\Http\Controllers\Employee\TeamController as EmployeeTeamController;
use App\Http\Controllers\Employee\RequestStatusController as EmployeeRequestStatusController;
use App\Http\Controllers\Employee\RemainingCreditController as EmployeeRemainingCreditController;
use App\Http\Controllers\Employee\TutorialController;
use App\Http\Controllers\Home\SavedJobsController;
use App\Http\Controllers\Home\SettingsController;
use App\Http\Controllers\TestController;
use App\Livewire\Employee\DailyTimeRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\SystemJobsController;
use App\Http\Controllers\Admin\LeaveImportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

$careersDomain = config('app.careers_domain');
$publicDomain = config('app.public_domain');
$essDomain = config('app.ess_domain');
$hrisDomain = config('app.hris_domain');

$publicRoutes = function () {
    Route::get('jobs', [HomeController::class, 'index'])
            ->name('home.index')
            ->middleware('applicant:guest');
    Route::get('jobs/view/{slug}', [ViewJobController::class, 'index'])
        ->name('home.view-job')
        ->middleware('applicant:guest');

    Route::prefix('login')->group(function() {
        Route::get('/', [HomeLoginController::class, 'index'])
            ->name('home.login');
        Route::post('/', [HomeLoginController::class, 'store'])
            ->name('home.login.store');
    });

    Route::any('logout', [HomeLoginController::class, 'logout'])
        ->name('home.logout');

    Route::prefix('register')->group(function() {
        Route::get('/', [RegisterController::class, 'index'])
            ->name('home.register');
        Route::post('/', [RegisterController::class, 'store'])
            ->name('home.register');
    });

    Route::middleware(['applicant'])->group(function() {
        Route::get('my/jobs/applied', [AppliedController::class, 'index'])
            ->name('home.applied');

        Route::get('my/jobs/saved', [SavedJobsController::class, 'index'])
            ->name('home.saved');

        Route::get('jobs/applied/view-job/{slug}', [ViewJobController::class, 'index'])
            ->name('home.applied.view-job');

        Route::get('search/{search?}', [HomeController::class, 'index'])
            ->name('home.search');

        Route::get('my/profile', [ProfileController::class, 'index'])
            ->name('home.profile');

        Route::get('assessment/respond/{job_id}/{interview_id}', [HomeInterviewController::class, 'interview'])
            ->name('interview-respond');

        Route::get('job/offer/upload/signed/{job_id}', [HomeInterviewController::class, 'offer'])
            ->name('upload-signed-offer');

        Route::get('job/requirements/upload/{job_id}', [HomeInterviewController::class, 'requirements'])
            ->name('upload-requirements');
    });
};

if ($careersDomain) {
    Route::domain($careersDomain)->group(function () use ($publicRoutes) {
        Route::redirect('/', 'jobs', 301);
        $publicRoutes();
    });
} elseif ($publicDomain) {
    Route::domain($publicDomain)->group(function () use ($publicRoutes) {
        Route::redirect('/', 'jobs', 301);
        $publicRoutes();
    });
} else {
    Route::redirect('/', 'jobs', 301);
    $publicRoutes();
}

$adminRoutes = function () {
    Route::prefix('admin')->group(function() {

    Route::redirect('/', 'admin/login', 302);

    Route::get('system/jobs', [SystemJobsController::class, 'index'])
        ->name('system.jobs');

    Route::get('login', [AdminLoginController::class, 'index'])
        ->name('admin.index');
    Route::post('login', [AdminLoginController::class, 'login'])
        ->name('admin.login');
    Route::any('logout', [AdminLoginController::class, 'logout'])
        ->name('admin.logout');


    Route::middleware(['auth'])->group(function() {

        Route::get('dashboard', [AdminDashboardController::class, 'index'])
            ->name('admin.dashboard');

        Route::get('download', [DownloadController::class, 'index'])
            ->name('download.view');

       

        Route::prefix('job')->group(function() {
    
            Route::resource('posts', PostController::class)
                ->only(['index', 'create', 'edit'])
                ->names('job.posts');
                    
            Route::get('applicants/{status}', [ApplicantController::class, 'index'])
                ->name('job.applicants.index');
            Route::get('applicants/{status}/create', [ApplicantController::class, 'create'])
                ->name('job.applicants.create');
            Route::post('applicants/{status}', [ApplicantController::class, 'store'])
                ->name('job.applicants.store');
            Route::get('applicants/{status}/{applicant}', [ApplicantController::class, 'show'])
                ->name('job.applicants.show');
            Route::get('applicants/{status}/{applicant}/edit', [ApplicantController::class, 'edit'])
                ->name('job.applicants.edit');
            Route::put('applicants/{status}/{applicant}', [ApplicantController::class, 'update'])
                ->name('job.applicants.update');
            Route::delete('applicants/{status}/{applicant}', [ApplicantController::class, 'destroy'])
                ->name('job.applicants.destroy');
        
        });
        
        Route::get('hris', [HRISController::class, 'index'])
            ->name('hris.index');

        Route::get('hris/staffing', [HRISController::class, 'staffing'])
            ->name('hris.staffing');
            
        Route::get('hris/employee/{employee_no?}/{form}', [HRISController::class, 'show'])
            ->name('hris.show');

        Route::get('hris/manual', [HRISController::class, 'manual'])
            ->name('hris.manual');
        
        Route::prefix('timekeeping')->group(function() {
            Route::get('upload', [TimeKeepingController::class, 'upload'])
                ->name('timekeeping.upload');
            Route::get('upload/job/{id}', [TimeKeepingController::class, 'job'])
                ->name('timekeeping.upload.job');

            Route::get('correction/apply/{bsd_no}/{date}', [TimeKeepingController::class, 'correction_apply'])
                ->name('timekeeping.correction-apply');
        });

        Route::prefix('payroll')->group(function() {

            Route::get('/', [PayrollController::class, 'index'])
                ->name('payroll.index');

            Route::get('process/{type}/{payroll_id}', [PayrollController::class, 'process'])
                ->name('payroll.process');

        });

        Route::prefix('employees')->group(function() {
            
            Route::get('official-business-slip', [OfficialBusinessSlipController::class, 'index'])
                ->name('ess.obs');

            Route::get('authority-to-render-over-time', [ESSAuthorityToRenderTimeController::class, 'index'])
                ->name('ess.atro');
                
            Route::get('leave', [ESSLeaveController::class, 'index'])
                ->name('ess.leave');

            Route::get('loan', [ESSLoanController::class, 'index'])
                ->name('ess.loan');    

            Route::get('payslip/request/download', [ESSPayslipRequestController::class, 'index'])
                ->name('ess.payslip-request');

            Route::get('time-adjustments', [ESSTimeAdjustmentsController::class, 'index'])
                ->name('ess.time-adjustments');
        
            Route::prefix('announcements')->group(function() {
                Route::get('/', [ESSAnnouncementController::class, 'index'])
                    ->name('ess.announcements.index');
                Route::get('apply', [ESSAnnouncementController::class, 'create'])
                    ->name('ess.announcements.create');
                Route::get('edit/{id}', [ESSAnnouncementController::class, 'edit'])
                    ->name('ess.announcements.edit');
            });

            Route::prefix('messages')->group(function() {
                Route::get('{employee_no?}', [ESSRequestStatusController::class, 'index'])
                    ->name('ess.messages');
            });

            Route::prefix('faqs')->group(function() {
                Route::get('/', [ESSFAQController::class, 'index'])
                    ->name('ess.faqs.index');
                Route::get('apply', [ESSFAQController::class, 'create'])
                    ->name('ess.faqs.create');
                Route::get('edit/{id}', [ESSFAQController::class, 'edit'])
                    ->name('ess.faqs.edit');
            });

            Route::get('profile/approval', [ESSApprovalProfile::class, 'index'])
                ->name('ess.approval-profile.index');

             Route::get('profile/approval/{employee_no}/{form}', [ESSApprovalProfile::class, 'show'])
                ->name('ess.approval-profile.show');
        });

        Route::prefix('reports')->group( function() {
            Route::get('daily-time-record', [DailyTimeRecordController::class, 'index'])->name('reports.dtr');
            Route::get('/daily-time-record/{id}/view', [DailyTimeRecordController::class, 'show'])->name('dtr.show');

            Route::get('bir/index', [BIRController::class, 'index'])
                ->name('reports.bir');
            Route::get('bir/form-2316/{id}', [BIRController::class, 'form2316'])
                ->name('reports.form-2316');
            Route::get('bir/form-1601', [BIRController::class, 'form1601'])
                ->name('reports.form-1601');
                
            Route::get('philhealth', [PhilhealthController::class, 'index'])
                ->name('reports.philhealth');

            Route::get('sss', [SSSController::class, 'index'])
                ->name('reports.sss');

            Route::get('pagibig', [PagibigController::class, 'index'])
                ->name('reports.pagibig');
        });
        
        Route::prefix('others/uploads')->group( function() {
            Route::get('overtime', [OthersController::class, 'overtime'])
                ->name('others.overtime');
        });

        Route::prefix('settings')->group( function() {
        
            Route::resource('assessments', InterviewController::class)->names('job.interview');
            Route::resource('requirements', RequirementsController::class)->names('job.requirements');

            Route::get('company-information', [CompanyInformationController::class, 'index'])
                ->name('company.index');
            
            Route::get('scheduled-tasks', [SchedulerController::class, 'index'])
                ->name('scheduler.index');

            Route::get('/leave-import', [LeaveImportController::class, 'index'])->name('leave.import.index');
            Route::post('/leave-import', [LeaveImportController::class, 'import'])->name('leave.import');   

            Route::resource('tranches', TranchesController::class)
                ->names('tranches')
                ->only('index', 'show', 'create', 'edit');

            Route::prefix('location')->group( function() {
                Route::resource('/branch', BranchController::class)
                    ->names('branch');
    
                Route::resource('/cost-center', CostCenterController::class)
                    ->names('cost-center');
    
                Route::resource('/department', DepartmentController::class)
                    ->names('department');

                Route::resource('/section', SectionController::class)
                    ->names('section');

            });

            Route::prefix('hris')->group( function() {
        
                Route::resource('bank-information', BankInformationController::class)
                    ->names('bank-information');             
        
                Route::resource('employment-type', EmploymentTypeController::class)
                    ->names('employment-type');

                Route::resource('loan-type', LoanTypeController::class)
                    ->names('loan-type');    
        
                Route::resource('position', PositionController::class)
                    ->names('position');
        
                Route::resource('violation', ViolationController::class)
                    ->names('violation');
                
                Route::resource('leave', LeaveController::class)
                    ->names('leave');

                Route::resource('gsis', GSISController::class)
                    ->names('gsis');

                Route::resource('other-earnings', OtherEarningsController::class)
                    ->names('other-earnings');

                Route::resource('other-deductions', OtherDeductionsController::class)
                    ->names('other-deductions');

                Route::get('employee/earnings/{id}', [EarningsController::class, 'index'])
                    ->name('earnings.index');

                Route::post('employee/earnings/{id}', [EarningsController::class, 'create'])
                    ->name('earnings.create');

                Route::get('employee/deductions/{id}', [DeductionController::class, 'index'])
                    ->name('deductions.index');

                Route::post('employee/deductions/{id}', [DeductionController::class, 'create'])
                    ->name('deductions.create');
            });

            Route::resource('shift-schedule', ShiftScheduleController::class)
                ->names('shift-schedule');

            Route::resource('employee-schedule', EmployeeScheduleController::class)
                ->names('employee-schedule');
        
            Route::prefix('users')->group(function() {
                Route::get('{type}', [UserController::class, 'index'])
                    ->name('users.index');
                Route::get('admins/new', [UserController::class, 'create'])
                    ->name('admin.new');
                Route::get('admins/update/{id}', [UserController::class, 'edit'])
                    ->name('admin.update');
            });

            Route::resource('user-access', RoleController::class)
                ->names('users.access');

            Route::get('/user-trails', [App\Http\Controllers\TrailController::class, 'index'])
    ->name('user.trails');    
            
            Route::prefix('payroll')->group( function() {
                Route::resource('/holidays', HolidayController::class)->only('create', 'index', 'edit')
                    ->names('holiday');
            });
        });
    });

});
};

$employeeRoutes = function () {
    Route::prefix('employee')->middleware('check_employee_allowed_module')->group(function() {


    Route::get('forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    Route::redirect('/', 'employee/login', 302);

    Route::prefix('login')->group(function() {
        Route::get('/', [EmployeeLoginController::class, 'index'])
            ->name('employee.login');
        Route::post('/', [EmployeeLoginController::class, 'store'])
            ->name('employee.login');
        Route::any('logout', [EmployeeLoginController::class, 'logout'])
            ->name('employee.logout');
    });

    Route::middleware('employee')->group(function() {
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('employee.dashboard');
        
        Route::prefix('leave')->group(function() {

            Route::get('/', [EmployeeLeaveController::class, 'index'])
                ->name('employee.leave');
            Route::get('card', [EmployeeLeaveController::class, 'card'])
                ->name('employee.leave-card');
            Route::get('apply', [EmployeeLeaveController::class, 'create'])
                ->name('employee.leave.apply');
            Route::get('edit/{id}', [EmployeeLeaveController::class, 'edit'])
                ->name('employee.leave.edit');
            Route::get('{id}', [EmployeeLeaveController::class, 'show'])
                ->name('employee.leave.show');
        });

        Route::prefix('official-business-slip')->group(function() {

            Route::get('/', [BusinessSlipController::class, 'index'])
                ->name('employee.obs.index');
            Route::get('apply', [BusinessSlipController::class, 'create'])
                ->name('employee.obs.apply');
            Route::get('edit/{id}', [BusinessSlipController::class, 'edit'])
                ->name('employee.obs.edit');
        });

        Route::prefix('authority-to-render-time')->group(function() {

            Route::get('/', [EmployeeATROController::class, 'index'])
                ->name('employee.atro');
            Route::get('apply', [EmployeeATROController::class, 'create'])
                ->name('employee.atro.apply');
            Route::get('edit/{id}', [EmployeeATROController::class, 'edit'])
                ->name('employee.atro.edit');
                
        });

         Route::prefix('loans')->group(function() {

            Route::get('/', [EmployeeLoanController::class, 'index'])
                ->name('employee.loan');
            Route::get('loan-application', [EmployeeLoanController::class, 'create'])->name('employee.loan-application');
            Route::get('{id}/edit', [EmployeeLoanController::class, 'edit'])->name('employee.loan.edit');
           
                
        });

        Route::prefix('time-adjustments')->group(function() {

            Route::get('/', [TimeAdjustmentsController::class, 'index'])
                ->name('employee.time-adjustments');
            Route::get('apply', [TimeAdjustmentsController::class, 'create'])
                ->name('employee.time-adjustments.apply');
            Route::get('edit/{id}', [TimeAdjustmentsController::class, 'edit'])
                ->name('employee.time-adjustments.edit');
            Route::get('{id}', [TimeAdjustmentsController::class, 'show'])
                ->name('employee.time-adjustments.show');
        });

        Route::prefix('payslip')->group(function() {
            Route::get('/', [PayslipController::class, 'index'])
                ->name('employee.payslip');
        });

        Route::get('daily-time-record', [EmployeeDailyTimeRecordController::class, 'index'])
            ->name('employee.dtr');

        Route::get('clock-in-out', [EmployeeClockInOutController::class, 'index'])
            ->name('employee.clock');

        Route::get('remaining-credit', [EmployeeRemainingCreditController::class, 'index'])
            ->name('employee.credit');

        Route::get('directory', [EmployeeDirectoryController::class, 'index'])
            ->name('employee.directory');
        
        Route::get('team', [EmployeeTeamController::class, 'index'])
            ->name('employee.team');

        Route::get('messages', [EmployeeRequestStatusController::class, 'index'])
            ->name('employee.messages');

        Route::get('announcements', [EmployeeAnnouncementController::class, 'index'])
            ->name('employee.announcements.index');
        Route::get('announcements/{id}', [EmployeeAnnouncementController::class, 'view'])
            ->name('employee.announcements.view');

        Route::get('profile/{form}', [EmployeeProfileController::class, 'index'])
            ->name('employee.profile');

        Route::get('tutorial', [TutorialController::class, 'index'])
            ->name('employee.tutorial');

    });
});
};

if ($hrisDomain) {
    Route::domain($hrisDomain)->group(function () use ($adminRoutes) {
        Route::redirect('/', '/admin/login', 302);
        $adminRoutes();
    });
} else {
    $adminRoutes();
}

if ($essDomain) {
    Route::domain($essDomain)->group(function () use ($employeeRoutes) {
        Route::redirect('/', '/employee/login', 302);
        $employeeRoutes();
    });
} else {
    $employeeRoutes();
}