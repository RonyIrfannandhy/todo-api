<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TodoController;

Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

Route::prefix('todos')->group(function () {
    Route::post('/', [TodoController::class, 'store']);
    Route::get('/export', [TodoController::class, 'export']);
    Route::get('/chart', [TodoController::class, 'chart']);
});

Route::get('/todos', function () {
    return response()->json([
        'message' => 'Gunakan metode POST untuk menambahkan todo.',
        'example' => [
            'title' => 'Contoh',
            'description' => 'Deskripsi todo',
            'due_date' => '2025-06-01'
        ]
    ]);
});

