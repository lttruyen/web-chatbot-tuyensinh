<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\TaiKhoan;

class InjectQuyen
{
    public function handle(Request $request, Closure $next, ...$quyens)
    {
        $username = session('username');
        $roleSess = session('role'); // string
        if (!$username || !$roleSess) {
            return $this->kick($request);
        }
        // Lấy quyền hiện tại trong DB (1 giá trị)
        $roleDb = TaiKhoan::where('username', $username)->value('quyen');
        if ($roleDb === null || strcasecmp($roleDb, $roleSess) !== 0) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return $this->kick($request, 'Quyền đã thay đổi, vui lòng đăng nhập lại.');
        }
        // Parse danh sách quyền được phép từ tham số middleware
        $allowed = collect($quyens)
            ->flatMap(fn($s) => preg_split('/[|,]/', (string)$s, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn($s) => strtolower(trim($s)))
            ->unique()
            ->all();
        app()->instance('quyen', $roleDb);
        if (!in_array(strtolower($roleDb), $allowed, true)) {
            return redirect()->route('admin.index')->with('tb_danger', 'Bạn không có quyền truy cập vào trang này.');
            // API: return response()->json(['message'=>'Forbidden'],403);
        }
        return $next($request);
    }


    private function kick(Request $request, string $msg = 'Vui lòng đăng nhập.')
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Unauthenticated or role changed'], 401);
        }
        // tránh loop: đừng gắn middleware này vào chính route('trangchu')/login
        return redirect()
            ->route('trangchu') // đổi sang route('login') nếu bạn có trang login riêng
            ->with('force_login_modal', true)
            ->with('login_warning', $msg);
    }
}
