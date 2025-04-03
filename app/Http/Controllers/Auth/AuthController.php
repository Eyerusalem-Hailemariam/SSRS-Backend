<?php

namespace App\Http\Controllers\Auth;

use OpenApi\Annotations as OA; 
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register/admin",
     *     summary="Register a new admin user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="Admin User"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="securePassword123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Admin registered successfully"),
     *     @OA\Response(response=400, description="Validation error")
     * )
     */
    public function registerAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'admin'
        ]);

        return response()->json($user, 201);
    }

    /**
     * @OA\Post(
     *     path="/register",
     *     summary="Register a new customer user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="Customer User"),
     *             @OA\Property(property="email", type="string", format="email", example="customer@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="customerPass123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Customer registered successfully"),
     *     @OA\Response(response=400, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'customer',
            'is_verified' => false,
        ]);

        DB::table('user_otps')->insert([
            'email' => $request->email,
            'otp' => $otp,
            'created_at' => now(),
        ]);

        Mail::to($request->email)->send(new \App\Mail\SendOtpMail($otp));

        return response()->json($user, 201);
    }


    public function VerifyOtp(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required'
        ]);

        $record = DB::table('user_otps')->where('email', $request->email)->first();

        if(!$record || $record->otp != $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        User::where('email', $request->email)->update(['is_verified' => true]);
        
        DB::table('user_otps')->where('email', $request->email)->delete();

        return response()->json(['message' => 'OTP verified successfully'], 200);

    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Log in an existing user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="userPassword123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User logged in successfully"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token');

        return response()->json([
            'message' => 'User logged in successfully',
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Log out the currently authenticated user",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Successfully logged out")
     * )
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }
        Auth::logout();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

}
