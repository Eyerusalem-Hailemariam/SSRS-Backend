<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    
     // Register Staff (Admin Only)
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
    