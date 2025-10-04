<?php

namespace App\Http\Controllers;

use App\Models\apiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Throwable;


class ApiKeyController extends Controller
{
    // 1. Lấy giao diện trang quản lý API key
    public function index(Request $request)
    {
        return view('api.index');
    }

    // 2. Xử lý thêm API key mới 
    public function store(Request $request): JsonResponse
    {
        $rid = (string) Str::uuid(); // request id để lần theo log

        // Validate: catch để luôn trả JSON
        try {
            $data = $request->validate([
                'key_name' => ['required', 'string', 'max:200', Rule::unique('api_key', 'key_name')],
                'mac_dinh' => ['nullable', 'boolean'],
            ]);
        } catch (ValidationException $e) {
            return response()
                ->json([
                    'rid'     => $rid,
                    'message' => 'Validation failed',
                    'errors'  => $e->errors(),
                ], 422)
                ->header('X-Request-ID', $rid);
        }

        // Transaction + try/catch để log lỗi 500
        try {
            $item = DB::transaction(function () use ($data, $rid) {
                $isDefault = (bool)($data['mac_dinh'] ?? false);

                if ($isDefault) {
                    $affected = apiKey::query()->update(['mac_dinh' => 0]);
                    Log::info('APIKEY_STORE_RESET_DEFAULT', [
                        'rid'      => $rid,
                        'affected' => $affected,
                    ]);
                }

                $created = apiKey::create([
                    'key_name' => trim($data['key_name']),
                    'mac_dinh' => $isDefault ? 1 : 0,
                ]);

                return $created;
            });

            Cache::forget('gemini_keys');
            return response()
                ->json([
                    'rid'     => $rid,
                    'message' => 'Tạo API key thành công',
                    'data'    => $item,
                ], 201)
                ->header('X-Request-ID', $rid);
        } catch (Throwable $e) {
            // Log full để lần ra lỗi 500 (SQL, cột thiếu, fillable thiếu, v.v.)
            return response()
                ->json([
                    'rid'     => $rid,
                    'message' => 'Internal Server Error',
                ], 500)
                ->header('X-Request-ID', $rid);
        }
    }

    // 3. Xử lý cập nhật API key
    public function update(Request $request): JsonResponse
    {
        $rid = (string) Str::uuid();
        // 1) Validate ID trước để dùng cho rule unique
        $idValidator = Validator::make($request->all(), [
            'id' => ['required', 'integer', 'exists:api_key,id'],
        ]);
        if ($idValidator->fails()) {
            return response()->json([
                'rid'     => $rid,
                'message' => 'Validation failed',
                'errors'  => $idValidator->errors(),
            ], 422)->header('X-Request-ID', $rid);
        }
        $id = (int) $request->input('id');

        // 2) Validate các trường còn lại
        $validator = Validator::make($request->all(), [
            'key_name' => ['required', 'string', 'max:200', Rule::unique('api_key', 'key_name')->ignore($id, 'id')],
            'mac_dinh' => ['nullable', 'boolean'],
        ], [], [
            'key_name' => 'API key',
            'mac_dinh' => 'mặc định',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'rid'     => $rid,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)->header('X-Request-ID', $rid);
        }
        $data = $validator->validated();

        try {
            $result = DB::transaction(function () use ($id, $data, $rid) {
                $item = ApiKey::find($id); // đã tồn tại do validate
                $wasDefault = (bool) $item->mac_dinh;

                // Giá trị mặc định mới (nếu không gửi thì giữ nguyên)
                $newDefault = array_key_exists('mac_dinh', $data)
                    ? (bool) $data['mac_dinh']
                    : $wasDefault;

                // Nếu set mặc định cho bản ghi này → bỏ mặc định các bản ghi khác
                if ($newDefault) {
                    $affected = ApiKey::where('id', '<>', $id)->update(['mac_dinh' => 0]);
                } else {
                    // Nếu đang tắt mặc định trên bản ghi hiện là mặc định
                    if ($wasDefault) {
                        $otherHasDefault = ApiKey::where('id', '<>', $id)->where('mac_dinh', 1)->exists();
                        if (!$otherHasDefault) {
                            // Tự gán mặc định cho bản ghi còn lại mới nhất
                            $candidate = ApiKey::where('id', '<>', $id)->orderByDesc('id')->first();
                            if ($candidate) {
                                $candidate->update(['mac_dinh' => 1]);
                            }
                        }
                    }
                }

                // Cập nhật chính bản ghi
                $item->key_name = trim($data['key_name']);
                $item->mac_dinh = $newDefault ? 1 : 0;
                $item->save();
                // Trả về kèm trạng thái trước/sau để FE hiển thị hợp lý
                return [
                    'data'        => $item->fresh(),
                    'was_default' => $wasDefault,
                    'is_default'  => (bool) $item->mac_dinh,
                ];
            });
            Cache::forget('gemini_keys');
            return response()->json([
                'rid'     => $rid,
                'message' => 'Updated',
                'data'    => $result['data'],
                'meta'    => [
                    'was_default' => $result['was_default'],
                    'is_default'  => $result['is_default'],
                ],
            ], 200)->header('X-Request-ID', $rid);
        } catch (Throwable $e) {
            return response()->json([
                'rid'     => $rid,
                'message' => 'Internal Server Error',
            ], 500)->header('X-Request-ID', $rid);
        }
    }
    
    // 4. Xứ lý xóa API key
    public function destroy(Request $request, $id)
    {
        $item = ApiKey::find($id);
        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return DB::transaction(function () use ($item, $request) {
            $wasDefault = (bool) $item->mac_dinh;
            $deletedId  = $item->id;
            // Xóa bản ghi
            $item->delete();
            $reassignedId = null;
            if ($wasDefault) {
                // Ưu tiên fallback_id do FE gửi lên
                $fallbackId = (int) $request->input('fallback_id');
                $candidate = null;
                if ($fallbackId && $fallbackId !== $deletedId) {
                    $candidate = ApiKey::where('id', $fallbackId)->first();
                }
                // Nếu không có fallback hợp lệ thì chọn key còn lại mới nhất
                if (!$candidate) {
                    $candidate = ApiKey::where('id', '<>', $deletedId)
                        ->orderByDesc('id')
                        ->first();
                }
                if ($candidate) {
                    // Đảm bảo chỉ có 1 mặc định
                    ApiKey::query()->update(['mac_dinh' => 0]);
                    $candidate->update(['mac_dinh' => 1]);
                    $reassignedId = $candidate->id;
                }
            }

            Cache::forget('gemini_keys');
            return response()->json([
                'message'       => 'Deleted',
                'deleted_id'    => $deletedId,
                'was_default'   => $wasDefault,
                'reassigned_to' => $reassignedId,
            ], 200);
        });
    }
}
