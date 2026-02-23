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

        return response()->json(['token' => $token, 'user' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role]]);
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

        $totalForms = WorkshopForm::count();
        $openForms = WorkshopForm::where('is_open', true)->count();
        $totalStudents = Student::count();
        $totalPresent = Attendance::count();
        $departmentsCount = Department::count();

        // Recent students
        $recentStudents = Student::with(['department', 'form'])
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

    // ─── WORKSHOP FORMS ─────────────────────────────────────────────────────

    public function getForms(): JsonResponse
    {
        $forms = WorkshopForm::withCount('students')->latest()->get();
        return response()->json($forms);
    }

    public function createForm(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $form = WorkshopForm::create($validated);
        return response()->json($form, 201);
    }

    public function updateForm(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        $form = WorkshopForm::findOrFail($id);
        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_open'     => 'sometimes|boolean',
        ]);
        $form->update($validated);
        return response()->json($form);
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

        return response()->json([
            'message' => 'تم تسجيل الحضور بنجاح!',
            'student' => $this->formatStudent($student),
            'already_attended' => false,
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
        $dept = Department::create($request->validate(['name' => 'required|string|max:100|unique:departments']));
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
        return response()->json(User::select('id', 'name', 'email', 'role')->orderBy('created_at')->get());
    }

    public function createUser(Request $request): JsonResponse
    {
        $this->requireAdmin($request);
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role'     => ['required', Rule::in(['admin', 'scanner'])]
        ]);
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function deleteUser(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin($request);
        if ($request->user()->id === $id) {
            return response()->json(['message' => 'لا يمكنك حذف حسابك الحالي.'], 400);
        }
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'تم الحذف.']);
    }
}
