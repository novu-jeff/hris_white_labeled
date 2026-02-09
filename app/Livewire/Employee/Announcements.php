<?php

namespace App\Livewire\Employee;

use App\Models\EmployeeAnnouncements;
use App\Models\EmployeeAnnouncementsSeen;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Announcements extends Component
{

    use WithPagination;

    public $record_id;
    public $user_id;
    public $nextAndPrev;
    public $seenBy;

    protected $paginationTheme = 'bootstrap';
    public $entries = 6;
    public $search = '';

    public $view;

    public function mount() {

        $user_id = Auth::user()->employee_no;

        if(is_null($user_id)) {
            return redirect()->route('employee.leave');
        }

        $this->loadRecords();
        $this->getSeen($user_id);
        $this->makeSeen($user_id);

        return $this->user_id = $user_id;
    }

    public function loadRecords() {
        $records = EmployeeAnnouncements::with('attachments')
            ->where('isDeleted', false);

        if (!is_null($this->record_id)) {
            
            $record = $records->where('id', $this->record_id)->first();

            if (!$record) {
                return redirect()->route('employee.announcements.index');
            }

            $this->getPreviousNextAnnouncements($record->id);

            $this->view = $record;
        }

        return $this->view;
    }

    public function getPreviousNextAnnouncements($id) {

        $currentJobId = EmployeeAnnouncements::where('id', $id)->value('id');

        $prev = EmployeeAnnouncements::select('id')->where('id', '<', $currentJobId)
            ->orderBy('id', 'desc')
            ->first();

        $next =  EmployeeAnnouncements::select('id')->where('id', '>', $currentJobId)
            ->orderBy('id', 'asc')
            ->first();
        
        return $this->nextAndPrev = [
            'prev' => !is_null($prev) ? route('employee.announcements.view', ['id' => $prev['id']]) : null,
            'next' => !is_null($next) ? route('employee.announcements.view', ['id' => $next['id']]) : null
        ];

    }

    private function getSeen(? string $employee_no = null) {
        
        $announcement_id = $this->record_id;

        $records = EmployeeAnnouncementsSeen::with('personal')
                ->where('announcement_id', $announcement_id)
                ->get();

        $seenBy = [];
        $me = null;

        foreach($records as $record) {
            if($record->employee_no == $employee_no) {
            $me = [
                'name' => 'Me',
                'timestamp' => $record->created_at
            ];
            } else {
            $seenBy[] = [
                'name' => strtolower(trim((string) optional($record->personal)->firstname . ' ' . (string) optional($record->personal)->lastname)) ?: 'unknown',
                'timestamp' => $record->created_at
            ];
            }
        }

        if ($me) {
            array_unshift($seenBy, $me);
        }

        return $this->seenBy = $seenBy;
    }

    private function makeSeen(string $employee_no) {
        
        $announcement_id = $this->record_id;

        $record = EmployeeAnnouncements::find($announcement_id);

        if ($record) {
            $exists = EmployeeAnnouncementsSeen::where('announcement_id', $announcement_id)
                ->where('employee_no', $employee_no)
                ->exists();

            if (!$exists) {
                $seen = new EmployeeAnnouncementsSeen();
                $seen->announcement_id = $announcement_id;
                $seen->employee_no = $employee_no;
                $seen->save();

                return redirect()->route('employee.announcements.view', ['id' => $announcement_id]);

            }
        }
    }

    public function render()
    {

        $model = EmployeeAnnouncements::where('isDeleted', false);

        if ($this->search) {
            $this->resetPage(); 
            $model->where('title', 'like', '%' . $this->search . '%');
        }

        $records = $model->paginate($this->entries);

        return view('livewire.employee.announcements', [
            'records' => $records
        ]);
    }
}
