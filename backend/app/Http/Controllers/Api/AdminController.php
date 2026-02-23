<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Interest;
use App\Models\Student;
use App\Models\WorkshopForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;

class AdminController extends Controller
{
    // ─── AUTH ───────────────────────────────────────────────────────────────

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 401);
        }

        $token = $user->createToken('admin-token')->plainTextToken;
        $user->load('department');

        return response()->json([
            'token' => $token, 
            'user' => [
                'name'          => $user->name, 
                'email'         => $user->email, 
                'role'          => $user->role,
                'department_id' => $user->department_id,
                'department_name' => $user->department ? $user->department->name : null,
                'department_logo' => $user->department ? $user->department->logo_url : null,
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    private function requireAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'غير مصرح لك بإجراء هذه العملية. هذه الصلاحية للمشرفين فقط.');
        }
    }

    // ─── DASHBOARD STATS ────────────────────────────────────────────────────
    public function getOverviewStats(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $user = $request->user();

        $formQuery = WorkshopForm::query();
        if ($user->department_id) {
            $formQuery->where('department_id', $user->department_id);
        }

        $totalForms = (clone $formQuery)->count();
        $openForms = (clone $formQuery)->where('is_open', true)->count();
        $departmentsCount = Department::count();

        // Stats only for forms they have access to
        $formIds = $formQuery->pluck('id');
        $totalStudents = Student::whereIn('form_id', $formIds)->count();
        $totalPresent = Attendance::whereIn('form_id', $formIds)->count();

        // Recent students
        $recentStudents = Student::with(['department', 'form'])
            ->whereIn('form_id', $formIds)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'form_title' => $s->form?->title,
                    'department' => $s->department?->name,
                    'stage' => $s->stage,
                    'registered_at' => $s->created_at->format('Y-m-d H:i')
                ];
            });

        return response()->json([
            'metrics' => [
                'total_forms' => $totalForms,
                'open_forms'  => $openForms,
                'total_students' => $totalStudents,
                'total_present' => $totalPresent,
                'departments_count' => $departmentsCount,
            ],
            'recent_students' => $recentStudents
        ]);
    }

    public function getChartStats(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $user = $request->user();

        // Only forms this user has access to
        $formQuery = WorkshopForm::query();
        if ($user->department_id) {
            $formQuery->where('department_id', $user->department_id);
        }
        $formIds = $formQuery->pluck('id');

        // 1. Registrations per day (last 7 days)
        $registrations = Student::whereIn('form_id', $formIds)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $data = [];
        
        // Fill exact 7 days array
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = $date;
            
            $record = $registrations->firstWhere('date', $date);
            $data[] = $record ? $record->count : 0;
        }

        // 2. Attendance Stats (Present vs Absent)
        $totalStudents = Student::whereIn('form_id', $formIds)->count();
        $totalPresent = Attendance::whereIn('form_id', $formIds)->count();
        $totalAbsent = max(0, $totalStudents - $totalPresent);

        // 3. Top Departments
        $topDepartments = Student::whereIn('form_id', $formIds)
            ->selectRaw('department_id, COUNT(*) as count')
            ->with('department')
            ->groupBy('department_id')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->department ? $row->department->name : 'غير محدد',
                    'count' => $row->count
                ];
            });

        return response()->json([
            'registrationsChart' => [
                'labels' => $labels,
                'data' => $data,
            ],
            'attendanceChart' => [
                'present' => $totalPresent,
                'absent' => $totalAbsent,
            ],
            'topDepartments' => $topDepartments
        ]);
    }

    // ─── WORKSHOP FORMS ─────────────────────────────────────────────────────

    public function getForms(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = WorkshopForm::with('department')->withCount('students')->latest();
        
        if ($user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        $forms = $query->get();
        return response()->json($forms);
    }

    public function createForm(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $user = $request->user();

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            // department_id is required ONLY if the creator is a super admin
            'department_id' => [$user->department_id ? 'nullable' : 'required', 'exists:departments,id'],
        ]);

        if ($user->department_id) {
            $validated['department_id'] = $user->department_id;
        }

        $form = WorkshopForm::create($validated);
        return response()->json($form->load('department'), 201);
    }

    public function updateForm(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        $form = WorkshopForm::findOrFail($id);
        $validated = $request->validate([
            'title'         => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'department_id' => 'sometimes|exists:departments,id',
            'is_open'       => 'sometimes|boolean',
        ]);
        $form->update($validated);
        return response()->json($form->load('department'));
    }

    public function deleteForm(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        WorkshopForm::findOrFail($id)->delete();
        return response()->json(['message' => 'تم الحذف.']);
    }

    // ─── STUDENTS ────────────────────────────────────────────────────────────

    public function getStudents(int $formId): JsonResponse
    {
        $form = WorkshopForm::findOrFail($formId);
        $students = $form->students()
            ->with(['department', 'interests', 'attendance'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id'          => $s->id,
                    'name'        => $s->name,
                    'gender'      => $s->gender,
                    'email'       => $s->email,
                    'phone'       => $s->phone,
                    'stage'       => $s->stage,
                    'study_type'  => $s->study_type,
                    'department'  => $s->department?->name,
                    'department_id' => $s->department_id,
                    'interests'   => $s->interests->pluck('name'),
                    'interest_ids' => $s->interests->pluck('id'),
                    'description' => $s->description,
                    'qr_token'    => $s->qr_token,
                    'registered_at' => $s->created_at->format('Y-m-d H:i'),
                    'attended'    => $s->attendance !== null,
                    'attended_at' => $s->attendance?->attended_at?->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'form'     => ['id' => $form->id, 'title' => $form->title, 'is_open' => $form->is_open],
            'students' => $students,
        ]);
    }

    public function updateStudent(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        $student = Student::findOrFail($id);
        $validated = $request->validate([
            'name'          => 'sometimes|string|min:3|max:150',
            'gender'        => ['sometimes', Rule::in(['male', 'female'])],
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|regex:/^[0-9]+$/|max:20',
            'stage'         => ['sometimes', 'integer', Rule::in([1, 2, 3, 4])],
            'study_type'    => ['sometimes', Rule::in(['morning', 'evening'])],
            'department_id' => 'sometimes|exists:departments,id',
            'interests'     => 'nullable|array',
            'interests.*'   => 'exists:interests,id',
            'description'   => 'nullable|string|max:1000',
        ]);

        $student->update($validated);

        if (array_key_exists('interests', $validated)) {
            $student->interests()->sync($validated['interests'] ?? []);
        }

        return response()->json(['message' => 'تم تحديث بيانات الطالب.', 'student' => $student]);
    }

    public function deleteStudent(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        Student::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الطالب بنجاح.']);
    }

    // ─── STATS ───────────────────────────────────────────────────────────────

    public function getStats(int $formId): JsonResponse
    {
        $form = WorkshopForm::withCount([
            'students',
            'attendance',
        ])->findOrFail($formId);

        $total   = $form->students_count;
        $present = $form->attendance_count;
        $absent  = $total - $present;

        // Stage distribution
        $byStage = Student::where('form_id', $formId)
            ->selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage');

        // Department distribution
        $byDept = Student::where('form_id', $formId)
            ->with('department')
            ->selectRaw('department_id, count(*) as count')
            ->groupBy('department_id')
            ->get()
            ->map(fn($r) => ['name' => $r->department?->name, 'count' => $r->count]);

        return response()->json([
            'total'        => $total,
            'present'      => $present,
            'absent'       => $absent,
            'by_stage'     => $byStage,
            'by_department'=> $byDept,
        ]);
    }

    // ─── ATTENDANCE ──────────────────────────────────────────────────────────

    public function scanAttendance(Request $request): JsonResponse
    {
        $request->validate(['qr_token' => 'required|string']);

        // Just search by the exact raw token from the QR code
        $student = Student::where('qr_token', $request->qr_token)
            ->with(['form', 'department', 'attendance'])
            ->first();

        if (!$student) {
            return response()->json(['message' => 'الطالب غير موجود.'], 404);
        }

        if ($student->attendance) {
            return response()->json([
                'message' => 'تم تسجيل حضور هذا الطالب مسبقاً.',
                'student' => $this->formatStudent($student),
                'already_attended' => true,
            ]);
        }

        Attendance::create([
            'student_id'  => $student->id,
            'form_id'     => $student->form_id,
            'attended_at' => now(),
        ]);

        $student->refresh()->load('attendance');
        
        // Broadcast event for real-time dashboard updates
        event(new \App\Events\StudentAttended($student));

        return response()->json([
            'message' => 'تم تسجيل الحضور بنجاح!',
            'student' => $this->formatStudent($student),
            'already_attended' => false,
        ]);
    }

    public function toggleAttendance(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        
        $student = Student::with('attendance')->findOrFail($id);

        if ($student->attendance) {
            // Remove attendance
            $student->attendance->delete();
            $attended = false;
            $message = 'تم إلغاء حضور الطالب.';
        } else {
            // Mark as attended
            Attendance::create([
                'student_id'  => $student->id,
                'form_id'     => $student->form_id,
                'attended_at' => now(),
            ]);
            $attended = true;
            $message = 'تم تسجيل حضور الطالب يدوياً.';
            
            // Broadcast event for real-time dashboard updates
            event(new \App\Events\StudentAttended($student));
        }

        $student->refresh()->load('attendance');

        return response()->json([
            'message' => $message,
            'attended' => $attended,
            'attended_at' => $attended ? now()->format('Y-m-d H:i') : null
        ]);
    }

    private function formatStudent(Student $student): array
    {
        return [
            'id'         => $student->id,
            'name'       => $student->name,
            'stage'      => $student->stage,
            'department' => $student->department?->name,
            'form_title' => $student->form?->title,
            'attended'   => $student->attendance !== null,
        ];
    }

    // ─── DEPARTMENTS ─────────────────────────────────────────────────────────

    public function getDepartments(): JsonResponse
    {
        return response()->json(Department::orderBy('name')->get());
    }

    public function createDepartment(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:departments',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('departments', 'public');
        }

        $dept = Department::create([
            'name' => $validated['name'],
            'logo_path' => $logoPath,
        ]);

        return response()->json($dept, 201);
    }

    public function deleteDepartment(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        try {
            Department::findOrFail($id)->delete();
            return response()->json(['message' => 'تم الحذف.']);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return response()->json(['message' => 'لا يمكن حذف هذا القسم لارتباطه بطلاب مسجلين.'], 400);
            }
            return response()->json(['message' => 'حدث خطأ غير متوقع أثناء الحذف.'], 500);
        }
    }

    // ─── INTERESTS ───────────────────────────────────────────────────────────

    public function getInterests(): JsonResponse
    {
        return response()->json(Interest::with('department')->orderBy('name')->get());
    }

    public function createInterest(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'department_id' => 'required|exists:departments,id'
        ]);
        $interest = Interest::create($validated);
        return response()->json($interest->load('department'), 201);
    }

    public function deleteInterest(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        try {
            Interest::findOrFail($id)->delete();
            return response()->json(['message' => 'تم الحذف.']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'حدث خطأ غير متوقع أثناء الحذف.'], 500);
        }
    }

    // ─── ADMIN USERS ─────────────────────────────────────────────────────────

    public function getUsers(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $user = $request->user();

        $query = User::with('department')->select('id', 'name', 'email', 'role', 'department_id');
        
        // Department admins only see their own department's users
        if ($user->department_id) {
            $query->where('department_id', $user->department_id);
        }

        return response()->json($query->orderBy('created_at')->get());
    }

    public function createUser(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $authUser = $request->user();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|string|min:6',
            'role'          => ['required', Rule::in(['admin', 'scanner'])],
            'department_id' => 'nullable|exists:departments,id',
        ]);

        // If the creator is a department admin, they can ONLY create scanners for their own department
        if ($authUser->department_id) {
            if ($validated['role'] !== 'scanner') {
                return response()->json(['message' => 'يمكن لمدير القسم إنشاء حسابات ماسح فقط.'], 403);
            }
            $validated['department_id'] = $authUser->department_id;
        }

        $validated['password'] = Hash::make($validated['password']);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->role = $validated['role'];
        if (isset($validated['department_id'])) {
            $user->department_id = $validated['department_id'];
        }
        $user->save();
        
        return response()->json($user->load('department'), 201);
    }

    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        if ($request->user()->id === $id) {
            return response()->json(['message' => 'لا يمكنك حذف حسابك الحالي.'], 400);
        }
        $userToDelete = User::findOrFail($id);
        
        // Department admins cannot delete users outside their department
        if ($request->user()->department_id && $userToDelete->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'غير مصرح لك بحذف هذا المستخدم.'], 403);
        }

        $userToDelete->delete();
        return response()->json(['message' => 'تم الحذف.']);
    }
}
