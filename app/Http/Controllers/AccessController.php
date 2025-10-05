<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccessController extends Controller
{
    // 1. Lấy giao diện trang biểu đồ
    public function index()
    {
        return view('chart.index');
    }
    public function log()
    {
        return view('chart.log');
    }
}
