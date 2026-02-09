<?php

namespace App\Livewire\Admin\Ess\RequestStatus;

use App\Models\EmployeeAccount;
use App\Models\EmployeeInformation;
use App\Models\EmployeePersonal;
use App\Models\Message;
use App\Models\MessageAttachments;
use App\Notifications\Notifications;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Chatbox extends Component
{
    use WithFileUploads;

    public $employee_no;
    public $records = [];
    public $selected_id;
    public $message;
    public $attachments = [];
    public $preview_attachments;

    protected $listeners = ['selected', 'loadRecords'];

    public function mount()
    {
        $this->selected($this->employee_no);
        $this->dispatch('refreshMessages');
    }

    public function selected(string $employee_no)
    {
        $this->selected_id = $employee_no;
        $this->loadRecords($employee_no);
        $this->dispatch('refreshMessages');
    }

    public function loadRecords( ? string $employee_no = null)
    {
        //dd($employee_no);
        $employee_no = $employee_no ?? $this->selected_id;

        $user = EmployeeInformation::with(['personal', 'positions'])
            ->where('employee_no', $employee_no)
            ->first();

         //  dd($user);

        if (!$user) {
            return redirect()->route('ess.request-status');
        }

        $sent = Message::with('attachments')
            ->where(['from_id' => 0, 'from_role' => 'admin', 'to_id' => $user->employee_no, 'to_role' => 'employee'])
            ->get();

        $received = Message::with('attachments')
            ->where(['from_id' => $user->employee_no, 'from_role' => 'employee', 'to_id' => 0, 'to_role' => 'admin'])
            ->get();

        // Mark messages from employee to admin as delivered when admin loads the conversation
        Message::where('from_id', $user->employee_no)
            ->where('to_id', 0)
            ->whereNull('delivered_at')
            ->update(['delivered_at' => now()]);

        $this->records = [
            'user' => $user,
            'messages' => $sent->merge($received)->sortBy('id')->values(),
        ];

        $this->isFirstTime($user->employee_no);
        // ✅ Dispatch browser event so JS can scroll
        $this->dispatch('refreshMessages'); // already in your component
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

    public function isFirstTime(string $id)
    {
        if (Message::where('from_id', $id)->orWhere('to_id', $id)->doesntExist()) {
            $user = EmployeePersonal::where('employee_no', $id)->first();
            $name = ucwords("{$user->firstname} {$user->lastname}");

            $messages = [
                "Hello {$name}",
                "I’m Josephine Garcia from the HR department. I just wanted to check in and see if there’s anything we can assist you with. If you have any questions or need support, feel free to reach out. We’re here to help!",
            ];

            foreach ($messages as $message) {
                Message::create([
                    'from_id' => 0,
                    'from_role' => 'admin',
                    'to_id' => $id,
                    'to_role' => 'employee',
                    'message' => $message,
                    'created_at' => Carbon::now(),
                ]);
            }

            $this->loadRecords($id);
        }
    }

    public function send()
    {
        $this->validate();

        $message = Message::create([
            'from_id' => 0,
            'from_role' => 'admin',
            'to_id' => $this->selected_id,
            'to_role' => 'employee',
            'message' => $this->message,
        ]);

        foreach ($this->attachments as $index => $attachment) {
            if ($attachment instanceof \Illuminate\Http\UploadedFile) {
                $this->storeAttachment($attachment, $message->id, $index);
            }
        }

        $sender = Auth::user();
        $sender_name = $sender->name . ' (' . $sender->roles[0]->name . ')';
       
        $user = EmployeeAccount::where('employee_no', $this->selected_id)->first();
        $user?->notify(new Notifications('message', $sender_name . ' sent you a message.', route('employee.messages'), 'employee'));

        $this->reset('message', 'preview_attachments', 'attachments');
        $this->loadRecords($this->selected_id);
        $this->dispatch('refreshMessages');
    }

    private function storeAttachment($attachment, $messageId, $index)
    {
        $extension = $attachment->getClientOriginalExtension();
        $originalFilename = $attachment->getClientOriginalName();
        $newFilename = time() . "_{$messageId}_{$index}." . strtolower($extension);
        $path = 'messages';

        $attachment->storeAs($path, $newFilename, 'public');

        MessageAttachments::create([
            'message_id' => $messageId,
            'original' => $originalFilename,
            'attachment' => $newFilename,
        ]);
    }

    public function download($message, $attachment)
    {
        $record = MessageAttachments::where('message_id', $message)
            ->where('id', $attachment)
            ->first();

        if (!$record || !Storage::disk('public')->exists('messages/' . $record->attachment)) {
            return $this->dispatch('alert', [
                'showAlert' => true,
                'status' => 'error',
                'title' => 'Oops!',
                'message' => 'Attachment does not exist',
            ]);
        }

        return response()->download(Storage::disk('public')->path('messages/' . $record->attachment), $record->original);
    }

    public function makeSeen()
    {
        if ($this->selected_id) {
            Message::where('from_id', $this->selected_id)
                ->where('to_id', 0)
                ->update([
                    'isSeen' => true,
                    'delivered_at' => \DB::raw('COALESCE(delivered_at, NOW())'),
                    'seen_at' => now(),
                ]);
        }
    }

    public function remove(int $index) {
        unset($this->preview_attachments[$index]);
        unset($this->attachments[$index]);
        $this->preview_attachments = array_values($this->preview_attachments);
    }

    public function render()
    {
        return view('livewire.admin.ess.request-status.chatbox');
    }
}
