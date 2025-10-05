<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Thống kê Câu hỏi (gộp 80%)</title>

    <!-- Tailwind CSS + Font Awesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            background: linear-gradient(to bottom, #dff5ff, #eaf9ff);
            background-attachment: fixed;
        }

        h1 {
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eef2ff;
            color: #1e293b;
            border: 1px solid #c7d2fe;
            font-size: 12px
        }

        details summary {
            cursor: pointer
        }

        .table-sticky thead th {
            position: sticky;
            top: 0;
            background: #fff
        }
    </style>

    <script>
        const API_URL = '{{ url('/api/log-cau-hoi') }}';
    </script>
</head>

<body class="min-h-screen">

    <!-- ===== NAV + MENU TOP ===== -->
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
        <!-- Thanh công cụ trang (BẢN ĐẸP/MODERN) -->
        <div
            class="sticky top-0 z-40 bg-gradient-to-b from-white/80 to-white/60 backdrop-blur-xl border-b border-slate-200/60">
            <div class="mx-auto w-full max-w-6xl px-3 sm:px-4 py-4">
                <div class="flex flex-col gap-4">
                    <!-- Hàng tiêu đề -->
                    <div class="flex items-center justify-between gap-3">
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                            <span
                                class="bg-clip-text text-transparent bg-gradient-to-r from-slate-900 via-indigo-700 to-blue-600">
                                Thống kê câu hỏi
                            </span>
                        </h1>

                        <!-- Toggle (mobile) -->
                        <div class="md:hidden">
                            <button id="filterToggleBtn"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200/80 bg-white/80 backdrop-blur shadow-sm active:scale-[0.98] transition text-slate-700 hover:bg-white"
                                aria-expanded="false" aria-controls="filtersPanel">
                                <i class="fas fa-sliders-h"></i>
                                <span>Bộ lọc</span>
                                <i id="filterChevron" class="fas fa-chevron-down text-xs transition-transform"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Panel bộ lọc -->
                    <div id="filtersPanel"
                        class="overflow-hidden transition-all duration-300 ease-in-out md:overflow-visible md:transition-none md:max-h-none hidden md:block">
                        <div
                            class="rounded-2xl border border-slate-200/70 bg-white/80 backdrop-blur p-3 sm:p-4 shadow-sm">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

                                <!-- Cột 1: Khoảng ngày tùy chọn -->
                                <div class="md:col-span-5">
                                    <label class="block text-xs font-semibold  tracking-wide text-slate-500 mb-2">KHOẢNG
                                        NGÀY:
                                        <span class="mt-2 text-xs text-slate-500"><i>Nếu không chọn, mặc định là 30 ngày
                                                gần nhất.</i>
                                        </span></label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="relative">
                                            <label for="startDate" class="sr-only">Từ ngày</label>
                                            <span
                                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                                <i class="fa-solid fa-calendar-day"></i>
                                            </span>
                                            <input id="startDate" type="date"
                                                class="w-full h-11 pl-9 pr-3 rounded-xl ring-1 ring-slate-200 bg-white focus:ring-2 focus:ring-blue-500/40 outline-none text-sm text-slate-800">
                                        </div>
                                        <div class="relative">
                                            <label for="endDate" class="sr-only">Đến ngày</label>
                                            <span
                                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                                <i class="fa-regular fa-calendar"></i>
                                            </span>
                                            <input id="endDate" type="date"
                                                class="w-full h-11 pl-9 pr-3 rounded-xl ring-1 ring-slate-200 bg-white focus:ring-2 focus:ring-blue-500/40 outline-none text-sm text-slate-800">
                                        </div>
                                    </div>

                                </div>

                                <!-- Cột 2: Segmented time tabs (giữ select ẩn để tương thích) -->
                                <div class="md:col-span-3">
                                    <label
                                        class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Khoảng
                                        thời gian nhanh</label>
                                    <div class="inline-flex items-center rounded-2xl bg-slate-100 p-1">
                                        <button type="button"
                                            class="time-tab px-3 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-slate-900"
                                            data-mode="">30 ngày</button>
                                        <button type="button"
                                            class="time-tab px-3 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-slate-900"
                                            data-mode="thang">Tháng</button>
                                        <button type="button"
                                            class="time-tab px-3 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-slate-900"
                                            data-mode="nam">Năm</button>
                                    </div>

                                    <!-- Select cũ để code hiện tại bắt sự kiện (ẩn) -->
                                    <select id="timeFilter" class="hidden">
                                        <option value="">Ngày (30 ngày gần nhất)</option>
                                        <option value="thang">Tháng</option>
                                        <option value="nam">Năm</option>
                                    </select>
                                </div>

                                <!-- Cột 3: Tháng / Năm -->
                                <div class="md:col-span-3">
                                    <div class="">
                                        <div id="monthWrap" class="hidden">
                                            <label for="monthPick"
                                                class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Chọn
                                                tháng</label>
                                            <input id="monthPick" type="month"
                                                class="w-full h-11 px-3 rounded-xl ring-1 ring-slate-200 bg-white focus:ring-2 focus:ring-blue-500/40 outline-none text-sm text-slate-800">
                                        </div>
                                        <div id="yearWrap" class="hidden">
                                            <label for="yearPick"
                                                class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Chọn
                                                năm</label>
                                            <select id="yearPick"
                                                class="w-full h-11 px-3 rounded-xl ring-1 ring-slate-200 bg-white focus:ring-2 focus:ring-blue-500/40 outline-none text-sm text-slate-800"></select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cột 4: Ngưỡng + nút chạy + stats -->
                                <div class="md:col-span-12">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="relative">
                                            <label for="threshold"
                                                class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                                                Ngưỡng tương tự (0.80 = 80%)
                                            </label>
                                            <div class="relative">
                                                <span
                                                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                                </span>
                                                <input id="threshold" type="number" step="0.01" min="0"
                                                    max="1" value="0.80"
                                                    class="h-11 w-40 pl-9 pr-3 rounded-xl ring-1 ring-slate-200 bg-white focus:ring-2 focus:ring-blue-500/40 outline-none text-sm text-slate-800">
                                            </div>
                                        </div>

                                        <button id="btnRun"
                                            class="h-11 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm active:scale-[0.98] transition inline-flex items-center gap-2">
                                            <i class="fa-solid fa-play"></i><span>Chạy thống kê</span>
                                        </button>

                                        <div id="stats"
                                            class="text-sm text-slate-600 flex items-center gap-2 flex-wrap">
                                            <!-- badge thống kê sẽ được JS hiện vào -->
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </header>
    <!-- ===== /NAV ===== -->

    <!-- ===== MAIN ===== -->
   <main class="mx-auto w-full max-w-6xl px-3 sm:px-4 pt-28 sm:pt-12 md:pt-24 lg:pt-48">
       <div class="hidden sm:block h-8 md:h-28"></div>
        <!-- Card: Bảng kết quả -->
        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-800">Bảng câu hỏi sau gộp (≥ ngưỡng)</h2>
                <div class="text-sm text-slate-500">Sắp xếp theo lượt hỏi giảm dần</div>
            </div>

            <div class="mt-3 overflow-auto max-h-[70vh] table-sticky">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-700 border-b">
                            <th class="text-left p-3 w-12">#</th>
                            <th class="text-left p-3">Câu hỏi</th>
                            <th class="text-right p-3 w-24">Lượt</th>
                            <th class="text-right p-3 w-28">Biến thể</th>
                            <th class="text-left p-3 w-80">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody id="resultBody"></tbody>
                </table>
            </div>

            <p class="mt-4 text-xs text-slate-500">
                * Chuẩn hoá so khớp: hạ chữ thường, bỏ dấu tiếng Việt, bỏ ký tự không chữ/số, gộp khoảng trắng.
                Độ tương tự dùng Levenshtein chuẩn hoá:
                <code>similarity = 1 - distance / max(lenA, lenB)</code>.
            </p>
        </div>
    </main>
    <!-- ===== /MAIN ===== -->

    <script>
        // ===== Mobile nav =====
        (function setupMobileNav() {
            const btn = document.getElementById('mobileMenuBtn');
            const panel = document.getElementById('mobileNavPanel');
            const isMobile = () => window.matchMedia('(max-width: 767.98px)').matches;
            const setOpen = (open) => {
                panel?.classList.toggle('hidden', !open);
                btn?.setAttribute('aria-expanded', open ? 'true' : 'false');
            };
            let open = false;
            btn?.addEventListener('click', (e) => {
                e.preventDefault();
                open = !open;
                setOpen(open);
            });
            window.addEventListener('resize', () => {
                if (!isMobile()) setOpen(false);
            });
        })();

        // ===== Filter toggle (mobile) =====
        (function setupFilterToggle() {
            const btn = document.getElementById('filterToggleBtn');
            const panel = document.getElementById('filtersPanel');
            const chev = document.getElementById('filterChevron');
            if (!btn || !panel) return;
            const mq = window.matchMedia('(min-width: 768px)');

            function sync() {
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
            sync();
            mq.addEventListener?.('change', sync);
            btn.addEventListener('click', () => {
                if (mq.matches) return;
                const willShow = panel.classList.toggle('hidden') === false;
                btn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                chev?.classList.toggle('rotate-180', willShow);
            });
        })();

        // ======== Chuẩn hoá & tương tự ========
        const rmDiacritics = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const normalizeText = s => rmDiacritics(String(s || '').toLowerCase())
            .replace(/[^\p{L}\p{N}\s]/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        function levenshtein(a, b) {
            const m = a.length,
                n = b.length;
            if (m === 0) return n;
            if (n === 0) return m;
            const dp = new Array(n + 1);
            for (let j = 0; j <= n; j++) dp[j] = j;
            for (let i = 1; i <= m; i++) {
                let prev = dp[0];
                dp[0] = i;
                for (let j = 1; j <= n; j++) {
                    const tmp = dp[j];
                    const cost = (a[i - 1] === b[j - 1]) ? 0 : 1;
                    dp[j] = Math.min(dp[j] + 1, dp[j - 1] + 1, prev + cost);
                    prev = tmp;
                }
            }
            return dp[n];
        }

        function similarity(a, b) {
            if (!a && !b) return 1;
            const maxLen = Math.max(a.length, b.length);
            if (maxLen === 0) return 1;
            const dist = levenshtein(a, b);
            return 1 - (dist / maxLen);
        }

        // ======== Nạp dữ liệu ========
        async function loadData() {
            if (Array.isArray(window.DATA)) return window.DATA;
            if (API_URL) {
                const r = await fetch(API_URL, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!r.ok) throw new Error('API error ' + r.status);
                return await r.json();
            }
            throw new Error('Không có dữ liệu: thiết lập API_URL hoặc gán window.DATA = [...].');
        }

        // ======== Bộ lọc thời gian ========
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

        function endOfMonth(d) {
            return new Date(d.getFullYear(), d.getMonth() + 1, 0, 23, 59, 59, 999);
        }

        function filterRecords(data) {
            const mode = document.getElementById('timeFilter').value;
            const startInput = document.getElementById('startDate').value;
            const endInput = document.getElementById('endDate').value;
            const monthVal = document.getElementById('monthPick').value;
            const yearVal = document.getElementById('yearPick').value;

            let from, to;

            if (mode === 'thang') {
                // Ưu tiên tháng chọn; nếu trống lấy tháng hiện tại
                const val = monthVal || new Date().toISOString().slice(0, 7);
                const [y, m] = val.split('-').map(Number);
                from = new Date(y, m - 1, 1, 0, 0, 0, 0);
                to = endOfMonth(from);
            } else if (mode === 'nam') {
                const y = Number(yearVal || new Date().getFullYear());
                from = new Date(y, 0, 1, 0, 0, 0, 0);
                to = new Date(y, 11, 31, 23, 59, 59, 999);
            } else if (startInput && endInput) {
                from = startOfDay(new Date(startInput));
                to = endOfDay(new Date(endInput));
            } else {
                // mặc định: 30 ngày gần nhất
                const rng = lastNDays(30);
                from = rng.start;
                to = rng.end;
            }

            return data.filter(r => {
                if (!r || !r.cau_hoi || !r.created_at) return false;
                const d = new Date(r.created_at);
                return d >= from && d <= to;
            });
        }

        // ======== Đếm tần suất & Gộp câu tương tự (Union-Find) ========
        function buildFreq(records) {
            const freq = new Map(); // norm -> {count, rawLongest, norm, rawSamples:Map}
            for (const r of records) {
                const raw = (r.cau_hoi ?? '').trim();
                const key = normalizeText(raw);
                if (!key) continue;
                if (!freq.has(key)) {
                    freq.set(key, {
                        count: 0,
                        rawLongest: raw,
                        norm: key,
                        rawSamples: new Map()
                    });
                }
                const obj = freq.get(key);
                obj.count += 1;
                obj.rawSamples.set(raw, (obj.rawSamples.get(raw) || 0) + 1);
                if (raw.length > obj.rawLongest.length) obj.rawLongest = raw;
            }
            return freq;
        }

        function clusterBySimilarity(freq, threshold) {
            const entries = Array.from(freq.values());
            const n = entries.length;
            const parent = Array.from({
                length: n
            }, (_, i) => i);
            const find = x => parent[x] === x ? x : (parent[x] = find(parent[x]));
            const union = (a, b) => {
                a = find(a);
                b = find(b);
                if (a !== b) parent[b] = a;
            };

            for (let i = 0; i < n; i++) {
                for (let j = i + 1; j < n; j++) {
                    const s = similarity(entries[i].norm, entries[j].norm);
                    if (s >= threshold) union(i, j);
                }
            }

            const groups = new Map();
            for (let i = 0; i < n; i++) {
                const root = find(i);
                if (!groups.has(root)) groups.set(root, []);
                groups.get(root).push(i);
            }

            const clusters = [];
            for (const idxs of groups.values()) {
                let rep = entries[idxs[0]].rawLongest;
                let repLen = rep.length;
                let total = 0;
                const exacts = new Map();

                for (const k of idxs) {
                    const e = entries[k];
                    total += e.count;
                    if (e.rawLongest.length > repLen) {
                        rep = e.rawLongest;
                        repLen = e.rawLongest.length;
                    }
                    for (const [txt, cnt] of e.rawSamples.entries()) {
                        exacts.set(txt, (exacts.get(txt) || 0) + cnt);
                    }
                }

                const exactList = Array.from(exacts.entries())
                    .map(([text, count]) => ({
                        text,
                        count
                    }))
                    .sort((a, b) => b.count - a.count || a.text.localeCompare(b.text));

                clusters.push({
                    rep,
                    count: total,
                    exactList,
                    size: new Set(idxs).size
                });
            }

            clusters.sort((a, b) => b.count - a.count || a.rep.localeCompare(b.rep));
            return clusters;
        }

        // ======== Render ========
        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [m]));
        }

        function renderStats(subset, clusters, modeLabel) {
            const uniqueNorm = new Set(subset.map(r => normalizeText(r.cau_hoi)).filter(Boolean)).size;
            document.getElementById('stats').innerHTML =
                `<div class="badge">Chế độ: ${escapeHtml(modeLabel)}</div>
         <div class="badge">Bản ghi: ${subset.length}</div>
         <div class="badge">Số câu (sau gộp): ${clusters.length}</div>
         <div class="badge">Số câu khác biệt (trước gộp): ${uniqueNorm}</div>`;
        }

        function renderTable(clusters) {
            const tbody = document.getElementById('resultBody');
            tbody.innerHTML = '';
            clusters.forEach((c, i) => {
                const tr = document.createElement('tr');
                tr.className = "border-b hover:bg-slate-50";

                const tdRank = `<td class="p-3">${i+1}</td>`;
                const tdRep = `<td class="p-3">${escapeHtml(c.rep)}</td>`;
                const tdCnt = `<td class="p-3 text-right"><span class="badge">${c.count}</span></td>`;
                const tdVar = `<td class="p-3 text-right">${c.size}</td>`;

                const det = document.createElement('details');
                const sum = document.createElement('summary');
                sum.className = "text-slate-700 hover:text-slate-900";
                sum.textContent = 'Xem biến thể';
                det.appendChild(sum);

                const ul = document.createElement('ul');
                ul.className = "mt-2 ml-6 list-disc";
                c.exactList.forEach(v => {
                    const li = document.createElement('li');
                    li.className = "mb-1";
                    li.innerHTML = `${escapeHtml(v.text)} <span class="badge">${v.count}</span>`;
                    ul.appendChild(li);
                });
                det.appendChild(ul);

                const tdDet = document.createElement('td');
                tdDet.className = "p-3";
                tdDet.appendChild(det);

                tr.innerHTML = tdRank + tdRep + tdCnt + tdVar;
                tr.appendChild(tdDet);
                tbody.appendChild(tr);
            });
        }

        // ======== UI wiring ========
        const timeFilterEl = document.getElementById('timeFilter');
        const monthWrap = document.getElementById('monthWrap');
        const yearWrap = document.getElementById('yearWrap');
        const monthPickEl = document.getElementById('monthPick');
        const yearPickEl = document.getElementById('yearPick');
        const thresholdEl = document.getElementById('threshold');
        const btnRun = document.getElementById('btnRun');

        function toggleMonthYear() {
            const mode = timeFilterEl.value;
            monthWrap.classList.toggle('hidden', mode !== 'thang');
            yearWrap.classList.toggle('hidden', mode !== 'nam');
        }
        timeFilterEl.addEventListener('change', toggleMonthYear);

        function populateMonthYearOptions(data) {
            const months = new Set();
            const years = new Set();
            for (const r of data) {
                if (!r || !r.created_at) continue;
                const d = new Date(r.created_at);
                months.add(d.toISOString().slice(0, 7));
                years.add(d.getFullYear());
            }
            const mSorted = Array.from(months).sort();
            if (mSorted.length) monthPickEl.value = mSorted[mSorted.length - 1];

            const ySorted = Array.from(years).sort((a, b) => a - b);
            yearPickEl.innerHTML = ySorted.map(y => `<option value="${y}">${y}</option>`).join('');
            if (ySorted.length) yearPickEl.value = ySorted[ySorted.length - 1];
        }

        function modeLabel() {
            const mode = timeFilterEl.value;
            if (mode === 'thang') return `Tháng ${monthPickEl.value || new Date().toISOString().slice(0,7)}`;
            if (mode === 'nam') return `Năm ${yearPickEl.value || new Date().getFullYear()}`;
            const s = document.getElementById('startDate').value;
            const e = document.getElementById('endDate').value;
            if (s && e) return `Khoảng ngày ${s} → ${e}`;
            return '30 ngày gần nhất';
        }

        let ALL = [];
        async function run() {
            const thr = Math.max(0, Math.min(1, Number(thresholdEl.value) || 0.8));
            const subset = filterRecords(ALL);
            const freq = buildFreq(subset);
            const clusters = clusterBySimilarity(freq, thr);
            renderStats(subset, clusters, modeLabel());
            renderTable(clusters);
        }

        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const raw = await loadData();
                ALL = (raw || []).filter(r => r && r.cau_hoi && r.created_at);
                populateMonthYearOptions(ALL);
                toggleMonthYear();
                await run();
            } catch (err) {
                console.error(err);
                alert('Lỗi nạp dữ liệu: ' + err.message);
            }
        });

        btnRun.addEventListener('click', run);
        monthPickEl.addEventListener('change', run);
        yearPickEl.addEventListener('change', run);
        thresholdEl.addEventListener('change', run);
        document.getElementById('startDate').addEventListener('change', run);
        document.getElementById('endDate').addEventListener('change', run);
    </script>
    <!--{{-- Phần chỉnh giao diện cho đẹp hơn --}}-->
    <script>
        (function segmentedTimeTabs() {
            const tabs = document.querySelectorAll('.time-tab');
            const sel = document.getElementById('timeFilter');
            const monthWrap = document.getElementById('monthWrap');
            const yearWrap = document.getElementById('yearWrap');

            function syncUI() {
                tabs.forEach(btn => {
                    const active = btn.dataset.mode === sel.value;
                    btn.classList.toggle('bg-white', active);
                    btn.classList.toggle('text-slate-900', active);
                    btn.classList.toggle('shadow', active);
                });
                monthWrap.classList.toggle('hidden', sel.value !== 'thang');
                yearWrap.classList.toggle('hidden', sel.value !== 'nam');
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', () => {
                    sel.value = btn.dataset.mode;
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    })); // để code cũ bắt sự kiện
                    syncUI();
                });
            });

            // Khởi tạo theo giá trị hiện tại (mặc định rỗng = 30 ngày)
            syncUI();
        })();
    </script>
</body>

</html>
