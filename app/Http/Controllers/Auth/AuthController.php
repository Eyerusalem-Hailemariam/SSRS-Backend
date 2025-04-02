<?php
namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
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

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
        ], 201);
    }
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'customer'
        ]);

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role
            
        ], 201);
    }



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

        // Generate a new token
        $token = $user->createToken('auth_token');

        return response()->json([
            'message' => 'User logged in successfully',
            'access_token' => $token->plainTextToken, 
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

        
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

    public function getUserProfile(Request $request)
    {
        $user = Auth::user();
        return response()->json($user);
    }
}