<?php

namespace App\Http\Controllers;

use App\Models\CuocHoiThoai;
use App\Models\LogCauHoi;
use Illuminate\Http\Request;
use App\Models\ThongTinNguoiDung;
use Illuminate\Support\Facades\Validator;
// Phần excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class nguoiDungController extends Controller
{
    // 1. Lấy giao diện lưu thông tin người dùng  
     public function index()
    {
        return view('admin.user');
    }
    // 2. Xử lý lưu thông tin người dùng 
    public function store(Request $request)
    {
        // 2) Validate dữ liệu đầu vào (bổ sung conversation_token)
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255',
            'email'              => 'required|string|email|max:255',
            'phone'              => 'required|string|max:20',
            'address'            => 'required|string|max:255',
            'birth_year'         => 'required|integer|min:1950|max:2030',
            'conversation_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // 3) Tìm id cuộc hội thoại từ token
            $token = $request->input('conversation_token');
            $cuocHoiThoai = CuocHoiThoai::where('token', $token)->first();
            if (!$cuocHoiThoai) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy cuộc hội thoại.',
                ], 404);
            }

            // 4) Tạo bản ghi thông tin người dùng
            ThongTinNguoiDung::create([
                'ten_nguoi_dung'    => $request->input('name'),
                'email'             => $request->input('email'),
                'so_dien_thoai'     => $request->input('phone'),
                'dia_chi'           => $request->input('address'),
                'nam_sinh'          => $request->input('birth_year'),
                'id_cuoc_hoi_thoai' => $cuocHoiThoai->id,
            ]);

            // (Tuỳ chọn) tạo session liên kết user-info tại đây nếu cần
            // session(['user_info_saved' => true]);


            return response()->json([
                'success' => true,
                'message' => 'Đã lưu thông tin cá nhân thành công!',
            ], 201);
        } catch (\Throwable $e) {
            // 5) Log lỗi kèm trace để bắt đúng điểm nổ 500
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lưu thông tin. Vui lòng thử lại.',
            ], 500);
        }
    }
    // 3. Xuất thông tin người dùng
    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Vui lòng cung cấp đầy đủ ngày bắt đầu và ngày kết thúc.'], 400);
        }

        $users = ThongTinNguoiDung::whereBetween('created_at', [$startDate, $endDate])->get();

        if ($users->isEmpty()) {
            return response()->json(['error' => 'Không tìm thấy dữ liệu người dùng trong khoảng thời gian này.'], 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Tiêu đề cột
        $sheet->setCellValue('A1', 'STT');
        $sheet->setCellValue('B1', 'Tên người dùng');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Số điện thoại');
        $sheet->setCellValue('E1', 'Địa chỉ');
        $sheet->setCellValue('F1', 'Ngày tạo');

        // Định dạng tiêu đề
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Auto width
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Dữ liệu
        $row = 2;
        foreach ($users as $index => $user) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $user->ten_nguoi_dung ?? '');
            $sheet->setCellValue('C' . $row, $user->email ?? '');
            $sheet->setCellValue('D' . $row, $user->so_dien_thoai ?? '');
            $sheet->setCellValue('E' . $row, $user->dia_chi ?? '');
            $sheet->setCellValue('F' . $row, $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '');
            $row++;
        }

        // Viền
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A1:F' . ($row - 1))->applyFromArray($styleArray);

        // Xuất file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Danh_sach_nguoi_dung_' . $startDate . '_den_' . $endDate . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName);
    }
    // 4. Lọc câu hỏi theo người dùng 
     public function showLog(Request $request)
    {
        try {
            $data = $request->validate([
                'id_cuoc_hoi_thoai' => 'required',
            ], [
                'id_cuoc_hoi_thoai.required' => 'Thiếu id_cuoc_hoi_thoai',
            ]);
            $conversationId = $data['id_cuoc_hoi_thoai'];
            $limit = 100;
            $items = LogCauHoi::where('id_cuoc_hoi_thoai', $conversationId)
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get(['cau_hoi', 'cau_tra_loi', 'created_at']);
            return response()->json([
                'data' => $items,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Không thể tải lịch sử cuộc hội thoại.',
            ], 500);
        }
    }
}
