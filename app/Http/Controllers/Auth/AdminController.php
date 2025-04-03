<?php

namespace App\Http\Controllers\Auth;

use OpenApi\Annotations as OA; 
use Illuminate\Http\Request;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\StaffAccountCreated;
use Illuminate\Support\Facades\Password;
use Carbon\Carbon;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\DB;



/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin Management"
 * )
 */
class AdminController extends Controller
{
    /**
     * Register a new staff member.
     *
     * @OA\Post(
     *     path="/api/admin/users",
     *     tags={"Admin"},
     *     summary="Register a new staff member",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "role", "total_salary", "overtime_rate"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="role", type="string", example="manager"),
     *             @OA\Property(property="total_salary", type="number", format="float", example=50000.00),
     *             @OA\Property(property="overtime_rate", type="number", format="float", example=50.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Staff registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="role", type="string", example="manager"),
     *             @OA\Property(property="total_salary", type="number", format="float", example=50000.00),
     *             @OA\Property(property="overtime_rate", type="number", format="float", example=50.00),
     *             @OA\Property(property="temp_password", type="string", example="randompassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function registerStaff(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:staff',
            'role' => 'required|string',
            'total_salary' => 'required|numeric|min:0',
            'overtime_rate' => 'required|numeric|min:0',
        ]);

        $tempPassword = Str::random(10);

        $staff = Staff::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($tempPassword),
            'role' => $request->role, 
            'total_salary' => $request->total_salary,
            'overtime_rate' => $request->overtime_rate,
            'tips' => 0,
        ]);

        Mail::to($staff->email)->send(new StaffAccountCreated($staff, $tempPassword));

        return response()->json([
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => $staff->role,
            'total_salary' => $staff->total_salary,
            'overtime_rate' => $staff->overtime_rate,
            'temp_password' => $tempPassword,
        ], 201);
    }

    
    }
