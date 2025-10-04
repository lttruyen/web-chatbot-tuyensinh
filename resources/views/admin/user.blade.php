<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản Lý Người Dùng</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- CSRF (nếu cần cho các request POST/PUT/DELETE sau này) -->
    <meta name="csrf-token" content="your-csrf-token-here" />

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

        /* Cho vùng bảng cuộn ngang mượt trên iOS */
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
    {{-- Phần đăng nhập --}}
    @php
        $needLogin = !session()->has('username') || session('force_login_modal');
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

            <!-- Menu chức năng của trang Người dùng -->
            <div class="bg-white/90 backdrop-blur border-b border-slate-200">
                <div class="mx-auto w-full max-w-6xl px-3 sm:px-4 py-3">
                    <div class="flex flex-col gap-3">
                        <!-- Hàng 1: Tiêu đề + Page size + Reload -->
                        <div class="flex items-center justify-between gap-3">
                             <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                <span
                                    class="bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-indigo-700 to-blue-600">
                                    Người dùng
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
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-3 sm:py-2.5 sm:px-4 rounded-md shadow-md transition"
                                    title="Tải lại">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Nút toggle cho mobile -->
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
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                <!-- Cột 1: Tìm kiếm -->
                                <div class="md:col-span-5">
                                    <div class="relative">
                                        <input id="searchInput" type="text"
                                            placeholder="Tìm tên, email, SĐT, địa chỉ..."
                                            class="w-full border border-gray-300 rounded-lg py-2.5 pl-3 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">⌕</span>
                                    </div>
                                </div>
                                <!-- Cột 2: Ngày tạo (2 dòng) -->
                                <div class="md:col-span-4">
                                    <div class="grid grid-rows-2 gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-700 whitespace-nowrap">Từ ngày:&nbsp;</span>
                                            <input type="date" id="startDate"
                                                class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-700 whitespace-nowrap">Đến ngày:</span>
                                            <input type="date" id="endDate"
                                                class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        </div>
                                    </div>
                                </div>
                                <!-- Cột 3: Năm sinh -->
                                <div class="md:col-span-2">
                                    <label for="birthYearInput"
                                        class="block text-sm font-medium text-gray-700 mb-1">Năm sinh</label>
                                    <input type="number" id="birthYearInput" placeholder="VD: 1990" min="1900"
                                        class="w-full max-w-[140px] border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                </div>
                                <!-- Cột 4: Excel + Email -->
                                <div class="md:col-span-1 flex flex-col gap-2 md:items-end">
                                    <button id="exportExcelBtn"
                                        class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-md shadow-md transition flex items-center justify-center gap-2"
                                        title="Xuất Excel theo ngày đã chọn">
                                        <i class="fas fa-file-excel"></i> <span>Excel</span>
                                    </button>
                                    <button id="sendEmailBtn"
                                        class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-md shadow-md transition flex items-center justify-center gap-2"
                                        title="Gửi email cho các nhân sự đã chọn">
                                        <i class="fas fa-paper-plane"></i> <span>Email</span>
                                    </button>
                                </div>
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
                <div class="relative -mx-4 md:mx-0">
                    <div class="shadow md:rounded-md overflow-x-auto md:overflow-visible table-scroll">
                        <!-- QUAN TRỌNG: bỏ table-fixed/colgroup; min-width chỉ áp dụng cho mobile -->
                        <table class="divide-y divide-gray-200 w-max md:w-full min-w-[960px] md:min-w-0">
                            <colgroup>
                                <col class="w-[10%] md:w-[10%]">
                                <col class="w-[25%] md:w-[25%]">
                                <col class="w-[30%] md:w-[30%]">
                                <col class="w-[20%] md:w-[20%]">
                                <col class="w-[15%] md:w-[15%]">
                            </colgroup>
                            <thead class="bg-gray-50 text-xs sm:text-sm sticky top-0 z-10">
                                <tr>
                                    <th
                                        class="px-3 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        <input type="checkbox" id="selectAllCheckbox" class="w-4 h-4"
                                            title="Chọn tất cả trên trang hiện tại">
                                    </th>
                                    <th
                                        class="px-3 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Người dùng</th>
                                    <th
                                        class="px-3 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Liên hệ</th>
                                    <th
                                        class="px-3 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Năm sinh</th>
                                    <th
                                        class="px-3 sm:px-6 py-3 text-right font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                        Tác vụ</th>
                                </tr>
                            </thead>

                            <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200 text-sm">
                                <!-- Hàng render bằng JS -->
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

        <!-- Modal Gửi Email -->
        <div id="emailModal" class="fixed inset-0 z-[1000] hidden">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="relative mx-auto mt-24 w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-800">Gửi email đến nhân sự đã chọn</h3>
                    <button id="closeEmailModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Người nhận</label>
                    <div id="recipientsPreview"
                        class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg p-2 min-h-[40px] overflow-y-auto max-h-28">
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Tự động lấy từ các hàng đã được chọn (checkbox).</p>
                </div>

                <div class="mb-3">
                    <label for="emailSubject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input id="emailSubject" type="text"
                        class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Nhập tiêu đề email">
                </div>

                <div class="mb-4">
                    <label for="emailBody" class="block text-sm font-medium text-gray-700 mb-1">Body</label>
                    <textarea id="emailBody" rows="6"
                        class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Nhập nội dung email"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button id="cancelEmailBtn"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-md">Hủy</button>
                    <button id="confirmSendEmailBtn"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md">Gửi</button>
                </div>
            </div>
        </div>

        <!-- Modal Lịch sử chat -->
        <div id="chatModal" class="fixed inset-0 z-[9998] hidden">
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="relative mx-auto mt-20 w-full max-w-3xl bg-white rounded-2xl shadow-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="chatModalTitle" class="text-lg font-semibold text-slate-800">Lịch sử chat</h3>
                    <button id="closeChatModalBtn" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="chatModalList" class="space-y-4 max-h-[60vh] overflow-y-auto"></div>
            </div>
        </div>

        <!-- Modal Thêm Q&A (giữ nguyên id) -->
        <div id="addModal"
            class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
            <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-4xl p-6 mx-4 transform transition-transform duration-300">
                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-800">Thêm Câu Hỏi Mới</h3>
                    <button id="closeAddModalBtn"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none text-3xl">&times;</button>
                </div>
                <form id="qaForm" class="space-y-4 mt-4">
                    <input type="hidden" id="tempAnswerId" name="tempAnswerId">
                    <div>
                        <label for="question" class="block text-sm font-semibold text-gray-700 mb-1">Câu hỏi</label>
                        <textarea id="question" name="question" rows="3" required
                            class="mt-1 block w-full rounded-xl border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3 transition-colors duration-200"></textarea>
                    </div>
                    <div>
                        <label for="answer" class="block text-sm font-semibold text-gray-700 mb-1">Câu trả
                            lời</label>
                        <textarea id="answer" name="answer" rows="5" required
                            class="mt-1 block w-full rounded-xl border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3 transition-colors duration-200"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" id="submitBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-200">Thêm</button>
                    </div>
                </form>
                <div id="statusMessage" class="mt-4 p-3 rounded-md text-sm text-center hidden"></div>
            </div>
        </div>

        <script src="{{ asset('plugin/tinymce/tinymce.js') }}" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#emailBody',
                plugins: 'advlist autolink lists charmap preview anchor fullscreen table',
                toolbar: 'undo redo | fullscreen | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | removeformat',
                menubar: true,
                branding: false,
                resize: 'both',
                setup: function(editor) {
                    editor.on('init', function() {
                        editor.getContainer().style.height = '400px';
                    });
                    editor.on('change input keyup', function() {
                        editor.save();
                    });
                },
                toolbar_mode: 'wrap',
                license_key: 'gpl',
                mobile: {
                    menubar: true,
                    toolbar_mode: 'floating',
                    plugins: 'advlist autolink lists charmap preview anchor fullscreen table',
                    toolbar: 'undo redo | fullscreen | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | removeformat'
                }
            });
        </script>

        <script defer>
            (() => {
                'use strict';
                if (window.__UsersPageInitialized) return; // tránh khai báo lại
                window.__UsersPageInitialized = true;

                // ==== Config ====
                const API_URL = '{{ url('/api/nguoi-dung') }}';
                const EXPORT_API_URL = '{{ url('/api/xuat-nguoi-dung') }}';
                const SEND_EMAIL_API_URL = '{{ url('/api/gui-email') }}';
                const CHAT_HISTORY_API_URL = '{{ url('/api/cuoc-hoi-thoai') }}';
                const QA_ADD_API = '{{ url('/api/add-qa') }}';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                // ==== State ====
                let rawUsers = [];
                let filteredUsers = [];
                let currentPage = 1;
                let pageSize = 10;
                let selectedEmails = new Set();
                let chatHiddenForAdd = false;
                let isSendingEmail = false;

                // ==== DOM ====
                const tbody = document.getElementById('usersTableBody');
                const pageInfoEl = document.getElementById('pageInfo');
                const prevBtn = document.getElementById('prevPageBtn');
                const nextBtn = document.getElementById('nextPageBtn');
                const firstBtn = document.getElementById('firstPageBtn');
                const lastBtn = document.getElementById('lastPageBtn');
                const pageSizeSelect = document.getElementById('pageSizeSelect');
                const reloadBtn = document.getElementById('reloadBtn');
                const searchInput = document.getElementById('searchInput');
                const startDateInput = document.getElementById('startDate');
                const endDateInput = document.getElementById('endDate');
                const birthYearInput = document.getElementById('birthYearInput');
                const exportExcelBtn = document.getElementById('exportExcelBtn');

                // Email modal
                const sendEmailBtn = document.getElementById('sendEmailBtn');
                const emailModal = document.getElementById('emailModal');
                const closeEmailModal = document.getElementById('closeEmailModal');
                const cancelEmailBtn = document.getElementById('cancelEmailBtn');
                const confirmSendEmailBtn = document.getElementById('confirmSendEmailBtn');
                const recipientsPreview = document.getElementById('recipientsPreview');
                const emailSubject = document.getElementById('emailSubject');
                const emailBody = document.getElementById('emailBody');

                // Chat modal
                const chatModal = document.getElementById('chatModal');
                const chatModalTitle = document.getElementById('chatModalTitle');
                const chatModalList = document.getElementById('chatModalList');
                const closeChatModalBtn = document.getElementById('closeChatModalBtn');

                // Select-all
                const selectAllHeader = document.getElementById('selectAllCheckbox');

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
                function getEditorHtml(id = 'emailBody') {
                    try {
                        const ed = window.tinymce?.get?.(id);
                        if (ed) return ed.getContent({
                            format: 'html'
                        }).trim();
                    } catch {}
                    return (document.getElementById(id)?.value || '').trim();
                }

                function getEditorText(id = 'emailBody') {
                    try {
                        const ed = window.tinymce?.get?.(id);
                        if (ed) return ed.getContent({
                            format: 'text'
                        }).trim();
                    } catch {}
                    return (document.getElementById(id)?.value || '').trim();
                }

                function setEditorHtml(html = '', id = 'emailBody') {
                    try {
                        const ed = window.tinymce?.get?.(id);
                        if (ed) {
                            ed.setContent(html);
                            return;
                        }
                    } catch {}
                    const el = document.getElementById(id);
                    if (el) el.value = html;
                }

                function showNotification(type = 'success', title = '', text = '') {
                    if (!notifyEl) return;
                    notifyEl.classList.remove('alert-success', 'alert-error');
                    if (type === 'success') {
                        notifyEl.classList.add('alert-success');
                        if (notifyIcon) notifyIcon.textContent = '✅';
                    } else {
                        notifyEl.classList.add('alert-error');
                        if (notifyIcon) notifyIcon.textContent = '⚠️';
                    }
                    if (notifyTitle) notifyTitle.textContent = title;
                    if (notifyText) notifyText.textContent = text;
                    notifyEl.style.opacity = '1';
                    notifyEl.style.transform = 'translateY(-50%) scale(1)';
                    notifyEl.style.pointerEvents = 'auto';
                    setTimeout(() => {
                        notifyEl.style.opacity = '0';
                        notifyEl.style.transform = 'translateY(-50%) scale(0.95)';
                        notifyEl.style.pointerEvents = 'none';
                    }, 3500);
                }
                notifyClose?.addEventListener('click', () => {
                    notifyEl.style.opacity = '0';
                    notifyEl.style.transform = 'translateY(-50%) scale(0.95)';
                    notifyEl.style.pointerEvents = 'none';
                });

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

                function escapeHtml(s = '') {
                    return String(s).replace(/[&<>"']/g, m => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    } [m]));
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

                // ==== Select-All helpers ====
                function getFilteredEmailSet() {
                    return new Set(filteredUsers.map(u => u?.email).filter(Boolean));
                }

                function getSelectedRecipientsInFilter() {
                    const fset = getFilteredEmailSet();
                    return Array.from(selectedEmails).filter(e => fset.has(e));
                }

                function isAllCheckedOnPage() {
                    const boxes = tbody?.querySelectorAll('.rowCheck') ?? [];
                    if (boxes.length === 0) return false;
                    for (const b of boxes)
                        if (!b.checked) return false;
                    return true;
                }

                function syncSelectAllCheckbox() {
                    if (!selectAllHeader || !tbody) return;
                    const boxes = tbody.querySelectorAll('.rowCheck');
                    const anyChecked = Array.from(boxes).some(b => b.checked);
                    selectAllHeader.checked = isAllCheckedOnPage();
                    selectAllHeader.indeterminate = !selectAllHeader.checked && anyChecked;
                }

                // ==== Render table (6 cột) ====
                function renderTable() {
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    const total = filteredUsers.length;
                    const isAll = pageSize === 'all';
                    const totalPages = isAll ? 1 : Math.max(1, Math.ceil(total / pageSize));
                    currentPage = Math.min(currentPage, totalPages);
                    const start = isAll ? 0 : (currentPage - 1) * pageSize;
                    const end = isAll ? total : start + pageSize;
                    const pageItems = filteredUsers.slice(start, end);

                    if (pageItems.length === 0) {
                        tbody.innerHTML =
                            `<tr><td colspan="6" class="px-6 py-6 text-center text-gray-500">Không có dữ liệu.</td></tr>`;
                    } else {
                        for (const u of pageItems) {
                            const email = u.email ?? '';
                            const checked = selectedEmails.has(email) ? 'checked' : '';
                            const chatId = u.id_cuoc_hoi_thoai ?? '';
                            const disabled = chatId ? '' : 'disabled';

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                  <input type="checkbox" class="rowCheck w-4 h-4" data-email="${escapeHtml(email)}" ${checked}>
                </td>
                <td class="px-4 sm:px-6 py-3 whitespace-normal break-words text-sm text-gray-700">
                  <div class="font-medium text-gray-900">${escapeHtml(u.ten_nguoi_dung ?? '')}</div>
                  <div class="text-xs text-gray-500 mt-0.5">Tạo: ${formatDate(u.created_at)}</div>
                </td>
                <td class="px-4 sm:px-6 py-3 whitespace-pre-line text-sm text-gray-700">
                  <div class="font-medium text-gray-900 truncate">${escapeHtml(u.dia_chi ?? '')}</div>
                  <div class="font-medium text-gray-900 truncate">${escapeHtml(u.email ?? '')}</div>
                  <div class="text-xs text-gray-500 mt-0.5">SĐT: ${escapeHtml(u.so_dien_thoai ?? '')}</div>
                </td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm text-gray-700">${escapeHtml(u.nam_sinh ?? '')}</td>
                <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-right text-sm">
                  <button class="btn-view-chat text-indigo-600 hover:text-indigo-900" data-chatid="${escapeHtml(chatId)}" ${disabled}
                          title="${chatId ? 'Xem lịch sử chat' : 'Không có cuộc hội thoại'}">
                    <i class="fas fa-eye"></i> <span>Xem</span>
                  </button>
                </td>`;
                            tbody.appendChild(tr);
                        }

                        tbody.querySelectorAll('.rowCheck').forEach(chk => {
                            chk.addEventListener('change', (e) => {
                                const mail = e.target.getAttribute('data-email') || '';
                                if (!mail) return;
                                if (e.target.checked) selectedEmails.add(mail);
                                else selectedEmails.delete(mail);
                                syncSelectAllCheckbox();
                            });
                        });
                    }

                    if (pageInfoEl) {
                        pageInfoEl.textContent = isAll ? `Trang 1/1 (${total} bản ghi) — đang xem Tất cả` :
                            `Trang ${currentPage}/${totalPages} (${total} bản ghi)`;
                    }
                    const disableNav = isAll;
                    if (prevBtn) prevBtn.disabled = disableNav || currentPage <= 1;
                    if (firstBtn) firstBtn.disabled = disableNav || currentPage <= 1;
                    if (nextBtn) nextBtn.disabled = disableNav || currentPage >= totalPages;
                    if (lastBtn) lastBtn.disabled = disableNav || currentPage >= totalPages;

                    syncSelectAllCheckbox();
                }

                // ==== Filtering ====
                function parseISODateOnly(s) {
                    if (!s) return null;
                    try {
                        const d = new Date(s);
                        if (Number.isNaN(+d)) return null;
                        return new Date(d.getFullYear(), d.getMonth(), d.getDate());
                    } catch {
                        return null;
                    }
                }

                function applyFilter() {
                    const q = (searchInput?.value || '').trim().toLowerCase();
                    const birthYear = (birthYearInput?.value || '').trim();
                    const sd = parseISODateOnly(startDateInput?.value);
                    const ed = parseISODateOnly(endDateInput?.value);
                    if (ed) ed.setHours(23, 59, 59, 999);

                    filteredUsers = rawUsers.filter(u => {
                        let ok = true;
                        if (q) {
                            const haystack = [u.ten_nguoi_dung, u.email, u.so_dien_thoai, u.dia_chi, u.nam_sinh]
                                .map(x => (x ?? '').toString().toLowerCase()).join(' | ');
                            ok = ok && haystack.includes(q);
                        }
                        if (birthYear) ok = ok && String(u?.nam_sinh ?? '') === birthYear;
                        if (sd || ed) {
                            const crt = u?.created_at ? new Date(u.created_at) : null;
                            ok = ok && !!crt;
                            if (ok && sd) ok = ok && (crt >= sd);
                            if (ok && ed) ok = ok && (crt <= ed);
                        }
                        return ok;
                    });

                    // Giữ lại các email đang chọn nhưng chỉ trong phạm vi bộ lọc hiện tại
                    const fset = getFilteredEmailSet();
                    selectedEmails = new Set([...selectedEmails].filter(e => fset.has(e)));

                    currentPage = 1;
                    renderTable();
                }

                // ==== Fetch data ====
                async function fetchUsers() {
                    try {
                        if (tbody) {
                            tbody.innerHTML =
                                `<tr><td colspan=\"6\" class=\"px-6 py-6 text-center text-gray-500\">Đang tải dữ liệu...</td></tr>`;
                        }
                        const res = await fetch(API_URL, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();
                        rawUsers = Array.isArray(data) ? data : (data.data ?? []);
                        filteredUsers = [...rawUsers];
                        selectedEmails = new Set();
                        renderTable();
                        showNotification('success', 'Tải dữ liệu thành công',
                            `Đã nạp ${rawUsers.length} bản ghi người dùng.`);
                    } catch (err) {
                        if (tbody) {
                            tbody.innerHTML =
                                `<tr><td colspan=\"6\" class=\"px-6 py-6 text-center text-red-600\">Lỗi tải dữ liệu: ${escapeHtml(err.message)}</td></tr>`;
                        }
                        showNotification('error', 'Không thể tải dữ liệu', 'Vui lòng kiểm tra API hoặc kết nối mạng.');
                    }
                }

                // ==== Export Excel ====
                async function exportToExcel() {
                    const startDate = startDateInput?.value;
                    const endDate = endDateInput?.value;
                    if (!startDate || !endDate) return showNotification('error', 'Lỗi!',
                        'Vui lòng chọn cả ngày bắt đầu và ngày kết thúc.');
                    if (new Date(startDate) > new Date(endDate)) return showNotification('error', 'Lỗi!',
                        'Ngày bắt đầu không được lớn hơn ngày kết thúc.');
                    try {
                        if (exportExcelBtn) {
                            exportExcelBtn.disabled = true;
                            exportExcelBtn.innerHTML = `<i class=\"fas fa-spinner fa-spin mr-2\"></i> Chờ...`;
                        }
                        const res = await fetch(
                            `${EXPORT_API_URL}?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`, {
                                method: 'GET'
                            });
                        if (!res.ok) {
                            const err = await res.json().catch(() => ({
                                error: `HTTP ${res.status}`
                            }));
                            throw new Error(err.error || `HTTP ${res.status}`);
                        }
                        const blob = await res.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `Danh_sach_nguoi_dung_${startDate}_den_${endDate}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                        showNotification('success', 'Thành công!', 'Đã xuất file Excel thành công.');
                    } catch (err) {
                        showNotification('error', 'Lỗi xuất file', `Đã xảy ra lỗi: ${escapeHtml(err.message)}`);
                    } finally {
                        if (exportExcelBtn) {
                            exportExcelBtn.disabled = false;
                            exportExcelBtn.innerHTML = `<i class=\"fas fa-file-excel\"></i> <span>Excel</span>`;
                        }
                    }
                }

                // ==== Email Modal ====
                function openEmailModal() {
                    const recipients = getSelectedRecipientsInFilter();
                    if (!recipients.length) return showNotification('error', 'Chưa chọn nhân sự',
                        'Hãy tick checkbox ở những người cần gửi email.');
                    if (!emailModal) return showNotification('error', 'Thiếu modal', 'Bạn chưa thêm HTML modal gửi email.');
                    if (recipientsPreview) {
                        recipientsPreview.innerHTML = recipients.map(e =>
                            `<span class=\"inline-block bg-white border border-slate-300 rounded px-2 py-0.5 mr-1 mb-1\">${escapeHtml(e)}</span>`
                        ).join('');
                    }
                    if (emailSubject) emailSubject.value = '';
                    setEditorHtml('', 'emailBody');
                    emailModal.classList.remove('hidden');
                }

                function closeEmailModalFn() {
                    emailModal?.classList.add('hidden');
                }

                function setEmailModalLoading(isLoading) {
                    if (!emailModal) return;
                    isSendingEmail = !!isLoading;
                    [confirmSendEmailBtn, cancelEmailBtn, closeEmailModal, emailSubject, emailBody].forEach(el => {
                        if (!el) return;
                        if (isLoading) el.setAttribute('disabled', 'true');
                        else el.removeAttribute('disabled');
                    });
                    if (confirmSendEmailBtn) {
                        if (isLoading) {
                            if (!confirmSendEmailBtn.dataset.originalHtml) confirmSendEmailBtn.dataset.originalHtml =
                                confirmSendEmailBtn.innerHTML;
                            confirmSendEmailBtn.innerHTML = `<i class=\"fas fa-spinner fa-spin\"></i> Đang gửi...`;
                        } else {
                            confirmSendEmailBtn.innerHTML = confirmSendEmailBtn.dataset.originalHtml || 'Gửi';
                            delete confirmSendEmailBtn.dataset.originalHtml;
                        }
                    }
                    const card = emailModal.querySelector('div.relative');
                    let mask = emailModal.querySelector('#emailSendingMask');
                    if (isLoading) {
                        if (!mask && card) {
                            mask = document.createElement('div');
                            mask.id = 'emailSendingMask';
                            mask.className = 'absolute inset-0 bg-white/60 rounded-2xl flex items-center justify-center';
                            mask.innerHTML =
                                `<div class=\"flex items-center gap-2 text-slate-700 text-sm\"><i class=\"fas fa-spinner fa-spin\"></i> Đang gửi email...</div>`;
                            card.appendChild(mask);
                        }
                    } else if (mask) {
                        mask.remove();
                    }
                }
                async function confirmSendEmail() {
                    if (isSendingEmail) return;
                    const emails = getSelectedRecipientsInFilter();
                    const subject = (emailSubject?.value || '').trim();
                    const bodyHtml = getEditorHtml('emailBody');
                    const bodyText = getEditorText('emailBody');
                    if (!emails.length) return showNotification('error', 'Chưa có người nhận',
                        'Hãy chọn ít nhất một nhân sự.');
                    if (!subject) return showNotification('error', 'Thiếu Subject', 'Vui lòng nhập tiêu đề email.');
                    if (!bodyText) return showNotification('error', 'Thiếu nội dung', 'Vui lòng nhập nội dung email.');
                    setEmailModalLoading(true);
                    try {
                        const res = await fetch(SEND_EMAIL_API_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content') || ''
                            },
                            body: JSON.stringify({
                                emails,
                                subject,
                                body: bodyHtml
                            })
                        });
                        const payload = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const msg = payload?.error || `HTTP ${res.status}`;
                            throw new Error(msg);
                        }
                        const sentTo = payload?.sent_to ?? emails.length;
                        const chunks = payload?.chunks;
                        const note = payload?.delivery === 'fallback_log' ? ' (SMTP lỗi – đã ghi log mail).' : '';
                        showNotification('success', 'Đã gửi email',
                            `Đã gửi tới ${sentTo} người nhận${chunks ? ` (${chunks} lô)` : ''}.${note}`);
                        setEditorHtml('', 'emailBody');
                        if (emailSubject) emailSubject.value = '';
                        closeEmailModalFn();
                    } catch (e) {
                        showNotification('error', 'Gửi email thất bại', e.message || 'Có lỗi xảy ra.');
                    } finally {
                        setEmailModalLoading(false);
                    }
                }

                // ==== AddModal (giữ id) ====
                const addModal = document.getElementById('addModal');
                const closeAddModalBtn = document.getElementById('closeAddModalBtn');
                const qaForm = document.getElementById('qaForm');
                const questionInput = document.getElementById('question');
                const answerInput = document.getElementById('answer');
                const openEmptyAddModalBtn = document.getElementById('openEmptyAddModalBtn');

                function openAddModalWithQA(q = '', a = '', opts = {
                    fromChat: false
                }) {
                    if (questionInput) questionInput.value = q;
                    if (answerInput) answerInput.value = a;
                    if (opts?.fromChat && chatModal && !chatModal.classList.contains('hidden')) {
                        chatModal.classList.add('hidden');
                        chatHiddenForAdd = true;
                    }
                    addModal.classList.add('show');
                    setTimeout(() => questionInput?.focus(), 0);
                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeAddModal() {
                    addModal.classList.remove('show');
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                    if (chatHiddenForAdd && chatModal) {
                        chatModal.classList.remove('hidden');
                    }
                    chatHiddenForAdd = false;
                }
                qaForm?.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const q = (questionInput?.value || '').trim();
                    const a = (answerInput?.value || '').trim();
                    if (!q || !a) {
                        alert('Vui lòng nhập đầy đủ Câu hỏi và Câu trả lời.');
                        return;
                    }
                    try {
                        await addQA({
                            question: q,
                            answer: a,
                            tempId: null
                        });
                        alert('Đã thêm Q&A thành công!');
                        qaForm.reset();
                        closeAddModal();
                    } catch (err) {
                        alert('Lỗi thêm Q&A: ' + (err.message || 'Có lỗi xảy ra'));
                    }
                });
                async function addQA({
                    question,
                    answer,
                    tempId
                }) {
                    const res = await fetch(QA_ADD_API, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            question,
                            answer,
                            tempAnswerId: tempId || null
                        })
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json();
                }
                closeAddModalBtn?.addEventListener('click', closeAddModal);
                addModal?.addEventListener('click', (e) => {
                    if (e.target === addModal) closeAddModal();
                });
                window.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && addModal?.classList.contains('show')) closeAddModal();
                });
                openEmptyAddModalBtn?.addEventListener('click', () => openAddModalWithQA('', ''));

                // ==== Chat History Modal ====
                async function openChatHistoryModal(id) {
                    if (!chatModal || !chatModalList) return showNotification('error', 'Thiếu modal',
                        'Bạn chưa thêm HTML modal lịch sử chat.');
                    if (chatModalTitle) chatModalTitle.textContent = `Lịch sử chat #${id}`;
                    chatModalList.innerHTML =
                        `<div class=\"text-sm text-gray-500 px-2 py-3\">Đang tải lịch sử chat...</div>`;
                    chatModal.classList.remove('hidden');
                    try {
                        const url = `${CHAT_HISTORY_API_URL}?id_cuoc_hoi_thoai=${encodeURIComponent(id)}`;
                        let res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (res.status === 405) {
                            res = await fetch(CHAT_HISTORY_API_URL, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    id_cuoc_hoi_thoai: id
                                })
                            });
                        }
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const payload = await res.json();
                        const items = Array.isArray(payload) ? payload : (payload.data ?? payload.items ?? []);
                        if (!items || !items.length) {
                            chatModalList.innerHTML =
                                `<div class=\"text-sm text-gray-500 px-2 py-3\">Không có dữ liệu.</div>`;
                            return;
                        }
                        const html = items.map((it, idx) => {
                            const q = it.cau_hoi ?? it.question ?? it.hoi ?? it.q ?? it.noi_dung_hoi ?? '';
                            const a = it.cau_tra_loi ?? it.answer ?? it.tra_loi ?? it.a ?? it
                                .noi_dung_tra_loi ?? '';
                            const time = it.created_at ?
                                `<div class=\"text-[11px] text-slate-400 mt-1\">${formatDate(it.created_at)}</div>` :
                                '';
                            const dq = encodeURIComponent(q);
                            const da = encodeURIComponent(a);
                            return `
                            <div class=\"rounded-xl border border-slate-200 overflow-hidden\">
                            <div class=\"bg-slate-50 px-4 py-3\">
                                <div class=\"flex items-start justify-between gap-3\">
                                <div>
                                    <div class=\"text-xs font-semibold text-slate-600\">Câu hỏi #${idx + 1}</div>
                                    <div class=\"text-sm text-slate-800 whitespace-pre-wrap\">${escapeHtml(q)}</div>
                                    ${time}
                                </div>
                                <button class=\"btn-add-to-form text-indigo-600 hover:text-indigo-900 mr-2\" title=\"Thêm cặp Hỏi/Đáp này vào form\" data-q=\"${dq}\" data-a=\"${da}\">
                                    <i class=\"fa-solid fa-plus\"></i> Thêm
                                </button>
                                </div>
                            </div>
                            <div class=\"bg-emerald-50 px-4 py-3 border-t border-emerald-100\">
                                <div class=\"text-xs font-semibold text-emerald-600\">Trả lời</div>
                                <div class=\"text-sm text-slate-800 whitespace-pre-wrap\">${escapeHtml(a)}</div>
                            </div>
                            </div>`;
                        }).join('');
                        chatModalList.innerHTML = html;
                    } catch (err) {
                        chatModalList.innerHTML =
                            `<div class=\"text-sm text-red-600 px-2 py-3\">Lỗi tải lịch sử: ${escapeHtml(err.message || 'Không xác định')}</div>`;
                    }
                }

                function closeChatHistoryModal() {
                    chatModal?.classList.add('hidden');
                }

                // ==== Events: phân trang ====
                prevBtn?.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        renderTable();
                    }
                });
                nextBtn?.addEventListener('click', () => {
                    currentPage++;
                    renderTable();
                });
                firstBtn?.addEventListener('click', () => {
                    currentPage = 1;
                    renderTable();
                });
                lastBtn?.addEventListener('click', () => {
                    const isAll = pageSize === 'all';
                    const totalPages = isAll ? 1 : Math.max(1, Math.ceil(filteredUsers.length / pageSize));
                    currentPage = totalPages;
                    renderTable();
                });
                pageSizeSelect?.addEventListener('change', () => {
                    const v = pageSizeSelect.value;
                    pageSize = (v === 'all') ? 'all' : (parseInt(v, 10) || 10);
                    currentPage = 1;
                    renderTable();
                });
                reloadBtn?.addEventListener('click', fetchUsers);

                // ==== Events: lọc ====
                function debounceInput(el, fn, delay = 200) {
                    if (!el) return;
                    el.addEventListener('input', () => {
                        clearTimeout(el._t);
                        el._t = setTimeout(fn, delay);
                    });
                }
                debounceInput(searchInput, applyFilter, 200);
                debounceInput(startDateInput, applyFilter, 100);
                debounceInput(endDateInput, applyFilter, 100);
                birthYearInput?.addEventListener('input', () => {
                    let v = birthYearInput.value.replace(/\D/g, '').slice(0, 4);
                    birthYearInput.value = v;
                    clearTimeout(birthYearInput._t);
                    birthYearInput._t = setTimeout(applyFilter, 200);
                });

                // ==== Export ====
                exportExcelBtn?.addEventListener('click', exportToExcel);

                // ==== Select-All (trang hiện tại) ====
                selectAllHeader?.addEventListener('change', () => {
                    const wantCheck = selectAllHeader.checked;
                    tbody?.querySelectorAll('.rowCheck').forEach(b => {
                        const mail = b.getAttribute('data-email') || '';
                        if (!mail) return;
                        b.checked = wantCheck;
                        if (wantCheck) selectedEmails.add(mail);
                        else selectedEmails.delete(mail);
                    });
                    syncSelectAllCheckbox();
                });

                // ==== Email modal buttons ====
                sendEmailBtn?.addEventListener('click', openEmailModal);
                closeEmailModal?.addEventListener('click', closeEmailModalFn);
                cancelEmailBtn?.addEventListener('click', closeEmailModalFn);
                confirmSendEmailBtn?.addEventListener('click', confirmSendEmail);

                // ==== Chat modal buttons ====
                closeChatModalBtn?.addEventListener('click', closeChatHistoryModal);
                tbody?.addEventListener('click', (e) => {
                    const btn = e.target.closest?.('.btn-view-chat');
                    if (!btn) return;
                    const chatId = btn.getAttribute('data-chatid');
                    if (chatId) openChatHistoryModal(chatId);
                });
                chatModalList?.addEventListener('click', (e) => {
                    const btn = e.target.closest?.('.btn-add-to-form');
                    if (!btn) return;
                    const q = decodeURIComponent(btn.dataset.q || '');
                    const a = decodeURIComponent(btn.dataset.a || '');
                    openAddModalWithQA(q, a, {
                        fromChat: true
                    });
                });

                // ==== Mobile filter toggle (giống trang tài khoản) ====
                const mq = window.matchMedia?.('(max-width: 767.98px)');
                let isMobileMode = mq ? mq.matches : false;
                let panelOpen = false; // mobile mặc định đóng
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
                    if (!mq) return;
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
                mq?.addEventListener?.('change', applyModeFromMQ);
                applyModeFromMQ();

                // ==== Init ====
                fetchUsers();
            })();
        </script>
    @endif
</body>

</html>
