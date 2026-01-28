<div class="chat-area" wire:ignore.self style="display: flex; flex-direction: column; height: 100%;">
    <div class="chatbox" style="flex: 1 1 auto; display: flex; flex-direction: column;">
        <div class="modal-content" style="flex: 1 1 auto; display: flex; flex-direction: column;">

            <div class="msg-head p-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <img class="img-fluid" src="{{asset('img/logo.png')}}" alt="user img" style="width: 40px;">
                    <div>
                        <h6 class="mb-0">Human Resources (HR)</h6>
                        <small>Administrator</small>
                    </div>
                </div>
            </div>

            <!-- ONLY THIS PART SHOULD AUTO-REFRESH (only when chat is open) -->
            <div class="modal-body msg-body" id="messagesContainer" @if($isChatOpen) wire:poll.3s="loadRecords" @endif>
                <ul class="list-unstyled mb-0">
                    @foreach ($records as $message)
                        @if(!empty($message['message']) || !$message['attachments']->isEmpty())
                            <li class="{{ $message['from_role'] === 'admin' ? 'sender' : 'reply' }} mb-2">
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
                                            {{ relative_time($message['created_at']) }}
                                        @endif
                                    </small>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

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

        </div>
    </div>
</div>

<script>
    document.querySelector('.send-box button').addEventListener('click', function () {
        @this.set('message', document.getElementById('message').value);
    });

    document.addEventListener('DOMContentLoaded', function() {
    const sendBtn = document.querySelector('.send-box button');
    const textarea = document.getElementById('message');

    if (sendBtn) {
        sendBtn.addEventListener('click', function () {
            @this.set('message', textarea.value);

            // Clear textarea IMMEDIATELY
            textarea.value = '';
        });
    }

 
    
    });


</script>

