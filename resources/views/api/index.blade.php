<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản Lý API Key</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- CSRF -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <style>
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

        .codechip {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
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
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
            window.addEventListener('load', () => document.getElementById('loginUsername')?.focus());
        </script>
    @else
        <!-- ===== NAV + MENU TOP (RESPONSIVE) ===== -->
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

            <!-- Thanh công cụ trang -->
            <div class="bg-white/90 backdrop-blur border-b border-slate-200">
                <div class="mx-auto w-full max-w-6xl px-3 sm:px-4 py-3">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                <span
                                    class="bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-indigo-700 to-blue-600">
                                    Quản lý API Key
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

                        <!-- Toggle cho mobile -->
                        <div class="md:hidden pt-1 flex justify-end">
                            <button id="filterToggleBtn"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 bg-white shadow-sm active:scale-[0.99] transition"
                                aria-expanded="false" aria-controls="filtersPanel">
                                <i class="fas fa-sliders-h"></i>
                                <span>Bộ lọc</span>
                                <i id="filterChevron" class="fas fa-chevron-down text-xs transition-transform"></i>
                            </button>
                        </div>

                        <!-- Panel bộ lọc -->
                        <div id="filtersPanel"
                            class="overflow-hidden transition-all duration-300 ease-in-out md:overflow-visible md:transition-none md:max-h-none hidden md:block">
                            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-3 items-end mt-3">
                                <div class="relative max-w-xl">
                                    <input id="searchInput" type="text" placeholder="Tìm theo key_name..."
                                        class="w-full border border-gray-300 rounded-lg py-2.5 pl-3 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">⌕</span>
                                </div>

                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input id="onlyDefaultCheckbox" type="checkbox" class="rounded border-slate-300">
                                    Chỉ hiển thị key mặc định
                                </label>

                                <button id="openCreateModalBtn"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-md shadow-md transition inline-flex items-center gap-2">
                                    <i class="fas fa-plus"></i>
                                    Thêm
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </header>
        <!-- ===== /NAV + MENU TOP ===== -->

        <!-- Nội dung -->
        <main class="mx-auto w-full max-w-6xl px-3 sm:px-4 pt-48 sm:pt-56">
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-8 w-full" style="min-height: 90vh;">
                <!-- Bảng: desktop vừa khung, mobile có thể cuộn ngang -->
                <div class="relative -mx-3 sm:mx-0">
                    <div class="overflow-x-auto sm:overflow-visible md:rounded-md shadow">
                        <table class="w-full min-w-[720px] sm:min-w-0 divide-y divide-gray-200">
                            <colgroup>
                                <col class="w-[44%] md:w-[46%]">
                                <col class="w-[16%] md:w-[16%]">
                                <col class="w-[16%] md:w-[16%]">
                                <col class="w-[16%] md:w-[16%]">
                                <col class="w-[8%]  md:w-[6%]">
                            </colgroup>
                            <thead class="bg-gray-50 text-sm sticky top-0 z-10">
                                <tr>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                        Key</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                        Mặc định</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                        Ngày tạo</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                        Cập nhật</th>
                                    <th
                                        class="px-4 sm:px-6 py-3 text-right font-medium text-gray-600 uppercase tracking-wider">
                                        Hành động</th>
                                </tr>
                            </thead>
                            <tbody id="apiKeyTableBody" class="bg-white divide-y divide-gray-200 text-sm">
                                <!-- Render by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Phân trang -->
                <div id="mainPagination" class="mt-4 flex flex-wrap gap-3 justify-between items-center">
                    <div class="flex items-center gap-2">
                        <button id="firstPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>« Đầu</button>
                        <button id="prevPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>‹ Trước</button>
                    </div>
                    <span id="pageInfo" class="text-sm text-gray-700">Trang 1/1 (0 bản ghi)</span>
                    <div class="flex items-center gap-2">
                        <button id="nextPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>Tiếp ›</button>
                        <button id="lastPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1.5 px-3 rounded-md"
                            disabled>Cuối »</button>
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

        <!-- Modal: Tạo/Sửa API Key -->
        <div id="apiKeyModal" class="modal fixed inset-0 z-[9998] flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="apiKeyModalTitle" class="text-xl font-bold text-slate-800">Thêm API Key</h3>
                    <button id="closeApiKeyModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="apiKeyForm" class="space-y-4">
                    <input type="hidden" id="api_id" />
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Key / Name</label>
                        <input id="api_key_name" type="text" maxlength="200"
                            class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="VD: OPENAI_DEFAULT_KEY" required />
                        <p class="text-xs text-gray-500 mt-1">* Tối đa 200 ký tự. Có thể là token thật hoặc nhãn/key
                            name.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="api_mac_dinh" type="checkbox" class="rounded border-slate-300" />
                        <label for="api_mac_dinh" class="text-sm text-slate-700">Đặt làm mặc định</label>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" id="cancelApiBtn"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200">Huỷ</button>
                        <button type="submit" id="submitApiBtn"
                            class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">Lưu</button>
                    </div>
                </form>
            </div>
        </div>

        <script defer>
            (() => {
                'use strict';
                if (window.__ApiKeyPageInitialized) return;
                window.__ApiKeyPageInitialized = true;

                // ==== Config ====
                const API_URL = '{{ url('/api/api-key') }}'; // GET list
                const CREATE_URL = '{{ url('/api/api-key') }}'; // POST create
                const UPDATE_URL = '{{ url('/api/api-key/update') }}'; // POST update
                const DELETE_URL = (id) => `{{ url('/api/api-key/${id}') }}`; // DELETE
                const SET_DEF_URL = (id) => `{{ url('/api/api-key/${id}/set-default') }}`; // POST (nếu có dùng)

                // ==== State ====
                let rawItems = [];
                let filteredItems = [];
                let currentPage = 1;
                let pageSize = 10;

                // ==== DOM ====
                const tbody = document.getElementById('apiKeyTableBody');
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
                const apiKeyModal = document.getElementById('apiKeyModal');
                const openCreateModalBtn = document.getElementById('openCreateModalBtn');
                const closeApiKeyModalBtn = document.getElementById('closeApiKeyModal');
                const cancelApiBtn = document.getElementById('cancelApiBtn');
                const apiKeyForm = document.getElementById('apiKeyForm');
                const apiKeyModalTitle = document.getElementById('apiKeyModalTitle');
                const submitApiBtn = document.getElementById('submitApiBtn');

                // Form fields
                const api_id = document.getElementById('api_id');
                const api_key_name = document.getElementById('api_key_name');
                const api_mac_dinh = document.getElementById('api_mac_dinh');

                // Notification
                const notifyEl = document.getElementById('unifiedNotification');
                const notifyTitle = document.getElementById('notificationTitle');
                const notifyText = document.getElementById('notificationText');
                const notifyIcon = document.getElementById('notificationIcon');
                const notifyClose = document.getElementById('closeNotificationBtn');

                // Mobile filter
                const filterToggleBtn = document.getElementById('filterToggleBtn');
                const filterChevron = document.getElementById('filterChevron');
                const filtersPanel = document.getElementById('filtersPanel');

                // Mobile nav
                const mobileMenuBtn = document.getElementById('mobileMenuBtn');
                const mobileNavPanel = document.getElementById('mobileNavPanel');

                // ==== Helpers ====
                let notifyTimer = null;
                let currentToast = null;
                const toastQueue = [];

                const toBool = (v) => {
                    if (typeof v === 'boolean') return v;
                    if (typeof v === 'number') return v === 1;
                    if (typeof v === 'string') {
                        const s = v.trim().toLowerCase();
                        return s === '1' || s === 'true' || s === 'yes' || s === 'y';
                    }
                    return false;
                };

                function showNotification(type = 'success', title = '', text = '') {
                    const toast = {
                        type,
                        title,
                        text,
                        duration: 3500
                    };
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
                    clearTimeout(notifyTimer);
                    notifyEl.style.opacity = '1';
                    notifyEl.style.transform = 'translateY(-50%) scale(1)';
                    notifyEl.style.pointerEvents = 'auto';
                    notifyTimer = setTimeout(hideNotification, duration);
                }

                function hideNotification() {
                    if (!notifyEl) return;
                    clearTimeout(notifyTimer);
                    notifyEl.style.opacity = '0';
                    notifyEl.style.transform = 'translateY(-50%) scale(0.95)';
                    notifyEl.style.pointerEvents = 'none';
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
                        if (isNaN(d.getTime())) return isoString;
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

                function getCSRF() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                }
                async function copyText(text) {
                    try {
                        await navigator.clipboard.writeText(text);
                        showNotification('success', 'Đã copy', 'Giá trị key đã được sao chép.');
                    } catch {
                        showNotification('error', 'Copy thất bại', 'Trình duyệt chặn truy cập clipboard.');
                    }
                }

                // Mobile nav helpers
                function isMobile() {
                    return window.matchMedia('(max-width: 767.98px)').matches;
                }

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
                function maskKey(val) {
                    if (!val) return '';
                    const s = String(val);
                    if (s.length <= 12) return s;
                    return s.slice(0, 6) + '•••' + s.slice(-4);
                }

                function renderTable() {
                    tbody.innerHTML = '';
                    const total = filteredItems.length;
                    const totalPages = Math.max(1, Math.ceil(total / pageSize));
                    currentPage = Math.min(currentPage, totalPages);
                    const start = (currentPage - 1) * pageSize;
                    const end = start + pageSize;
                    const pageItems = filteredItems.slice(start, end);

                    if (pageItems.length === 0) {
                        tbody.innerHTML =
                            `<tr><td colspan="5" class="px-6 py-6 text-center text-gray-500">Không có dữ liệu.</td></tr>`;
                    } else {
                        for (const r of pageItems) {
                            const isDefault = toBool(r.mac_dinh);
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center gap-2">
                    <code class="codechip bg-slate-50 border border-slate-200 rounded px-2 py-1">
                      <span class="key-mask">${maskKey(r.key_name ?? '')}</span>
                      <span class="key-full hidden">${(r.key_name ?? '')}</span>
                    </code>
                    <button class="text-slate-500 hover:text-slate-700" title="Hiện/ẩn" data-action="toggle" data-id="${r.id}">
                      <i class="fa fa-eye"></i>
                    </button>
                    <button class="text-slate-500 hover:text-slate-700" title="Copy" data-action="copy" data-id="${r.id}">
                      <i class="fa fa-copy"></i>
                    </button>
                  </div>
                </td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm">
                  ${
                    isDefault
                      ? `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                                           <i class="fa-solid fa-check"></i> Mặc định
                                                         </span>`
                      : `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold text-slate-600 bg-slate-50 ring-1 ring-inset ring-slate-200">
                                                           <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span> Không
                                                         </span>`
                  }
                </td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm text-gray-700">${formatDate(r.created_at)}</td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm text-gray-700">${formatDate(r.updated_at)}</td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-right text-sm">
                  <button class="text-indigo-600 hover:text-indigo-800 mr-3" title="Sửa" data-action="edit" data-id="${r.id}">
                    <i class="fas fa-edit"></i> <span>Sửa</span>
                  </button>
                  <button class="text-rose-600 hover:text-rose-800" title="Xoá" data-action="delete" data-id="${r.id}">
                    <i class="fas fa-trash"></i> <span>Xóa</span>
                  </button>
                </td>
              `;
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
                    const onlyDefault = !!onlyDefaultCheckbox?.checked;
                    let data = [...rawItems];

                    if (q) data = data.filter(r => (r.key_name || '').toString().toLowerCase().includes(q));
                    if (onlyDefault) data = data.filter(r => toBool(r.mac_dinh));

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

                // ==== API ====
                async function fetchApiKeyList() {
                    try {
                        tbody.innerHTML =
                            `<tr><td colspan="5" class="px-6 py-6 text-center text-gray-500">Đang tải dữ liệu...</td></tr>`;
                        const res = await fetch(API_URL, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const json = await res.json();

                        let arr = [];
                        if (Array.isArray(json)) arr = json;
                        else if (Array.isArray(json.data)) arr = json.data;
                        else if (json.data && Array.isArray(json.data.data)) arr = json.data.data;

                        rawItems = arr.map(r => ({
                            id: r.id,
                            key_name: r.key_name,
                            mac_dinh: toBool(r.mac_dinh),
                            created_at: r.created_at,
                            updated_at: r.updated_at
                        }));

                        filteredItems = [...rawItems];
                        renderTable();
                        showNotification('success', 'Tải dữ liệu thành công', `Đã nạp ${rawItems.length} API key.`);
                    } catch (err) {
                        tbody.innerHTML =
                            `<tr><td colspan="5" class="px-6 py-6 text-center text-red-600">Lỗi tải dữ liệu: ${err.message}</td></tr>`;
                        showNotification('error', 'Không thể tải dữ liệu', 'Vui lòng kiểm tra API/kết nối mạng.');
                    }
                }

                async function createApiKey(payload) {
                    const res = await fetch(CREATE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCSRF(),
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json();
                }
                async function updateApiKey(id, payload) {
                    const res = await fetch(UPDATE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCSRF(),
                            'Accept': 'application/json'
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
                async function deleteApiKey(id) {
                    const res = await fetch(DELETE_URL(id), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': getCSRF(),
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.text();
                }
                async function setDefaultApi(id) {
                    const res = await fetch(SET_DEF_URL(id), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCSRF(),
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json?.() ?? {};
                }

                // ==== Modal helpers ====
                function openModalForCreate() {
                    apiKeyModalTitle.textContent = 'Thêm API Key';
                    api_id.value = '';
                    api_key_name.value = '';
                    api_mac_dinh.checked = false;
                    apiKeyModal.classList.add('show');
                }

                function openModalForEdit(rec) {
                    apiKeyModalTitle.textContent = 'Sửa API Key';
                    api_id.value = rec.id;
                    api_key_name.value = rec.key_name || '';
                    api_mac_dinh.checked = toBool(rec.mac_dinh);
                    apiKeyModal.classList.add('show');
                }

                function closeApiKeyModal() {
                    apiKeyModal.classList.remove('show');
                }

                function validateApiForm({
                    key_name
                }) {
                    if (!key_name || !key_name.trim()) {
                        showNotification('error', 'Lỗi nhập liệu', 'Vui lòng nhập key_name.');
                        return false;
                    }
                    if (key_name.length > 200) {
                        showNotification('error', 'Lỗi nhập liệu', 'Key tối đa 200 ký tự.');
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
                    const totalPages = Math.max(1, Math.ceil(filteredItems.length / pageSize));
                    currentPage = totalPages;
                    renderTable();
                });
                pageSizeSelect.addEventListener('change', () => {
                    pageSize = parseInt(pageSizeSelect.value, 10) || 10;
                    currentPage = 1;
                    renderTable();
                });

                // ==== Events: load + filter ====
                reloadBtn.addEventListener('click', fetchApiKeyList);
                debounce(searchInput, applyFilter, 200);
                onlyDefaultCheckbox?.addEventListener('change', applyFilter);

                // ==== Events: modal submit ====
                openCreateModalBtn.addEventListener('click', openModalForCreate);
                closeApiKeyModalBtn.addEventListener('click', closeApiKeyModal);
                cancelApiBtn.addEventListener('click', closeApiKeyModal);

                apiKeyForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const id = api_id.value;
                    const isCreate = !id;
                    const payload = {
                        key_name: (api_key_name.value || '').trim(),
                        mac_dinh: !!api_mac_dinh.checked
                    };
                    if (!validateApiForm(payload)) return;

                    try {
                        submitApiBtn.disabled = true;
                        submitApiBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang lưu...';
                        if (isCreate) {
                            await createApiKey(payload);
                            showNotification('success', 'Đã tạo', 'Tạo API key thành công.');
                        } else {
                            await updateApiKey(id, payload);
                            showNotification('success', 'Đã cập nhật', 'Cập nhật API key thành công.');
                        }
                        closeApiKeyModal();
                        await fetchApiKeyList();
                    } catch (err) {
                        showNotification('error', 'Lỗi lưu dữ liệu', err.message || 'Không thể lưu API key.');
                    } finally {
                        submitApiBtn.disabled = false;
                        submitApiBtn.innerHTML = 'Lưu';
                    }
                });

                // ==== Row actions (edit/delete/toggle/copy/setDefault) ====
                tbody.addEventListener('click', async (e) => {
                    const btn = e.target.closest('button');
                    if (!btn) return;
                    const action = btn.getAttribute('data-action');
                    const id = btn.getAttribute('data-id');
                    if (!action || !id) return;
                    const rec = rawItems.find(x => String(x.id) === String(id));
                    if (!rec) return;

                    if (action === 'edit') {
                        openModalForEdit(rec);
                    } else if (action === 'delete') {
                        if (!confirm(`Xoá API Key này? Hành động này không thể hoàn tác.`)) return;
                        try {
                            await deleteApiKey(id);
                            showNotification('success', 'Đã xoá', 'Xoá API key thành công.');
                            await fetchApiKeyList();
                        } catch (err) {
                            showNotification('error', 'Lỗi xoá', err.message || 'Không thể xoá API key.');
                        }
                    } else if (action === 'toggle') {
                        const row = btn.closest('tr');
                        const maskEl = row.querySelector('.key-mask');
                        const fullEl = row.querySelector('.key-full');
                        const icon = btn.querySelector('i');
                        const isHidden = fullEl.classList.contains('hidden');
                        if (isHidden) {
                            fullEl.classList.remove('hidden');
                            maskEl.classList.add('hidden');
                            icon.classList.replace('fa-eye', 'fa-eye-slash');
                        } else {
                            fullEl.classList.add('hidden');
                            maskEl.classList.remove('hidden');
                            icon.classList.replace('fa-eye-slash', 'fa-eye');
                        }
                    } else if (action === 'copy') {
                        if (rec && rec.key_name) copyText(rec.key_name);
                    } else if (action === 'setDefault') {
                        try {
                            await setDefaultApi(id);
                            showNotification('success', 'Đã đặt mặc định', 'Đã cập nhật key mặc định.');
                            await fetchApiKeyList();
                        } catch (err) {
                            showNotification('error', 'Lỗi cập nhật', err.message || 'Không thể đặt mặc định.');
                        }
                    }
                });

                // ==== Mobile filter collapse ====
                const mq = window.matchMedia('(max-width: 767.98px)');
                let isMobileMode = mq.matches;
                let panelOpen = false;

                function expandPanel() {
                    if (!filtersPanel) return;
                    filtersPanel.classList.remove('hidden');
                    filtersPanel.style.maxHeight = '0px';
                    filtersPanel.offsetHeight;
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
                    filtersPanel.offsetHeight;
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
                fetchApiKeyList();
            })();
        </script>
    @endif
</body>

</html>
