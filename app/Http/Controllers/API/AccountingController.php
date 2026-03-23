<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AccountingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('module:finance');
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = PaymentTransaction::query();
        
        if (!$user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $perPage = $request->get('per_page', 20);
        $transactions = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'transactions' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'current_page' => $transactions->currentPage(),
            ]
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = PaymentTransaction::query();
        
        if (!$user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $stats = [
            'total_amount' => (float) $query->sum('amount'),
            'count' => $query->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $stats
        ]);
    }
}
