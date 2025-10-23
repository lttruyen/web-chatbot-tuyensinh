<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Models\apiKey;
use App\Models\CuocHoiThoai;
use App\Models\CauHoi;
use App\Models\CauTraLoiTam;
use App\Models\LogCauHoi;
use HTMLPurifier;
use HTMLPurifier_Config;
// Phần render HTML 
// use League\CommonMark\Environment\Environment;
// use League\CommonMark\CommonMarkConverter;
// use League\CommonMark\Extension\Table\TableExtension;
// use League\CommonMark\Extension\Autolink\AutolinkExtension;
// use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
// use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;



class ChatController extends Controller
{
    // 1. Hàm xử lý hỏi đáp từ người dùng 
    public function chat(Request $request)
    {
        $t0    = microtime(true);
        $reqId = (string) Str::uuid();

        Log::info('[CHAT] Request received', [
            'req_id'    => $reqId,
            'ip'        => $request->ip(),
            'ua'        => $request->userAgent(),
            'input'     => $request->all(),
        ]);
        // ===== CORS =====
        $corsHeaders = [
            'Access-Control-Allow-Origin'      => $request->headers->get('Origin', '*'),
            'Access-Control-Allow-Credentials' => 'true',
            'Vary'                              => 'Origin',
            'Cache-Control'                     => 'no-store, no-cache, must-revalidate',
            'Access-Control-Allow-Headers'      => $request->headers->get('Access-Control-Request-Headers', 'Content-Type, Authorization'),
            'Access-Control-Allow-Methods'      => 'GET, POST, OPTIONS',
        ];
        if ($request->isMethod('OPTIONS')) {
            return response('', 204, $corsHeaders);
        }

        // ===== Validate (thêm min/max để tránh payload rác) =====
        try {
            $request->validate([
                'question'           => 'required|string|min:2|max:1000',
                'conversation_token' => 'nullable|string|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CHAT] Validation failed', ['req_id' => $reqId, 'errors' => $e->errors()]);
            return Response::json([
                'error'   => 'Invalid request data.',
                'details' => $e->errors(),
                'req_id'  => $reqId,
            ], 422, $corsHeaders);
        }

        $questionRaw = trim((string) $request->input('question', ''));
        if ($questionRaw === '') {
            return Response::json([
                'error'  => 'Empty question.',
                'req_id' => $reqId,
            ], 422, $corsHeaders);
        }

        // ===== Config chống spam =====
        $COOLDOWN_MS    = 1200; // tối thiểu 1.2s giữa 2 lần hỏi
        $WINDOW_SEC     = 10;   // cửa sổ trượt 10s
        $WINDOW_LIMIT   = 6;    // tối đa 6 câu/10s
        $DUP_EXPIRE_SEC = 20;   // nhớ câu trùng trong 20s
        $ASK_GATING_MAX = 0;    // ép điền info sau 3 câu (0 = tắt)

        // ===== Anti-spam (inner guard) =====
        $token  = (string) $request->input('conversation_token', '');
        if ($token === '') $token = (string) Str::uuid(); // luôn có token để FE giữ lại
        $ip     = (string) $request->ip();
        $ua     = substr((string) $request->userAgent(), 0, 120);
        $keyRaw = $token ?: $ip;
        $baseKey = 'chat:rl:' . sha1($keyRaw . '|' . $ua);

        $tooMany = function (int $retryAfterSec, string $msg) use ($corsHeaders) {
            return Response::json(['message' => $msg], 429, $corsHeaders + ['Retry-After' => $retryAfterSec]);
        };

        // Cooldown giữa 2 lần hỏi
        $nowMs   = (int) floor(microtime(true) * 1000);
        $lastKey = $baseKey . ':last';
        $lastTs  = (int) (Cache::get($lastKey) ?? 0);
        if ($lastTs && ($nowMs - $lastTs) < $COOLDOWN_MS) {
            $leftMs = $COOLDOWN_MS - ($nowMs - $lastTs);
            $leftS  = max(1, (int) ceil($leftMs / 1000));
            return $tooMany($leftS, 'Bạn đang gửi hơi nhanh. Vui lòng chờ giây lát.');
        }
        Cache::put($lastKey, $nowMs, now()->addSeconds(30));

        // Cửa sổ trượt 10s: đếm + tính Retry-After không cần Redis
        $bucketCntKey = $baseKey . ':cnt';
        $bucketExpKey = $baseKey . ':exp';
        $nowSec = time();
        $expTs  = (int) (Cache::get($bucketExpKey) ?? 0);
        $cnt    = (int) (Cache::get($bucketCntKey) ?? 0);

        if ($expTs <= $nowSec) {
            // reset cửa sổ
            Cache::put($bucketCntKey, 1, now()->addSeconds($WINDOW_SEC));
            Cache::put($bucketExpKey, $nowSec + $WINDOW_SEC, now()->addSeconds($WINDOW_SEC + 1));
        } else {
            $cnt++;
            Cache::put($bucketCntKey, $cnt, now()->addSeconds(max(1, $expTs - $nowSec)));
            if ($cnt > $WINDOW_LIMIT) {
                $retry = max(1, $expTs - $nowSec);
                return $tooMany($retry, 'Giới hạn tốc độ: vui lòng đợi một chút.');
            }
        }

        // Chặn lặp lại nội dung liền kề (duplicate)
        $normQuestion = preg_replace('/\s+/u', ' ', mb_strtolower($questionRaw));
        $hash  = sha1($normQuestion);
        $dupKey = $baseKey . ':dup';
        $prev = Cache::get($dupKey);
        if ($prev === $hash) {
            return $tooMany(5, 'Nội dung trùng lặp. Hãy thử diễn đạt khác nhé.');
        }
        Cache::put($dupKey, $hash, now()->addSeconds($DUP_EXPIRE_SEC));

        // (Tuỳ chọn) Ép cung cấp thông tin sau X câu hỏi
        $providedKey = 'chat:info:provided:' . $keyRaw;
        $askedKey    = 'chat:asked:' . $keyRaw;
        $askedCount  = (int) (Cache::get($askedKey) ?? 0);
        if (!Cache::get($providedKey) && $ASK_GATING_MAX > 0 && $askedCount >= $ASK_GATING_MAX) {
            return Response::json([
                'answer'             => '🔒 Vui lòng cung cấp thông tin để tiếp tục được tư vấn chi tiết.',
                'conversation_token' => $token,
                'need_info'          => true,
                'req_id'             => $reqId,
            ], 200, $corsHeaders);
        }
        Cache::put($askedKey, $askedCount + 1, now()->addHours(1));

        // ===== Conversation token (STATLESS) & đối tượng cuộc hội thoại =====
        $cuocHoiThoai = CuocHoiThoai::firstOrCreate(['token' => $token]);

        // ===== Time budget cho retrieval/paraphrase =====
        $budgetMs = 600; // ví dụ 600ms cho phần tìm kiếm & paraphrase
        $nowMsFn = function () use ($t0) {
            return (microtime(true) - $t0) * 1000;
        };

        // ===== Chuẩn hoá trước khi embed / truy vấn =====
        $qCanon = $this->canonicalizeForEmbedding($questionRaw);

        // ===== Embed nhanh 1 biến thể trước =====
        $variants = [$qCanon];
        $variantEmbeds = [];
        try {
            $vec = $this->getEmbeddingFromGemini($qCanon, 9000); // timeout ngắn
            if (is_array($vec)) $variantEmbeds[] = $this->l2normalize($vec);
        } catch (\Throwable $e) {
            Log::warning('[CHAT] First embed failed', ['req_id' => $reqId, 'msg' => $e->getMessage()]);
        }

        // ===== Retrieval lần 1 (từ cache đã chuẩn hoá) =====
        $questions = $this->getAllQuestionsCached(); // cache 30 phút
        $ensemble  = $this->getTopMatchesEnsemble($variants, $variantEmbeds, $questions, 6, 0.60);

        // Chuẩn bị output
        $matched     = false;
        $matchScore  = null;
        $confidence  = 0.0;
        $answer      = null;
        $origin      = 'free'; // 'db' | 'rag' | 'free'
        $note        = 'Đây là câu trả lời do Gemini AI tạo ra.';

        // ===== Fast-path =====
        $s1 = $ensemble[0]['score'] ?? 0.0;
        $s2 = $ensemble[1]['score'] ?? 0.0;
        $gap = max(0.0, $s1 - $s2);

        if (!empty($ensemble)) {
            $top1  = $ensemble[0];
            $item1 = $top1['item'];
            $dbA1  = $item1['answer'] ?? '';
            $dbQ1  = $item1['q'] ?? '';

            if ($dbA1 !== '' && ($s1 >= 0.87 || ($s1 >= 0.80 && $gap >= 0.08))) {
                $answer     = $dbA1;
                $matched    = true;
                $matchScore = $s1;
                $confidence = min(1.0, 0.6 + 0.4 * $s1);
                $note       = 'Đã dùng dữ liệu nội bộ (match cao).';
                $origin     = 'db';
            }
        }

        // ===== Paraphrase nếu cần & còn budget =====
        $needParaphrase = (!$matched) && (empty($ensemble) || $s1 < 0.78);
        if ($needParaphrase && $nowMsFn() <= $budgetMs) {
            $paraphrases    = $this->generateParaphrases($qCanon, 2);
            $variants       = $this->buildQueryVariants($qCanon, $paraphrases);
            $variantEmbeds  = $this->embedVariantsFast($variants);
            $ensemble       = $this->getTopMatchesEnsemble($variants, $variantEmbeds, $questions, 6, 0.60);

            $s1 = $ensemble[0]['score'] ?? 0.0;
            $s2 = $ensemble[1]['score'] ?? 0.0;
            $gap = max(0.0, $s1 - $s2);

            if (!empty($ensemble)) {
                $top1  = $ensemble[0];
                $item1 = $top1['item'];
                $dbA1  = $item1['answer'] ?? '';
                $dbQ1  = $item1['q'] ?? '';
                if ($dbA1 !== '' && ($s1 >= 0.87 || ($s1 >= 0.80 && $gap >= 0.08))) {
                    $answer     = $dbA1;
                    $matched    = true;
                    $matchScore = $s1;
                    $confidence = min(1.0, 0.6 + 0.4 * $s1);
                    $note       = 'Đã dùng dữ liệu nội bộ (match cao).';
                    $origin     = 'db';
                }
            }
        }

        // ===== Judge vùng xám =====
        if (!$matched && !empty($ensemble)) {
            $top1  = $ensemble[0];
            $item1 = $top1['item'];
            $dbA1  = $item1['answer'] ?? '';
            $dbQ1  = $item1['q'] ?? '';
            if ($dbA1 !== '' && $s1 >= 0.72 && $s1 < 0.84 && $gap < 0.12) {
                $judge = $this->judgeDbAnswerFitness($qCanon, $dbQ1, $dbA1);
                if ($judge['use'] === true && $judge['confidence'] >= 0.55) {
                    $answer     = $dbA1;
                    $matched    = true;
                    $matchScore = $s1;
                    $confidence = (0.5 * $s1) + (0.5 * $judge['confidence']);
                    $note       = 'Đã dùng dữ liệu nội bộ (qua bộ lọc).';
                    $origin     = 'db';
                }
            }
        }

        // ===== RAG hybrid =====
        if (!$matched && !empty($ensemble)) {
            $contexts = [];
            foreach ($ensemble as $cand) {
                $it = $cand['item'];
                $a  = $it['answers'][0] ?? ($it['answer'] ?? '');
                $q  = $it['q'] ?? '';
                if ($a !== '') $contexts[] = "Q: " . $q . "\nA: " . $a;
                if (count($contexts) >= 3) break;
            }
            if (!empty($contexts)) {
                $rag = $this->callGeminiWithContextHybrid($qCanon, $contexts, $cuocHoiThoai->id);
                if ($this->isNoDataAnswer($rag)) {
                    $answer     = $rag;
                    $matched    = false;
                    $confidence = 0.30;
                    $note       = 'Chưa đủ dữ liệu nội bộ; đã lưu câu hỏi để bổ sung.';
                    $origin     = 'free';
                } else {
                    $answer     = $rag;
                    $matched    = true;
                    $confidence = min(0.85, 0.45 + 0.4 * ($ensemble[0]['score'] ?? 0) + 0.02 * count($contexts));
                    $note       = 'Đã dùng dữ liệu nội bộ (RAG hybrid).';
                    $origin     = 'rag';
                }
            }
        }

        // ===== Sinh tự do nếu vẫn chưa có =====
        if ($answer === null) {
            $answer = $this->callGemini($qCanon, $cuocHoiThoai->id)
                ?? '[Hệ thống] Hiện tại chưa thể tạo câu trả lời. Vui lòng thử lại sau.';
            $matched    = false;
            $confidence = 0.25;
            $note       = 'Đây là câu trả lời do Gemini AI tạo ra.';
            $origin     = 'free';
        }

        $finalAnswer = $answer; // không polish thêm

        // ===== Lưu log / tạm =====
        try {
            if ($confidence < 0.60 || !$matched || $this->isNoDataAnswer($finalAnswer)) {
                CauTraLoiTam::create([
                    'id_cuoc_hoi_thoai' => $cuocHoiThoai->id,
                    'cau_hoi'           => $questionRaw,
                    'cau_tra_loi'       => $finalAnswer,
                ]);
            }

            LogCauHoi::create([
                'id_cuoc_hoi_thoai' => $cuocHoiThoai->id,
                'cau_hoi'           => $questionRaw,
                'cau_tra_loi'       => $finalAnswer,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[CHAT] Save logs failed (soft)', ['req_id' => $reqId, 'msg' => $e->getMessage()]);
        }
        // Sau khi có $finalAnswer và $origin
        $answerHtml = null;

        // - Nếu là dữ liệu nội bộ đã soạn bằng TinyMCE (thường đã là HTML): decode + purify
        if ($origin === 'db' && $this->isHtmlLike($finalAnswer)) {
            $answerHtml = $this->purifyHtml(html_entity_decode($finalAnswer, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        } else {
            // - Nếu là AI tự sinh (free/rag) hoặc là plain text → beautify + purify
            if (!$this->isHtmlLike($finalAnswer)) {
                $answerHtml = $this->purifyHtml($this->beautifyAiText($finalAnswer));
            } else {
                $answerHtml = $this->purifyHtml(html_entity_decode($finalAnswer, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }
        $durationMs = round((microtime(true) - $t0) * 1000, 1);
        Log::info('[CHAT] Response ready', [
            'req_id'       => $reqId,
            'duration_ms'  => $durationMs,
            'matched'      => $matched,
            'match_score'  => $matchScore,
            'confidence'   => $confidence,
            'origin'       => $origin,
            'note'         => $note,
            'answer_snip'  => mb_substr($finalAnswer, 0, 200), // log 200 ký tự đầu để tránh quá dài
        ]);
        // ===== Response =====
        return Response::json([
            'answer'             => $finalAnswer,
            'answer_html'        => $answerHtml,   // <— FE sẽ ưu tiên field này
            'conversation_token' => $token,
            'matched'            => $matched,
            'match_score'        => $matchScore,
            'confidence'         => round($confidence, 3),
            'note'               => $note,
            'origin'             => $origin,
            'req_id'             => $reqId,
        ], 200, $corsHeaders);
    }

    // 2 DB helpers (CACHED, TTL dài + dữ liệu đã xử lý)
    private function getAllQuestionsCached(): array
    {
        return Cache::remember('qa_with_embed_v1_norm_tokens', 600, function () {
            $rows = CauHoi::with([
                // KHÔNG orderByDesc('id') trên pivot vì pivot không có cột id
                'cauHoiCauTraLoi',
                // Load thêm cột thời gian để tự sort trong PHP
                'cauHoiCauTraLoi.cauTraLoi' => function ($q) {
                    $q->select(['id', 'cau_tra_loi', 'updated_at', 'created_at']);
                },
            ])
                ->whereNotNull('embedding')
                ->orderByDesc('updated_at')   // bảng cau_hoi: ok
                ->orderByDesc('id')           // bảng cau_hoi: ok
                ->get();

            $seenKeys = [];
            $out = [];

            foreach ($rows as $q) {
                $vec = json_decode($q->embedding, true);
                if (!is_array($vec)) continue;

                // Loại trùng theo nội dung câu hỏi đã chuẩn hoá
                $key = $this->normalizeQuestionKey((string)$q->cau_hoi);
                if (isset($seenKeys[$key])) continue;
                $seenKeys[$key] = true;

                // Thu thập các câu trả lời kèm mốc thời gian để sort trong PHP
                $arr = [];
                foreach ($q->cauHoiCauTraLoi as $ln) {
                    if (!$ln || !$ln->cauTraLoi) continue;
                    $aModel = $ln->cauTraLoi;
                    $text   = (string) $aModel->cau_tra_loi;
                    if ($text === '') continue;

                    $t = $aModel->updated_at ?? $aModel->created_at ?? null;
                    $ts = $t ? strtotime((string)$t) : 0;
                    $arr[] = [
                        'text' => $text,
                        'ts'   => $ts,
                        'id'   => (int) ($aModel->id ?? 0),
                    ];
                }

                // Sort: mới nhất trước (theo timestamp, rồi theo id)
                usort($arr, function ($x, $y) {
                    if ($y['ts'] === $x['ts']) return $y['id'] <=> $x['id'];
                    return $y['ts'] <=> $x['ts'];
                });

                // Lấy danh sách answer giữ nguyên thứ tự sau khi sort, loại trùng theo nội dung
                $answers = [];
                foreach ($arr as $r) {
                    if (!in_array($r['text'], $answers, true)) {
                        $answers[] = $r['text'];
                    }
                    if (count($answers) >= 3) break; // đủ cho RAG
                }

                $out[] = [
                    'id'             => (int)$q->id,
                    'q'              => (string)$q->cau_hoi,
                    'embedding_norm' => $this->l2normalize(array_map('floatval', $vec)),
                    'tokens'         => $this->tokenizeForOverlap((string)$q->cau_hoi),
                    'answer'         => $answers[0] ?? '',           // luôn là bản mới nhất
                    'answers'        => $answers,                    // top vài bản gần nhất
                    'updated_at'     => optional($q->updated_at)->toDateTimeString(),
                ];
            }

            return $out;
        });
    }

    // 3. Khoá loại trùng: chuẩn hoá giống logic embed để gộp “câu hỏi giống nhau”
    private function normalizeQuestionKey(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = $this->canonicalizeForEmbedding($t);
        return preg_replace('/\s+/u', ' ', $t);
    }

    // 4. ENSEMBLE matching với dữ liệu đã cache (vector norm + tokens)
    private function getTopMatchesEnsemble(array $variants, array $variantEmbeds, array $questions, int $k = 6, float $minScore = 0.60): array
    {
        $out = [];
        $vtokens = $this->tokenizeForOverlap($variants[0] ?? '');

        foreach ($questions as $item) {
            $dbNorm = $item['embedding_norm'] ?? null;
            if (!$dbNorm || !is_array($dbNorm)) continue;

            // 1) Cosine: chọn max theo biến thể
            $maxCos = 0.0;
            foreach ($variantEmbeds as $qvec) {
                $len = min(count($qvec), count($dbNorm));
                if ($len <= 0) continue;
                $cos = $this->cosineSimilarity(
                    array_slice($qvec, 0, $len),
                    array_slice($dbNorm, 0, $len)
                );
                if ($cos > $maxCos) $maxCos = $cos;
            }

            // 2) Levenshtein: max giữa các biến thể & câu hỏi DB
            $maxLev = 0.0;
            foreach ($variants as $v) {
                $lev = $this->levenshteinSimilarity($v, (string)$item['q']);
                if ($lev > $maxLev) $maxLev = $lev;
            }

            // 3) Keyword overlap (dùng tokens đã cache)
            $overlap = $this->tokenOverlapFast($vtokens, $item['tokens'] ?? []);

            // Trọng số
            $score = (0.65 * $maxCos) + (0.25 * $maxLev) + (0.10 * $overlap);

            if ($score >= $minScore) {
                $out[] = ['item' => $item, 'score' => $score];
            }
        }

        usort($out, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($out, 0, $k);
    }

    // 5. Keyword overlap (dùng tokens đã cache) 
    private function tokenOverlapFast(array $a, array $b): float
    {
        if (!$a || !$b) return 0.0;
        $ia = array_intersect($a, $b);
        $u  = array_unique(array_merge($a, $b));
        return count($u) ? count($ia) / count($u) : 0.0;
    }

    private function normalizeText(string $t): string
    {
        $t = preg_replace('/\s+/u', ' ', trim($t));
        return mb_strtolower($t);
    }

    private function tokenizeForOverlap(string $t): array
    {
        $t = $this->normalizeText($t);
        $t = preg_replace('/[^a-zàáảãạăằắẳẵặâầấẩẫậèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợpùúủũụưừứửữựỳýỷỹỵđ\s]/u', ' ', $t);
        $parts = preg_split('/\s+/u', trim($t));
        $stop = ['là', 'ở', 'có', 'của', 'về', 'và', 'hoặc', 'như', 'bao', 'nhiêu', 'nào', 'gì', 'theo', 'tại', 'cho', 'đến', 'trong', 'khi', 'năm', 'ngày'];
        $res = [];
        foreach ($parts as $p) {
            if ($p === '' || in_array($p, $stop, true)) continue;
            $res[] = $p;
        }
        return array_values(array_unique($res));
    }

    private function cosineSimilarity($vecA, $vecB): float
    {
        if (!is_array($vecA) || !is_array($vecB)) return 0.0;
        $n = min(count($vecA), count($vecB));
        if ($n === 0) return 0.0;
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $a = (float) $vecA[$i];
            $b = (float) $vecB[$i];
            $dot += $a * $b;
            $na  += $a * $a;
            $nb  += $b * $b;
        }
        if ($na == 0.0 || $nb == 0.0) return 0.0;
        return $dot / (sqrt($na) * sqrt($nb));
    }

    private function l2normalize(array $v): array
    {
        $sum = 0.0;
        foreach ($v as $x) {
            $sum += ((float)$x) * ((float)$x);
        }
        $norm = sqrt($sum);
        if ($norm <= 0) return $v;
        foreach ($v as $i => $x) {
            $v[$i] = (float)$x / $norm;
        }
        return $v;
    }

    private function levenshteinSimilarity(string $a, string $b): float
    {
        $na = $this->normalizeText($a);
        $nb = $this->normalizeText($b);
        $maxLen = max(mb_strlen($na), mb_strlen($nb));
        if ($maxLen === 0) return 1.0;
        $lev = levenshtein($na, $nb);
        return 1.0 - min(1.0, $lev / $maxLen);
    }

    // 6. SYNONYMS & PARAPHRASES ======
    private function canonicalizeForEmbedding(string $text): string
    {
        $t = preg_replace('/\s+/u', ' ', trim($text));
        foreach ($this->getSynonymRules() as $rule) {
            $t = preg_replace($rule['pattern'], $rule['replace'], $t);
        }
        return preg_replace('/\s+/u', ' ', trim($t));
    }

    // 7. map dữ liệu TVU
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
            // Chuẩn hoá biến thể dấu/khoảng trắng → “đại học trà vinh”
            ['pattern' => '/(?<![\p{L}\p{N}])đại\s*học\s*tr(?:à|a)\s*vinh(?![\p{L}\p{N}])/iu', 'replace' => 'đại học trà vinh'],
        ];
    }
    // 8. Nội dung prompt format lại câu trả lời  
    private function generateParaphrases(string $q, int $max = 2): array
    {
        try {
            $prompt = "Viết tối đa $max cách diễn đạt khác cho câu hỏi sau, mỗi dòng một câu, không đánh số: \"$q\"";
            $res = $this->sendToGemini([['role' => 'user', 'parts' => [['text' => $prompt]]]], 'gemini-2.5-flash');
            if (!is_string($res) || trim($res) === '') return [];
            $lines = preg_split('/\r?\n/u', trim($res));
            $out = [];
            foreach ($lines as $ln) {
                $ln = trim(preg_replace('/^\s*[-*•\d\.\)]+/u', '', $ln));
                if ($ln !== '' && mb_strlen($ln) >= 3) $out[] = $this->canonicalizeForEmbedding($ln);
                if (count($out) >= $max) break;
            }
            return array_values(array_unique($out));
        } catch (\Throwable $e) {
            Log::warning('[CHAT] Paraphrase failed', ['msg' => $e->getMessage()]);
            return [];
        }
    }

    private function buildQueryVariants(string $canon, array $paraphrases): array
    {
        $variants = array_merge([$canon], $paraphrases);
        $variants = array_map(fn($s) => mb_substr($s, 0, 200), $variants);
        $seen = [];
        $final = [];
        foreach ($variants as $v) {
            $k = mb_strtolower($v);
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $final[] = $v;
            if (count($final) >= 4) break;
        }
        return $final;
    }

    // 9. Embed nhiều biến thể song song bằng HTTP pool
    private function embedVariantsFast(array $variants): array
    {
        if (empty($variants)) return [];
        $keys = $this->getGeminiKeys();
        $key  = $keys[0]; // dùng key đầu; nếu lỗi các call khác sẽ xoay sau
        $url  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key=' . $key;

        $responses = Http::pool(function ($pool) use ($variants, $url) {
            $reqs = [];
            foreach ($variants as $i => $v) {
                $payload = ['content' => ['parts' => [['text' => mb_substr($v, 0, 8000)]]]];
                $reqs[$i] = $pool->as("e{$i}")
                    ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                    ->timeout(9)
                    ->post($url, $payload);
            }
            return $reqs;
        });

        $out = [];
        foreach ($variants as $i => $v) {
            $resp = $responses["e{$i}"] ?? null;
            if ($resp && $resp->successful()) {
                $data = $resp->json();
                $vec  = $data['embedding']['values']
                    ?? ($data['data'][0]['embedding']['values'] ?? null)
                    ?? ($data['embeddings'][0]['values'] ?? null);
                if (is_array($vec)) $out[] = $this->l2normalize(array_map('floatval', $vec));
            }
        }
        return $out;
    }

    // ===== LLM Judge =====
    private function judgeDbAnswerFitness(string $question, string $dbQuestion, string $dbAnswer): array
    {
        $prompt = <<<TXT
        Bạn là bộ lọc tính phù hợp câu trả lời.
        - Câu hỏi người dùng: "$question"
        - Câu hỏi trong CSDL: "$dbQuestion"
        - Câu trả lời CSDL: "$dbAnswer"

        Trả đúng 1 dòng JSON:
        {"use": true|false, "confidence": 0..1, "reason": "ngắn gọn"}
        Quy tắc:
        - "use" = true chỉ khi nội dung CSDL trực tiếp và đầy đủ trả lời câu hỏi hiện tại.
        - Nếu chỉ một phần nhỏ hoặc lệch chủ đề → use=false.
        TXT;
        $res = $this->sendToGemini([['role' => 'user', 'parts' => [['text' => $prompt]]]], 'gemini-2.5-flash');
        $out = ['use' => false, 'confidence' => 0.0, 'reason' => ''];
        if (is_string($res)) {
            $res = trim($res);
            $json = null;
            if (preg_match('/\{.*\}/s', $res, $m)) $json = $m[0];
            $data = $json ? json_decode($json, true) : json_decode($res, true);
            if (is_array($data)) {
                $out['use']        = (bool)($data['use'] ?? false);
                $out['confidence'] = max(0.0, min(1.0, (float)($data['confidence'] ?? 0.0)));
                $out['reason']     = (string)($data['reason'] ?? '');
            } elseif (preg_match('/\b(yes|true)\b/i', $res)) {
                $out['use'] = true;
                $out['confidence'] = 0.55;
            }
        }
        return $out;
    }

    // ===== RAG HYBRID: gộp tone thân thiện NGAY TRONG PROMPT =====
    private function callGeminiWithContextHybrid(string $prompt, array $contexts, $cuocHoiThoaiId): ?string
    {
        $contextText = implode("\n---\n", array_map('strval', $contexts));
        $instruction =
            "Bạn là trợ lý cho Đại học Trà Vinh.
        Hãy trả lời TRƯỚC HẾT dựa trên NGUỒN NỘI BỘ dưới đây, không bịa.
        - Có thể tổng hợp từ nhiều cặp Q/A nội bộ để đầy đủ hơn, nhưng không thêm chi tiết không có nguồn.
        - Nếu phần nào chưa có dữ liệu nội bộ, nói rõ “[Chưa có dữ liệu nội bộ phù hợp]” rồi gợi ý hướng tham khảo chung.
        - Văn phong: thân thiện, tự nhiên, súc tích, dễ đọc; ưu tiên gạch đầu dòng khi phù hợp; xưng hô trung lập (mình/bạn).
        - Nếu đưa link, dùng URL đầy đủ bắt đầu bằng https://
        ";

        $contents   = $this->getConversationHistory($cuocHoiThoaiId);
        $contents[] = ['role' => 'user', 'parts' => [[
            'text' => $instruction
                . "\n\nNGUỒN NỘI BỘ (các cặp Q/A):\n"
                . $contextText
                . "\n\nCÂU HỎI NGƯỜI DÙNG: " . $prompt
        ]]];

        return $this->sendToGemini($contents, 'gemini-2.5-flash');
    }

    // Nhận diện "[Chưa có dữ liệu nội bộ phù hợp]"
    private function isNoDataAnswer(?string $text): bool
    {
        if (!is_string($text)) return false;
        $s = mb_strtolower(trim($text));
        $s = trim($s, "[] \t\n\r\0\x0B");
        return strpos($s, 'chưa có dữ liệu nội bộ phù hợp') !== false;
    }

    // ===== Conversation history (rút gọn để nhanh) =====
    private function getConversationHistory($cuocHoiThoaiId, int $limitTurns = 6, int $maxChars = 6000)
    {
        $contents = [];
        if (!$cuocHoiThoaiId) return $contents;

        $logs = LogCauHoi::where('id_cuoc_hoi_thoai', $cuocHoiThoaiId)
            ->orderByDesc('id')
            ->limit($limitTurns)
            ->get()
            ->reverse();

        foreach ($logs as $log) {
            $contents[] = ['role' => 'user',  'parts' => [['text' => (string) $log->cau_hoi]]];
            $contents[] = ['role' => 'model', 'parts' => [['text' => (string) $log->cau_tra_loi]]];
        }

        // Cắt bớt theo ký tự (giữ phần cuối)
        $total = 0;
        $kept  = [];
        for ($i = count($contents) - 1; $i >= 0; $i--) {
            $chunk = $contents[$i];
            $len   = mb_strlen(json_encode($chunk, JSON_UNESCAPED_UNICODE), 'UTF-8');
            if ($total + $len > $maxChars) break;
            array_unshift($kept, $chunk);
            $total += $len;
        }
        return $kept;
    }
    // 10. Dùng API lưu trữ trong DB 
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

    // ===== Embedding (rotate keys on 429/5xx) =====
    private function getEmbeddingFromGemini(string $text, int $timeoutMs = 9000): array
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
                    ->connectTimeout(2)
                    ->timeout(max(1, (int) round($timeoutMs / 1000)))
                    ->post($url . '?key=' . $key, $payload);

                if ($resp->successful()) {
                    $data = $resp->json();
                    $vec  = $data['embedding']['values']
                        ?? ($data['data'][0]['embedding']['values'] ?? null)
                        ?? ($data['embeddings'][0]['values'] ?? null);
                    if (!is_array($vec)) throw new \RuntimeException('Unexpected embedding response shape.');
                    return array_values(array_map('floatval', $vec));
                }

                $status = $resp->status();
                if ($status === 429 || ($status >= 500 && $status <= 599)) {
                    Log::warning('[Embedding] rotate key', ['status' => $status, 'key_index' => $idx]);
                    usleep(120 * 1000); // 0.12s
                    continue;
                }
                $lastErr = new \RuntimeException("Embedding API error: HTTP {$status}");
                break;
            } catch (\Throwable $e) {
                $lastErr = $e;
                Log::warning('[Embedding] exception, rotate key', ['key_index' => $idx, 'msg' => $e->getMessage()]);
                usleep(100 * 1000);
                continue;
            }
        }
        throw new \RuntimeException('Failed to get embedding from Gemini: ' . ($lastErr?->getMessage() ?? 'unknown'));
    }

    // ===== Text generation (rotate keys on 429/5xx) =====
    private function sendToGemini(array $contents, string $model = 'gemini-2.5-flash'): ?string
    {
        $keys = $this->getGeminiKeys();
        $base = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        foreach ($keys as $idx => $key) {
            try {
                $resp = Http::withHeaders([
                    'Content-Type'   => 'application/json',
                    'Accept'         => 'application/json',
                ])
                    ->connectTimeout(2)
                    ->timeout(12)
                    ->post($base . '?key=' . $key, ['contents' => $contents]);

                if ($resp->successful()) {
                    $parts = $resp->json('candidates.0.content.parts') ?? [];
                    if (is_array($parts) && $parts) {
                        $texts = [];
                        foreach ($parts as $p) if (isset($p['text']) && is_string($p['text'])) $texts[] = $p['text'];
                        $joined = trim(implode("\n", $texts));
                        return $joined !== '' ? $joined : '[Gemini] Không có phản hồi.';
                    }
                    $one = $resp->json('candidates.0.content.parts.0.text');
                    return (is_string($one) && $one !== '') ? $one : '[Gemini] Không có phản hồi.';
                }

                $status = $resp->status();
                if ($status === 429 || ($status >= 500 && $status <= 599)) {
                    Log::warning('[Generate] rotate key', ['status' => $status, 'key_index' => $idx]);
                    usleep(120 * 1000);
                    continue;
                }
                Log::warning('[Generate] non-retryable', ['status' => $status]);
                return null;
            } catch (\Throwable $e) {
                Log::warning('[Generate] exception, rotate key', ['key_index' => $idx, 'msg' => $e->getMessage()]);
                usleep(100 * 1000);
                continue;
            }
        }
        return null;
    }

    // ===== Free generation (đã gộp tone thân thiện) =====
    private function callGemini(string $prompt, $cuocHoiThoaiId): ?string
    {
        $instruction =
            "Bạn là trợ lý cho Đại học Trà Vinh.
            Văn phong: thân thiện, súc tích, ưu tiên gạch đầu dòng, tiêu đề ngắn.
            Khi phù hợp dùng danh sách (-, 1.), đoạn ngắn (≤3 câu/đoạn), highlight **Lưu ý:** rõ ràng.
            Nếu đưa link, dùng URL đầy đủ bắt đầu bằng https://";
        $contents   = $this->getConversationHistory($cuocHoiThoaiId);
        $contents[] = ['role' => 'user', 'parts' => [[
            'text' => $instruction . "\n\nCÂU HỎI: " . $prompt
        ]]];
        return $this->sendToGemini($contents, 'gemini-2.5-flash');
    }

    // Nhận diện chuỗi có dấu hiệu HTML
    private function isHtmlLike(string $s): bool
    {
        return (bool) preg_match('/<[^>]+>/', $s);
    }
    // Chuẩn hóa & render HTML an toàn từ answer gốc (giữ màu)

    private function purifyHtml(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();

        $config->set('Cache.SerializerPath', storage_path('app/purifier'));
        $config->set('Cache.SerializerPermissions', 0755);

        $config->set('HTML.Allowed', implode(',', [
            'p,div,span,ul,ol,li,br,hr,blockquote,pre,code',
            'strong,em,b,i,u',
            'table,thead,tbody,tr,td,th',
            'h1,h2,h3,h4,h5,h6',
            'a[href|title|target|rel]',
            'img[src|alt|title|width|height]', // nếu bạn muốn hiển thị ảnh
        ]));

        $config->set('HTML.AllowedAttributes', implode(',', [
            'a.href',
            'a.title',
            'a.target',
            'a.rel',
            'span.style',
            'p.style',
            'div.style',
            'td.style',
            'th.style',
            'blockquote.style',
            'img.src',
            'img.alt',
            'img.title',
            'img.width',
            'img.height',
        ]));

        $config->set('CSS.AllowedProperties', [
            'color',
            'background-color',
            'text-align',
            'width',
            'height',
            'max-width'
        ]);

        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'tel' => true,
            'data' => true, // nếu TinyMCE có dán ảnh base64; bỏ nếu không dùng
        ]);

        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);

        $def = $config->getHTMLDefinition(true);
        $def->addAttribute('a', 'target', new \HTMLPurifier_AttrDef_Enum(['_blank', '_self', '_parent', '_top']));
        $def->addAttribute('a', 'rel',    new \HTMLPurifier_AttrDef_Text());

        $purifier = new \HTMLPurifier($config);
        $clean = $purifier->purify($html);

        // ép link mở tab mới + rel an toàn
        $clean = preg_replace_callback('/<a\b([^>]*)>/i', function ($m) {
            $attrs = $m[1] ?? '';
            if (!preg_match('/\btarget\s*=\s*"_blank"/i', $attrs)) {
                $attrs = preg_replace('/\btarget\s*=\s*"[^"]*"/i', '', $attrs) . ' target="_blank"';
            }
            if (preg_match('/\brel\s*=\s*"([^"]*)"/i', $attrs, $rm)) {
                $rels = array_map('trim', explode(' ', strtolower($rm[1])));
                foreach (['nofollow', 'noopener', 'noreferrer'] as $need) if (!in_array($need, $rels, true)) $rels[] = $need;
                $attrs = preg_replace('/\brel\s*=\s*"[^"]*"/i', ' rel="' . implode(' ', $rels) . '"', $attrs);
            } else {
                $attrs .= ' rel="nofollow noopener noreferrer"';
            }
            return '<a' . $attrs . '>';
        }, $clean);

        return $clean;
    }

    private function beautifyAiText(string $text): string
    {
        // Chuẩn hóa xuống dòng, escape trước để tránh XSS rồi chèn thẻ
        $s = trim($text);
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $esc = htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Code block ```...```
        $esc = preg_replace_callback('/```([\s\S]*?)```/u', function ($m) {
            $code = trim($m[1]);
            return '<pre><code>' . $code . '</code></pre>';
        }, $esc);

        // Inline code `...`
        $esc = preg_replace('/`([^`\n]+)`/u', '<code>$1</code>', $esc);

        // Bold / Italic
        $esc = preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $esc);
        // tránh bắt list marker "* " (đã xử lý ở phần list)
        $esc = preg_replace('/(?<!\w)\*([^*\n]+)\*(?!\w)/u', '<em>$1</em>', $esc);

        // Quote: dòng bắt đầu bằng >
        $lines = explode("\n", $esc);
        $out = [];
        $inUl = false;
        $inOl = false;
        $inBlockquote = false;

        $flushList = function () use (&$out, &$inUl, &$inOl) {
            if ($inUl) {
                $out[] = '</ul>';
                $inUl = false;
            }
            if ($inOl) {
                $out[] = '</ol>';
                $inOl = false;
            }
        };
        $startUl = function () use (&$out, &$inUl, &$inOl, $flushList) {
            if ($inOl) {
                $out[] = '</ol>';
                $inOl = false;
            }
            if (!$inUl) {
                $out[] = '<ul>';
                $inUl = true;
            }
        };
        $startOl = function () use (&$out, &$inUl, &$inOl, $flushList) {
            if ($inUl) {
                $out[] = '</ul>';
                $inUl = false;
            }
            if (!$inOl) {
                $out[] = '<ol>';
                $inOl = true;
            }
        };
        $endBlockquote = function () use (&$out, &$inBlockquote) {
            if ($inBlockquote) {
                $out[] = '</blockquote>';
                $inBlockquote = false;
            }
        };

        foreach ($lines as $ln) {
            $trim = trim($ln);

            // Bỏ qua dòng đã là codeblock
            if (preg_match('/^<\/?pre>|^<\/?code>$/i', $trim)) {
                $out[] = $ln; // giữ nguyên
                continue;
            }

            // Nhóm list: - / * / +
            if (preg_match('/^\s*[-*+]\s+(.+)/u', $trim, $m)) {
                $endBlockquote();
                $startUl();
                $out[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Nhóm list số: 1. 2. ...
            if (preg_match('/^\s*\d+\.\s+(.+)/u', $trim, $m)) {
                $endBlockquote();
                $startOl();
                $out[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Quote bắt đầu bằng >
            if (preg_match('/^&gt;\s*(.+)/u', $trim, $m)) {
                $flushList();
                if (!$inBlockquote) {
                    $out[] = '<blockquote>';
                    $inBlockquote = true;
                }
                $out[] = '<p>' . $m[1] . '</p>';
                continue;
            }

            // Dòng trống → kết thúc list/blockquote, tạo ngắt đoạn
            if ($trim === '') {
                $flushList();
                $endBlockquote();
                $out[] = '';
                continue;
            }

            // Dòng thường → kết thúc list/blockquote, bọc <p>
            $flushList();
            $endBlockquote();
            $out[] = '<p>' . $trim . '</p>';
        }
        // đóng thẻ mở còn lại
        if ($inUl) $out[] = '</ul>';
        if ($inOl) $out[] = '</ol>';
        if ($inBlockquote) $out[] = '</blockquote>';

        $html = implode("\n", $out);

        // Linkify http/https
        $html = preg_replace(
            '#(?<!["\'])(https?://[^\s<]+)#u',
            '<a href="$1" target="_blank" rel="nofollow noopener noreferrer">$1</a>',
            $html
        );

        // Callout: Lưu ý:/Ghi chú:/Cảnh báo:/Note:/Warning:
        $html = preg_replace_callback(
            '/<(p)>(\s*(Lưu ý|Luu y|Ghi chú|Ghi chu|Cảnh báo|Canh bao|Note|Warning)\s*:)(.*?)<\/p>/iu',
            function ($m) {
                $label = strip_tags($m[2]);
                $body  = trim($m[4]);
                return '<p><span style="color:#e03e2d;background-color:#fbeeb8;padding:0 .2em;border-radius:.2em;"><strong>'
                    . $label .
                    '</strong></span> ' . $body . '</p>';
            },
            $html
        );

        return $html;
    }
    // Xóa câu trả lời tạm thời 
    public function deleteTLTam($id)
    {
        try {
            $deleted = CauTraLoiTam::find($id)?->delete();
            if ($deleted) {
                return response()->json(['message' => 'Xóa câu trả lời tạm thời thành công!'], 200);
            } else {
                return response()->json(['error' => 'Không tìm thấy câu trả lời tạm thời để xóa.'], 404);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Có lỗi xảy ra khi xóa.'], 500);
        }
    }
}
