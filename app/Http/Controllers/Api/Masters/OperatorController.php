<?php

namespace App\Http\Controllers\Api\Masters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operator;
use App\Models\Tenant;
use App\Helpers\ApiResponse;
use App\Http\Resources\OperatorResource;
use Illuminate\Validation\ValidationException;

class OperatorController extends Controller
{
    public function index(Request $request)
    {
        // バリデーション（空・0・負数も弾く）
        try {
            $request->validate([
                'tenant_id' => ['required', 'integer', 'min:1'],
                'page' => 'integer',
                'per_page' => 'integer',
                'q' => 'string|nullable',
                'sort' => 'string|nullable',
            ], [
                'tenant_id.required' => 'Tenant ID not found',
                'tenant_id.integer' => 'Tenant ID not found',
                'tenant_id.min' => 'Tenant ID not found',
            ]);
        } catch (ValidationException $e) {
            return ApiResponse::error($e->errors()['tenant_id'] ?? ['Tenant ID not found']);
        }

        // DBに存在する tenant_id かチェック
        if (!Tenant::where('id', $request->tenant_id)->exists()) {
            return ApiResponse::error(['Tenant ID not found']);
        }

        // クエリ作成
        $query = Operator::where('tenant_id', $request->tenant_id);

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
            $operators = $query->paginate($perPage);
            $data = OperatorResource::collection($operators);
            $meta = [
                'page' => $operators->currentPage(),
                'per_page' => $operators->perPage(),
                'total' => $operators->total(),
            ];
        } else {
            // 全件取得
            $operators = $query->get();
            $data = OperatorResource::collection($operators);
            $meta = [
                'page' => 1,
                'per_page' => $operators->count(),
                'total' => $operators->count(),
            ];
        }

        return ApiResponse::success($data, $meta);
    }
}

