<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

    <link
          rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"
          />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@12.4.0/dist/handsontable.min.css">
    
     <link rel="apple-touch-icon" sizes="180x180" href="{{$provider['favicon']}}/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="96x96" href="{{$provider['favicon']}}/favicon-96x96.png">
    <link rel="shortcut icon" href="{{$provider['favicon']}}/favicon.ico" />
    <link rel="manifest" href="{{$provider['favicon']}}/site.webmanifest">

    <title>{{$title}}</title>

    

    @yield('style')

   
    @livewireStyles

    
    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/sass/admin-layout.scss', 'resources/sass/chat.scss'])
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

    <div class="scroll-top">
        <i class="fa-solid fa-arrow-up fa-bounce"></i>
    </div>

    <div id="admin-app">
        @include('components.admin.navbar')
      {{-- @include('components.admin.sub-navbar') --}}

        {{-- INSERT SIDEBAR HERE --}}
       @include('components.admin.sidebar-desk')
        <main>
            <div class="container">
                {{--@include('components.admin.breadcrumb')--}}
                <div class="content">
                    @yield('content')
                </div>
            </div>
        </main>
        <div class="footer mt-5 py-4">
            <div class="container d-flex justify-content-center">
                <div class="text-center">
                    <div class="logo">
                        <img src="{{ asset('/img/' . $provider['logo'])}}">
                    </div>
                    <p class="text-uppercase fw-bold mb-0 mt-3">Powered by {{ $provider['company'] }}</p>
                    <hr class="my-2" style="width: 100%;">
                    <p class="text-uppercase text-muted fw-bold" style="font-size: 13px;">{{ $provider['tagline'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="//cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/jquery.inputmask.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1/daterangepicker.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/handsontable@12.4.0/dist/handsontable.min.js"></script>

    @yield('script')
 @livewireScripts
    <script>
    console.log("Livewire scripts loaded");

     setTimeout(() => {
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    }, 200);

    // Livewire hook for new messages
    document.addEventListener("livewire:load", () => {
        console.log("Scrolled to bottomsss");
    });


    

     document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('adminSidebar');

        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        
    });

    document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.getElementById("adminSidebar");
    const currentUrl = window.location.href;

    const menuGroups = document.querySelectorAll("#adminSidebar .menu-group");
    const menuGroupTitles = document.querySelectorAll("#adminSidebar .menu-group-title");
    const submenuLinks = document.querySelectorAll("#adminSidebar .submenu-item");

    /* -----------------------------
       1. AUTO EXPAND ACTIVE MENU GROUP
    --------------------------------*/
    submenuLinks.forEach(link => {
        if (currentUrl.includes(link.href)) {
            const group = link.closest(".menu-group");
            group?.classList.add("active");
        }
    });

    /* -----------------------------
       2. ACCORDION BEHAVIOR
    --------------------------------*/
    menuGroupTitles.forEach(title => {
        title.addEventListener("click", function () {
            const parent = this.parentElement;

            // Toggle current
            parent.classList.toggle("active");

            // Close others
            menuGroups.forEach(group => {
                if (group !== parent) {
                    group.classList.remove("active");
                }
            });

            // Scroll to top when opened
            sidebar.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    });

    /* -----------------------------
       3. AUTO-SCROLL ON ANY MENU CLICK
    --------------------------------*/
   
});

    document.addEventListener("DOMContentLoaded", function () {

        const sidebars = document.getElementById("adminSidebar");

        // Find currently active menu group
        const activeGroup = document.querySelector(".menu-group.active");

        if (activeGroup) {
            // Scroll sidebar to the active menu group
            console.log("Scrolling to active menu group");
            sidebars.scrollTo({
                top: activeGroup.offsetTop - 20, // small padding
                behavior: "smooth"
            });
        }

        document.querySelectorAll(".menu-group-title").forEach(title => {
            title.addEventListener("click", function () {
                const parent = this.parentElement;

                if (parent.classList.contains("active")) {
                    sidebars.scrollTo({
                        top: parent.offsetTop - 80,
                        behavior: "smooth"
                    });
                }
            });
        });

    });

    document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('adminSidebar');

    sidebar.addEventListener('mouseenter', () => {
        // When mouse is over the sidebar, listen to arrow keys
        window.addEventListener('keydown', handleArrowScroll);
    });

    sidebar.addEventListener('mouseleave', () => {
        // Stop listening when mouse leaves
        window.removeEventListener('keydown', handleArrowScroll);
    });

    function handleArrowScroll(e) {
        const scrollAmount = 50; // px per arrow press

        if (e.key === 'ArrowDown') {
            sidebar.scrollBy({ top: scrollAmount, behavior: 'smooth' });
            e.preventDefault(); // prevent page scroll
        } else if (e.key === 'ArrowUp') {
            sidebar.scrollBy({ top: -scrollAmount, behavior: 'smooth' });
            e.preventDefault(); // prevent page scroll
        }
    }
});

  

</script>
</body>
</html>