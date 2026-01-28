<div class="notification d-none d-md-block" @if($isOpened) wire:poll.10s.visible="loadNotifications" @endif>
    <div wire:click="toggle" data-bs-toggle="tooltip" title="Notification">
        <i class="fa-regular fa-bell"></i>
        @if($notifications['unread'] > 0)
            <span class="count">{{$notifications['unread']}}</span>
        @endif
    </div>
    <div class="content {{$isOpened ? 'd-block' : 'd-none'}}">
        <div class="header d-flex align-items-center justify-content-between">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div>
                    <h6 class="m-0">All Notifications</h6>
                </div>
            </div>
            <div class="d-flex gap-3 align-items-center">
                @if(!$isShowSearch)
                    <button class="btn btn-primary" wire:click="toggleSearch">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 16px"></i>
                    </button>
                    @if($notifications['unread'] > 0) 
                        <button wire:click="markAsRead" class="btn btn-info mb-0 px-3 fw-bold" style="font-size:12px">Mark as Read</button>
                    @endif
                    <div class="overlay close d-lg-none">
                        <i class="fa-solid fa-xmark" wire:click="toggle"></i>
                    </div>
                @else
                    <input type="text" wire:model="search_param" id="search" class="form-control">
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-primary" wire:click="search">
                            <i class="fa-solid fa-magnifying-glass" style="font-size: 16px"></i>
                        </button>
                        <button class="btn btn-primary" wire:click="toggleSearch">
                            <i class="fa-solid fa-xmark" style="font-size: 16px"></i>
                        </button>
                    </div>
                @endif
            </div>
        </div>
        <div class="scrollable" id="notificationList">
            @forelse ($notifications['data'] as $key => $item)
                @php
                    $data = json_decode($item['data'], true);
                @endphp
                <div wire:click="read('{{$item['id']}}', '{{$data['redirect']}}')" class="item nav-link {{is_null($item['read_at']) ? 'active' : ''}}">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon">
                            @if($data['type'] == 'info')
                                <i class="fa-regular fa-lightbulb" style="color: #d3b404"></i>
                            @elseif($data['type'] == 'success')
                                <i class="fa-regular fa-thumbs-up" style="color: #225F8B"></i>
                            @elseif($data['type'] == 'error')
                                <i class="fa-solid fa-triangle-exclamation" style="color: #dc3545"></i>
                            @elseif($data['type'] == 'message')
                                <i class="fa-regular fa-message" style="color: #225F8B"></i>
                            @endif
                        </div>
                        <div class="message">
                            <div> {!! $data['message'] !!}</div>
                            <small class="text-muted fw-medium">(click this notification to view more details)</small>
                        </div>
                    </div>
                    <div class="timestamp">
                        <small>{{ \Carbon\Carbon::parse($item['created_at'])->diffForHumans() }}</small>
                    </div>
                </div>
            @empty
                <p class="text-uppercase text-center p-3 mt-4">No notifications available.</p>
            @endforelse
        </div>
    </div>
    <video id="notification-video" controls width="400" class="d-none">
        <source src="{{ asset('sounds/notification.mp3') }}" type="video/mp4">
        Your browser does not support the video element.
    </video>      
</div>

@section('script')
<script>
  $(function() {

        var notificationList = $('#notificationList');
        notificationList.on('scroll', function() {
            // Only refresh when user is near the bottom (prevents constant /livewire/update spam).
            const el = notificationList[0];
            if (!el) return;

            const nearBottom = (notificationList.scrollTop() + notificationList.innerHeight()) >= (el.scrollHeight - 300);
            if (nearBottom) {
                Livewire.dispatch('loadNotifications');
            }
        });

        // Livewire.on('notify', function(event) {
        //     const videoPlayer = document.getElementById('notification-video'); // Select the <video> element
        //     videoPlayer.play().catch((error) => {
        //         console.error('Video playback failed:', error);
        //     });
        // });

        Livewire.on('refreshPage', () => {
            location.reload();  
        });

    });

</script>
@endsection
