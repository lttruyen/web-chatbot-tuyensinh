<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\apiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CauHoi;
use App\Models\CauTraLoi;
use App\Models\CauHoiCauTraLoi;
use App\Models\CauTraLoiTam;
use App\Models\CuocHoiThoai;
use App\Models\LogCauHoi;
use App\Models\smtp;
use App\Models\TaiKhoan;
use App\Models\ThongTinNguoiDung;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class ApiDuLieuController extends Controller
{
    // ================= API: Lấy dữ liệu ==================
    // 1. Lấy danh sách câu hỏi, câu trả lời
    public function getCauHoi()
    {
        $qaList = CauHoi::with('cauHoiCauTraLoi.cauTraLoi')
            ->get()
            ->map(function ($item) {
                $traLoi = optional($item->cauHoiCauTraLoi->first())->cauTraLoi;

                return [
                    'id_cau_hoi'    => $item->id,
                    'cau_hoi'       => $item->cau_hoi,
                    'id_cau_tra_loi' => $traLoi->id ?? null,
                    'cau_tra_loi'   => $traLoi->cau_tra_loi ?? null,
                ];
            })
            ->filter(function ($item) {
                return !empty($item['cau_tra_loi']);
            })
            ->values();

        return response()->json($qaList);
    }
    // 2. Lấy danh sách log câu hỏi gần giống nhau
    public function getLogCauHoi()
    {
        $logs = LogCauHoi::orderByDesc('created_at')->get([
            'id',
            'id_cuoc_hoi_thoai',
            'cau_hoi',
            'cau_tra_loi',
            'created_at'
        ]);

        return response()->json($logs);
    }
    // 3. Lấy danh sách câu trả lời tạm
    public function getCauTraLoiTam()
    {
        $data = CauTraLoiTam::orderByDesc('created_at')->get([
            'id',
            'id_cuoc_hoi_thoai',
            'cau_hoi',
            'cau_tra_loi',
            'created_at'
        ]);

        return response()->json($data);
    }
    // 4. Lấy danh sách thông tin người dùng
    public function getUserInfor()
    {
        $data = ThongTinNguoiDung::orderByDesc('created_at')->get([
            'ten_nguoi_dung',
            'id_cuoc_hoi_thoai',
            'email',
            'so_dien_thoai',
            'dia_chi',
            'nam_sinh',
            'created_at'
        ]);

        return response()->json($data);
    }
    // 5. Lấy danh sách tài khoản
    public function getAccount()
    {
        $data = TaiKhoan::where('username', '!=', 'admin')->orderByDesc('created_at')->get([
            'id',
            'username',
            'password',
            'ho_ten',
            'quyen',
            'created_at'
        ]);

        return response()->json($data);
    }
    // 6. Lấy danh sách SMTP
    public function getSmtp()
    {
        $logs = smtp::orderByDesc('mac_dinh', 'desc')->get([
            'id',
            'smtp',
            'matkhau',
            'mac_dinh',
            'created_at',
            'updated_at'
        ]);
        return response()->json($logs);
    }
    // 7. Lấy danh sách api-key
    public function getApikey()
    {
        $logs = apiKey::orderByDesc('mac_dinh', 'desc')->get([
            'id',
            'key_name',
            'mac_dinh',
            'created_at',
            'updated_at'
        ]);
        return response()->json($logs);
    }
    // 8. Lấy dữ liệu biểu đồ truy cập
    public function getAccess()
    {
        $logs = CuocHoiThoai::get([
            'danh_gia',
            'created_at',
            'updated_at'
        ]);
        return response()->json($logs);
    }
}
