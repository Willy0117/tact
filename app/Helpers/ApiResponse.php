<?php
namespace App\Helpers;

class ApiResponse
{
    public static function success($data, $meta = null)
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => $meta,
            'errors' => null
        ]);
    }

    public static function error($errors, $statusCode = 400)
    {
        return response()->json([
            'status' => 'error',
            'data' => null,
            'meta' => null,
            'errors' => $errors
        ], $statusCode);
    }
}