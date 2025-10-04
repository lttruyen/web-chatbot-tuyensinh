<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản Lý Tài Khoản</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- CSRF (cho các request POST/PUT/DELETE) -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <style>
        h1 {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            font-weight: 800;
            /* đậm hơn */
            letter-spacing: -0.02em;
            /* nén nhẹ chữ */
            line-height: 1.2;
            /* thoáng vừa phải */
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            font-size: 1.875rem;
            /* ~30px: mặc định (md) */
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to bottom, #dff5ff, #eaf9ff);
            background-attachment: fixed;
        }

        .modal {
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
        }

        .modal.show {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
        }

        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 0;
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .alert-success {
            border-color: #66DE93;
        }

        .alert-error {
            border-color: #f7b42c;
        }

        /* Giấu scrollbar khi cần cho thanh nav ngang */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-4">
    {{-- Phần đăng nhập --}}
    @php
        $needLogin = !session()->has('username') || session('force_login_modal', false);
    @endphp
    @if ($needLogin)
        <!-- Overlay + Modal Đăng nhập -->
        <div id="loginModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                <h2 class="text-xl font-bold text-slate-800 mb-4">Đăng nhập quản trị</h2>

                @if ($errors->has('login'))
                    <div class="mb-3 rounded-md bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
                        {{ $errors->first('login') }}
                    </div>
                @endif

                <form id="loginForm" method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="loginUsername" class="block text-sm font-medium text-slate-700 mb-1">Tài
                            khoản</label>
                        <input id="loginUsername" name="username" type="text" value="{{ old('username') }}"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            autocomplete="username" required>
                    </div>
                    <div>
                        <label for="loginPassword" class="block text-sm font-medium text-slate-700 mb-1">Mật
                            khẩu</label>
                        <input id="loginPassword" name="password" type="password"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            autocomplete="current-password" required>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-md shadow-md transition">
                            Đăng nhập
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Khóa scroll khi modal mở & auto focus username
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
            window.addEventListener('load', () => {
                const el = document.getElementById('loginUsername');
                if (el) el.focus();
            });
        </script>
    @else
        <!-- ===== NAV + MENU TOP (CỐ ĐỊNH, RESPONSIVE) ===== -->
        <header class="fixed top-0 inset-x-0 z-50">
            <!-- NAV chính -->
            <div class="bg-slate-900">
                <div class="mx-auto w-full max-w-6xl px-3 sm:px-4 py-2 flex items-center justify-between gap-2">
                    <!-- Left: Hamburger (mobile) -->
                    <div class="flex items-center gap-2">
                        <button id="mobileMenuBtn"
                            class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-700 text-white/90 hover:text-white hover:bg-slate-800 active:scale-[0.99]"
                            aria-controls="mobileNavPanel" aria-expanded="false">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>

                    <!-- Center: Desktop nav -->
                    <nav class="hidden md:flex items-center gap-2">
                        <a href="{{ route('admin.index') }}"
                            class="px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('admin.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Câu
                            hỏi / trả lời</a>
                        <a href="{{ route('user.index') }}"
                            class="px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('user.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Người
                            dùng</a>
                        <a href="{{ route('admin.account') }}"
                            class="px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('admin.account') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Tài
                            khoản</a>
                        <a href="{{ route('smtp.index') }}"
                            class="px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('smtp.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">SMTP</a>
                        <a href="{{ route('api.index') }}"
                            class="px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('api.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">API
                            Key</a>
                        <a href="{{ route('access.index') }}"
                            class="px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('access.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Biểu
                            đồ</a>
                        <a href="{{ route('access.log') }}"
                            class="px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('access.log') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Log
                            câu hỏi
                        </a>
                    </nav>

                    <!-- Right: User -->
                    @if (session()->has('username'))
                        <div class="flex items-center gap-3">
                            <span class="hidden sm:inline text-slate-300 text-sm">{{ session('username') }}</span>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit"
                                    class="px-3 py-2 text-sm font-semibold rounded-md bg-rose-500 text-white hover:bg-rose-600 transition inline-flex items-center gap-2">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Đăng xuất
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- Mobile dropdown nav -->
                <div id="mobileNavPanel" class="md:hidden hidden border-t border-slate-800/50 bg-slate-900">
                    <div class="px-3 py-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <a href="{{ route('admin.index') }}"
                            class="w-full text-left px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('admin.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Câu
                            hỏi / trả lời</a>
                        <a href="{{ route('user.index') }}"
                            class="w-full text-left px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('user.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Người
                            dùng</a>
                        <a href="{{ route('admin.account') }}"
                            class="w-full text-left px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('admin.account') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Tài
                            khoản</a>
                        <a href="{{ route('smtp.index') }}"
                            class="w-full text-left px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('smtp.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">SMTP</a>
                        <a href="{{ route('api.index') }}"
                            class="w-full text-left px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('api.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">API
                            Key</a>
                        <a href="{{ route('access.index') }}"
                            class="w-full text-left px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('access.index') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Biểu
                            đồ</a>
                        <a href="{{ route('access.log') }}"
                            class="w-full text-left px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('access.log') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Log
                            câu hỏi
                        </a>
                    </div>
                </div>
            </div>
            <!-- Menu chức năng của trang Tài khoản -->
            <div class="bg-white/90 backdrop-blur border-b border-slate-200">
                <div class="mx-auto w-full max-w-6xl px-3 sm:px-4 py-3">
                    <div class="flex flex-col gap-3">

                        <!-- Hàng 1: Tiêu đề + Page size + Reload + Thêm mới -->
                        <div class="flex items-center justify-between gap-3">
                            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                <span
                                    class="bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-indigo-700 to-blue-600">
                                    Quản lý tài khoản
                                </span>
                            </h1>
                            <div class="flex items-center gap-2 sm:gap-3">
                                <select id="pageSizeSelect"
                                    class="border border-gray-300 rounded-lg py-2 px-2 sm:py-2.5 sm:px-3 text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="5">5 hàng/trang</option>
                                    <option value="10" selected>10 hàng/trang</option>
                                    <option value="20">20 hàng/trang</option>
                                    <option value="50">50 hàng/trang</option>
                                </select>
                                <button id="reloadBtn"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-3 sm:py-2.5 sm:px-4 rounded-md shadow-md transition"
                                    title="Tải lại">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Hàng 2: Search + Nút mở panel -->
                        <div class="md:hidden pt-1 flex justify-end">
                            <button id="filterToggleBtn"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 bg-white shadow-sm active:scale-[0.99] transition"
                                aria-expanded="false" aria-controls="filtersPanel">
                                <i class="fas fa-sliders-h"></i>
                                <span>Bộ lọc</span>
                                <i id="filterChevron" class="fas fa-chevron-down text-xs transition-transform"></i>
                            </button>
                        </div>

                        <!-- Panel bộ lọc: mobile gập/mở, desktop luôn mở -->
                        <div id="filtersPanel"
                            class="overflow-hidden transition-all duration-300 ease-in-out md:overflow-visible md:transition-none md:max-h-none hidden md:block">
                            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end mt-3">
                                <div class="relative max-w-none md:max-w-xl">
                                    <input id="searchInput" type="text"
                                        placeholder="Tìm username, họ tên hoặc quyền ..."
                                        class="w-full border border-gray-300 rounded-lg py-2.5 pl-3 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">⌕</span>
                                </div>

                                <button id="openCreateModalBtn"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-md shadow-md transition inline-flex items-center gap-2">
                                    <i class="fas fa-user-plus"></i>
                                    Thêm
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </header>
        <!-- ===== /NAV + MENU TOP ===== -->

        <!-- Nội dung: tăng padding-top để không bị che bởi 2 thanh trên -->
        <main class="mx-auto w-full max-w-6xl px-3 sm:px-4 pt-48 sm:pt-56">
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-8 w-full" style="min-height: 90vh;">

                <!-- Bảng: full width trên mobile -->
                <div class="relative -mx-3 sm:mx-0">
                    <div class="overflow-x-auto md:rounded-md shadow">
                        <table class="w-full min-w-full table-fixed divide-y divide-gray-200">
                            <colgroup>
                                <col class="w-[25%] md:w-[30%]"> <!-- Họ tên -->
                                <col class="w-[22%] md:w-[30%]"> <!-- Username -->
                                <col class="w-[12%] md:w-[12%]"> <!-- Quyền -->
                                <col class="w-[15%] md:w-[18%]"> <!-- Hành động -->
                            </colgroup>
                            <thead class="bg-gray-50 text-sm sticky top-0 z-10">
                                <tr>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                        Họ tên</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                        Account</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                        Quyền</th>

                                    <th
                                        class="px-4 sm:px-6 py-3 text-right font-medium text-gray-600 uppercase tracking-wider">
                                        Tác vụ</th>
                                </tr>
                            </thead>
                            <tbody id="accountsTableBody" class="bg-white divide-y divide-gray-200 text-sm">
                                <!-- Hàng sẽ render bằng JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Phân trang -->
                <div id="mainPagination" class="mt-4 flex flex-wrap gap-3 justify-between items-center">
                    <div class="flex items-center">
                        <button id="firstPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>«</button>
                        <button id="prevPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>‹</button>
                    </div>
                    <span id="pageInfo" class="text-sm text-gray-700">Trang 1/1 (0 bản ghi)</span>
                    <div class="flex items-center">
                        <button id="nextPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>›</button>
                        <button id="lastPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>»</button>
                    </div>
                </div>
            </div>
        </main>

        <!-- Notification -->
        <div id="unifiedNotification"
            class="alert fixed top-1/2 left-5 z-50 px-4 py-4 w-[300px] bg-white rounded-lg shadow-lg flex items-start gap-3 transition-all duration-300 transform -translate-y-1/2 scale-95 opacity-0 pointer-events-none">
            <div id="notificationIcon" class="text-2xl">✅</div>
            <div class="flex-1">
                <p id="notificationTitle" class="text-sm font-bold text-gray-900"></p>
                <p id="notificationText" class="mt-1 text-sm text-gray-600"></p>
            </div>
            <button id="closeNotificationBtn"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <!-- Modal: Tạo / Sửa tài khoản -->
        <div id="accountModal" class="modal fixed inset-0 z-[9998] flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="accountModalTitle" class="text-xl font-bold text-slate-800">Thêm tài khoản</h3>
                    <button id="closeAccountModal" class="text-gray-400 hover:text-gray-600"><i
                            class="fas fa-times"></i></button>
                </div>
                <form id="accountForm" class="space-y-4">
                    <input type="hidden" id="acc_id" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                        <input id="acc_username" type="text"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu</label>
                        <input id="acc_pass" type="password"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <p class="text-xs text-gray-500 mt-1">* Để trống khi sửa nếu không muốn đổi mật khẩu.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Họ tên</label>
                        <input id="acc_ho_ten" type="text"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Quyền</label>
                        <select id="acc_quyen"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="user">user</option>
                            <option value="admin">admin</option>
                            @if (session('role') == 'dev')
                                <option value="dev">dev</option>
                            @endif
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" id="cancelAccountBtn"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200">Huỷ</button>
                        <button type="submit" id="submitAccountBtn"
                            class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">Lưu</button>
                    </div>
                </form>
            </div>
        </div>

        <script defer>
            (() => {
                'use strict';
                if (window.__AccountsPageInitialized) return;
                window.__AccountsPageInitialized = true;

                // ==== Config ====
                const API_URL = '{{ url('/api/tai-khoan') }}'; // GET list, PUT/DELETE item
                const ADD_URL = '{{ url('/api/add-ac') }}'; // POST create
                const SAVE_URL = '{{ url('/api/luu-account') }}';
                // ==== State ====
                let rawAccounts = [];
                let filteredAccounts = [];
                let currentPage = 1;
                let pageSize = 10;

                // ==== DOM ====
                const tbody = document.getElementById('accountsTableBody');
                const pageInfoEl = document.getElementById('pageInfo');
                const prevBtn = document.getElementById('prevPageBtn');
                const nextBtn = document.getElementById('nextPageBtn');
                const firstBtn = document.getElementById('firstPageBtn');
                const lastBtn = document.getElementById('lastPageBtn');
                const pageSizeSelect = document.getElementById('pageSizeSelect');
                const reloadBtn = document.getElementById('reloadBtn');

                const searchInput = document.getElementById('searchInput');

                // Modal
                const accountModal = document.getElementById('accountModal');
                const openCreateModalBtn = document.getElementById('openCreateModalBtn');
                const closeAccountModalBtn = document.getElementById('closeAccountModal');
                const cancelAccountBtn = document.getElementById('cancelAccountBtn');
                const accountForm = document.getElementById('accountForm');
                const accountModalTitle = document.getElementById('accountModalTitle');
                const submitAccountBtn = document.getElementById('submitAccountBtn');

                // Form fields
                const acc_id = document.getElementById('acc_id');
                const acc_username = document.getElementById('acc_username');
                const acc_pass = document.getElementById('acc_pass');
                const acc_ho_ten = document.getElementById('acc_ho_ten');
                const acc_quyen = document.getElementById('acc_quyen');

                // Notification
                const notifyEl = document.getElementById('unifiedNotification');
                const notifyTitle = document.getElementById('notificationTitle');
                const notifyText = document.getElementById('notificationText');
                const notifyIcon = document.getElementById('notificationIcon');
                const notifyClose = document.getElementById('closeNotificationBtn');

                // Mobile filter toggle
                const filterToggleBtn = document.getElementById('filterToggleBtn');
                const filterChevron = document.getElementById('filterChevron');
                const filtersPanel = document.getElementById('filtersPanel');

                // Mobile nav toggle
                const mobileMenuBtn = document.getElementById('mobileMenuBtn');
                const mobileNavPanel = document.getElementById('mobileNavPanel');

                // ==== Helpers ====
                let notifyTimer = null;
                let currentToast = null; // đang hiển thị gì
                const toastQueue = []; // hàng đợi {type,title,text,duration}

                function showNotification(type = 'success', title = '', text = '') {
                    const duration = 3500;
                    const toast = {
                        type,
                        title,
                        text,
                        duration
                    };

                    // Nếu đang hiện 1 toast -> cho vào hàng đợi
                    if (currentToast) {
                        toastQueue.push(toast);
                        return;
                    }
                    currentToast = toast;
                    renderToast(toast);
                }

                function renderToast({
                    type,
                    title,
                    text,
                    duration
                }) {
                    if (!notifyEl) return;

                    // reset + style
                    notifyEl.classList.remove('alert-success', 'alert-error');
                    if (type === 'success') {
                        notifyEl.classList.add('alert-success');
                        notifyIcon && (notifyIcon.textContent = '✅');
                    } else {
                        notifyEl.classList.add('alert-error');
                        notifyIcon && (notifyIcon.textContent = '⚠️');
                    }
                    notifyTitle && (notifyTitle.textContent = title);
                    notifyText && (notifyText.textContent = text);

                    // show
                    clearTimeout(notifyTimer);
                    notifyEl.style.opacity = '1';
                    notifyEl.style.transform = 'translateY(-50%) scale(1)';
                    notifyEl.style.pointerEvents = 'auto';

                    // auto-hide
                    notifyTimer = setTimeout(() => hideNotification(), duration);
                }

                function hideNotification() {
                    if (!notifyEl) return;
                    clearTimeout(notifyTimer);

                    notifyEl.style.opacity = '0';
                    notifyEl.style.transform = 'translateY(-50%) scale(0.95)';
                    notifyEl.style.pointerEvents = 'none';

                    // kết thúc toast hiện tại, lấy toast kế tiếp (nếu có)
                    currentToast = null;
                    if (toastQueue.length) {
                        const next = toastQueue.shift();
                        setTimeout(() => {
                            currentToast = next;
                            renderToast(next);
                        }, 120);
                    }
                }
                notifyClose?.addEventListener('click', hideNotification);

                function formatDate(isoString) {
                    if (!isoString) return '';
                    try {
                        const d = new Date(isoString);
                        return d.toLocaleString('vi-VN', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });
                    } catch {
                        return isoString;
                    }
                }

                function isMobile() {
                    return window.matchMedia('(max-width: 767.98px)').matches;
                }

                // ==== Mobile nav toggle ====
                function setMobileNav(open) {
                    if (!mobileNavPanel || !mobileMenuBtn) return;
                    if (open) {
                        mobileNavPanel.classList.remove('hidden');
                        mobileMenuBtn.setAttribute('aria-expanded', 'true');
                    } else {
                        mobileNavPanel.classList.add('hidden');
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                    }
                }
                let mobileNavOpen = false;
                mobileMenuBtn?.addEventListener('click', (e) => {
                    e.preventDefault();
                    mobileNavOpen = !mobileNavOpen;
                    setMobileNav(mobileNavOpen);
                });
                window.addEventListener('resize', () => {
                    if (!isMobile()) setMobileNav(false);
                });

                // ==== Render table ====
                function renderTable() {
                    tbody.innerHTML = '';
                    const total = filteredAccounts.length;
                    const totalPages = Math.max(1, Math.ceil(total / pageSize));
                    currentPage = Math.min(currentPage, totalPages);
                    const start = (currentPage - 1) * pageSize;
                    const end = start + pageSize;
                    const pageItems = filteredAccounts.slice(start, end);

                    if (pageItems.length === 0) {
                        tbody.innerHTML =
                            `<tr><td colspan="5" class="px-6 py-6 text-center text-gray-500">Không có dữ liệu.</td></tr>`;
                    } else {
                        for (const a of pageItems) {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                <td class="px-4 sm:px-6 py-3 whitespace-normal break-words text-gray-700">${a.ho_ten ?? ''}
                    <div class="text-xs text-gray-500 mt-0.5">Tạo ${formatDate(a.created_at)}</div></td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-gray-900">${a.username ?? ''}</td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100">${a.quyen ?? ''}</span>
                </td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-right">
                    <button class="text-indigo-600 hover:text-indigo-800 mr-3" title="Sửa" data-action="edit" data-id="${a.id}">
                        <i class="fas fa-edit"></i> <span>Sửa</span>
                    </button>
                    <button class="text-rose-600 hover:text-rose-800" title="Xoá" data-action="delete" data-id="${a.id}">
                        <i class="fas fa-trash"></i> <span>Xóa</span>
                    </button>
                </td>`;
                            tbody.appendChild(tr);
                        }
                    }

                    pageInfoEl.textContent = `Trang ${currentPage}/${totalPages} (${total} bản ghi)`;
                    prevBtn.disabled = currentPage <= 1;
                    firstBtn.disabled = currentPage <= 1;
                    nextBtn.disabled = currentPage >= totalPages;
                    lastBtn.disabled = currentPage >= totalPages;
                }

                // ==== Filtering ====
                function applyFilter() {
                    const q = (searchInput.value || '').trim().toLowerCase();
                    if (!q) {
                        filteredAccounts = [...rawAccounts];
                    } else {
                        filteredAccounts = rawAccounts.filter(a => {
                            const haystack = [a.username, a.ho_ten, a.quyen].map(x => (x || '').toString()
                                .toLowerCase()).join(' | ');
                            return haystack.includes(q);
                        });
                    }
                    currentPage = 1;
                    renderTable();
                }

                // ==== Fetch data ====
                async function fetchAccounts() {
                    try {
                        tbody.innerHTML =
                            `<tr><td colspan=\"5\" class=\"px-6 py-6 text-center text-gray-500\">Đang tải dữ liệu...</td></tr>`;
                        const res = await fetch(API_URL, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();
                        rawAccounts = Array.isArray(data) ? data : (data.data ?? []);
                        filteredAccounts = [...rawAccounts];
                        renderTable();
                        showNotification('success', 'Tải dữ liệu thành công',
                            `Đã nạp ${rawAccounts.length} bản ghi tài khoản.`);
                    } catch (err) {
                        tbody.innerHTML =
                            `<tr><td colspan=\"5\" class=\"px-6 py-6 text-center text-red-600\">Lỗi tải dữ liệu: ${err.message}</td></tr>`;
                        showNotification('error', 'Không thể tải dữ liệu', 'Vui lòng kiểm tra API hoặc kết nối mạng.');
                    }
                }

                // ==== Modal create/edit ====
                function openModalForCreate() {
                    accountModalTitle.textContent = 'Thêm tài khoản';
                    acc_id.value = '';
                    acc_username.value = '';
                    acc_pass.value = '';
                    acc_ho_ten.value = '';
                    acc_quyen.value = 'user';
                    accountModal.classList.add('show');
                }

                function openModalForEdit(rec) {
                    accountModalTitle.textContent = 'Sửa tài khoản';
                    acc_id.value = rec.id;
                    acc_username.value = rec.username || '';
                    acc_pass.value = '';
                    acc_ho_ten.value = rec.ho_ten || '';
                    acc_quyen.value = rec.quyen || 'user';
                    accountModal.classList.add('show');
                }

                function closeAccountModal() {
                    accountModal.classList.remove('show');
                }

                function getCSRF() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                }
                async function createAccount(payload) {
                    const res = await fetch(ADD_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCSRF(),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json();
                }
                async function updateAccount(id, payload) {
                    const res = await fetch(SAVE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCSRF(),
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            ...payload,
                            id: Number(id)
                        }) // QUAN TRỌNG: kèm id trong body
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json();
                }
                async function deleteAccount(id) {
                    const url = `${API_URL}/${id}`;
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': getCSRF(),
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.text();
                }

                function validateAccountForm(payload) {
                    const {
                        username,
                        password,
                        ho_ten,
                        quyen
                    } = payload;
                    if (!username) {
                        showNotification('error', 'Lỗi nhập liệu', 'Vui lòng nhập tên đăng nhập.');
                        return false;
                    }
                    if (username.length > 100) {
                        showNotification('error', 'Lỗi nhập liệu', 'Tên đăng nhập không được quá 100 ký tự.');
                        return false;
                    }
                    if (password !== undefined) {
                        if (!password) {
                            showNotification('error', 'Lỗi nhập liệu', 'Vui lòng nhập mật khẩu.');
                            return false;
                        }
                        if (password.length < 6) {
                            showNotification('error', 'Lỗi nhập liệu', 'Mật khẩu phải có ít nhất 6 ký tự.');
                            return false;
                        }
                    }
                    if (!ho_ten) {
                        showNotification('error', 'Lỗi nhập liệu', 'Vui lòng nhập họ tên.');
                        return false;
                    }
                    if (ho_ten.length > 200) {
                        showNotification('error', 'Lỗi nhập liệu', 'Họ tên không được quá 200 ký tự.');
                        return false;
                    }
                    const allowed = ['user', 'dev', 'admin'];
                    if (!quyen || !allowed.includes(quyen)) {
                        showNotification('error', 'Lỗi nhập liệu', 'Vui lòng chọn quyền hợp lệ.');
                        return false;
                    }
                    return true;
                }

                // ==== Events: pagination ====
                prevBtn.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        renderTable();
                    }
                });
                nextBtn.addEventListener('click', () => {
                    currentPage++;
                    renderTable();
                });
                firstBtn.addEventListener('click', () => {
                    currentPage = 1;
                    renderTable();
                });
                lastBtn.addEventListener('click', () => {
                    const totalPages = Math.max(1, Math.ceil(filteredAccounts.length / pageSize));
                    currentPage = totalPages;
                    renderTable();
                });
                pageSizeSelect.addEventListener('change', () => {
                    pageSize = parseInt(pageSizeSelect.value, 10) || 10;
                    currentPage = 1;
                    renderTable();
                });

                // ==== Events: load + search ====
                reloadBtn.addEventListener('click', fetchAccounts);

                function debounceInput(el, fn, delay = 200) {
                    if (!el) return;
                    el.addEventListener('input', () => {
                        clearTimeout(el._t);
                        el._t = setTimeout(fn, delay);
                    });
                }
                debounceInput(searchInput, applyFilter, 200);

                // ==== Events: create/edit modal ====
                openCreateModalBtn.addEventListener('click', openModalForCreate);
                closeAccountModalBtn.addEventListener('click', closeAccountModal);
                cancelAccountBtn.addEventListener('click', closeAccountModal);

                accountForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const id = acc_id.value;
                    const payload = {
                        username: acc_username.value.trim(),
                        ho_ten: acc_ho_ten.value.trim(),
                        quyen: acc_quyen.value
                    };
                    // chỉ gửi pass khi tạo mới hoặc có nhập đổi
                    if (!id || (acc_pass.value && acc_pass.value.trim() !== '')) {
                        payload.password = acc_pass.value.trim();
                    }
                    if (!validateAccountForm(payload)) return;

                    try {
                        submitAccountBtn.disabled = true;
                        submitAccountBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang lưu...';
                        if (id) {
                            await updateAccount(id, payload);
                            showNotification('success', 'Đã cập nhật', 'Cập nhật tài khoản thành công.');
                        } else {
                            await createAccount(payload);
                            showNotification('success', 'Đã tạo', 'Tạo tài khoản mới thành công.');
                        }
                        closeAccountModal();
                        await fetchAccounts();
                    } catch (err) {
                        showNotification('error', 'Lỗi lưu dữ liệu', err.message || 'Không thể lưu tài khoản.');
                    } finally {
                        submitAccountBtn.disabled = false;
                        submitAccountBtn.innerHTML = 'Lưu';
                    }
                });

                // ==== Actions trong bảng ====
                tbody.addEventListener('click', async (e) => {
                    const btn = e.target.closest('button');
                    if (!btn) return;
                    const action = btn.getAttribute('data-action');
                    const id = btn.getAttribute('data-id');
                    if (!action || !id) return;
                    const rec = rawAccounts.find(x => String(x.id) === String(id));
                    if (action === 'edit') {
                        if (!rec) return;
                        openModalForEdit(rec);
                    } else if (action === 'delete') {
                        if (!rec) return;
                        if (!confirm(`Xoá tài khoản "${rec.username}"? Hành động này không thể hoàn tác.`))
                            return;
                        try {
                            await deleteAccount(id);
                            showNotification('success', 'Đã xoá', 'Xoá tài khoản thành công.');
                            await fetchAccounts();
                        } catch (err) {
                            showNotification('error', 'Lỗi xoá', err.message || 'Không thể xoá tài khoản.');
                        }
                    }
                });

                // ==== Mobile filter toggle (không tự đóng khi mở bàn phím) ====
                const mq = window.matchMedia('(max-width: 767.98px)');
                let isMobileMode = mq.matches;
                let panelOpen = false; // mobile mặc định đóng

                function expandPanel() {
                    if (!filtersPanel) return;
                    filtersPanel.classList.remove('hidden');
                    filtersPanel.style.maxHeight = '0px';
                    filtersPanel.offsetHeight; // reflow
                    filtersPanel.style.maxHeight = filtersPanel.scrollHeight + 'px';
                    filterToggleBtn?.setAttribute('aria-expanded', 'true');
                    filterChevron?.classList.add('rotate-180');
                    const onEnd = (e) => {
                        if (!e || e.propertyName === 'max-height') {
                            filtersPanel.style.maxHeight = 'none';
                            filtersPanel.removeEventListener('transitionend', onEnd);
                        }
                    };
                    filtersPanel.addEventListener('transitionend', onEnd);
                }

                function collapsePanel() {
                    if (!filtersPanel) return;
                    filtersPanel.style.maxHeight = filtersPanel.scrollHeight + 'px';
                    filtersPanel.offsetHeight; // reflow
                    filtersPanel.style.maxHeight = '0px';
                    filterToggleBtn?.setAttribute('aria-expanded', 'false');
                    filterChevron?.classList.remove('rotate-180');
                    const onEnd = (e) => {
                        if (!e || e.propertyName === 'max-height') {
                            filtersPanel.classList.add('hidden');
                            filtersPanel.removeEventListener('transitionend', onEnd);
                        }
                    };
                    filtersPanel.addEventListener('transitionend', onEnd);
                }

                function applyModeFromMQ() {
                    isMobileMode = mq.matches;
                    if (!filtersPanel) return;
                    if (isMobileMode) {
                        if (panelOpen) {
                            filtersPanel.classList.remove('hidden');
                            filtersPanel.style.maxHeight = 'none';
                            filterToggleBtn?.setAttribute('aria-expanded', 'true');
                            filterChevron?.classList.add('rotate-180');
                        } else {
                            filtersPanel.classList.add('hidden');
                            filtersPanel.style.maxHeight = '0px';
                            filterToggleBtn?.setAttribute('aria-expanded', 'false');
                            filterChevron?.classList.remove('rotate-180');
                        }
                    } else {
                        // desktop: luôn mở
                        filtersPanel.classList.remove('hidden');
                        filtersPanel.style.maxHeight = 'none';
                        filterToggleBtn?.setAttribute('aria-expanded', 'true');
                        filterChevron?.classList.add('rotate-180');
                        panelOpen = true;
                    }
                }
                filterToggleBtn?.addEventListener('click', () => {
                    if (!isMobileMode) return;
                    panelOpen ? collapsePanel() : expandPanel();
                    panelOpen = !panelOpen;
                });
                filtersPanel?.addEventListener('focusin', () => {
                    if (isMobileMode && !panelOpen) {
                        expandPanel();
                        panelOpen = true;
                    }
                });
                mq.addEventListener('change', applyModeFromMQ);
                applyModeFromMQ();

                // ==== Init ====
                fetchAccounts();
            })();
        </script>


    @endif
</body>

</html>
