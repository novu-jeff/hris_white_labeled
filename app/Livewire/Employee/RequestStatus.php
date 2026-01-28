<?php

namespace App\Livewire\Employee;

use App\Livewire\Admin\Ess\RequestStatus\Chatbox;
use App\Models\EmployeeAccount;
use App\Models\Message;
use App\Models\MessageAttachments;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class RequestStatus extends Component
{
    
    use WithFileUploads;

    public $isChatOpen = false;
    public $user;
    public $message;
    public $records;
    public $attachments = [];
    public $preview_attachments;

    protected $listeners = [
        'setChatOpen',
        'markMessagesAsSeen',
    ];

    public function setChatOpen($open = false): void
    {
        $this->isChatOpen = (bool) $open;

        if ($this->isChatOpen) {
            $this->loadRecords();
            $this->makeSeen();
            $this->dispatch('showLatest');
        }
    }

    public function markMessagesAsSeen(): void
    {
        $this->makeSeen();
    }
    
    public function mount() {
        $this->user = Auth::user()->load('personal')->personal;
        $this->isFirstTime();
        $this->loadRecords();
        $this->makeSeen();
        $this->dispatch('showLatest');
    }

    public function loadRecords() {
        $sent = Message::with('attachments')->where('from_id', $this->user->employee_no)
            ->where('from_role', 'employee')
            ->where('to_id', 0)
            ->where('to_role', 'admin')
            ->get();
        $received = Message::with('attachments')->where('from_id', 0)
            ->where('from_role', 'admin')
            ->where('to_id', $this->user->employee_no)
            ->where('to_role', 'employee')
            ->get();

        $mergedMessages = $sent->merge($received);
        $sortedMessages = $mergedMessages->sortBy('id')->values();

        $this->records = $sortedMessages;

    }

    public function isFirstTime() {

        $model = Message::class;
        $record = $model::where('from_id', $this->user->employee_no)
            ->orWhere('to_id', $this->user->employee_no)
            ->count();

        if($record <= 0) {

            $name = ucwords($this->user->firstname . ' ' . $this->user->lastname);
            $messages = [
                [
                    'Hello ' . $name
                ], [
                    'I\'m Juan Dela Cruz from the HR department. I just wanted to check in and see if there\'s anything we can assist you with. If you have any questions or need support, feel free to reach out. We\'re here to help!'
                ]
            ];

            foreach ($messages as $message) {
                Message::insert([
                    'from_id' => 0,
                    'from_role' => 'admin',
                    'to_id' => $this->user->employee_no,
                    'to_role' => 'employee',
                    'message' => $message[0] ?? null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }


        }

    }

    public function updated($propertyName)
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($propertyName === 'attachments') {
                $this->addError('attachments', $e->validator->errors()->first('attachments'));
            }
            return;
        }

        if ($propertyName === 'attachments') {
            $this->preview_attachments = collect($this->attachments)
                ->filter(fn($attachment) => $attachment instanceof \Illuminate\Http\UploadedFile)
                ->map(fn($attachment) => $this->processAttachmentPreview($attachment))
                ->values()
                ->toArray();
        }
    }

    private function processAttachmentPreview($attachment)
    {
        $extension = strtolower($attachment->getClientOriginalExtension());

        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return ['type' => 'image', 'url' => $attachment->temporaryUrl()];
        }

        if (in_array($extension, ['pdf', 'xls', 'xlsx', 'doc', 'docs', 'docx'])) {
            $filename = $attachment->store('public/temp');
            return ['type' => 'file', 'url' => Storage::url($filename)];
        }

        return null;
    }

    public function rules()
    {
        return [
            'message' => 'required_without:attachments',
            'attachments' => 'required_without:message|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,pdf,xlsx,xls,doc,docs,docx|max:5120',
        ];
    }

    public function messages()
    {
        return [
            'message.required_without' => 'Message is required when no attachments are provided.',
            'attachments.required_without' => 'At least one attachment is required when no message is provided.',
            'attachments.max' => 'You can upload a maximum of 5 files.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.mimes' => 'Only JPG, JPEG, PNG, GIF, and PDF files are allowed.',
            'attachments.*.max' => 'Each file must not exceed 2 MB.',
        ];
    }

    public function makeSeen() {

        $employee_no = $this->user->employee_no;

        return Message::where('to_id', $employee_no)
            ->update([
                'isSeen' => true,
            ]);
    }

    public function download($message, $attachment) {

        $record = MessageAttachments::where('message_id', $message)
            ->where('id', $attachment)
            ->first();
        
        if(is_null($record)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Attachment does not exists'
            ]);
        }
        
        $path = 'messages/' . $record->attachment;
        
        if(!Storage::disk('public')->exists($path)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Attachment does not exists'
            ]);
        } 

        return response()->download(Storage::disk('public')->path($path), $record->original);
    }

    public function send() {

        $this->validate();

        $message = Message::create([
            'from_id' => $this->user->employee_no,
            'from_role' => 'employee',
            'to_id' => '0',
            'to_role' => 'admin',
            'message' => $this->message ?? null,
        ]);

        $sender_name = $this->user->firstname . ' ' . $this->user->lastname  . '(employee)';
        $user = EmployeeAccount::find($this->user->id);
        $user?->notify(new Notifications('message', $sender_name . ' sent you a message.', route('ess.messages', ['employee_no' => $this->user->employee_no]), 'admin'));

        foreach ($this->attachments as $index => $attachment) {

            if ($attachment instanceof \Illuminate\Http\UploadedFile) {
                $extension = $attachment->getClientOriginalExtension();
                $original_filename = $attachment->getClientOriginalName();
                $new_filename = time() . '_' . $message->id . '_' . $index . '.' . $extension;
                
                $path = 'messages';

                $attachment->storeAs($path, strtolower($new_filename), 'public');
            
                MessageAttachments::insert([
                    'message_id' => $message->id,
                    'original' => $original_filename,
                    'attachment' => $new_filename
                ]);
            
            }

        }

        $this->reset('message', 'preview_attachments', 'attachments');
        $this->loadRecords();
        $this->dispatch('showLatest');

    }

    public function remove(int $index) {
        unset($this->preview_attachments[$index]);
        unset($this->attachments[$index]);
        $this->preview_attachments = array_values($this->preview_attachments);
    }

    public function render()
    {
        return view('livewire.employee.request-status');
    }
}
