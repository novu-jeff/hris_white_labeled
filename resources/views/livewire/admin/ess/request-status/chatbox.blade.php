<div id="chatboxWindow">
<div class="chatbox d-flex flex-column" style="height: 100%;">

    @if(!is_null($selected_id))
        @php
            $user = $records['user'] ?? null;
            $fullname = $user ? $user['personal']->firstname . ' ' . $user['personal']->lastname : 'Unknown User';
            $profilePhoto = $user && !empty($user['personal']->profile)
                            ? asset(Storage::url($user['personal']->profile))
                            : 'https://ui-avatars.com/api/?background=005668&color=ffffff&font-size=0.4&bold=true&name=' . urlencode($fullname);
        @endphp

        <!-- Chat Header -->
        <div class="msg-head px-4 py-2 border-bottom d-flex align-items-center gap-2">
            <span class="chat-icon">
                
            </span>
            <img src="{{ $profilePhoto }}" alt="Profile Photo" style="width:50px; height:50px; object-fit:cover; border-radius:50%;">
            <div class="flex-grow-1 ms-2">
                <h6 class="mb-0">{{ ucwords($fullname) }} </h6>
                <small>{{ strtoupper($user['positions']->name ?? 'Employee') }}</small>
            </div>
        </div>

        <!-- Messages -->
      <div class="modal-body msg-body" id="messagesContainer" wire:poll.3s="loadRecords">
            <ul class="list-unstyled mb-0">
                @foreach ($records['messages'] ?? [] as $message)
                    @if (!empty($message['message']) || !$message['attachments']->isEmpty())
                        <li class="{{ $message['from_role'] === 'admin' ? 'reply' : 'sender' }} mb-2">
                            @if(!empty($message['message']))
                                <p>{{ $message['message'] }}</p>
                                <small class="text-muted">
                                    @if($message['from_role'] != 'admin')
                                        @if($message['isSeen'])
                                            Seen at {{ format_date($message['created_at'], 'day_date_time_string') }}
                                        @else
                                            {{ relative_time($message['created_at']) }}
                                        @endif
                                    @else
                                        @if(!empty($message['seen_at']))
                                            Seen {{ format_date($message['seen_at'], 'day_date_time_string') }}
                                        @elseif(!empty($message['delivered_at']))
                                            Delivered
                                        @else
                                            {{ relative_time($message['created_at']) }}
                                        @endif
                                    @endif
                                </small>
                            @endif

                            @if(!empty($message['attachments']))
                                <div class="attachments mt-2">
                                    @foreach ($message['attachments'] as $attachment)
                                        @php
                                            $filePath = Storage::url('public/messages/' . $attachment['attachment']);
                                            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                                        @endphp

                                        @if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']))
                                            <img src="{{ $filePath }}" class="img-fluid mt-2" style="max-width:200px; object-fit:cover;">
                                        @elseif (in_array($extension, ['doc','docs','docx','xls','xlsx','pdf']))
                                            <p>
                                                <a wire:click="download({{ $message['id'] }}, {{ $attachment->id }})" href="javascript:void(0)" class="d-flex align-items-center gap-2">
                                                    <i class="fa-solid fa-download"></i> {{$attachment['original']}}
                                                </a>
                                            </p>
                                        @endif
                                    @endforeach
                                </div>
                                @if(empty($message['message']))
                                    <small class="text-muted">
                                        @if($message['from_role'] === 'admin')
                                            @if(!empty($message['seen_at']))
                                                Seen {{ format_date($message['seen_at'], 'day_date_time_string') }}
                                            @elseif(!empty($message['delivered_at']))
                                                Delivered
                                            @else
                                                {{ relative_time($message['created_at']) }}
                                            @endif
                                        @else
                                            @if($message['isSeen'])
                                                Seen at {{ format_date($message['created_at'], 'day_date_time_string') }}
                                            @else
                                                {{ relative_time($message['created_at']) }}
                                            @endif
                                        @endif
                                    </small>
                                @endif
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>

        <!-- Send Box -->
        <!-- DO NOT RE-RENDER BELOW -->
            <div class="send-box" wire:ignore>
                <textarea
                    id="message"
                    class="form-control"
                    placeholder="Type something..."
                ></textarea>

                <button class="btn btn-primary" type="button" wire:click="send">
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>


    @endif
</div>
</div>
<script>
    document.querySelector('.send-box button').addEventListener('click', function () {
        @this.set('message', document.getElementById('message').value);
    });

    document.addEventListener('DOMContentLoaded', function() {
        const sendBtn = document.querySelector('.send-box button');
        const textarea = document.getElementById('message');
        const container = document.getElementById('messagesContainer');
        const chatbox = document.getElementById('chatboxWindow');
console.log("Messages container:", container.scrollHeight);
        if (sendBtn) {
            sendBtn.addEventListener('click', function () {
                console.log("Scrolled to bottom after sending message");
               
                if (chatbox.style.display === "none" || chatbox.style.display === "") {
                    console.log("Showing  chatbox");
                    chatbox.style.display = "block";

                        // Scroll to the bottom after a short delay (for Livewire rendering)
                        setTimeout(() => {
                            const messagesContainer = chatbox.querySelector('#messagesContainer'); // replace with actual message container class/id
                            if(messagesContainer){
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
                        }, 100); // 100ms delay to ensure messages are rendered
                    } else {
                        chatbox.style.display = "none";
                    }


                @this.set('message', textarea.value);

                // Clear textarea IMMEDIATELY
                textarea.value = '';
            });
        }

 
    
    });


</script>



