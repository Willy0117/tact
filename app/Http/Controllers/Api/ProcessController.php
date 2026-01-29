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
        // クエリ作成
        $query = Process::where('tenant_id', $request->tenant_id)->where('disabled', 1); // ここを追加

        if ($request->q) {
            $query->where('name', 'like', "%{$request->q}%");
        }

        if ($request->sort) {
            [$field, $direction] = explode(':', $request->sort);
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('display_order', 'asc');
        }

        $perPage = $request->per_page;

        if ($perPage) {
            // ページネーションあり
            $Processes = $query->paginate($perPage);
            $data = ProcessResource::collection($Processes);
            $meta = [
                'page' => $Processes->currentPage(),
                'per_page' => $Processes->perPage(),
                'total' => $Processes->total(),
            ];
        } else {
            // 全件取得
            $Processes = $query->get();
            $data = ProcessResource::collection($Processes);
            $meta = [
                'page' => 1,
                'per_page' => $Processes->count(),
                'total' => $Processes->count(),
            ];
        }

        return ApiResponse::success($data, $meta);
    }
}
