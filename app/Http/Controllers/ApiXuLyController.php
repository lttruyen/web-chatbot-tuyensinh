<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\apiKey;
use Illuminate\Http\Request;
// use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
// use App\Models\CuocHoiThoai;
use App\Models\CauHoi;
use App\Models\CauTraLoi;
use App\Models\CauHoiCauTraLoi;
use App\Models\CauTraLoiTam;
use App\Models\CuocHoiThoai;
use App\Models\LogCauHoi;
use App\Models\smtp;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

// Phần excel

class ApiXuLyController extends Controller
{
    /**
     * Chuẩn hoá văn bản trước khi tạo embedding.
     * - Loại bỏ khoảng trắng thừa.
     * - Chuẩn hoá các từ đồng nghĩa (theo bảng quy tắc).
     * - Giới hạn độ dài tối đa 8000 ký tự (sau chuẩn hoá).
     */
    private function canonicalizeForEmbedding(string $text): string
    {
        $t = preg_replace('/\s+/u', ' ', trim($text));
        foreach ($this->getSynonymRules() as $rule) {
            $t = preg_replace($rule['pattern'], $rule['replace'], $t);
        }
        return preg_replace('/\s+/u', ' ', trim($t));
    }
    /**
     * Quy tắc chuẩn hoá từ đồng nghĩa.
     * - pattern: regex (PCRE) với các tham số u (unicode), i (ignore case)
     * - replace: chuỗi thay thế
     * - Chú ý: pattern có sử dụng negative lookbehind/lookahead để tránh thay thế nhầm trong từ khác.
     */
    private function getSynonymRules(): array
    {
        return [
            // dhtv / đhtv / tvu  → đại học trà vinh
            ['pattern' => '/(?<![\p{L}\p{N}])(?:dhtv|đhtv|tvu)(?![\p{L}\p{N}])/iu', 'replace' => 'đại học trà vinh'],

            // (trường )đh|dh trà/a vinh  → đại học trà vinh
            ['pattern' => '/(?<![\p{L}\p{N}])(?:trường\s*)?(?:đh|dh)\s*tr(?:à|a)\s*vinh(?![\p{L}\p{N}])/iu', 'replace' => 'đại học trà vinh'],

            // tra vinh university | university of tra vinh  → đại học trà vinh
            ['pattern' => '/(?<![\p{L}\p{N}])(?:tra\s*vinh\s*(?:uni|university)|university\s*of\s*tra\s*vinh)(?![\p{L}\p{N}])/iu', 'replace' => 'đại học trà vinh'],

            // dai hoc tra vinh (không dấu) → đại học trà vinh
            ['pattern' => '/(?<![\p{L}\p{N}])dai\s*hoc\s*tr(?:a|à)\s*vinh(?![\p{L}\p{N}])/iu', 'replace' => 'đại học trà vinh'],

            // “đại học trà vinh” đã đúng nhưng khác khoảng trắng/biến thể dấu → chuẩn hoá lại
            ['pattern' => '/(?<![\p{L}\p{N}])đại\s*học\s*tr(?:à|a)\s*vinh(?![\p{L}\p{N}])/iu', 'replace' => 'đại học trà vinh'],
        ];
    }
    /**
     * Lấy danh sách API key Gemini từ DB hoặc biến môi trường.
     * - Trả về mảng các key (không rỗng, không trùng).
     * - Ưu tiên key mặc định (mac_dinh) trong DB, sau đó theo id mới nhất.
     * - Lưu cache 10 phút để giảm tải DB.
     * - Nếu không có key trong DB thì lấy từ biến môi trường GEMINI_API_KEY (có thể là nhiều key, phân tách dấu phẩy).
     * - Ném ngoại lệ nếu không có key nào cả.
     */
    private function getGeminiKeys(): array
    {
        // Lấy từ DB, ưu tiên key mặc định trước, sau đó theo id mới nhất lưu 10  phút
        $keys = Cache::remember('gemini_keys', 600, function () {
            return apiKey::query()
                ->whereNotNull('key_name')
                ->where('key_name', '!=', '')
                ->orderByDesc('mac_dinh')   // mặc định lên đầu
                ->orderByDesc('id')         // mới nhất trước
                ->pluck('key_name')
                ->map(fn($k) => trim($k))
                ->filter()                  // loại bỏ rỗng
                ->unique()                  // loại trùng
                ->values()
                ->all();
        });

        if (!$keys) {
            $raw = trim((string) env('GEMINI_API_KEY', ''));
            if ($raw === '') {
                throw new \RuntimeException('No Gemini API keys found in table api_key and GEMINI_API_KEY is missing.');
            }
            $keys = (strpos($raw, ',') !== false)
                ? array_map('trim', explode(',', $raw))
                : [$raw];
            $keys = array_values(array_filter($keys, fn($k) => $k !== ''));
            if (!$keys) {
                throw new \RuntimeException('GEMINI_API_KEY is empty.');
            }
        }
        return $keys;
    }
    /**
     * Gọi API Gemini để tạo embedding cho văn bản.
     * - Giới hạn văn bản tối đa 8000 ký tự (cắt bớt nếu dài hơn).
     * - Sử dụng xoay vòng API key nếu có lỗi tạm thời (429, 5xx, timeout, network error).
     * - Thời gian chờ tối đa có thể cấu hình (mặc định 12000ms).
     * - Ném ngoại lệ nếu không thành công sau khi thử tất cả key.
     * - Trả về mảng số thực (float) của vector embedding.
     */
    private function getEmbeddingFromGemini(string $text, int $timeoutMs = 12000): array
    {
        if (!is_string($text) || trim($text) === '') {
            throw new \RuntimeException('Empty text for embedding.');
        }

        $text = mb_substr($text, 0, 8000);
        $keys = $this->getGeminiKeys();

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent';
        $payload = [
            'content' => [
                'parts' => [
                    ['text' => $text],
                ],
            ],
        ];

        $lastErr = null;

        foreach ($keys as $idx => $key) {
            try {
                $resp = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                    ->timeout(max(1, (int) round($timeoutMs / 1000)))
                    ->post($url . '?key=' . $key, $payload);

                if ($resp->successful()) {
                    $data = $resp->json();
                    $vec  = $data['embedding']['values']
                        ?? ($data['data'][0]['embedding']['values'] ?? null)
                        ?? ($data['embeddings'][0]['values'] ?? null);
                    if (!is_array($vec)) {
                        Log::error('[Embedding] Unexpected response', ['json' => $data, 'key_index' => $idx]);
                        throw new \RuntimeException('Unexpected embedding response shape.');
                    }
                    return array_values(array_map('floatval', $vec));
                }

                $status = $resp->status();
                $body   = $resp->json() ?: $resp->body();

                if ($status === 429 || ($status >= 500 && $status <= 599)) {
                    Log::warning('[Embedding] Temporary error, rotate key', [
                        'status'    => $status,
                        'key_index' => $idx,
                        'body'      => $body,
                    ]);
                    usleep(200 * 1000);
                    continue;
                }

                Log::error('[Embedding] Non-retryable error', [
                    'status'    => $status,
                    'key_index' => $idx,
                    'body'      => $body,
                ]);
                $lastErr = new \RuntimeException("Embedding API error: HTTP {$status}");
                break;
            } catch (\Throwable $e) {
                Log::warning('[Embedding] Exception, rotate key', ['key_index' => $idx, 'msg' => $e->getMessage()]);
                $lastErr = $e;
                usleep(150 * 1000);
                continue;
            }
        }

        throw new \RuntimeException('Failed to get embedding from Gemini: ' . ($lastErr?->getMessage() ?? 'unknown'));
    }
    // ==================Các route lấy giao diện =================
    // 1. Lấy giao diện trang quản lý câu hỏi
    public function index()
    {
        return view('admin.chat');
    }
    // 2. Lấy giao diện trang quản lý tài khoản 
    public function account()
    {
        return view('admin.account');
    }
    // ==================Các API xử lý chức năng=================
    // A. Phần câu hỏi
    // 1. Thêm câu hỏi, trả lời mới 
    public function add(Request $request)
    {
        try {
            $request->validate([
                'question' => 'required|string',
                'answer'   => 'required|string',
            ]);

            $questionText = (string) $request->input('question');
            $answerText   = (string) $request->input('answer');

            // Chuẩn hoá câu hỏi trước khi tạo embedding (đồng bộ với ChatController)
            $canonQuestion = $this->canonicalizeForEmbedding($questionText);

            // Tạo embedding với xoay key
            $embedding = $this->getEmbeddingFromGemini($canonQuestion, 12000);
            if (!$embedding || !is_array($embedding)) {
                $msg = 'Lỗi: Không thể tạo embedding cho câu hỏi.';
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $msg], 500)
                    : back()->with('error', $msg);
            }

            DB::beginTransaction();
            try {
                // Lưu answer trước
                $cauTraLoi = CauTraLoi::create(['cau_tra_loi' => $answerText]);

                // Lưu question + embedding (JSON)
                $cauHoi = CauHoi::create([
                    'cau_hoi'   => $questionText,            // LƯU nguyên văn người nhập
                    'embedding' => json_encode($embedding),  // LƯU vector chuẩn hoá embedding
                ]);

                // Link Q-A
                CauHoiCauTraLoi::create([
                    'id_cau_hoi'     => $cauHoi->id,
                    'id_cau_tra_loi' => $cauTraLoi->id
                ]);
                // Xoá bản tạm (nếu có)
                if ($tempAnswerId = $request->input('tempAnswerId')) {
                    DB::table('cau_tra_loi_tam')->where('id', $tempAnswerId)->delete();
                }

                DB::commit();

                $msg = 'Đã thêm câu hỏi và câu trả lời thành công!';

                Cache::forget('qa_with_embed_v1_norm_tokens');

                return $request->wantsJson()
                    ? response()->json([
                        'success' => true,
                        'message' => $msg,
                        'data'    => [
                            'question' => $cauHoi,
                            'answer'   => $cauTraLoi
                        ]
                    ], 200)
                    : back()->with('success', $msg);
            } catch (\Throwable $e) {
                DB::rollBack();
                $msg = 'Lỗi khi lưu dữ liệu: ' . $e->getMessage();
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'message' => $msg], 500)
                    : back()->with('error', $msg);
            }
        } catch (\Throwable $e) {
            $msg = 'Có lỗi xảy ra: ' . $e->getMessage();
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $msg], 500)
                : back()->with('error', $msg);
        }
    }
    // 2. Cập nhật một cặp câu hỏi và câu trả lời đã có.  
    public function update(Request $request)
    {
        // Log::info('Dữ liệu nhận được cho cập nhật:', ['data' => $request->all()]);

        $request->validate([
            'id_cau_hoi'     => 'required|integer',
            'id_cau_tra_loi' => 'required|integer',
            'cau_hoi'        => 'required|string',
            'cau_tra_loi'    => 'required|string'
        ]);

        // Log::info('Dữ liệu sau khi validate:', [
        //     'id_cau_hoi'     => $request->input('id_cau_hoi'),
        //     'id_cau_tra_loi' => $request->input('id_cau_tra_loi'),
        //     'cau_hoi'        => $request->input('cau_hoi'),
        //     'cau_tra_loi'    => $request->input('cau_tra_loi')
        // ]);

        // Chuẩn hoá trước khi embed để đồng bộ với ChatController
        $canonQuestion = $this->canonicalizeForEmbedding((string) $request->input('cau_hoi'));

        try {
            $embedding = $this->getEmbeddingFromGemini($canonQuestion, 12000);
            if (!$embedding) {
                return back()->with('error', 'Lỗi: Không thể tạo embedding cho câu hỏi.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi khi tạo embedding: ' . $e->getMessage());
        }

        try {
            $updated1 = CauHoi::find($request->input('id_cau_hoi'))->update([
                'cau_hoi'   => $request->input('cau_hoi'),   // vẫn lưu nguyên văn hiển thị
                'embedding' => json_encode($embedding),      // đảm bảo lưu JSON string
                'updated_at' => now(),
            ]);

            $updated2 = CauTraLoi::find($request->input('id_cau_tra_loi'))->update([
                'cau_tra_loi' => $request->input('cau_tra_loi'),
                'updated_at'  => now(),
            ]);

            if ($updated1 && $updated2) {
                // Log::info('Cập nhật câu hỏi thành công.', ['answer_id' => $request->input('id_cau_tra_loi')]);
                Cache::forget('qa_with_embed_v1_norm_tokens');
                return response()->json(['message' => 'Cập nhật câu hỏi thành công!'], 200);
            } else {
                // Log::warning('Không có bản ghi nào được cập nhật.', ['answer_id' => $request->input('id_cau_tra_loi')]);
                return response()->json(['message' => 'Không có bản ghi nào được cập nhật.'], 200);
            }
        } catch (\Throwable $e) {
            // Log::error('Lỗi khi cập nhật câu hỏi:', ['error' => $e->getMessage(), 'request_data' => $request->all()]);
            return response()->json(['error' => 'Không thể cập nhật câu hỏi. Lỗi: ' . $e->getMessage()], 500);
        }
    }
    // 3. Xóa câu hỏi và câu trả lời 
    public function deleteCauHoi($id1, $id2)
    {
        try {
            $delete1 = CauHoi::find($id1)?->delete();
            $delete2 = CauTraLoi::find($id2)?->delete();
            if ($delete1 && $delete2) {

                Cache::forget('qa_with_embed_v1_norm_tokens');

                return response()->json(['message' => 'Xóa câu trả lời tạm thời thành công!'], 200);
            } else {
                return response()->json(['error' => 'Không tìm thấy câu trả lời tạm thời để xóa.'], 404);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Có lỗi xảy ra khi xóa.'], 500);
        }
    }
    // B. Phần tài khoản 
    // 1. Thêm mới tài khoản
    public function addAccount(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100|unique:tai_khoan,username',
            'password' => 'required|string|min:6',
            'ho_ten'   => 'required|string|max:200',
            'quyen'    => 'required|string|in:user,dev,admin',
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập',
            'username.unique'   => 'Tên đăng nhập đã tồn tại',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min'      => 'Mật khẩu phải có ít nhất 6 ký tự',
            'ho_ten.required'   => 'Vui lòng nhập họ tên',
            'quyen.required'    => 'Vui lòng chọn quyền',
        ]);

        $account = new TaiKhoan();
        $account->username = $validated['username'];
        $account->password = bcrypt($validated['password']);
        $account->ho_ten   = $validated['ho_ten'];
        $account->quyen    = $validated['quyen'];
        $account->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tạo tài khoản thành công',
                'data'    => $account
            ], 201);
        }

        return redirect()->back()->with('success', 'Tạo tài khoản thành công!');
    }
    // 2. Cập nhật tài khoản 
    public function updateAccount(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return response()->json(['message' => 'Thiếu id tài khoản cần cập nhật.'], 400);
        }

        $acc = TaiKhoan::find($id);
        if (! $acc) {
            return response()->json(['message' => 'Không tìm thấy tài khoản.'], 404);
        }

        $data = $request->validate([
            'id'       => ['required', 'integer'],
            'username' => ['required', 'string', 'max:100', Rule::unique('tai_khoan', 'username')->ignore($acc->id, 'id')],
            'password' => ['nullable', 'string', 'min:6'],
            'ho_ten'   => ['required', 'string', 'max:200'],
            'quyen'    => ['required', 'string', Rule::in(['user', 'dev', 'admin'])],
        ], [], [
            'username' => 'tên đăng nhập',
            'password' => 'mật khẩu',
            'ho_ten'   => 'họ tên',
            'quyen'    => 'quyền',
        ]);


        $acc->username = $data['username'];
        $acc->ho_ten   = $data['ho_ten'];
        $acc->quyen    = $data['quyen'];
        if (!empty($data['password'])) {
            $acc->password = Hash::make($data['password']);
        }
        $acc->save();


        return response()->json([
            'message' => 'Cập nhật tài khoản thành công.',
            'data'    => [
                'id'        => $acc->id,
                'username'  => $acc->username,
                'ho_ten'    => $acc->ho_ten,
                'quyen'     => $acc->quyen,
                'created_at' => $acc->created_at,
                'updated_at' => $acc->updated_at,
            ],
        ], 200);
    }
    // 3. Xóa tài khoản
    public function destroy($id)
    {
        $acc = TaiKhoan::find($id);
        if (! $acc) {
            return response()->json(['message' => 'Không tìm thấy tài khoản.'], 404);
        }
        $acc->delete();

        return response('OK', 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
    // C. Phần đăng nhập, đăng xuất hệ thống
    // 1. Xử lý đăng nhập
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Vui lòng nhập tài khoản',
            'password.required' => 'Vui lòng nhập mật khẩu',
        ]);
        $user = TaiKhoan::query()
            ->select(['id', 'username', 'password', 'quyen'])
            ->where('username', $validated['username'])
            ->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'Sai tài khoản hoặc mật khẩu'])
                ->with('force_login_modal', true)
                ->withInput($request->only('username'));
        }
        // Đổi session ID để chống fixation, rồi dọn các key cũ (nếu trước đó có)
        $request->session()->regenerate();
        $request->session()->forget(['login', 'role']);
        // Lưu session 
        $request->session()->put('username', $user->username);
        $request->session()->put('role', $user->quyen);

        return back()->with('login_success', true);
    }
    // 2. Đăng xuất hệ thống 
    public function logout(Request $request)
    {
        $request->session()->forget('username');
        return redirect()->back();
    }   
}
