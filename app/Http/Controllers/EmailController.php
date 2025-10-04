<?php

namespace App\Http\Controllers;

use App\Models\smtp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;

class EmailController extends Controller
{
    public function sendBulk(Request $request)
    {
        $reqId = (string) Str::uuid();

        // 1) Lấy credential SMTP từ DB (bản ghi mac_dinh = 1)
        $cred = Smtp::where('mac_dinh', 1)->first(['smtp', 'matkhau']);
        if (!$cred || !$cred->smtp || !$cred->matkhau) {
            return Response::json([
                'error'  => 'Không tìm thấy tài khoản SMTP mặc định trong DB hoặc thiếu trường smtp/matkhau.',
                'req_id' => $reqId,
            ], 500);
        }
        $dbUser = (string) $cred->smtp;
        $dbPass = (string) $cred->matkhau;

        // 2) Lấy các tham số còn lại từ config/.env (host/port/encryption…)
        $host       = config('mail.mailers.smtp.host')       ?? env('MAIL_HOST');
        $port       = config('mail.mailers.smtp.port')       ?? env('MAIL_PORT', 587);
        $encryption = config('mail.mailers.smtp.encryption') ?? env('MAIL_ENCRYPTION', 'tls');
        $timeout    = config('mail.mailers.smtp.timeout');

        // Kiểm tra đầu vào tối thiểu
        $missing = [];
        if (empty($host))     $missing[] = 'host';
        if (empty($port))     $missing[] = 'port';
        if (empty($dbUser))   $missing[] = 'username';
        if (empty($dbPass))   $missing[] = 'password';

        if ($missing) {
            return Response::json([
                'error'  => 'Thiếu cấu hình SMTP: ' . implode(', ', $missing) . '. Kiểm tra DB và .env (MAIL_HOST, MAIL_PORT, …).',
                'req_id' => $reqId,
            ], 500);
        }

        // 3) Tạo mailer động dùng cặp username/password từ DB
        $dynMailer = 'smtp_db_' . str_replace('-', '_', (string) Str::ulid());
        config([
            "mail.mailers.$dynMailer" => array_filter([
                'transport'  => 'smtp',
                'host'       => $host,
                'port'       => (int) $port,
                'encryption' => $encryption, // 'tls' hoặc 'ssl' theo .env
                'username'   => $dbUser,
                'password'   => $dbPass,
                'timeout'    => $timeout,
                // nếu bạn có các option verify_peer... trong config thì copy thêm vào đây
            ], fn($v) => $v !== null && $v !== ''),
        ]);

        // 4) Validate payload
        try {
            $data = $request->validate([
                'emails'   => 'required|array|min:1',
                'emails.*' => 'required|email:rfc', // đổi thành email:rfc,dns nếu MX ổn định
                'subject'  => 'required|string|max:200',
                'body'     => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::json([
                'error'   => 'Payload không hợp lệ.',
                'details' => $e->errors(),
                'req_id'  => $reqId,
            ], 422);
        }

        // 5) Chuẩn hoá danh sách email
        $rawList     = $data['emails'] ?? [];
        $beforeCount = is_array($rawList) ? count($rawList) : 0;
        $emails      = array_values(array_unique(array_map('mb_strtolower', $rawList)));

        $fromAddress = mb_strtolower((string) config('mail.from.address'));
        $fromName    = config('mail.from.name');

        $allowSelf   = (bool) env('MAIL_ALLOW_SELF_BCC', false);
        if (!$allowSelf || $beforeCount > 1) {
            // Không BCC chính mình (trừ khi chỉ 1 người nhận và bật allowSelf)
            $emails = array_values(array_filter($emails, fn($e) => $e !== $fromAddress));
        }

        if (empty($emails)) {
            return Response::json([
                'error'  => 'Không còn người nhận hợp lệ sau khi lọc. (Danh sách có thể trùng với FROM)',
                'req_id' => $reqId,
            ], 422);
        }

        // 6) Chuẩn bị gửi theo BCC chunk
        $toAddress   = $fromAddress ?: $emails[0];
        $chunkSize   = max(1, (int) env('MAIL_BCC_CHUNK', 50));
        $chunks      = array_chunk($emails, $chunkSize);
        $totalChunks = count($chunks);

        $baseSubject = $data['subject'];
        $bodyHtml    = $data['body'];
        $plain       = trim(preg_replace('/\s+/', ' ', strip_tags($bodyHtml))); // optional text part

        try {
            foreach ($chunks as $idx => $chunk) {
                $chunkNo = $idx + 1;

                // Lô 1 giữ nguyên; lô >=2 thêm " #2", " #3", ...
                $subject = $baseSubject;
                if ($chunkNo > 1) {
                    $suffix = ' #' . $chunkNo;
                    $maxLen = 200; // khớp rule validate
                    if (mb_strlen($subject) + mb_strlen($suffix) > $maxLen) {
                        $subject = mb_substr($subject, 0, $maxLen - mb_strlen($suffix));
                    }
                    $subject .= $suffix;
                }

                // 7) Gửi qua mailer động (dùng user/pass lấy từ DB)
                Mail::mailer($dynMailer)->send([], [], function ($message) use ($subject, $bodyHtml, $plain, $chunk, $toAddress, $fromAddress, $fromName) {
                    if ($fromAddress) {
                        $message->from($fromAddress, $fromName);
                    }
                    $message->to($toAddress)
                        ->bcc($chunk)
                        ->subject($subject)
                        ->html($bodyHtml);
                    if ($plain !== '') {
                        $message->text($plain);
                    }
                });
            }

            return Response::json([
                'ok'         => true,
                'sent_to'    => count($emails),
                'chunks'     => $totalChunks,
                'chunk_size' => $chunkSize,
                'req_id'     => $reqId,
                'mailer'     => $dynMailer,
            ], 200);
        } catch (\Throwable $e) {
            // Fallback: ghi log để không mất yêu cầu khi SMTP lỗi
            try {
                foreach ($chunks as $idx => $chunk) {
                    $chunkNo = $idx + 1;
                    $subject = $baseSubject . ($chunkNo > 1 ? (' #' . $chunkNo) : '');

                    Mail::mailer('log')->send([], [], function ($message) use ($subject, $bodyHtml, $plain, $chunk, $toAddress, $fromAddress, $fromName) {
                        if ($fromAddress) $message->from($fromAddress, $fromName);
                        $message->to($toAddress)
                            ->bcc($chunk)
                            ->subject($subject)
                            ->html($bodyHtml);
                        if ($plain !== '') {
                            $message->text($plain);
                        }
                    });
                }

                return Response::json([
                    'ok'         => false,
                    'delivery'   => 'fallback_log',
                    'reason'     => 'SMTP failed; messages were logged instead.',
                    'sent_to'    => count($emails),
                    'chunks'     => $totalChunks,
                    'req_id'     => $reqId,
                    'mailer'     => $dynMailer,
                    'debug_hint' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
                ], 202);
            } catch (\Throwable $e2) {
                return Response::json([
                    'error'  => app()->hasDebugModeEnabled()
                        ? ('Gửi email thất bại: ' . $e->getMessage())
                        : 'Gửi email thất bại. Vui lòng kiểm tra cấu hình mail.',
                    'req_id' => $reqId,
                ], 500);
            }
        }
    }
}
