<!-- Chatbox Container -->
    <div id="chatboxWindow" class="floating-chatbox">
        <span class="chat-close-btn" onclick="toggleChatbox()">&times;</span>

        <div class="chat-area">
            <div class="chatbox">
                <div class="modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="msg-head">
                            <div class="row">
                                <div class="col-12 col-lg-8">
                                    <div class="px-lg-4 d-flex align-items-center">
                                        <span class="chat-icon"><img class="img-fluid" src="{{ asset('img/logo.png') }}" alt="logo"></span>
                                        <div class="flex-shrink-0">
                                            <img class="img-fluid" src="{{ asset('img/logo.png') }}" alt="user img" style="width: 80px;">
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h3>Human Resources (HR)</h3>
                                            <p>Administrator</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-body">
                            <div class="msg-body" wire:poll.3s.visible="loadRecords" id="messagesContainer">
                                <ul>
                                    @foreach ($records as $message)
                                        @if (!empty($message['message']) || !$message['attachments']->isEmpty())
                                            <li class="{{ $message['from_role'] === 'admin' ? 'sender' : 'reply' }} {{ !$message['attachments']->isEmpty() ? 'active' : '' }}">
                                                @if (!empty($message['message']))
                                                    <p>{{ $message['message'] }}</p>
                                                    @if($message['from_role'] == 'admin')
                                                        <span class="time">{{ relative_time($message['created_at']) }}</span>
                                                    @else
                                                        @if(!empty($message['seen_at']))
                                                            <span class="time">Seen {{ format_date($message['seen_at'], 'day_date_time_string') }}</span>
                                                        @elseif(!empty($message['delivered_at']))
                                                            <span class="time">Delivered</span>
                                                        @else
                                                            <span class="time">{{ relative_time($message['created_at']) }}</span>
                                                        @endif
                                                    @endif
                                                @endif

                                                @if (!empty($message['attachments']))
                                                    <div class="attachments mt-2">
                                                        @foreach ($message['attachments'] as $attachment)
                                                            @php
                                                                $filePath = Storage::url('public/messages/' . $attachment['attachment']);
                                                                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                                                            @endphp

                                                            @if (in_array($extension, ['jpg','jpeg','png','gif']))
                                                                <img src="{{ $filePath }}" alt="Image Attachment" class="img-fluid mt-2">
                                                            @elseif (in_array($extension, ['doc','docs','docx','xls','xlsx','pdf']))
                                                                <p>
                                                                    <a wire:click="download({{ $message['id'] }}, {{ $attachment->id }})" href="javascript:void(0)" class="nav-link d-flex align-items-center gap-2">
                                                                        <i class="fa-solid fa-download"></i>
                                                                        {{$attachment['original']}}
                                                                    </a>
                                                                </p>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    @if(empty($message['message']))
                                                        @if($message['from_role'] != 'admin')
                                                            @if(!empty($message['seen_at']))
                                                                <span class="time">Seen {{ format_date($message['seen_at'], 'day_date_time_string') }}</span>
                                                            @elseif(!empty($message['delivered_at']))
                                                                <span class="time">Delivered</span>
                                                            @else
                                                                <span class="time">{{ relative_time($message['created_at']) }}</span>
                                                            @endif
                                                        @else
                                                            <span class="time">{{ relative_time($message['created_at']) }}</span>
                                                        @endif
                                                    @endif
                                                @endif
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="send-box w-100">
                            <form wire:submit.prevent="send" wire:target="send" class="mb-0">
                                <div class="d-lg-flex gap-3">
                                    <div class="form-group w-100">
                                        <label for="message" class="visually-hidden">Message</label>
                                        <textarea
                                            wire:model.defer="message"
                                            id="message"
                                            rows="2"
                                            class="w-100 form-control"
                                            placeholder="Type something..."
                                        ></textarea>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary send w-100">
                                            <span wire:loading.remove wire:target="send">
                                                <i class="fa fa-paper-plane"></i> Send
                                            </span>
                                            <span wire:loading wire:target="send">
                                                <i class="fa-solid fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="error-field mt-2">
                                @error('message') <span class="text-danger">{{ $message }}</span> @enderror
                                @error('attachments') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="send-btns mt-4">
                                <div class="attach">
                                    <div class="button-wrapper btn btn-primary text-white px-4 py-2 rounded-2">
                                        <span class="label fw-bold text-white" style="cursor: pointer">Upload attachments</span>
                                        <input type="file" wire:model.live="attachments" multiple id="upload" class="upload-box" placeholder="Upload File">
                                    </div>
                                    <div>
                                        <small class="text-muted fw-bold">Max 5 files, each ≤ 5 MB.</small>
                                    </div>

                                    @if(isset($preview_attachments))
                                        <div class="attachment-grid mt-4">
                                            @foreach($preview_attachments as $index => $attachment)
                                                @if($attachment['type'] === 'image')
                                                    <div class="attachment-item position-relative">
                                                        <img src="{{ $attachment['url'] }}" class="img-fluid" style="height:100%; width:100%; object-fit: scale-down;">
                                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" wire:click="remove({{$index}})">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>

                                        <div class="attachment-files mt-2">
                                            @foreach($preview_attachments as $index => $attachment)
                                                @if($attachment['type'] === 'file')
                                                    <div class="mb-2 d-flex align-items-center gap-2">
                                                        <i class="{{ format_extension($attachment['url']) }}"></i> 
                                                        {{ format_getFileName($attachment['url']) }}
                                                        <button type="button" class="btn btn-sm btn-danger" wire:click="remove({{$index}})">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- END send-box -->
                    </div>
                </div>
            </div>
        </div>
    </div>