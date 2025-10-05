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
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                        <!-- Hàng 1 -->
                        <div class="flex items-center justify-between gap-3">
                            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                <span
                                    class="bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-indigo-700 to-blue-600">
                                    Biểu đồ lượt truy cập
                                </span>
                            </h1>
                            <div class="flex items-center gap-2 sm:gap-3">
                                <!-- chừa slot nếu cần thêm nút -->
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
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center mt-3">
                                <!-- Cột 1: khoảng ngày -->
                                <div class="md:col-span-8">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="flex items-center gap-2">
                                            <label for="startDate"
                                                class="w-20 text-sm text-slate-700 whitespace-nowrap">Từ ngày:</label>
                                            <input id="startDate" type="date"
                                                class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label for="endDate"
                                                class="w-20 text-sm text-slate-700 whitespace-nowrap">Đến ngày:</label>
                                            <input id="endDate" type="date"
                                                class="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Cột 2: Lọc theo Tháng/Năm -->
                                <div class="md:col-span-4 md:justify-self-end">
                                    <div class="flex items-center gap-2">
                                        <label for="timeFilter" class="text-sm text-slate-700 whitespace-nowrap">Lọc
                                            theo:</label>
                                        <select id="timeFilter"
                                            class="h-10 w-full md:w-48 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="">Ngày (mặc định 30 ngày gần nhất)</option>
                                            <option value="thang">Tháng (năm hiện tại)</option>
                                            <option value="nam">Năm</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </header>
        <!-- ===== /NAV + MENU TOP ===== -->

        <!-- Nội dung -->
        <main class="mx-auto w-full max-w-6xl px-3 sm:px-4 pt-48 sm:pt-56">
            <div class="bg-white rounded-xl shadow-lg p-4 sm:p-8 w-full" style="min-height: 70vh;">
                <!-- Chart wrapper: mobile/desktop co giãn -->
                <div class="h-[52vh] sm:h-[60vh]">
                    <canvas id="trafficChart" class="w-full h-full"></canvas>
                </div>
            </div>
        </main>

        <script defer>
            // ===== Config & helpers =====
            const API_URL = '{{ url('/api/access') }}';
            let chartInstance = null;

            // Mobile nav
            (function setupMobileNav() {
                const mobileMenuBtn = document.getElementById('mobileMenuBtn');
                const mobileNavPanel = document.getElementById('mobileNavPanel');

                function isMobile() {
                    return window.matchMedia('(max-width: 767.98px)').matches;
                }

                function setMobileNav(open) {
                    if (!mobileNavPanel || !mobileMenuBtn) return;
                    mobileNavPanel.classList.toggle('hidden', !open);
                    mobileMenuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
                let open = false;
                mobileMenuBtn?.addEventListener('click', (e) => {
                    e.preventDefault();
                    open = !open;
                    setMobileNav(open);
                });
                window.addEventListener('resize', () => {
                    if (!isMobile()) setMobileNav(false);
                });
            })();

            // Bộ lọc: mobile gập/mở
            (function setupFilterToggle() {
                const btn = document.getElementById('filterToggleBtn');
                const panel = document.getElementById('filtersPanel');
                const chev = document.getElementById('filterChevron');
                if (!btn || !panel) return;
                const mq = window.matchMedia('(min-width: 768px)');

                function syncByViewport() {
                    if (mq.matches) {
                        panel.classList.remove('hidden');
                        btn.setAttribute('aria-expanded', 'true');
                        chev?.classList.add('rotate-180');
                    } else {
                        panel.classList.add('hidden');
                        btn.setAttribute('aria-expanded', 'false');
                        chev?.classList.remove('rotate-180');
                    }
                }
                syncByViewport();
                mq.addEventListener?.('change', syncByViewport);
                btn.addEventListener('click', () => {
                    if (mq.matches) return;
                    const willShow = panel.classList.toggle('hidden') === false;
                    btn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                    chev?.classList.toggle('rotate-180', willShow);
                });
            })();

            // Fetch dữ liệu
            async function fetchData() {
                const res = await fetch(API_URL, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            }

            // Utils thời gian
            function startOfDay(d) {
                const x = new Date(d);
                x.setHours(0, 0, 0, 0);
                return x;
            }

            function endOfDay(d) {
                const x = new Date(d);
                x.setHours(23, 59, 59, 999);
                return x;
            }

            function lastNDays(n) {
                const end = endOfDay(new Date());
                const start = startOfDay(new Date(Date.now() - (n - 1) * 24 * 3600 * 1000));
                return {
                    start,
                    end
                };
            }

            // Xử lý theo ngày trong khoảng
            function groupByDateRange(data, startDate, endDate) {
                const counts = {};
                for (const {
                        created_at
                    }
                    of data) {
                    const d = new Date(created_at);
                    if (d >= startDate && d <= endDate) {
                        const label = d.toISOString().split('T')[0]; // YYYY-MM-DD
                        counts[label] = (counts[label] || 0) + 1;
                    }
                }
                const labels = Object.keys(counts).sort((a, b) => new Date(a) - new Date(b));
                return {
                    labels,
                    counts: labels.map(l => counts[l])
                };
            }

            // Theo tháng trong năm hiện tại
            function groupByMonthOfYear(data, year) {
                const counts = {};
                for (const {
                        created_at
                    }
                    of data) {
                    const d = new Date(created_at);
                    if (d.getFullYear() === year) {
                        const m = d.getMonth() + 1;
                        const label = (m < 10 ? '0' : '') + m;
                        counts[label] = (counts[label] || 0) + 1;
                    }
                }
                const labels = Object.keys(counts).sort((a, b) => parseInt(a) - parseInt(b));
                return {
                    labels,
                    counts: labels.map(l => counts[l])
                };
            }

            // Theo năm
            function groupByYear(data) {
                const counts = {};
                for (const {
                        created_at
                    }
                    of data) {
                    const y = new Date(created_at).getFullYear();
                    counts[y] = (counts[y] || 0) + 1;
                }
                const labels = Object.keys(counts).sort((a, b) => parseInt(a) - parseInt(b));
                return {
                    labels,
                    counts: labels.map(l => counts[l])
                };
            }

            // Vẽ biểu đồ
            async function renderChart() {
                try {
                    const data = await fetchData();

                    const startInput = document.getElementById('startDate').value;
                    const endInput = document.getElementById('endDate').value;
                    const timeFilter = document.getElementById('timeFilter').value;

                    let labels = [],
                        series = [];

                    // Ưu tiên bộ lọc chọn
                    if (timeFilter === 'thang') {
                        const {
                            labels: L,
                            counts
                        } = groupByMonthOfYear(data, new Date().getFullYear());
                        labels = L;
                        series = counts;
                    } else if (timeFilter === 'nam') {
                        const {
                            labels: L,
                            counts
                        } = groupByYear(data);
                        labels = L;
                        series = counts;
                    } else if (startInput && endInput) {
                        const {
                            labels: L,
                            counts
                        } = groupByDateRange(
                            data,
                            startOfDay(new Date(startInput)),
                            endOfDay(new Date(endInput))
                        );
                        labels = L;
                        series = counts;
                    } else {
                        // Mặc định: 30 ngày gần nhất
                        const {
                            start,
                            end
                        } = lastNDays(30);
                        const {
                            labels: L,
                            counts
                        } = groupByDateRange(data, start, end);
                        labels = L;
                        series = counts;
                    }

                    // Hủy chart cũ trước khi tạo chart mới
                    if (chartInstance) {
                        chartInstance.destroy();
                    }

                    const ctx = document.getElementById('trafficChart').getContext('2d');
                    chartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Số lượt truy cập',
                                data: series,
                                borderWidth: 2,
                                tension: 0.2,
                                pointRadius: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false, // dùng theo chiều cao container
                            interaction: {
                                intersect: false,
                                mode: 'index'
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                tooltip: {
                                    enabled: true
                                }
                            },
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: timeFilter === 'thang' ? 'Tháng' : (timeFilter === 'nam' ? 'Năm' :
                                            'Ngày')
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Số lượt truy cập'
                                    }
                                }
                            }
                        }
                    });
                } catch (err) {
                    console.error('Lỗi tải/hiển thị biểu đồ:', err);
                    if (chartInstance) {
                        chartInstance.destroy();
                    }
                }
            }

            // Lắng nghe thay đổi filter
            document.getElementById('startDate').addEventListener('change', renderChart);
            document.getElementById('endDate').addEventListener('change', renderChart);
            document.getElementById('timeFilter').addEventListener('change', renderChart);

            // Khởi tạo
            document.addEventListener('DOMContentLoaded', renderChart);
        </script>
    @endif
</body>

</html>
