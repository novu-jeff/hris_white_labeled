<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

     <link rel="apple-touch-icon" sizes="180x180" href="{{$provider['favicon']}}/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="96x96" href="{{$provider['favicon']}}/favicon-96x96.png">
    <link rel="shortcut icon" href="{{$provider['favicon']}}/favicon.ico" />
    <link rel="manifest" href="{{$provider['favicon']}}/site.webmanifest">

    <title>{{ $title }}</title>

    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.js"></script>
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css"
        integrity="sha512-pTaEn+6gF1IeWv3W1+7X7eM60TFu/agjgoHmYhAfLEU8Phuf6JKiiE8YmsNC0aCgQv4192s4Vai8YZ6VNM6vyQ=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    />
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"
        integrity="sha512-IOebNkvA/HZjMM7MxL0NYeLYEalloZ8ckak+NDtOViP7oiYzG5vn6WVXyrJDiJPhl4yRdmNAG49iuLmhkUdVsQ=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link
          rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"
          />

    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>



    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/sass/home-layout.scss', 'resources/sass/employee-layout.scss', 'resources/sass/chat.scss'])

    @yield('style')

    @livewireStyles
    

      <style>
        .chat-float-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 65px;
            height: 65px;
            background: #005668;
            color: #fff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
            z-index: 9999;
        }
        .chat-float-btn i { font-size: 30px; }

        .floating-chatbox {
    position: fixed;
    bottom: 80px;
    right: 20px;
    width: 350px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0px 4px 15px rgba(0,0,0,0.2);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    user-select: none;
}

.chat-header {
    cursor: move;
    background: #007bff;
    color: #fff;
    padding: 10px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.chat-close-btn {
    cursor: pointer;
    font-size: 18px;
}

.chat-resizer {
    width: 100%;
    height: 8px;
    cursor: ns-resize;
    background: transparent;
}
        .floating-chatbox.show { display: flex; animation: fadeInUp .3s ease-out; }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(20px);} to{opacity:1; transform:translateY(0);} }

        .chat-close-btn {
            position: absolute;
            right: 15px;
            top: 4px;
            color: #444;
            cursor: pointer;
            font-size: 20px;
            z-index: 999;
        }

        .msg-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 15px;
        }
        .send-box {
            flex-shrink: 0;
            padding: 10px 15px;
            border-top: 1px solid #ddd;
            background: #f8f9fa;
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .send-box textarea {
            flex: 1 1 auto;
            min-height: 45px;
            resize: none;
        }
        .send-box button {
            flex-shrink: 0;
        }
        /* Chatbox must have higher z-index */
        .send-box {
            position: relative;
            z-index: 50;
        }
    </style>

</head>
<body>
    <div id="app">

        <div class="scroll-top">
            <i class="fa-solid fa-arrow-up fa-bounce"></i>
        </div>

        @livewire('employee.new-employee')
        @livewire('employee.account-status')
        
        @include('components.employee.navbarnew')

        <!-- FLOATING CLOCK-IN/OUT BUTTON -->
        @canany(['read clock-in-out', 'write clock-in-out'])
        <div id="floatingClockBtn">
            <a href="{{ route('employee.clock') }}" class="btn btn-success">
                <i class="fa-solid fa-clock"></i> Clock In / Out
            </a>
        </div>
        @endcanany

          <!-- ================= SIDEBAR ================= -->
        @include('components.employee.sidebar') <!-- create a separate sidebar Blade -->
        <main>
            <div class="container">
                <div class="content">
                    @yield('content')

                     <!-- Floating Chat Button -->
                    <div class="chat-float-btn" onclick="toggleChatbox()">
                        <i class="fa-solid fa-message"></i>
                        @livewire('employee.unseen-messages-badge')
                    </div>

                    <!-- Chatbox Container -->
                    <!-- Chatbox Container -->
                    <div id="chatboxWindow" class="floating-chatbox" style="display:none;">
                        <div id="chatboxHeader" class="chat-header">
                            <span>Chat</span>
                            <span class="chat-close-btn" onclick="toggleChatbox()">&times;</span>
                        </div>

                        <div class="messages-container" style="overflow-y:auto; height:440px;">
                            @livewire('employee.request-status')
                        </div>

                        <div class="chat-resizer"></div>
                    </div>
                </div>
            </div>
        </main><div class="footer mt-5">
    <div class="container mt-3 py-5">
        <div class="row">
            <div class="col-12 text-center">

                <div class="logo">
                    <img src="{{ asset('/img/' . $provider['logo']) }}">
                </div>

                <div class="logo-phrase mt-3">
                    <p>{{ $provider['tagline'] }}</p>
                </div>

                <hr class="mt-3 mb-2 mx-auto" style="width:150px;">

                <div class="socials">
                    <ul class="list-inline">
                        <li class="list-inline-item">
                            <a target="_blank" href="https://novulutions.com/">
                                <i class="fa-solid fa-earth-asia"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a target="_blank" href="https://www.facebook.com/novulutionsinc">
                                <i class="fa-brands fa-facebook"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a target="_blank" href="https://www.linkedin.com/company/novulutions-inc/">
                                <i class="fa-brands fa-linkedin"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a target="_blank" href="https://www.youtube.com/@NovulutionsInc">
                                <i class="fa-brands fa-youtube"></i>
                            </a>
                        </li>
                    </ul>
                </div>

                <p class="ending text-center mb-0 text-muted mt-5">
                    &copy; 2025. Powered by {{$provider['company']}}
                </p>
            </div>
        </div>
    </div>
</div>

        
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="//cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/jquery.inputmask.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>



    @yield('script')
    @livewireScripts
    <script>
    let lastScrollTop = 0;
    const clockBtn = document.getElementById("floatingClockBtn");

    window.addEventListener("scroll", function() {
        let st = window.pageYOffset || document.documentElement.scrollTop;
        if (st > lastScrollTop) {
            // scrolling down
            clockBtn.style.opacity = "0.3";
        } else {
            // scrolling up
            clockBtn.style.opacity = "1";
        }
        lastScrollTop = st <= 0 ? 0 : st;
    }, false);

   
    document.addEventListener("DOMContentLoaded", function () {
        const sidebar = document.getElementById("employeeSidebar");
        const toggle = document.getElementById("sidebarToggle");

        toggle.addEventListener("click", function () {
            sidebar.classList.toggle("active");
        });
    });

    
function toggleChatbox() {
    const chat = document.getElementById('chatboxWindow');
    chat.classList.toggle('show');

      // Only emit after Livewire is ready
    document.addEventListener('livewire:load', () => {
        if (window.Livewire) {
            window.Livewire.emit('markMessagesAsSeen');
        }
    });
    
    setTimeout(() => {
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    }, 200);

    
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
}

// Livewire hook for new messages
document.addEventListener("livewire:load", () => {
    Livewire.hook('message.processed', () => {
        const chatbox = document.getElementById('chatboxWindow');
        if(chatbox.style.display === "block") scrollToBottom();
    });
});

// Drag functionality
dragElement(document.getElementById("chatboxWindow"));

function dragElement(elm) {
    const header = document.getElementById("chatboxHeader");
    let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;

    if (header) {
        header.onmousedown = dragMouseDown;
    }

    function dragMouseDown(e) {
        e.preventDefault();
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
        e.preventDefault();
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        elm.style.top = (elm.offsetTop - pos2) + "px";
        elm.style.left = (elm.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
    }
}

// Resize functionality
const resizer = document.querySelector('.chat-resizer');
const chatbox = document.getElementById('chatboxWindow');
const messagesContainer = chatbox.querySelector('.messages-container');

resizer.addEventListener('mousedown', initResize);

function initResize(e) {
    e.preventDefault();
    window.addEventListener('mousemove', resize);
    window.addEventListener('mouseup', stopResize);
}

function resize(e) {
    const newHeight = e.clientY - chatbox.getBoundingClientRect().top;
    if(newHeight > 150) messagesContainer.style.height = newHeight + 'px';
}

function stopResize() {
    window.removeEventListener('mousemove', resize);
    window.removeEventListener('mouseup', stopResize);
}

   

        
   
</script>
</body>
</html> 