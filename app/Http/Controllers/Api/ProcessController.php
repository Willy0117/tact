<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Process;
use App\Helpers\ApiResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\ProcessResource;

class ProcessController extends Controller
{
    public function index(Request $request)
    {
        // 全件取得
        $processes = Process::all();
        $data = ProcessResource::collection($processes);
        $meta = [
            'page' => null,
            'per_page' => null,
            'total' => $data->count(),
        ];

        return ApiResponse::success($data, $meta);
    }
}
