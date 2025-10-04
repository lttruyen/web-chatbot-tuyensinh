<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản Lý SMTP</title>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to bottom, #dff5ff, #eaf9ff);
            background-attachment: fixed;
        }

        .modal {
            transition: opacity .3s ease, visibility .3s ease;
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

        .table-scroll {
            -webkit-overflow-scrolling: touch;
        }

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
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-4">
    {{-- Check đăng nhập --}}
    @php $needLogin = !session()->has('username') || session('force_login_modal', false); @endphp

    @if ($needLogin)
        <!-- Đăng nhập -->
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                <h2 class="text-xl font-bold text-slate-800 mb-4">Đăng nhập quản trị</h2>
                @if ($errors->has('login'))
                    <div class="mb-3 rounded-md bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm">
                        {{ $errors->first('login') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tài khoản</label>
                        <input id="loginUsername" name="username" type="text" value="{{ old('username') }}"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            autocomplete="username" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu</label>
                        <input name="password" type="password"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            autocomplete="current-password" required>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-md shadow-md transition">Đăng
                            nhập</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
            window.addEventListener('load', () => document.getElementById('loginUsername')?.focus());
        </script>
    @else
        <!-- ===== NAV + MENU TOP (responsive) ===== -->
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
                        <a href="{{ route('admin.user') }}"
                            class="px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('admin.user') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Người
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
                        <a href="{{ route('admin.user') }}"
                            class="w-full text-left px-3 py-2 text-sm font-semibold rounded-md {{ request()->routeIs('admin.user') ? 'bg-white text-slate-900' : 'bg-slate-800 text-white hover:bg-slate-700' }}">Người
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

            <!-- Menu phụ -->
            <div class="bg-white/90 backdrop-blur border-b border-slate-200">
                <div class="mx-auto w-full max-w-6xl px-3 sm:px-4 py-3">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                <span
                                    class="bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-indigo-700 to-blue-600">
                                    Quản lý SMTP
                                </span>
                            </h1>
                            <div class="flex items-center gap-2 sm:gap-3">
                                <select id="pageSizeSelect"
                                    class="border border-gray-300 rounded-lg py-2 px-2 sm:py-2.5 sm:px-3 text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="5">5 hàng/trang</option>
                                    <option value="10" selected>10 hàng/trang</option>
                                    <option value="20">20 hàng/trang</option>
                                    <option value="50">50 hàng/trang</option>
                                    <option value="all">Tất cả</option>
                                </select>
                                <button id="reloadBtn"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-3 sm:py-2.5 sm:px-4 rounded-md shadow-md"
                                    title="Tải lại">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Toggle cho mobile -->
                        <div class="md:hidden pt-1 flex justify-end">
                            <button id="filterToggleBtn"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 bg-white shadow-sm"
                                aria-expanded="false" aria-controls="filtersPanel">
                                <i class="fas fa-sliders-h"></i><span>Bộ lọc</span>
                                <i id="filterChevron" class="fas fa-chevron-down text-xs transition-transform"></i>
                            </button>
                        </div>

                        <!-- Panel lọc -->
                        <div id="filtersPanel"
                            class="overflow-hidden transition-all duration-300 ease-in-out md:overflow-visible md:transition-none md:max-h-none hidden md:block">
                            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-3 items-end mt-3">
                                <div class="relative max-w-xl">
                                    <input id="searchInput" type="text"
                                        placeholder="Tìm theo email/SMTP hoặc mật khẩu..."
                                        class="w-full border border-gray-300 rounded-lg py-2.5 pl-3 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">⌕</span>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input id="onlyDefaultCheckbox" type="checkbox" class="rounded border-slate-300">
                                    Chỉ hiển thị cấu hình mặc định
                                </label>
                                <button id="openCreateModalBtn"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-md shadow-md inline-flex items-center gap-2">
                                    <i class="fas fa-plus"></i> Thêm
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </header>
        <!-- ===== /NAV ===== -->

        <!-- Nội dung -->
        <main class="mx-auto w-full max-w-6xl px-3 sm:px-4 pt-48 sm:pt-56">
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-8 w-full" style="min-height:90vh;">
                <!-- Bảng: desktop fit, mobile cuộn ngang -->
                <div class="relative -mx-3 sm:mx-0">
                    <div class="shadow md:rounded-md overflow-x-auto md:overflow-visible table-scroll">
                        <table class="divide-y divide-gray-200 w-max md:w-full min-w-[720px] md:min-w-0">
                            <thead class="bg-gray-50 text-xs sm:text-sm sticky top-0 z-10">
                                <tr>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Email/SMTP</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Mặc định</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Mật khẩu</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Ngày tạo</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-right font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Hành động</th>
                                </tr>
                            </thead>
                            <tbody id="smtpTableBody" class="bg-white divide-y divide-gray-200 text-sm">
                                <!-- render bằng JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Phân trang -->
                <div id="mainPagination" class="mt-4 flex flex-wrap gap-3 justify-between items-center">
                    <div class="flex items-center gap-2">
                        <button id="firstPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>«</button>
                        <button id="prevPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>‹</button>
                    </div>
                    <span id="pageInfo" class="text-sm text-gray-700">Trang 1/1 (0 bản ghi)</span>
                    <div class="flex items-center gap-2">
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

        <!-- Toast -->
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

        <!-- Modal: Tạo/Sửa SMTP -->
        <div id="smtpModal" class="modal fixed inset-0 z-[9998] flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="smtpModalTitle" class="text-xl font-bold text-slate-800">Thêm SMTP</h3>
                    <button id="closeSmtpModal" class="text-gray-400 hover:text-gray-600"><i
                            class="fas fa-times"></i></button>
                </div>
                <form id="smtpForm" class="space-y-4">
                    <input type="hidden" id="smtp_id" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email/SMTP</label>
                        <input id="smtp_email" type="email"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="ví dụ: no-reply@yourdomain.com" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mật khẩu</label>
                        <input id="smtp_password" type="password"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <p class="text-xs text-gray-500 mt-1">* Để trống khi sửa nếu không muốn đổi mật khẩu.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="smtp_mac_dinh" type="checkbox" class="rounded border-slate-300" />
                        <label for="smtp_mac_dinh" class="text-sm text-slate-700">Đặt làm mặc định</label>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" id="cancelSmtpBtn"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200">Huỷ</button>
                        <button type="submit" id="submitSmtpBtn"
                            class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">Lưu</button>
                    </div>
                </form>
            </div>
        </div>

        <script defer>
            (() => {
                'use strict';
                if (window.__SmtpPageInitialized) return;
                window.__SmtpPageInitialized = true;

                // ===== API endpoints =====
                const API_URL = '{{ url('/api/smtp') }}'; // GET list
                const CREATE_URL = '{{ url('/api/smtp') }}'; // POST create
                const UPDATE_URL = '{{ url('/api/smtp/update') }}'; // POST update
                const DELETE_URL = (id) => '{{ url('/api/smtp') }}/' + id; // DELETE  <-- FIXED
                const SET_DEF_URL = (id) => '{{ url('/api/smtp') }}/' + id + '/set-default';

                // ===== State =====
                let rawItems = [];
                let filteredItems = [];
                let currentPage = 1;
                let pageSize = 10;

                // ===== DOM =====
                const tbody = document.getElementById('smtpTableBody');
                const pageInfoEl = document.getElementById('pageInfo');
                const prevBtn = document.getElementById('prevPageBtn');
                const nextBtn = document.getElementById('nextPageBtn');
                const firstBtn = document.getElementById('firstPageBtn');
                const lastBtn = document.getElementById('lastPageBtn');
                const pageSizeSelect = document.getElementById('pageSizeSelect');
                const reloadBtn = document.getElementById('reloadBtn');

                const searchInput = document.getElementById('searchInput');
                const onlyDefaultCheckbox = document.getElementById('onlyDefaultCheckbox');

                // Modal
                const smtpModal = document.getElementById('smtpModal');
                const openCreateModalBtn = document.getElementById('openCreateModalBtn');
                const closeSmtpModalBtn = document.getElementById('closeSmtpModal');
                const cancelSmtpBtn = document.getElementById('cancelSmtpBtn');
                const smtpForm = document.getElementById('smtpForm');
                const smtpModalTitle = document.getElementById('smtpModalTitle');
                const submitSmtpBtn = document.getElementById('submitSmtpBtn');

                const smtp_id = document.getElementById('smtp_id');
                const smtp_email = document.getElementById('smtp_email');
                const smtp_password = document.getElementById('smtp_password');
                const smtp_mac_dinh = document.getElementById('smtp_mac_dinh');

                // Toast
                const notifyEl = document.getElementById('unifiedNotification');
                const notifyTitle = document.getElementById('notificationTitle');
                const notifyText = document.getElementById('notificationText');
                const notifyIcon = document.getElementById('notificationIcon');
                const notifyClose = document.getElementById('closeNotificationBtn');

                // Mobile nav
                const mobileMenuBtn = document.getElementById('mobileMenuBtn');
                const mobileNavPanel = document.getElementById('mobileNavPanel');

                // Filter toggle (mobile)
                const filterToggleBtn = document.getElementById('filterToggleBtn');
                const filterChevron = document.getElementById('filterChevron');
                const filtersPanel = document.getElementById('filtersPanel');

                // ===== Helpers =====
                const toBool = (v) => {
                    if (typeof v === 'boolean') return v;
                    if (typeof v === 'number') return v === 1;
                    if (typeof v === 'string') {
                        const s = v.trim().toLowerCase();
                        return s === '1' || s === 'true' || s === 'yes' || s === 'y';
                    }
                    return false;
                };

                function getCSRF() {
                    return document.querySelector('meta[name="csrf-token"]')?.content || '';
                }

                let toastTimer = null,
                    currentToast = null,
                    queue = [];

                function showNotification(type = 'success', title = '', text = '') {
                    const t = {
                        type,
                        title,
                        text,
                        duration: 3500
                    };
                    if (currentToast) {
                        queue.push(t);
                        return;
                    }
                    currentToast = t;
                    renderToast(t);
                }

                function renderToast({
                    type,
                    title,
                    text,
                    duration
                }) {
                    notifyEl.classList.remove('alert-success', 'alert-error');
                    if (type === 'success') {
                        notifyEl.classList.add('alert-success');
                        notifyIcon.textContent = '✅';
                    } else {
                        notifyEl.classList.add('alert-error');
                        notifyIcon.textContent = '⚠️';
                    }
                    notifyTitle.textContent = title;
                    notifyText.textContent = text;
                    clearTimeout(toastTimer);
                    notifyEl.style.opacity = '1';
                    notifyEl.style.transform = 'translateY(-50%) scale(1)';
                    notifyEl.style.pointerEvents = 'auto';
                    toastTimer = setTimeout(hideNotification, duration);
                }

                function hideNotification() {
                    notifyEl.style.opacity = '0';
                    notifyEl.style.transform = 'translateY(-50%) scale(0.95)';
                    notifyEl.style.pointerEvents = 'none';
                    currentToast = null;
                    if (queue.length) {
                        const n = queue.shift();
                        setTimeout(() => {
                            currentToast = n;
                            renderToast(n);
                        }, 120);
                    }
                }
                notifyClose?.addEventListener('click', hideNotification);

                function formatDate(s) {
                    if (!s) return '';
                    try {
                        const d = new Date(s);
                        return d.toLocaleString('vi-VN', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });
                    } catch {
                        return s;
                    }
                }

                // Mobile nav toggle
                function setMobileNav(open) {
                    if (!mobileMenuBtn || !mobileNavPanel) return;
                    if (open) {
                        mobileNavPanel.classList.remove('hidden');
                        mobileMenuBtn.setAttribute('aria-expanded', 'true');
                    } else {
                        mobileNavPanel.classList.add('hidden');
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                    }
                }
                let mobileOpen = false;
                mobileMenuBtn?.addEventListener('click', (e) => {
                    e.preventDefault();
                    mobileOpen = !mobileOpen;
                    setMobileNav(mobileOpen);
                });
                window.addEventListener('resize', () => {
                    if (window.matchMedia('(min-width: 768px)').matches) setMobileNav(false);
                });

                // ===== Render table =====
                function renderTable() {
                    tbody.innerHTML = '';
                    const total = filteredItems.length;
                    const isAll = pageSize === 'all';
                    const totalPages = isAll ? 1 : Math.max(1, Math.ceil(total / (pageSize || 10)));
                    currentPage = Math.min(currentPage, totalPages);

                    const start = isAll ? 0 : (currentPage - 1) * (pageSize || 10);
                    const end = isAll ? total : start + (pageSize || 10);
                    const rows = filteredItems.slice(start, end);

                    if (rows.length === 0) {
                        tbody.innerHTML =
                            `<tr><td colspan="5" class="px-6 py-6 text-center text-gray-500">Không có dữ liệu.</td></tr>`;
                    } else {
                        for (const r of rows) {
                            const isDefault = toBool(r.mac_dinh);
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm text-gray-900 break-all">${r.smtp ?? ''}</td>
              <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm">
                ${isDefault
                    ? `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                                                <i class="fa-solid fa-check"></i> Mặc định
                                                            </span>`
                    : `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold text-slate-600 bg-slate-50 ring-1 ring-inset ring-slate-200">
                                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span> Không
                                                            </span>`
                }
                </td>

                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                  <div class="flex items-center gap-2">
                    <span class="font-mono break-all">${r.matkhau ?? ''}</span>
                    ${r.matkhau ? `<button class="text-slate-500 hover:text-slate-700" title="Copy mật khẩu" data-action="copyPass" data-id="${r.id}"><i class="fa fa-copy"></i></button>`:''}
                  </div>
                </td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm text-gray-700">${formatDate(r.created_at)}</td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-right text-sm">
                  <button class="text-indigo-600 hover:text-indigo-800 mr-3" title="Sửa" data-action="edit" data-id="${r.id}"><i class="fas fa-edit"></i> <span>Sửa</span></button>
                  <button class="text-rose-600 hover:text-rose-800" title="Xoá" data-action="delete" data-id="${r.id}"><i class="fas fa-trash"></i> <span>Xoá</span></button>
                </td>`;
                            tbody.appendChild(tr);
                        }
                    }

                    pageInfoEl.textContent = isAll ? `Trang 1/1 (${total} bản ghi) — đang xem Tất cả` :
                        `Trang ${currentPage}/${totalPages} (${total} bản ghi)`;
                    const dis = isAll;
                    prevBtn.disabled = dis || currentPage <= 1;
                    firstBtn.disabled = dis || currentPage <= 1;
                    nextBtn.disabled = dis || currentPage >= totalPages;
                    lastBtn.disabled = dis || currentPage >= totalPages;
                }

                // ===== Filtering =====
                function applyFilter() {
                    const q = (searchInput.value || '').trim().toLowerCase();
                    const onlyDef = onlyDefaultCheckbox.checked;
                    let data = [...rawItems];
                    if (q) {
                        data = data.filter(r => {
                            const a = (r.smtp || '').toLowerCase();
                            const b = (r.matkhau || '').toLowerCase();
                            return a.includes(q) || b.includes(q);
                        });
                    }
                    if (onlyDef) data = data.filter(r => toBool(r.mac_dinh));
                    filteredItems = data;
                    currentPage = 1;
                    renderTable();
                }

                function debounce(el, fn, delay = 200) {
                    if (!el) return;
                    el.addEventListener('input', () => {
                        clearTimeout(el._t);
                        el._t = setTimeout(fn, delay);
                    });
                }

                // ===== API =====
                async function fetchSmtpList() {
                    try {
                        tbody.innerHTML =
                            `<tr><td colspan="5" class="px-6 py-6 text-center text-gray-500">Đang tải dữ liệu...</td></tr>`;
                        const res = await fetch(API_URL, {
                            headers: {
                                Accept: 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const json = await res.json();
                        const arr = Array.isArray(json) ? json : (json.data ?? []);
                        rawItems = arr.map(r => ({
                            ...r,
                            mac_dinh: toBool(r.mac_dinh)
                        }));
                        filteredItems = [...rawItems];
                        renderTable();
                        showNotification('success', 'Tải dữ liệu thành công', `Đã nạp ${rawItems.length} SMTP.`);
                    } catch (err) {
                        tbody.innerHTML =
                            `<tr><td colspan="5" class="px-6 py-6 text-center text-red-600">Lỗi tải dữ liệu: ${err.message}</td></tr>`;
                        showNotification('error', 'Không thể tải dữ liệu', 'Vui lòng kiểm tra API/kết nối mạng.');
                    }
                }

                async function createSmtp(payload) {
                    const res = await fetch(CREATE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCSRF(),
                            Accept: 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json();
                }
                async function updateSmtp(id, payload) {
                    const res = await fetch(UPDATE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCSRF(),
                            Accept: 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            id: Number(id),
                            ...payload
                        })
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json();
                }
                async function deleteSmtp(id) {
                    const res = await fetch(DELETE_URL(id), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': getCSRF(),
                            Accept: 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.text();
                }
                async function setDefaultSmtp(id) {
                    const res = await fetch(SET_DEF_URL(id), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCSRF(),
                            Accept: 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json?.() ?? {};
                }

                // ===== Modal =====
                function openModalForCreate() {
                    smtpModalTitle.textContent = 'Thêm SMTP';
                    smtp_id.value = '';
                    smtp_email.value = '';
                    smtp_password.value = '';
                    smtp_mac_dinh.checked = false;
                    smtpModal.classList.add('show');
                }

                function openModalForEdit(rec) {
                    smtpModalTitle.textContent = 'Sửa SMTP';
                    smtp_id.value = rec.id;
                    smtp_email.value = rec.smtp || '';
                    smtp_password.value = '';
                    smtp_mac_dinh.checked = toBool(rec.mac_dinh);
                    smtpModal.classList.add('show');
                }

                function closeModal() {
                    smtpModal.classList.remove('show');
                }

                function validate(payload, isCreate) {
                    const {
                        smtp,
                        matkhau
                    } = payload;
                    if (!smtp) return showNotification('error', 'Lỗi nhập liệu', 'Vui lòng nhập email/SMTP.'), false;
                    if (smtp.length > 200) return showNotification('error', 'Lỗi nhập liệu',
                        'Email/SMTP tối đa 200 ký tự.'), false;
                    if (isCreate && (!matkhau || matkhau.length < 6)) return showNotification('error', 'Lỗi nhập liệu',
                        'Mật khẩu tối thiểu 6 ký tự.'), false;
                    if (matkhau && matkhau.length < 6) return showNotification('error', 'Lỗi nhập liệu',
                        'Mật khẩu tối thiểu 6 ký tự.'), false;
                    return true;
                }

                // ===== Events =====
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
                    const isAll = pageSize === 'all';
                    const totalPages = isAll ? 1 : Math.max(1, Math.ceil(filteredItems.length / (pageSize || 10)));
                    currentPage = totalPages;
                    renderTable();
                });
                pageSizeSelect.addEventListener('change', () => {
                    const v = pageSizeSelect.value;
                    pageSize = (v === 'all') ? 'all' : (parseInt(v, 10) || 10);
                    currentPage = 1;
                    renderTable();
                });

                reloadBtn.addEventListener('click', fetchSmtpList);
                debounce(searchInput, applyFilter, 200);
                onlyDefaultCheckbox.addEventListener('change', applyFilter);

                openCreateModalBtn.addEventListener('click', openModalForCreate);
                closeSmtpModalBtn.addEventListener('click', closeModal);
                cancelSmtpBtn.addEventListener('click', closeModal);

                smtpForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const id = smtp_id.value;
                    const isCreate = !id;
                    const payload = {
                        smtp: (smtp_email.value || '').trim(),
                        mac_dinh: !!smtp_mac_dinh.checked
                    };
                    if (isCreate || (smtp_password.value && smtp_password.value.trim() !== ''))
                        payload.matkhau = smtp_password.value.trim();
                    if (!validate(payload, isCreate)) return;

                    try {
                        submitSmtpBtn.disabled = true;
                        submitSmtpBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang lưu...';
                        if (isCreate) {
                            await createSmtp(payload);
                            showNotification('success', 'Đã tạo', 'Tạo cấu hình SMTP thành công.');
                        } else {
                            await updateSmtp(id, payload);
                            showNotification('success', 'Đã cập nhật', 'Cập nhật cấu hình SMTP thành công.');
                        }
                        closeModal();
                        await fetchSmtpList();
                    } catch (err) {
                        showNotification('error', 'Lỗi lưu dữ liệu', err.message || 'Không thể lưu SMTP.');
                    } finally {
                        submitSmtpBtn.disabled = false;
                        submitSmtpBtn.innerHTML = 'Lưu';
                    }
                });

                // Row actions
                tbody.addEventListener('click', async (e) => {
                    const btn = e.target.closest('button');
                    if (!btn) return;
                    const action = btn.dataset.action;
                    const id = btn.dataset.id;
                    const rec = rawItems.find(x => String(x.id) === String(id));
                    if (!rec) return;

                    if (action === 'edit') {
                        openModalForEdit(rec);
                    } else if (action === 'delete') {
                        if (!confirm(`Xoá SMTP "${rec.smtp}"? Hành động này không thể hoàn tác.`)) return;
                        try {
                            await deleteSmtp(id);
                            showNotification('success', 'Đã xoá', 'Xoá cấu hình SMTP thành công.');
                            await fetchSmtpList();
                        } catch (err) {
                            showNotification('error', 'Lỗi xoá', err.message || 'Không thể xoá SMTP.');
                        }
                    } else if (action === 'setDefault') {
                        try {
                            await setDefaultSmtp(id);
                            showNotification('success', 'Đã đặt mặc định', `"${rec.smtp}" là SMTP mặc định.`);
                            await fetchSmtpList();
                        } catch (err) {
                            showNotification('error', 'Lỗi cập nhật', err.message || 'Không thể đặt mặc định.');
                        }
                    } else if (action === 'copyPass') {
                        if (rec?.matkhau) {
                            try {
                                await navigator.clipboard.writeText(rec.matkhau);
                                showNotification('success', 'Đã copy', 'Mật khẩu đã được sao chép.');
                            } catch {
                                showNotification('error', 'Copy thất bại',
                                    'Trình duyệt chặn truy cập clipboard.');
                            }
                        }
                    }
                });

                // Mobile filter collapse
                const mq = window.matchMedia('(max-width: 767.98px)');
                let isMobileMode = mq.matches,
                    panelOpen = false;

                function expandPanel() {
                    if (!filtersPanel) return;
                    filtersPanel.classList.remove('hidden');
                    filtersPanel.style.maxHeight = '0px';
                    filtersPanel.offsetHeight;
                    filtersPanel.style.maxHeight = filtersPanel.scrollHeight + 'px';
                    filterToggleBtn?.setAttribute('aria-expanded', 'true');
                    filterChevron?.classList.add('rotate-180');
                    filtersPanel.addEventListener('transitionend', onEnd);

                    function onEnd(e) {
                        if (!e || e.propertyName === 'max-height') {
                            filtersPanel.style.maxHeight = 'none';
                            filtersPanel.removeEventListener('transitionend', onEnd);
                        }
                    }
                }

                function collapsePanel() {
                    if (!filtersPanel) return;
                    filtersPanel.style.maxHeight = filtersPanel.scrollHeight + 'px';
                    filtersPanel.offsetHeight;
                    filtersPanel.style.maxHeight = '0px';
                    filterToggleBtn?.setAttribute('aria-expanded', 'false');
                    filterChevron?.classList.remove('rotate-180');
                    filtersPanel.addEventListener('transitionend', onEnd);

                    function onEnd(e) {
                        if (!e || e.propertyName === 'max-height') {
                            filtersPanel.classList.add('hidden');
                            filtersPanel.removeEventListener('transitionend', onEnd);
                        }
                    }
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

                // Init
                fetchSmtpList();
            })();
        </script>
    @endif
</body>

</html>
