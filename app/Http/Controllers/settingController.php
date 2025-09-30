<?php

namespace App\Http\Controllers;

use App\Models\smtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class settingController extends Controller
{
    public function index()
    {
        return view('admin.smtp');
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'smtp'     => ['required', 'email', 'max:200', 'unique:smtp,smtp'],
            'matkhau'  => ['required', 'string', 'min:6'],
            'mac_dinh' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($data) {
            $isDefault = (bool)($data['mac_dinh'] ?? false);

            if ($isDefault) {
                // Chỉ giữ 1 bản ghi mặc định
                Smtp::query()->update(['mac_dinh' => 0]);
            }

            // cần $fillable trong Model: ['smtp','matkhau','mac_dinh']
            $item = Smtp::create([
                'smtp'     => trim($data['smtp']),
                'matkhau'  => $data['matkhau'],
                'mac_dinh' => $isDefault ? 1 : 0,
            ]);

            return response()->json([
                'message' => 'Tạo SMTP thành công',
                'data'    => $item,
            ], 201);
        });
    }
    public function updateByRequest(Request $request)
    {
        $id = (int) $request->input('id');

        $data = $request->validate([
            'id'       => ['required', 'integer', 'exists:smtp,id'],
            'smtp'     => ['required', 'email', 'max:200', Rule::unique('smtp', 'smtp')->ignore($id)],
            'matkhau'  => ['nullable', 'string', 'min:6'],
            'mac_dinh' => ['nullable', 'boolean'],
        ]);

        $smtp = Smtp::findOrFail($id);
        $before = $smtp->only(['smtp', 'mac_dinh']); // log tối giản, không log mật khẩu

        DB::transaction(function () use ($smtp, $data) {
            // Cập nhật cơ bản
            $smtp->smtp = trim($data['smtp']);
            if (!empty($data['matkhau'])) {
                $smtp->matkhau = $data['matkhau'];
            }

            // Xử lý "mặc định"
            if ($smtp->mac_dinh) {
                // Đang là mặc định -> luôn giữ mặc định
                $smtp->mac_dinh = 1;
            } else {
                // Chưa mặc định
                if (!empty($data['mac_dinh'])) {
                    // Nếu tick mặc định, reset các bản khác
                    Smtp::where('id', '!=', $smtp->id)->update(['mac_dinh' => 0]);
                    $smtp->mac_dinh = 1;
                } else {
                    $smtp->mac_dinh = 0;
                }
            }

            $smtp->save();
        });

        return response()->json([
            'message' => 'Cập nhật SMTP thành công',
            'data'    => $smtp,
        ]);
    }
    public function destroy(Request $request, $id)
    {
        $item = Smtp::find($id);
        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return DB::transaction(function () use ($item, $request) {
            $wasDefault  = (bool) $item->mac_dinh;
            $deletedId   = $item->id;
            $reassignedId = null;

            // Xoá bản ghi
            $item->delete();

            if ($wasDefault) {
                // Ưu tiên fallback_id nếu FE gửi (query hoặc body)
                $fallbackId = (int) ($request->query('fallback_id') ?? $request->input('fallback_id'));
                $candidate = null;

                if ($fallbackId && $fallbackId !== $deletedId) {
                    $candidate = Smtp::where('id', $fallbackId)->first();
                }

                // Nếu không có fallback hợp lệ thì chọn bản ghi còn lại mới nhất
                if (!$candidate) {
                    $candidate = Smtp::where('id', '<>', $deletedId)
                        ->orderByDesc('id')
                        ->first();
                }

                if ($candidate) {
                    // Đảm bảo chỉ 1 mặc định
                    Smtp::query()->update(['mac_dinh' => 0]);
                    $candidate->update(['mac_dinh' => 1]);
                    $reassignedId = $candidate->id;
                }
            }
            return response()->json([
                'message'       => 'Deleted',
                'deleted_id'    => $deletedId,
                'was_default'   => $wasDefault,
                'reassigned_to' => $reassignedId, // null nếu không còn bản ghi nào
            ], 200);
        });
    }
}
