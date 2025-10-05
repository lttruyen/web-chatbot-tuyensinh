<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Câu Hỏi & Câu Trả Lời</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Thêm meta tag cho CSRF token để Laravel hoạt động đúng -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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

        /* Modal: ẩn/hiện mượt + khóa click khi ẩn */
        .modal {
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            /* ngăn click khi đang ẩn */
            overflow-y: auto;
            /* fallback nếu panel cao hơn màn hình rất nhỏ */
        }

        .modal.show {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
            /* cho phép tương tác khi hiện */
        }

        /* Nội dung panel trắng bên trong modal: giới hạn chiều cao + cuộn */
        .modal>div,
        .modal .modal-content {
            max-height: min(90vh, 100dvh - 32px);
            /* chừa viền trên/dưới để thoáng */
            overflow-y: auto;
            /* cuộn dọc nội dung */
            -webkit-overflow-scrolling: touch;
            /* mượt trên iOS */
            overscroll-behavior: contain;
            /* tránh cuộn “văng” ra nền */
        }

        /* Wrapper riêng cho bảng trong modal tạm thời để cuộn bảng */
        .table-wrapper {
            max-height: min(65vh, 100dvh - 180px);
            /* còn chỗ cho tiêu đề + nút */
            overflow-y: auto;
            overflow-x: auto;
            /* cuộn ngang nếu bảng rộng */
        }

        /* Thêm hiệu ứng cho thông báo (toast) */
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

        .alert-left-icon {
            font-size: 36px;
            line-height: 1;
            display: flex;
            align-items: center;
        }

        /* Tôn trọng người dùng “giảm chuyển động” */
        @media (prefers-reduced-motion: reduce) {
            .modal {
                transition: none;
            }
        }

        /* Giấu scrollbar cho thanh menu ngang trên mobile nếu cần */
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
        <!-- ===== MENU TOP CỐ ĐỊNH (Responsive) ===== -->
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

            <!-- Menu chức năng của trang (hàng tiêu đề + công cụ) -->
            <div class="bg-white/90 backdrop-blur border-b border-slate-200">
                <div class="mx-auto w-full max-w-6xl px-3 sm:px-4 py-3">
                    <div class="flex flex-col gap-3">
                        <!-- Hàng 1: Tiêu đề + nút chức năng + page size + reload -->
                        <div class="flex items-center justify-between gap-3">
                            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                <span
                                    class="bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-indigo-700 to-blue-600">
                                    Câu hỏi / trả lời
                                </span>
                            </h1>
                            <!-- WRAPPER bên phải -->
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

                        <!-- Hàng 2: Search + Nút mở các panel -->
                        <div class="md:hidden pt-1 flex justify-end">
                            <button id="toolsToggleBtn"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-300 text-slate-700 bg-white shadow-sm active:scale-[0.99] transition"
                                aria-expanded="false" aria-controls="toolsPanel">
                                <i class="fas fa-sliders-h"></i>
                                <span>Công cụ</span>
                                <i id="toolsChevron" class="fas fa-chevron-down text-xs transition-transform"></i>
                            </button>
                        </div>

                        <!-- Panel công cụ: mobile gập/mở, desktop luôn mở -->
                        <div id="toolsPanel"
                            class="overflow-hidden transition-all duration-300 ease-in-out md:overflow-visible md:transition-none md:max-h-none hidden md:block">
                            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto] gap-3 mt-3">
                                <div class="relative max-w-none md:max-w-xl">
                                    <input id="searchInput" type="text"
                                        placeholder="Tìm câu hỏi hoặc câu trả lời..."
                                        class="w-full border border-gray-300 rounded-lg py-2.5 pl-3 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">⌕</span>
                                </div>
                                <button id="openTempAnswersModalBtn"
                                    class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition">
                                    Câu hỏi tạm
                                </button>
                                <button id="openAddModalBtn"
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

        <!-- Nội dung: chừa khoảng trống cho header -->
        <main class="mx-auto w-full max-w-6xl px-3 sm:px-4 pt-48 sm:pt-56">
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-8 w-full" style="min-height: 90vh;">
                <!-- Bảng Hiển thị Câu Hỏi -->
                <div>
                    <!-- FULL-WIDTH on mobile (edge-to-edge) -->
                    <div class="relative -mx-3 sm:mx-0">
                        <div class="overflow-x-auto md:rounded-md shadow">
                            <table class="w-full min-w-full table-fixed divide-y divide-gray-200">
                                <!--<colgroup>-->
                                <!--    <col class="w-[40%] md:w-[40%]">-->
                                <!--    <col class="w-[45%] md:w-[45%]">-->
                                <!--    <col class="w-[15%] md:w-[15%]">-->
                                <!--</colgroup>-->

                                <thead class="bg-gray-50 text-sm">
                                    <tr>
                                        <th scope="col"
                                            class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                            Câu hỏi</th>
                                        <th scope="col"
                                            class="px-4 sm:px-6 py-3 text-left font-medium text-gray-600 uppercase tracking-wider">
                                            Câu trả lời</th>
                                        <th scope="col"
                                            class="px-4 sm:px-6 py-3 text-right font-medium text-gray-600 uppercase tracking-wider">
                                            Tác vụ
                                        </th>

                                    </tr>
                                </thead>
                                <tbody id="qaTableBody" class="bg-white divide-y divide-gray-200 text-sm">
                                    <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Phân trang chính -->
                    <div id="mainPagination" class="mt-4 flex flex-wrap gap-3 justify-between items-center hidden">
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

                    <!-- Notification -->
                    <div id="unifiedNotification"
                        class="alert fixed top-1/2 left-5 z-50 px-4 py-4 w-[300px] bg-white rounded-lg shadow-lg flex items-start space-x-2 transition-all duration-300 transform -translate-y-1/2 scale-95 opacity-0">
                        <div id="notificationIcon" class="alert-left-icon">
                            <i class="fas fa-check blue"></i>
                        </div>
                        <div class="flex-1">
                            <p id="notificationTitle" class="text-sm font-bold text-gray-900"></p>
                            <p id="notificationText" class="mt-1 text-sm text-gray-600"></p>
                        </div>
                        <button id="closeNotificationBtn"
                            class="absolute top-2 right-2 text-gray-400 hover:text-gray-500 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

            </div>
        </main>

        <!-- Modal để thêm câu hỏi -->
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
                        <textarea id="answer" name="answer" rows="5"
                            class="mt-1 block w-full rounded-xl border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3 transition-colors duration-200"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" id="submitBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-200">
                            Thêm
                        </button>
                    </div>
                </form>
                <div id="statusMessage" class="mt-4 p-3 rounded-md text-sm text-center hidden"></div>
            </div>
        </div>

        <!-- Modal để sửa câu hỏi -->
        <div id="editModal"
            class="modal modal-edit fixed inset-0 z-50 flex items-center justify-center transition-opacity duration-300">
            <div class="modal-overlay absolute inset-0 bg-gray-900 opacity-50"></div>
            <div
                class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-4xl p-8 mx-4 transform transition-transform duration-300">
                <div class="flex justify-between items-center pb-4 border-b border-gray-200 mb-6">
                    <h4 class="text-xl font-bold text-gray-800">Sửa Câu Hỏi & Câu Trả Lời</h4>
                    <button id="closeEditModalBtn"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none text-3xl font-light leading-none transition-colors">&times;</button>
                </div>
                <form id="editForm" class="space-y-6">
                    <input type="hidden" id="editQuestionId" name="editQuestionId">
                    <input type="hidden" id="editAnswerId" name="editAnswerId">
                    <div>
                        <label for="editQuestion" class="block text-sm font-semibold text-gray-700 mb-1">Câu
                            hỏi</label>
                        <textarea id="editQuestion" name="editQuestion" rows="3" required
                            class="mt-1 block w-full rounded-xl border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3 transition-colors duration-200"></textarea>
                    </div>
                    <div>
                        <label for="editAnswer" class="block text-sm font-semibold text-gray-700 mb-1">Câu trả
                            lời</label>
                        <textarea id="editAnswer" name="editAnswer" rows="5"
                            class="mt-1 block w-full rounded-xl border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-3 transition-colors duration-200"></textarea>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button type="submit" id="updateBtn"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-xl shadow-md transition duration-200 transform hover:scale-105">
                            Cập nhật
                        </button>
                    </div>
                </form>
                <div id="editStatusMessage" class="mt-4 p-3 rounded-xl text-sm text-center hidden"></div>
            </div>
        </div>

        <!-- Modal để xác nhận thêm câu hỏi và xem câu trả lời tạm thời -->
        <div id="reviewModal"
            class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
            <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 mx-4 transform transition-transform duration-300">
                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <h3 class="text-2xl font-semibold text-gray-800">Xác nhận Thêm Câu Hỏi</h3>
                    <button id="closeReviewModalBtn"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none text-2xl">&times;</button>
                </div>
                <div class="py-4 space-y-4">
                    <div>
                        <p class="text-gray-600 font-semibold mb-1">Câu hỏi của bạn:</p>
                        <p id="modalQuestion" class="bg-gray-100 p-3 rounded-md"></p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold mb-1">Câu trả lời bạn muốn lưu:</p>
                        <p id="modalAnswer" class="bg-gray-100 p-3 rounded-md"></p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-semibold mb-1">Câu trả lời tạm thời từ API:</p>
                        <p id="tempAnswer" class="bg-blue-50 text-blue-800 p-3 rounded-md italic">
                            <span class="text-red-500">Lưu ý:</span> Không thể lấy câu trả lời tạm thời từ API vì
                            endpoint
                            được cung cấp trả về danh sách chứ không phải một câu trả lời cụ thể cho câu hỏi này.
                        </p>
                        <p class="text-sm text-gray-500 mt-2">Xác nhận để thêm câu hỏi và câu trả lời vào database.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button id="cancelBtn"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md transition duration-200">Hủy</button>
                    <button id="confirmAddBtn"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-200">Xác
                        nhận thêm</button>
                </div>
            </div>
        </div>

        <!-- Modal để xem danh sách câu trả lời tạm thời -->
        <div id="tempAnswersModal"
            class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
            <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-4xl p-6 mx-4 transform transition-transform duration-300">
                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <h3 class="text-2xl font-semibold text-gray-800">Danh Sách Câu Trả Lời Tạm Thời</h3>
                    <button id="closeTempAnswersModalBtn"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none text-2xl">&times;</button>
                </div>
                <div class="py-4 space-y-4">
                    <div class="table-wrapper overflow-x-auto rounded-md shadow">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider max-w-xs break-words">
                                        Câu hỏi</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Câu trả lời tạm thời</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">
                                        Tác vụ</th>
                                </tr>
                            </thead>
                            <tbody id="tempAnswersTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Dữ liệu sẽ được thêm vào đây bằng JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Phân trang tạm thời -->
                    <div id="tempPagination" class="mt-4 flex justify-between items-center hidden">
                        <button id="prevTempPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded-md transition duration-200"
                            disabled>
                            Trước
                        </button>
                        <span id="tempPageInfo" class="text-sm text-gray-600">Trang 1/1</span>
                        <button id="nextTempPageBtn"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded-md transition duration-200"
                            disabled>
                            Sau
                        </button>
                    </div>
                </div>
                <div id="tempStatusMessage" class="mt-4 p-3 rounded-md text-sm text-center hidden"></div>
            </div>
        </div>

        <!-- Modal xác nhận xóa câu trả lời tạm thời -->
        <div id="deleteConfirmModal"
            class="modal fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50">
            <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 mx-4 transform transition-transform duration-300">
                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <h3 class="text-2xl font-semibold text-gray-800">Xác Nhận Xóa</h3>
                    <button id="closeDeleteConfirmModalBtn"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none text-2xl">&times;</button>
                </div>
                <div class="py-4">
                    <p id="deleteMessage" class="text-gray-700">Bạn có chắc chắn muốn xóa mục này không?</p>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button id="cancelDeleteBtn"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md transition duration-200">Hủy</button>
                    <button id="confirmDeleteBtn"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">Xác
                        nhận xóa</button>
                </div>
            </div>
        </div>
        <script src="{{ asset('plugin/tinymce/tinymce.js') }}" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#answer,#editAnswer',
                plugins: 'advlist autolink lists link charmap preview anchor fullscreen table code',
                toolbar: 'undo redo | fullscreen | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table | removeformat | code',
                menubar: true,
                branding: false,
                resize: 'both',
                content_style: 'body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;line-height:1.6} table{border-collapse:collapse;width:100%} th,td{border:1px solid #e5e7eb;padding:6px 8px}',
                setup(editor) {
                    editor.on('init', () => {
                        editor.getContainer().style.height = '420px';
                    });
                    editor.on('change input keyup', () => editor.save()); // sync về <textarea>
                },
                toolbar_mode: 'wrap',
                license_key: 'gpl',
                mobile: {
                    menubar: true,
                    toolbar_mode: 'floating',
                    plugins: 'advlist autolink lists link charmap preview anchor fullscreen table code',
                    toolbar: 'undo redo | fullscreen | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table | removeformat | code'
                }
            });
        </script>
        <!-- JavaScript để xử lý form và hiển thị dữ liệu -->
        <script type="module">
            (() => {
                'use strict';
                if (window.__QA_PageInitialized) return;
                window.__QA_PageInitialized = true;

                /* ======================= API CONFIG ======================= */
                const DS_TAM_LIST_API = '{{ url('/api/cau-tra-loi-tam') }}';
                const DS_TAM_XOA_API = '{{ url('/api/cau-tra-loi-tam') }}'; // + /{id} (DELETE)
                const QA_LIST_API = '{{ url('/api/export-qa') }}';
                const QA_ADD_API = '{{ url('/api/add-qa') }}';
                const QA_UPDATE_API = '{{ url('/api/update-qa') }}';
                const QA_XOA_API = '{{ url('/api/xoa-qa') }}';

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                /* ======================= HELPERS ======================= */
                const $ = (id) => document.getElementById(id);
                const qs = (sel, root = document) => root.querySelector(sel);

                function escapeHtml(s = '') {
                    return String(s).replace(/[&<>"']/g, (m) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    } [m]));
                }

                function isMobile() {
                    return window.matchMedia('(max-width: 767.98px)').matches;
                }

                // Sanitize HTML bằng DOMPurify (nếu có). Fallback: strip nguy hiểm cơ bản.
                function purifyHtmlClient(html, {
                    forTable = true
                } = {}) {
                    const input = String(html || '');
                    if (window.DOMPurify) {
                        const cfg = {
                            ALLOWED_TAGS: [
                                'p', 'div', 'span', 'ul', 'ol', 'li', 'br', 'hr',
                                'strong', 'em', 'b', 'i', 'u', 'code', 'pre', 'blockquote',
                                'table', 'thead', 'tbody', 'tr', 'td', 'th',
                                'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a'
                            ],
                            ALLOWED_ATTR: ['href', 'title', 'target', 'rel', 'style', 'colspan', 'rowspan'],
                            ALLOW_DATA_ATTR: false,
                            FORBID_TAGS: forTable ? ['img', 'iframe', 'video', 'audio', 'style', 'script'] : ['style',
                                'script'
                            ],
                            FORBID_ATTR: ['onerror', 'onclick', 'onload'],
                        };
                        let clean = DOMPurify.sanitize(input, cfg);
                        // Ép a target=_blank + rel an toàn
                        clean = clean.replace(/<a\b([^>]*)>/gi, (m, attrs) => {
                            if (!/target=/i.test(attrs)) attrs += ' target="_blank"';
                            if (!/rel=/i.test(attrs)) attrs += ' rel="nofollow noopener noreferrer"';
                            return '<a' + attrs + '>';
                        });
                        return clean;
                    }
                    // Fallback thô nếu thiếu DOMPurify
                    console.warn('DOMPurify not found. Using basic sanitizer fallback.');
                    return input
                        .replace(/<script[\s\S]*?<\/script>/gi, '')
                        .replace(/<style[\s\S]*?<\/style>/gi, '')
                        .replace(/on\w+="[^"]*"/gi, '')
                        .replace(/on\w+='[^']*'/gi, '');
                }

                function stripHtmlToText(html) {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = purifyHtmlClient(html, {
                        forTable: true
                    });
                    return (tmp.textContent || '').trim();
                }

                // Tinymce helpers
                function getEditor(id) {
                    return window.tinymce?.get(id) || null;
                }

                function readEditor(id, fallbackEl) {
                    const ed = getEditor(id);
                    if (ed) {
                        return {
                            html: ed.getContent(),
                            text: ed.getContent({
                                format: 'text'
                            }).trim()
                        };
                    }
                    const html = fallbackEl?.value || '';
                    const text = html.replace(/<[^>]+>/g, ' ').replace(/&nbsp;/g, ' ').trim();
                    return {
                        html,
                        text
                    };
                }

                // Tắt native validation để tránh lỗi "not focusable"
                window.addEventListener('DOMContentLoaded', () => {
                    $('qaForm')?.setAttribute('novalidate', 'novalidate');
                    $('editForm')?.setAttribute('novalidate', 'novalidate');
                    $('answer')?.removeAttribute('required');
                    $('editAnswer')?.removeAttribute('required');
                });

                // Nếu trang chưa khởi tạo TinyMCE cho #answer, #editAnswer thì init
                if (window.tinymce && !window.__TINYMCE_INIT_ADMIN) {
                    window.__TINYMCE_INIT_ADMIN = true;
                    window.tinymce.init({
                        selector: '#answer,#editAnswer',
                        plugins: 'advlist autolink lists link charmap preview anchor fullscreen table code',
                        toolbar: 'undo redo | fullscreen | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table | removeformat | code',
                        menubar: true,
                        branding: false,
                        resize: 'both',
                        content_style: 'body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;line-height:1.6} table{border-collapse:collapse;width:100%} th,td{border:1px solid #e5e7eb;padding:6px 8px}',
                        setup(editor) {
                            editor.on('init', () => {
                                editor.getContainer().style.height = '420px';
                            });
                            editor.on('change input keyup', () => editor.save());
                        },
                        toolbar_mode: 'wrap',
                        license_key: 'gpl',
                        mobile: {
                            menubar: true,
                            toolbar_mode: 'floating',
                            plugins: 'advlist autolink lists link charmap preview anchor fullscreen table code',
                            toolbar: 'undo redo | fullscreen | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table | removeformat | code'
                        }
                    });
                }

                /* ======================= DOM REFS ======================= */
                // Header controls
                const pageSizeSelect = $('pageSizeSelect');
                const reloadBtn = $('reloadBtn');

                // Tools
                const toolsToggleBtn = $('toolsToggleBtn');
                const toolsChevron = $('toolsChevron');
                const toolsPanel = $('toolsPanel');

                // Tools content
                const searchInput = $('searchInput');
                const openTempAnswersModalBtn = $('openTempAnswersModalBtn');
                const openAddModalBtn = $('openAddModalBtn');

                // Main table
                const qaTableBody = $('qaTableBody');
                const mainPager = $('mainPagination');
                const firstPageBtn = $('firstPageBtn');
                const prevPageBtn = $('prevPageBtn');
                const pageInfoSpan = $('pageInfo');
                const nextPageBtn = $('nextPageBtn');
                const lastPageBtn = $('lastPageBtn');

                // Temp modal (list)
                const tempAnswersModal = $('tempAnswersModal');
                const closeTempAnswersModalBtn = $('closeTempAnswersModalBtn');
                const tempAnswersTableBody = $('tempAnswersTableBody');
                const tempPagination = $('tempPagination');
                const prevTempPageBtn = $('prevTempPageBtn');
                const nextTempPageBtn = $('nextTempPageBtn');
                const tempPageInfoSpan = $('tempPageInfo');

                // Add modal
                const addModal = $('addModal');
                const closeAddModalBtn = $('closeAddModalBtn');
                const qaForm = $('qaForm');
                const tempAnswerIdInp = $('tempAnswerId');
                const questionInp = $('question');
                const answerInp = $('answer'); // fallback khi editor chưa sẵn sàng

                // Review modal
                const reviewModal = $('reviewModal');
                const closeReviewModalBtn = $('closeReviewModalBtn');
                const cancelBtn = $('cancelBtn');
                const confirmAddBtn = $('confirmAddBtn');
                const modalQuestion = $('modalQuestion');
                const modalAnswer = $('modalAnswer');

                // Edit modal
                const editModal = $('editModal');
                const closeEditModalBtn = $('closeEditModalBtn');
                const editForm = $('editForm');
                const editQuestionIdInput = $('editQuestionId');
                const editAnswerIdInput = $('editAnswerId');
                const editQuestionInput = $('editQuestion');
                const editAnswerInput = $('editAnswer'); // fallback

                // Delete confirm modal
                const deleteConfirmModal = $('deleteConfirmModal');
                const closeDeleteConfirmModalBtn = $('closeDeleteConfirmModalBtn');
                const cancelDeleteBtn = $('cancelDeleteBtn');
                const confirmDeleteBtn = $('confirmDeleteBtn');
                const deleteMessage = $('deleteMessage');

                // Notification
                const unifiedNotification = $('unifiedNotification');
                const notificationTitle = $('notificationTitle');
                const notificationText = $('notificationText');
                const notificationIcon = $('notificationIcon');
                const closeNotificationBtn = $('closeNotificationBtn');

                // Mobile nav
                const mobileMenuBtn = $('mobileMenuBtn');
                const mobileNavPanel = $('mobileNavPanel');

                /* ======================= TOAST ======================= */
                let notifyTimer = null;
                let currentToast = null;
                const toastQueue = [];

                function showNotification(type = 'success', title = '', text = '', opts = {}) {
                    const duration = Number(opts.duration ?? 3500);
                    const toast = {
                        type,
                        title,
                        text,
                        duration
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
                    if (!unifiedNotification) return;
                    unifiedNotification.classList.remove('alert-success', 'alert-error');
                    if (type === 'success') {
                        unifiedNotification.classList.add('alert-success');
                        if (notificationIcon) notificationIcon.textContent = '✅';
                    } else {
                        unifiedNotification.classList.add('alert-error');
                        if (notificationIcon) notificationIcon.textContent = '⚠️';
                    }
                    if (notificationTitle) notificationTitle.textContent = title || '';
                    if (notificationText) notificationText.textContent = text || '';

                    clearTimeout(notifyTimer);
                    unifiedNotification.style.opacity = '1';
                    unifiedNotification.style.transform = 'translateY(-50%) scale(1)';
                    unifiedNotification.style.pointerEvents = 'auto';
                    notifyTimer = setTimeout(() => hideNotification(), duration);
                }

                function hideNotification() {
                    if (!unifiedNotification) return;
                    clearTimeout(notifyTimer);
                    unifiedNotification.style.opacity = '0';
                    unifiedNotification.style.transform = 'translateY(-50%) scale(0.95)';
                    unifiedNotification.style.pointerEvents = 'none';
                    currentToast = null;
                    if (toastQueue.length) {
                        const next = toastQueue.shift();
                        setTimeout(() => {
                            currentToast = next;
                            renderToast(next);
                        }, 120);
                    }
                }
                closeNotificationBtn?.addEventListener('click', hideNotification);

                /* ======================= MOBILE NAV ======================= */
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

                /* ======================= MODALS ======================= */
                function showModal(el) {
                    el?.classList.add('show');
                }

                function hideModal(el) {
                    el?.classList.remove('show');
                }
                [addModal, reviewModal, tempAnswersModal, editModal, deleteConfirmModal].forEach((m) => {
                    m?.addEventListener('click', (e) => {
                        if (e.target === m) hideModal(m);
                    });
                });

                /* ======================= STATE ======================= */
                let allExportedAnswers = [];
                let filteredAnswers = [];
                let currentPage = 1;
                let pageSize = 10;

                let allTemporaryAnswers = [];
                let currentTempPage = 1;
                const tempItemsPerPage = 5;

                let deletionType = null; // 'permanent' | 'temporary'
                let questionIdToDelete = null;
                let answerIdToDelete = null;
                let tempAnswerToDeleteId = null;

                // Lưu RAW HTML riêng, tránh nhét vào data-*
                const answerStore = new Map(); // key: id_cau_tra_loi, value: raw HTML
                const tempAnswerStore = new Map(); // key: id (temp), value: raw HTML

                /* ======================= RENDER ======================= */
                function renderMainRow(item) {
                    const idAns = String(item.id_cau_tra_loi ?? '');
                    const rawHtml = (answerStore.get(idAns) ?? item.cau_tra_loi ?? '');
                    const safeHtml = purifyHtmlClient(rawHtml, {
                        forTable: true
                    });
                    // whitespace-pre-wrap
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td class="px-4 sm:px-6 py-3 align-top break-words">
                        ${escapeHtml(item.cau_hoi ?? '')}
                    </td>
                    <td class="px-4 sm:px-6 py-3 align-top">
                        <div class="answer-wrap">
                        <div class="answer-html">${safeHtml}</div>
                        </div>
                    </td>
                    <td class="px-4 sm:px-6 py-3 whitespace-nowrap text-sm font-medium text-right">
                        <button class="edit-qa-btn text-indigo-600 hover:text-indigo-900 mr-2"
                        data-question-id="${escapeHtml(item.id_cau_hoi)}"
                        data-answer-id="${escapeHtml(item.id_cau_tra_loi)}"
                        data-question-text="${escapeHtml(item.cau_hoi ?? '')}">
                        <i class="fas fa-edit"></i> Sửa
                        </button>
                        <button class="delete-qa-btn text-red-600 hover:text-red-900"
                        data-id1="${escapeHtml(item.id_cau_hoi)}"
                        data-id2="${escapeHtml(item.id_cau_tra_loi)}">
                        <i class="fas fa-trash"></i> Xóa
                        </button>
                    </td>`;
                    return tr;
                }

                function renderTempRow(item) {
                    const tid = String(item.id ?? '');
                    const rawHtml = (tempAnswerStore.get(tid) ?? item.cau_tra_loi ?? '');
                    const safeHtml = purifyHtmlClient(rawHtml, {
                        forTable: true
                    });

                    const tr = document.createElement('tr');
                    // whitespace-pre-wrap
                    tr.innerHTML = `
                <td class="px-6 py-4 align-top max-w-xs break-words ">
                    ${escapeHtml(item.cau_hoi ?? '')}
                </td>
                <td class="px-6 py-4 align-top">
                    <div class="answer-wrap">
                    <div class="answer-html">${safeHtml}</div>
                    </div>
                </td>
                <td class="px-6 py-4 align-top whitespace-nowrap text-sm font-medium text-right">
                    <button class="approve-temp-answer-btn text-indigo-600 hover:text-indigo-900 mr-2"
                    data-id="${escapeHtml(item.id)}"
                    data-question="${escapeHtml(item.cau_hoi ?? '')}">
                    <i class="fas fa-plus"></i> Thêm
                    </button>
                    <button class="delete-temp-answer-btn text-red-600 hover:text-red-900" data-id="${escapeHtml(item.id)}">
                    <i class="fas fa-trash"></i> Xóa
                    </button>
                </td>`;
                    return tr;
                }

                function renderTableData(tbody, data, renderRow) {
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    if (!data.length) {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td colspan="3" class="px-6 py-6 text-center text-gray-500">Không có dữ liệu.</td>`;
                        tbody.appendChild(tr);
                        return;
                    }
                    data.forEach(item => tbody.appendChild(renderRow(item)));
                }

                function goToPage({
                    page,
                    allData,
                    tbody,
                    itemsPerPage,
                    renderRow,
                    pager,
                    infoEl,
                    prevBtn,
                    nextBtn,
                    firstBtn,
                    lastBtn
                }) {
                    const total = allData.length;
                    const totalPages = Math.max(1, Math.ceil(total / itemsPerPage));
                    page = Math.max(1, Math.min(page, totalPages));
                    const start = (page - 1) * itemsPerPage;
                    const end = start + itemsPerPage;
                    const chunk = allData.slice(start, end);

                    renderTableData(tbody, chunk, renderRow);

                    if (pager)(total > 0) ? pager.classList.remove('hidden') : pager.classList.add('hidden');
                    if (infoEl) infoEl.textContent = `Trang ${page}/${totalPages} (${total} bản ghi)`;
                    if (prevBtn) prevBtn.disabled = page <= 1;
                    if (firstBtn) firstBtn.disabled = page <= 1;
                    if (nextBtn) nextBtn.disabled = page >= totalPages;
                    if (lastBtn) lastBtn.disabled = page >= totalPages;

                    return page;
                }

                function goToMain(page) {
                    currentPage = goToPage({
                        page,
                        allData: filteredAnswers,
                        tbody: qaTableBody,
                        itemsPerPage: pageSize,
                        renderRow: renderMainRow,
                        pager: mainPager,
                        infoEl: pageInfoSpan,
                        prevBtn: prevPageBtn,
                        nextBtn: nextPageBtn,
                        firstBtn: firstPageBtn,
                        lastBtn: lastPageBtn
                    });
                }

                function goToTemp(page) {
                    currentTempPage = goToPage({
                        page,
                        allData: allTemporaryAnswers,
                        tbody: tempAnswersTableBody,
                        itemsPerPage: tempItemsPerPage,
                        renderRow: renderTempRow,
                        pager: tempPagination,
                        infoEl: tempPageInfoSpan,
                        prevBtn: prevTempPageBtn,
                        nextBtn: nextTempPageBtn,
                        firstBtn: null,
                        lastBtn: null
                    });
                }

                /* ======================= FETCH ======================= */
                async function fetchExportedAnswers() {
                    if (qaTableBody) {
                        qaTableBody.innerHTML =
                            `<tr><td colspan="3" class="px-6 py-6 text-center text-gray-500">Đang tải dữ liệu...</td></tr>`;
                    }
                    try {
                        const res = await fetch(QA_LIST_API);
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();
                        allExportedAnswers = Array.isArray(data) ? data : (data.data ?? []);

                        // Nạp RAW HTML vào store (ưu tiên field *_html nếu có)
                        answerStore.clear();
                        for (const item of allExportedAnswers) {
                            const key = String(item.id_cau_tra_loi ?? '');
                            const raw = item.cau_tra_loi_html ?? item.cau_tra_loi ?? '';
                            if (key) answerStore.set(key, raw);
                        }

                        applyFilter();
                        showNotification('success', 'Tải dữ liệu thành công',
                            `Đã nạp ${allExportedAnswers.length} bản ghi câu hỏi.`);
                    } catch (err) {
                        if (qaTableBody) {
                            qaTableBody.innerHTML =
                                `<tr><td colspan="3" class="px-6 py-6 text-center text-red-500">Không thể tải dữ liệu.</td></tr>`;
                        }
                        showNotification('error', 'Lỗi tải dữ liệu', err.message || 'Không thể lấy dữ liệu từ API.');
                    }
                }

                const flashDanger = @json(session('tb_danger'));
                if (flashDanger) {
                    showNotification('error', 'Truy cập bị từ chối', flashDanger);
                }

                async function fetchTemporaryAnswers() {
                    if (tempAnswersTableBody) {
                        tempAnswersTableBody.innerHTML =
                            `<tr><td colspan="3" class="px-6 py-6 text-center text-gray-500">Đang tải dữ liệu...</td></tr>`;
                    }
                    try {
                        const res = await fetch(DS_TAM_LIST_API);
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();
                        allTemporaryAnswers = Array.isArray(data) ? data : (data.data ?? []);

                        // RAW HTML cho danh sách tạm
                        tempAnswerStore.clear();
                        for (const item of allTemporaryAnswers) {
                            const key = String(item.id ?? '');
                            const raw = item.cau_tra_loi_html ?? item.cau_tra_loi ?? '';
                            if (key) tempAnswerStore.set(key, raw);
                        }

                        goToTemp(1);
                    } catch (err) {
                        if (tempAnswersTableBody) {
                            tempAnswersTableBody.innerHTML =
                                `<tr><td colspan="3" class="px-6 py-6 text-center text-red-500">Không thể tải dữ liệu.</td></tr>`;
                        }
                    }
                }

                /* ======================= FILTER ======================= */
                function applyFilter() {
                    const q = (searchInput?.value || '').trim().toLowerCase();
                    filteredAnswers = !q ? [...allExportedAnswers] :
                        allExportedAnswers.filter(item => {
                            const raw = answerStore.get(String(item.id_cau_tra_loi ?? '')) ?? item.cau_tra_loi ?? '';
                            const hay = `${(item.cau_hoi ?? '')} | ${stripHtmlToText(raw)}`.toLowerCase();
                            return hay.includes(q);
                        });
                    goToMain(1);
                }

                /* ======================= ACTIONS (CRUD) ======================= */
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

                async function updateQA({
                    idQuestion,
                    idAnswer,
                    question,
                    answer
                }) {
                    const res = await fetch(QA_UPDATE_API, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            id_cau_hoi: idQuestion,
                            id_cau_tra_loi: idAnswer,
                            cau_hoi: question,
                            cau_tra_loi: answer
                        })
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.json();
                }

                async function deletePermanentQA({
                    idQuestion,
                    idAnswer
                }) {
                    const res = await fetch(`${QA_XOA_API}/${idQuestion}/${idAnswer}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.text();
                }

                async function deleteTempAnswer(id) {
                    const res = await fetch(`${DS_TAM_XOA_API}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    if (!res.ok) throw new Error((await res.text()) || `HTTP ${res.status}`);
                    return res.text();
                }

                /* ======================= TOOLS PANEL (mobile collapse) ======================= */
                function expandTools() {
                    if (!toolsPanel) return;
                    toolsPanel.classList.remove('hidden');
                    toolsPanel.style.maxHeight = '0px';
                    toolsPanel.offsetHeight;
                    toolsPanel.style.maxHeight = toolsPanel.scrollHeight + 'px';
                    toolsToggleBtn?.setAttribute('aria-expanded', 'true');
                    toolsChevron?.classList.add('rotate-180');
                    const onEnd = (e) => {
                        if (!e || e.propertyName === 'max-height') {
                            toolsPanel.style.maxHeight = 'none';
                            toolsPanel.removeEventListener('transitionend', onEnd);
                        }
                    };
                    toolsPanel.addEventListener('transitionend', onEnd);
                }

                function collapseTools() {
                    if (!toolsPanel) return;
                    toolsPanel.style.maxHeight = toolsPanel.scrollHeight + 'px';
                    toolsPanel.offsetHeight;
                    toolsPanel.style.maxHeight = '0px';
                    toolsToggleBtn?.setAttribute('aria-expanded', 'false');
                    toolsChevron?.classList.remove('rotate-180');
                    const onEnd = (e) => {
                        if (!e || e.propertyName === 'max-height') {
                            toolsPanel.classList.add('hidden');
                            toolsPanel.removeEventListener('transitionend', onEnd);
                        }
                    };
                    toolsPanel.addEventListener('transitionend', onEnd);
                }
                let toolsOpen = false;
                toolsToggleBtn?.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!isMobile()) return;
                    toolsOpen ? collapseTools() : expandTools();
                    toolsOpen = !toolsOpen;
                });

                function syncToolsByViewport() {
                    if (!toolsPanel) return;
                    if (isMobile()) {
                        toolsOpen = false;
                        toolsPanel.classList.add('hidden');
                        toolsPanel.style.maxHeight = '0px';
                        toolsChevron?.classList.remove('rotate-180');
                        toolsToggleBtn?.setAttribute('aria-expanded', 'false');
                    } else {
                        toolsPanel.classList.remove('hidden');
                        toolsPanel.style.maxHeight = 'none';
                        toolsChevron?.classList.add('rotate-180');
                        toolsToggleBtn?.setAttribute('aria-expanded', 'true');
                    }
                }
                window.addEventListener('resize', syncToolsByViewport);
                syncToolsByViewport();

                /* ======================= EVENTS: Search / Paging / Reload ======================= */
                let searchTimer;
                searchInput?.addEventListener('input', () => {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(applyFilter, 250);
                });
                pageSizeSelect?.addEventListener('change', () => {
                    pageSize = parseInt(pageSizeSelect.value, 10) || 10;
                    goToMain(1);
                });
                reloadBtn?.addEventListener('click', () => {
                    if (searchInput) searchInput.value = '';
                    pageSizeSelect && (pageSizeSelect.value = '10');
                    pageSize = 10;
                    fetchExportedAnswers();
                });

                firstPageBtn?.addEventListener('click', () => goToMain(1));
                prevPageBtn?.addEventListener('click', () => goToMain(currentPage - 1));
                nextPageBtn?.addEventListener('click', () => goToMain(currentPage + 1));
                lastPageBtn?.addEventListener('click', () => {
                    const totalPages = Math.max(1, Math.ceil(filteredAnswers.length / pageSize));
                    goToMain(totalPages);
                });

                prevTempPageBtn?.addEventListener('click', () => goToTemp(currentTempPage - 1));
                nextTempPageBtn?.addEventListener('click', () => goToTemp(currentTempPage + 1));

                /* ======================= EVENTS: Modals open/close ======================= */
                openAddModalBtn?.addEventListener('click', () => {
                    qaForm?.reset();
                    if (tempAnswerIdInp) tempAnswerIdInp.value = '';
                    const ed = getEditor('answer');
                    if (ed) {
                        ed.setContent('');
                        ed.undoManager?.clear();
                    }
                    showModal(addModal);
                });
                closeAddModalBtn?.addEventListener('click', () => hideModal(addModal));

                openTempAnswersModalBtn?.addEventListener('click', () => {
                    fetchTemporaryAnswers();
                    showModal(tempAnswersModal);
                });
                closeTempAnswersModalBtn?.addEventListener('click', () => hideModal(tempAnswersModal));

                closeReviewModalBtn?.addEventListener('click', () => hideModal(reviewModal));
                cancelBtn?.addEventListener('click', () => hideModal(reviewModal));

                closeEditModalBtn?.addEventListener('click', () => hideModal(editModal));

                closeDeleteConfirmModalBtn?.addEventListener('click', () => hideModal(deleteConfirmModal));
                cancelDeleteBtn?.addEventListener('click', () => hideModal(deleteConfirmModal));

                /* ======================= EVENTS: Add / Review / Confirm ======================= */
                qaForm?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const q = questionInp?.value ?? '';
                    const {
                        html,
                        text
                    } = readEditor('answer', answerInp);
                    if (!q.trim() || !text) {
                        showNotification('error', 'Thiếu dữ liệu', 'Vui lòng nhập đầy đủ Câu hỏi và Câu trả lời.');
                        return;
                    }
                    if (modalQuestion) modalQuestion.textContent = q;
                    if (modalAnswer) modalAnswer.innerHTML = purifyHtmlClient(html, {
                        forTable: true
                    });
                    showModal(reviewModal);
                });

                confirmAddBtn?.addEventListener('click', async () => {
                    try {
                        const q = questionInp?.value ?? '';
                        const {
                            html,
                            text
                        } = readEditor('answer', answerInp);
                        if (!q.trim() || !text) {
                            showNotification('error', 'Thiếu dữ liệu', 'Câu trả lời đang để trống.');
                            return;
                        }
                        const t = tempAnswerIdInp?.value ?? '';
                        await addQA({
                            question: q,
                            answer: html,
                            tempId: t
                        });
                        hideModal(addModal);
                        hideModal(reviewModal);
                        showNotification('success', 'Thành công!', 'Thêm câu hỏi thành công.');
                        await fetchExportedAnswers();
                        if (t) await fetchTemporaryAnswers();
                    } catch (err) {
                        showNotification('error', 'Lỗi!', err.message || 'Không thể thêm câu hỏi.');
                    }
                });

                /* ======================= EVENTS: Edit Submit ======================= */
                editForm?.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const idQ = editQuestionIdInput?.value;
                    const idA = editAnswerIdInput?.value;
                    const q = editQuestionInput?.value ?? '';
                    const {
                        html,
                        text
                    } = readEditor('editAnswer', editAnswerInput);

                    if (!idQ || !idA) {
                        showNotification('error', 'Thiếu ID', 'Không xác định được bản ghi cần sửa.');
                        return;
                    }
                    if (!q.trim() || !text) {
                        showNotification('error', 'Thiếu dữ liệu', 'Câu trả lời đang để trống.');
                        return;
                    }
                    try {
                        await updateQA({
                            idQuestion: idQ,
                            idAnswer: idA,
                            question: q,
                            answer: html
                        });
                        showNotification('success', 'Cập nhật thành công!', 'Câu hỏi đã được cập nhật.');
                        await fetchExportedAnswers();
                        setTimeout(() => hideModal(editModal), 200);
                    } catch (err) {
                        showNotification('error', 'Lỗi!', err.message || 'Không thể cập nhật.');
                    }
                });

                /* ======================= EVENTS: Table delegates ======================= */
                // Main table: edit / delete
                qaTableBody?.addEventListener('click', (e) => {
                    const editBtn = e.target.closest?.('.edit-qa-btn');
                    const delBtn = e.target.closest?.('.delete-qa-btn');

                    if (editBtn) {
                        const qid = editBtn.getAttribute('data-question-id');
                        const aid = editBtn.getAttribute('data-answer-id');
                        const qt = editBtn.getAttribute('data-question-text') || '';
                        if (editQuestionIdInput) editQuestionIdInput.value = qid || '';
                        if (editAnswerIdInput) editAnswerIdInput.value = aid || '';
                        if (editQuestionInput) editQuestionInput.value = qt;

                        const rawHtml = answerStore.get(String(aid)) || '';
                        const ed = getEditor('editAnswer');
                        if (ed) {
                            ed.setContent(rawHtml);
                            ed.undoManager?.clear();
                        } else if (editAnswerInput) editAnswerInput.value = rawHtml;

                        showModal(editModal);
                        return;
                    }

                    if (delBtn) {
                        deletionType = 'permanent';
                        questionIdToDelete = delBtn.getAttribute('data-id1');
                        answerIdToDelete = delBtn.getAttribute('data-id2');
                        if (deleteMessage) deleteMessage.textContent =
                            'Bạn có chắc chắn muốn xóa câu hỏi này không?';
                        showModal(deleteConfirmModal);
                        return;
                    }
                });

                // Temp table: approve / delete
                tempAnswersTableBody?.addEventListener('click', (e) => {
                    const approveBtn = e.target.closest?.('.approve-temp-answer-btn');
                    const delBtn = e.target.closest?.('.delete-temp-answer-btn');

                    if (approveBtn) {
                        const tid = approveBtn.getAttribute('data-id');
                        const q = approveBtn.getAttribute('data-question') || '';
                        if (questionInp) questionInp.value = q;

                        const rawHtml = tempAnswerStore.get(String(tid)) || '';
                        const ed = getEditor('answer');
                        if (ed) ed.setContent(rawHtml);
                        else if (answerInp) answerInp.value = rawHtml;

                        if (tempAnswerIdInp) tempAnswerIdInp.value = tid || '';
                        hideModal(tempAnswersModal);
                        showModal(addModal);
                        return;
                    }

                    if (delBtn) {
                        deletionType = 'temporary';
                        tempAnswerToDeleteId = delBtn.getAttribute('data-id');
                        if (deleteMessage) deleteMessage.textContent =
                            'Bạn có chắc chắn muốn xóa câu trả lời tạm thời này không?';
                        showModal(deleteConfirmModal);
                        return;
                    }
                });

                // Confirm delete
                confirmDeleteBtn?.addEventListener('click', async () => {
                    try {
                        if (deletionType === 'permanent' && questionIdToDelete && answerIdToDelete) {
                            await deletePermanentQA({
                                idQuestion: questionIdToDelete,
                                idAnswer: answerIdToDelete
                            });
                            await fetchExportedAnswers();
                            showNotification('success', 'Thành công!', 'Xóa câu hỏi thành công.');
                        } else if (deletionType === 'temporary' && tempAnswerToDeleteId) {
                            await deleteTempAnswer(tempAnswerToDeleteId);
                            await fetchTemporaryAnswers();
                            showNotification('success', 'Thành công!', 'Xóa câu trả lời tạm thời thành công.');
                        }
                    } catch (err) {
                        showNotification('error', 'Lỗi!', err.message || 'Không thể xóa.');
                    } finally {
                        hideModal(deleteConfirmModal);
                        deletionType = null;
                        questionIdToDelete = null;
                        answerIdToDelete = null;
                        tempAnswerToDeleteId = null;
                    }
                });

                /* ======================= INIT ======================= */
                if (pageSizeSelect) pageSize = parseInt(pageSizeSelect.value, 10) || 10;
                fetchExportedAnswers();
            })();
        </script>
    @endif
</body>

</html>
