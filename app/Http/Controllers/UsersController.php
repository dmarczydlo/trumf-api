<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class Users extends Controller
{
    //

    public function login(Request $request)
    {

        $creditionals = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($creditionals)) {

              return  response()->json(['error' => 'User email or password as no correct'], 401);
            }

        } catch (JWTException $exception) {

            return response()->json(['error' => 'Something was wrong'], 500);
        }


        return response()->json(compact('token'));
    }

    public function logout()
    {
        return response()->json([
            'action' => 'logout'
        ]);
    }

    public function create()
    {
        return response()->json([
            'action' => 'create'
        ]);
    }

    public function delete($user_id)
    {
        return response()->json([
            'action' => 'delete'
        ]);
    }

    public function update($user_id)
    {
        return response()->json([
            'action' => 'update'
        ]);
    }

    public function read($user_id)
    {
        return response()->json([
            'action' => 'read'
        ]);
    }
}
