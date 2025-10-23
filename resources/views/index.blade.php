<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AI Chatbot Tuyển Sinh</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Font Awesome 5 (tương thích các class fas/fa-*) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('asset/css/index.css') }}">
    <style>
        /* Gợi ý: style hiển thị HTML từ BE cho gọn */
        /* .message.bot .msg-html {
            line-height: 1.6;
        } */

        .message.bot .msg-html p {
            margin: .4rem 0;
        }

        .message.bot .msg-html ul,
        .message.bot .msg-html ol {
            margin: .5rem 1.25rem;
        }

        .message.bot .msg-html table {
            border-collapse: collapse;
            width: 100%;
            margin: .5rem 0;
        }

        .message.bot .msg-html th,
        .message.bot .msg-html td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }

        .message.bot .note {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #6b7280;
        }


        .message.bot .msg-html pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: .5rem .75rem;
            border-radius: .5rem;
            overflow: auto;
        }

        .message.bot .msg-html code {
            background: #f1f5f9;
            padding: 0 .25rem;
            border-radius: .25rem;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <img src="{{ asset('img/logoAI.gif') }}" alt="AI Bot" />
            <div class="header-text">
                <h2>AI Chatbot Tuyển Sinh xin chào!</h2>
                <p>Bạn đang tương tác với trợ lý AI để tìm hiểu thông tin tuyển sinh. Đặt câu hỏi để được hỗ trợ nhé!
                </p>
            </div>
        </div>

        <div id="chatBox"></div>

        <div id="inputArea" class="composer">
            <textarea id="userInput" maxlength="3000" rows="2" placeholder="Nhắn tin cho TVU AI Chatbot"
                aria-label="Nhập câu hỏi"></textarea>
            <button id="sendBtn" class="send" title="Gửi" disabled><i class="fas fa-paper-plane"></i></button>
            <div class="counter"><span id="charCount">0</span> / 1500</div>
            <div id="suggestBox" class="suggest"></div>
        </div>
    </div>

    <div id="infoModal" class="modal">
        <div class="modal-content">
            <button class="modal-close-btn">&times;</button>
            <h3>Cung cấp thông tin cá nhân</h3>
            <p>Vui lòng điền thông tin bên dưới để chúng tôi có thể liên hệ tư vấn chi tiết hơn.</p>
            <form id="infoForm">
                <input type="text" id="userName" placeholder="Họ và tên" required>
                <input type="email" id="userEmail" placeholder="Email" required>
                <input type="tel" id="userPhone" placeholder="Số điện thoại" required>
                <input type="text" id="userAddress" placeholder="Địa chỉ" required>
                <input type="number" id="userBirthYear" placeholder="Năm sinh" min="1970" max="2020" required>
                <button type="submit" class="modal-close-btn-100">Gửi thông tin</button>
            </form>
        </div>
    </div>

    <script>
        /* =========================
               AI Chatbot – Frontend (Full chức năng, ưu tiên render answer_html)
               - Giữ nguyên: anti-spam FE, gợi ý, token, modal, counter, Enter/Shift+Enter...
               ========================= */
        (function() {
            'use strict';

            // ===== API endpoints =====
            const CHAT_API_URL = '{{ url('/api/chat') }}';
            const SUGGESTIONS_API_URL = '{{ url('/api/export-qa') }}';
            const SAVE_INFO_API_URL = '{{ url('/api/luu-nguoi-dung') }}';

            // ===== DOM refs =====
            const chatBox = document.getElementById('chatBox');
            const textarea = document.getElementById('userInput');
            const sendBtn = document.getElementById('sendBtn');
            const charEl = document.getElementById('charCount');
            const suggestBox = document.getElementById('suggestBox');

            const infoModal = document.getElementById('infoModal');
            const infoForm = document.getElementById('infoForm');
            const modalCloseBtn = document.querySelector('.modal-close-btn');

            // ===== State =====
            let suggestions = [];
            let conversationToken = localStorage.getItem('conversation_token') || null;
            let hasProvidedInfo = localStorage.getItem('has_provided_info') === 'true';
            let isTyping = false;
            let enterLock = false;

            const MAX_FREE_QUESTIONS = 0; // 0 = tắt gating phía FE
            let askedCount = 0;

            // Giới hạn từ/ký tự
            const MAX_WORDS = 500;
            const MAX_CHARS = 1500;

            // Anti-spam FE
            const COOLDOWN_MS = 1500;
            const QUICK_WINDOW_MS = 10000;
            const QUICK_LIMIT = 6;
            let lastAskAt = 0;
            let windowStart = Date.now();
            let asksInWindow = 0;
            let lastMsgHash = '';
            let duplicateCount = 0;

            // ===== Utils =====
            const getCSRF = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function escapeHtml(s = '') {
                return String(s).replace(/[&<>"']/g, m => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [m]));
            }

            // Fallback khi BE chưa trả answer_html
            function toSafeHtmlFromText(text = '') {
                return escapeHtml(text || '')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // hỗ trợ **bold** cơ bản
                    .replace(/\n/g, '<br>');
            }

            // NEW: addMessage ưu tiên html đã render từ BE
            function addMessage(payload, sender, note = null) {
                // payload: { html?: string, text?: string }
                const msg = document.createElement('div');
                msg.className = `message ${sender}`;

                let inner = '';
                if (payload && typeof payload.html === 'string' && payload.html.trim() !== '') {
                    inner = `<div class="msg-html">${payload.html}</div>`;
                } else {
                    const t = (payload && payload.text) ? payload.text : '';
                    inner = `<div class="msg-text">${toSafeHtmlFromText(t)}</div>`;
                }

                if (note) {
                    inner += `<div class="note">${escapeHtml(note).replace(/\n/g,'<br>')}</div>`;
                }

                msg.innerHTML = inner;
                chatBox.appendChild(msg);
                chatBox.scrollTop = chatBox.scrollHeight;
                return msg;
            }

            const _norm = s => (s || '').replace(/\s+/g, ' ').trim().toLowerCase();
            const countWords = s => (String(s).trim().match(/\S+/g) || []).length;
            const trimToWords = (s, max) => {
                const t = String(s).trim().split(/\s+/);
                return t.length <= max ? s : t.slice(0, max).join(' ');
            };

            function showTyping() {
                if (!isTyping) {
                    addMessage({
                        text: 'Đang trả lời...'
                    }, 'bot typing');
                    isTyping = true;
                }
            }

            function hideTyping() {
                const el = chatBox.querySelector('.bot.typing');
                if (el) el.remove();
                isTyping = false;
            }

            function showInfoModal() {
                infoModal.style.display = 'flex';
            }

            function renderInfoButton(messageElement) {
                if (!hasProvidedInfo && messageElement) {
                    const infoBtn = document.createElement('button');
                    infoBtn.textContent = 'Nhận tư vấn chi tiết';
                    infoBtn.className = 'info-form-btn';
                    infoBtn.onclick = showInfoModal;
                    messageElement.appendChild(infoBtn);
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            }

            // ===== Composer helpers =====
            function autoResize() {
                textarea.style.height = 'auto';
                const max = 160; // khớp CSS
                textarea.style.height = Math.min(textarea.scrollHeight, max) + 'px';
            }

            function updateComposerUI() {
                // enforce limits
                let v = textarea.value;
                if (v.length > MAX_CHARS) v = v.slice(0, MAX_CHARS);
                if (countWords(v) > MAX_WORDS) v = trimToWords(v, MAX_WORDS);
                if (v !== textarea.value) {
                    const pos = textarea.selectionStart ?? v.length;
                    textarea.value = v;
                    try {
                        textarea.setSelectionRange(pos, pos);
                    } catch (_) {}
                }

                // counter + states
                const len = textarea.value.length;
                charEl.textContent = len;
                document.querySelector('.composer .counter').classList.toggle('too-long', len > MAX_CHARS);
                sendBtn.disabled = (len === 0) || (len > MAX_CHARS) || isTyping;

                autoResize();
            }

            // ===== Suggestions =====
            async function fetchSuggestions() {
                try {
                    const res = await fetch(SUGGESTIONS_API_URL, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    suggestions = Array.isArray(data) ?
                        data.map(x => typeof x === 'string' ? x : (x?.cau_hoi ?? '')).filter(Boolean) : [];
                } catch (e) {
                    console.error('Could not fetch suggestions:', e);
                }
            }

            function showSuggestions() {
                const input = (textarea.value || '').toLowerCase().trim();
                suggestBox.innerHTML = '';
                if (!input) return (suggestBox.style.display = 'none');
                const filtered = suggestions.filter(s => s.toLowerCase().includes(input)).slice(0, 8);
                if (!filtered.length) return (suggestBox.style.display = 'none');

                filtered.forEach(text => {
                    const div = document.createElement('div');
                    div.className = 'suggest-item';
                    div.textContent = text;
                    div.onclick = () => {
                        textarea.value = text;
                        suggestBox.style.display = 'none';
                        updateComposerUI();
                    };
                    suggestBox.appendChild(div);
                });
                suggestBox.style.display = 'block';
            }

            // ===== Anti-spam guard (FE) =====
            function canSendNow(question) {
                const now = Date.now();
                // Cooldown
                if (now - lastAskAt < COOLDOWN_MS) {
                    const waitS = Math.ceil((COOLDOWN_MS - (now - lastAskAt)) / 1000);
                    addMessage({
                        text: `⏳ Bạn đang gửi hơi nhanh, vui lòng chờ ${waitS}s rồi hỏi tiếp nhé.`
                    }, 'bot');
                    return false;
                }
                // Window 10s
                if (now - windowStart > QUICK_WINDOW_MS) {
                    windowStart = now;
                    asksInWindow = 0;
                }
                if (asksInWindow >= QUICK_LIMIT) {
                    addMessage({
                        text: `🚦 Giới hạn tốc độ: tối đa ${QUICK_LIMIT} câu hỏi trong ${QUICK_WINDOW_MS/1000}s. Vui lòng đợi một chút.`
                    }, 'bot');
                    return false;
                }
                // Duplicate
                const h = _norm(question);
                if (h === lastMsgHash) {
                    duplicateCount++;
                    if (duplicateCount >= 2) {
                        addMessage({
                            text: '🔁 Bạn vừa gửi câu này rồi. Thử diễn đạt khác hoặc bổ sung chi tiết nhé.'
                        }, 'bot');
                        return false;
                    }
                } else {
                    lastMsgHash = h;
                    duplicateCount = 0;
                }
                return true;
            }

            // ===== ASK =====
            async function ask() {
                const question = (textarea.value || '').trim();
                if (!question) return;

                // FE gating (tuỳ chọn)
                if (MAX_FREE_QUESTIONS > 0 && !hasProvidedInfo && askedCount >= MAX_FREE_QUESTIONS) {
                    showInfoModal();
                    addMessage({
                        text: '🔒 Vui lòng cung cấp thông tin để tiếp tục trao đổi chi tiết nhé.'
                    }, 'bot');
                    return;
                }

                // enforce limits lần cuối
                const w = countWords(question);
                if (w > MAX_WORDS || question.length > MAX_CHARS) {
                    addMessage({
                        text: `❗️Giới hạn: tối đa ${MAX_WORDS} từ và ${MAX_CHARS} ký tự. Bạn đang có ${w} từ, ${question.length} ký tự.`
                    }, 'bot');
                    return;
                }

                // FE anti-spam
                if (!canSendNow(question)) return;

                addMessage({
                    text: question
                }, 'user');
                textarea.value = '';
                suggestBox.style.display = 'none';
                updateComposerUI();
                showTyping();

                const body = new URLSearchParams({
                    question,
                    conversation_token: conversationToken || ''
                });

                try {
                    const res = await fetch(CHAT_API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCSRF()
                        },
                        body
                    });

                    if (res.status === 429) {
                        hideTyping();
                        const retryAfter = res.headers.get('Retry-After');
                        addMessage({
                            text: `⚠️ Bạn hỏi quá nhanh. ${retryAfter ? `Thử lại sau ${retryAfter}s.` : 'Vui lòng chờ rồi thử lại.'}`
                        }, 'bot');
                        return;
                    }
                    if (!res.ok) {
                        let msg = `Lỗi API: ${res.status}`;
                        try {
                            const e = await res.json();
                            msg += ` - ${e?.message || res.statusText}`;
                        } catch {}
                        hideTyping();
                        addMessage({
                            text: msg
                        }, 'bot');
                        return;
                    }

                    const result = await res.json();
                    hideTyping();

                    // Ưu tiên answer_html từ BE
                    const botText = result?.answer ?? 'Mình đã nhận được câu hỏi của bạn.';
                    const botHtml = result?.answer_html ?? '';
                    const botNote = result?.note ?? null;

                    const el = addMessage({
                        html: botHtml,
                        text: botText
                    }, 'bot', botNote);

                    if (result?.conversation_token) {
                        conversationToken = result.conversation_token;
                        localStorage.setItem('conversation_token', conversationToken);
                    }

                    if (result?.need_info && !hasProvidedInfo) {
                        renderInfoButton(el);
                        showInfoModal();
                    } else {
                        renderInfoButton(el);
                    }

                    asksInWindow++;
                    lastAskAt = Date.now();
                    askedCount++;

                } catch (err) {
                    console.error('Lỗi:', err);
                    hideTyping();
                    addMessage({
                        text: '❌ Lỗi kết nối hoặc không thể xử lý yêu cầu.'
                    }, 'bot');
                } finally {
                    updateComposerUI();
                    textarea.focus();
                }
            }

            // ===== Info form =====
            infoForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = document.getElementById('userName')?.value?.trim() || '';
                const email = document.getElementById('userEmail')?.value?.trim() || '';
                const phone = document.getElementById('userPhone')?.value?.trim() || '';
                const address = document.getElementById('userAddress')?.value?.trim() || '';
                const birthYear = document.getElementById('userBirthYear')?.value?.trim() || '';

                if (!name || !email || !phone || !address || !birthYear) {
                    addMessage({
                        text: 'Vui lòng điền đầy đủ thông tin.'
                    }, 'bot');
                    return;
                }

                try {
                    const res = await fetch(SAVE_INFO_API_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCSRF()
                        },
                        body: JSON.stringify({
                            name,
                            email,
                            phone,
                            address,
                            birth_year: birthYear,
                            conversation_token: conversationToken || ''
                        })
                    });

                    if (!res.ok) {
                        addMessage({
                            text: 'Có lỗi xảy ra khi gửi thông tin, vui lòng thử lại.'
                        }, 'bot');
                        return;
                    }

                    infoModal.style.display = 'none';
                    infoForm.reset();
                    addMessage({
                        text: 'Cảm ơn bạn! Chúng tôi đã nhận được thông tin và sẽ liên hệ lại sớm nhất có thể.'
                    }, 'bot');
                    hasProvidedInfo = true;
                    localStorage.setItem('has_provided_info', 'true');
                    document.querySelectorAll('.info-form-btn').forEach(b => b.remove());
                } catch (e) {
                    console.error('Lỗi khi gửi thông tin:', e);
                    addMessage({
                        text: 'Lỗi kết nối khi gửi thông tin.'
                    }, 'bot');
                }
            });

            // ===== Modal close =====
            modalCloseBtn?.addEventListener('click', () => {
                infoModal.style.display = 'none';
            });
            window.addEventListener('click', (e) => {
                if (e.target === infoModal) infoModal.style.display = 'none';
            });

            // ===== Events =====
            textarea.addEventListener('input', () => {
                updateComposerUI();
                showSuggestions();
            });
            textarea.addEventListener('paste', () => setTimeout(() => {
                updateComposerUI();
                showSuggestions();
            }, 0));

            // Enter để gửi, Shift+Enter xuống dòng
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (enterLock) return;
                    enterLock = true;
                    Promise.resolve(ask()).finally(() => enterLock = false);
                }
            });

            sendBtn.addEventListener('click', () => {
                if (enterLock) return;
                enterLock = true;
                Promise.resolve(ask()).finally(() => enterLock = false);
            });

            // ===== Init =====
            document.addEventListener('DOMContentLoaded', () => {
                fetchSuggestions();
                updateComposerUI();
            });

            // Expose ask() nếu cần
            window.ask = ask;
        })();
    </script>
</body>

</html>
