<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Controller;

class LogController extends Controller
{
    public function index()
    {
        $logPath = storage_path('logs/laravel.log');
        $lines = [];
        if (file_exists($logPath)) {
            $lines = explode("\n", trim(file_get_contents($logPath)));
            $lines = array_slice($lines, -100); // last 100 lines
        }
        return view('admin::settings.logs', ['lines' => $lines]);
    }
    public function download()
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            return Response::download($logPath);
        }
        abort(404);
    }
}
