<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Interest;
use App\Models\Student;
use App\Models\WorkshopForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicController extends Controller
{
    /**
     * GET /api/forms/{id}
     * Return form info + departments + interests for the registration page
     */
    public function getForm(int $id): JsonResponse
    {
        $form = WorkshopForm::where('id', $id)->where('is_open', true)->firstOrFail();

        return response()->json([
            'form'        => [
                'id'          => $form->id,
                'title'       => $form->title,
                'description' => $form->description,
            ],
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'interests'   => Interest::orderBy('name')->get(['id', 'name', 'department_id']),
        ]);
    }

    /**
     * POST /api/forms/{id}/register
     * Register a new student and return their QR token
     */
    public function register(Request $request, int $id): JsonResponse
    {
        $form = WorkshopForm::where('id', $id)->where('is_open', true)->firstOrFail();

        $validated = $request->validate([
            'name'          => ['required', 'string', 'min:3', 'max:150'],
            'gender'        => ['required', Rule::in(['male', 'female'])],
            'email'         => ['nullable', 'email', 'max:255'],
            'phone'         => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:20'],
            'stage'         => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'study_type'    => ['required', Rule::in(['morning', 'evening'])],
            'department_id' => ['required', 'exists:departments,id'],
            'interests'     => ['nullable', 'array'],
            'interests.*'   => ['exists:interests,id'],
            'description'   => ['nullable', 'string', 'max:1000'],
        ]);

        // At least one of email or phone
        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'message' => 'يجب إدخال البريد الإلكتروني أو رقم الهاتف على الأقل.',
                'errors'  => ['contact' => ['يجب إدخال البريد الإلكتروني أو رقم الهاتف.']],
            ], 422);
        }

        // Generate completely random, secure UUID
        $rawToken  = Str::uuid()->toString();

        $student = Student::create([
            'form_id'       => $form->id,
            'department_id' => $validated['department_id'],
            'name'          => $validated['name'],
            'gender'        => $validated['gender'],
            'email'         => $validated['email'] ?? null,
            'phone'         => $validated['phone'] ?? null,
            'stage'         => $validated['stage'],
            'study_type'    => $validated['study_type'],
            'description'   => $validated['description'] ?? null,
            'qr_token'      => $rawToken,
        ]);

        if (!empty($validated['interests'])) {
            $student->interests()->sync($validated['interests']);
        }

        return response()->json([
            'message'   => 'تم التسجيل بنجاح!',
            'student'   => [
                'id'   => $student->id,
                'name' => $student->name,
            ],
            'qr_token'  => $rawToken,
        ], 201);
    }
}
