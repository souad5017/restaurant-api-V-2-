<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    function register (Request $request) {
        $request->validate([
            'name'=> 'required|string|max:100',
            'email'=> 'required|email|unique:users',
            'password'=> 'required|min:8',
            'role' => 'required|string|in:admin,user'
        ]);

        $user = User::create([
            'name'=>$request->name ,
            'email'=>$request->email,
            'role' => $request->role,
            'password'=> Hash::make($request->password),
            
        ]) ;

        $token = $user->createToken('login-token')->plainTextToken;

        return response()->json([
            'token' => $token , 
            'user'=> $user
        ]);
    }

    function login (Request $request){
        $request->validate([
            'email'=> 'required|email',
            'password'=>'required|min:8'
        ]);

        $user = User::where('email' , $request->email)->first();

        if(!$user || !Hash::check($request->password , $user->password)){
            return response()->json([
                'missage' => 'Invalid login credentials'
            ]);
        }

        $token = $user->createToken('login-token')->plainTextToken;

        return response()->json([
            'token'=> $token,
            'user'=> $user 
        ]);
    }

    function logout(Request $request){
        $user = $request->user();

        if(!$user){
            return response()->json([
                'missage' => 'error'
            ]);
        }
        $user->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }


    function user(Request $request){
        return response()->json([
            $request->user()
        ]);
    }
}

