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

     /**
     * Delete a staff member.
     *
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     tags={"Admin"},
     *     summary="Delete a staff member",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the staff member to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Staff member deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Staff member not found"
     *     )
     * )
     */
    public function deleteStaff($id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $staff->delete();

        return response()->json(['message' => 'Staff deleted successfully'], 200);
    
    }

     /**
     * Update a staff member's details.
     *
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     tags={"Admin"},
     *     summary="Update a staff member's details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the staff member to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="role", type="string", example="manager"),
     *             @OA\Property(property="total_salary", type="number", format="float", example=50000.00),
     *             @OA\Property(property="overtime_rate", type="number", format="float", example=50.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Staff member updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="role", type="string", example="manager"),
     *             @OA\Property(property="total_salary", type="number", format="float", example=50000.00),
     *             @OA\Property(property="overtime_rate", type="number", format="float", example=50.00)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Staff member not found"
     *     )
     * )
     */
    public function updateStaff(Request $request, $id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email|unique:staff,email,' . $staff->id,
            'role' => 'sometimes|required|string',
            'total_salary' => 'sometimes|required|numeric|min:0',
            'overtime_rate' => 'sometimes|required|numeric|min:0',
        ]);

        $staff->update($request->all());

        return response()->json(['message' => 'Staff updated successfully', 'staff' => $staff], 200);
    }
}

