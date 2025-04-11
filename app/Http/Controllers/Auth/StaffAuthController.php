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

class StaffAuthController extends Controller
{
    //
    /**
     * 
     */
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]); 
 
        $staff = Staff::where('email', $credentials['email'])->first();
 
        if (!$staff || !Hash::check($credentials['password'], $staff->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
 
         $token = $staff->createToken('auth_token')->plainTextToken;
 
         return response()->json(['token' => $token], 200);
     }
 
     /**
      * @OA\Post(
      *     path="/api/staff/change-password",
      *     tags={"Admin"},
      *     summary="Change staff password",
      *     security={{"sanctum":{}}},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"current_password", "new_password"},
      *             @OA\Property(property="current_password", type="string", example="oldpassword123"),
      *             @OA\Property(property="new_password", type="string", example="newpassword456")
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Password changed successfully",
      *         @OA\JsonContent(
      *            @OA\Property(property="message", type="string", example="Password changed successfully")  
      *        )
      *    ),
      *    @OA\Response(
      *        response=400,
      *       description="Current password is incorrect",
      *        @OA\JsonContent(
      *           @OA\Property(property="message", type="string", example="Current password is incorrect")
      *       )
      *   )
      * )
      */ 
     public function changePassword(Request $request) {
         $request->validate([
             'current_password' => 'required',
             'new_password' => 'required|min:6',
         ]);
 
         $staff = auth()->user();
 
         if (!Hash::check($request->current_password, $staff->password)) {
             return response()->json(['message' => 'Current password is incorrect'], 400);
         }
 
         $staff->password = Hash::make($request->new_password);
         $staff->save();
 
         return response()->json(['message' => 'Password changed successfully'], 200);
     }
     


     /**
      * @OA\Post(
      *     path="/api/staff/forgot-password",
      *     tags={"Admin"},
      *     summary="Send password reset link",
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"email"},
      *             @OA\Property(property="email", type="string", example="staff@example.com")
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Password reset link sent",
      *         @OA\JsonContent(
      *            @OA\Property(property="message", type="string", example="Password reset link sent")
      *        )
      *    ),
      *    @OA\Response(
      *        response=400,
      *       description="Email not found",
      *        @OA\JsonContent(
      *           @OA\Property(property="message", type="string", example="Email not found")
      *       )
      *   )
      * )
      */
 
     public function forgotPassword(Request $request)
     {
         $request->validate(['email' => 'required|email|exists:staff,email']);
 
         $email = $request->email;
     
         DB::table('password_reset_tokens')->where('email', $email)->delete();
 
         $token = Str::random(60);
         DB::table('password_reset_tokens')->insert([
             'email' => $request->email,
             'token' => Hash::make($token), 
             'created_at' => Carbon::now()
         ]);
     
         Mail::to($request->email)->send(new \App\Mail\PasswordResetMail($token));
     
         return response()->json(['message' => 'Password reset link sent'], 200);
     }
     
         /**
      * @OA\Post(
      *     path="/api/staff/reset-password",
      *     tags={"Admin"},
      *     summary="Reset staff password",
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             required={"email", "token", "password", "password_confirmation"},
      *             @OA\Property(property="email", type="string", example="staff@example.com"),
      *             @OA\Property(property="token", type="string", example="reset_token_string"),
      *             @OA\Property(property="password", type="string", example="newpassword456"),
      *             @OA\Property(property="password_confirmation", type="string", example="newpassword456")
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Password reset successfully",
      *         @OA\JsonContent(
      *            @OA\Property(property="message", type="string", example="Password reset successfully")
      *        )
      *    ),
      *    @OA\Response(
      *        response=400,
      *       description="Invalid token",
      *        @OA\JsonContent(
      *           @OA\Property(property="message", type="string", example="Invalid token")
      *       )
      *   )
      * )
      */
     public function resetPassword(Request $request)
     {
         $request->validate([
             'email' => 'required|email|exists:staff,email',
             'token' => 'required',
             'password' => 'required|min:6|confirmed'
         ]);
 
         $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
 
         if (!$record || !Hash::check($request->token, $record->token)) {
             return response()->json(['message' => 'Invalid token'], 400);
         }
 
         $staff = Staff::where('email', $request->email)->first();
         $staff->password = Hash::make($request->password); 
         $staff->save();
 
         DB::table('password_reset_tokens')->where('email', $request->email)->delete();
 
         return response()->json(['message' => 'Password reset successfully'], 200);
     }

     /**
      * @OA\Put(
      *     path="/api/staff/update-account",
      *     tags={"Admin"},
      *     summary="Update staff account",
      *     security={{"sanctum":{}}},
      *     @OA\RequestBody(
      *         required=true,
      *         @OA\JsonContent(
      *             @OA\Property(property="email", type="string", example="newemail@example.com")
      *         )
      *     ),
      *     @OA\Response(
      *         response=200,
      *         description="Account updated successfully",
      *         @OA\JsonContent(
      *            @OA\Property(property="message", type="string", example="Account updated successfully"),
      *            @OA\Property(property="staff", type="object", 
      *                @OA\Property(property="id", type="integer", example=1),
      *                @OA\Property(property="email", type="string", example="newemail@example.com"),
      *                @OA\Property(property="role", type="string", example="manager")
      *            )
      *        )
      *    ),
      *    @OA\Response(
      *        response=400,
      *       description="Validation error",
      *        @OA\JsonContent(
      *           @OA\Property(property="message", type="string", example="Validation error")
      *       )
      *   )
      * )
      */

     public function updateAccount(Request $request) {

        $staff = auth()->user();

        $request->validate([
            'email' => 'nullable|email|unique:staff,email,' . $staff->id,
        ]);

        if ($request->has('email') && $request->email !== $staff->email) {
            $staff->email = $request->email;
        }

        $staff->save();

        return response()->json([
            'message' => 'Account updated successfully',
            'staff' => $staff
        ], 200);    


     }
     
}
