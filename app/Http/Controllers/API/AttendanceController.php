<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('module:attendance');
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = Attendance::query();
        
        if (!$user->isSuperAdmin()) {
            $query->whereHas('student', function($q) use ($user) {
                $q->where('tenant_id', $user->tenant_id);
            });
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        $attendance = $query->latest('date')->paginate(50);

        return response()->json([
            'success' => true,
            'attendance' => $attendance->items(),
            'pagination' => [
                'total' => $attendance->total(),
                'current_page' => $attendance->currentPage(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late,excused',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = auth('api')->user();
            $date = $request->date;

            foreach ($request->attendance as $record) {
                Attendance::updateOrCreate(
                    ['student_id' => $record['student_id'], 'date' => $date],
                    ['status' => $record['status'], 'marked_by' => $user->id]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark attendance'
            ], 500);
        }
    }
}
