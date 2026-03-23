<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('module:academic'); // academic module handles student management
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = Student::query();
        
        if (!$user->isSuperAdmin()) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('roll_no', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $students = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'students' => $students->items(),
            'pagination' => [
                'total' => $students->total(),
                'per_page' => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $user = auth('api')->user();
        $student = Student::findOrFail($id);

        if (!$user->isSuperAdmin() && $student->tenant_id !== $user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'student' => $student
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'roll_no' => 'required|string|max:20',
            'gender' => 'required|in:male,female,other',
            'admission_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $studentData = $request->all();
        $studentData['tenant_id'] = $user->tenant_id;
        $studentData['status'] = 'active';

        $student = Student::create($studentData);

        return response()->json([
            'success' => true,
            'message' => 'Student added successfully',
            'student' => $student
        ], 201);
    }
}
