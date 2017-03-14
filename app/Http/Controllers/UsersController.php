<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use App\Http\Requests;
use League\Flysystem\Exception;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWTGuard;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    //

    public function login(Request $request)
    {

        $creditionals = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($creditionals)) {

                return response()->json(['error' => 'User email or password as no correct'], 401);
            }

        } catch (JWTException $exception) {

            return response()->json(['error' => 'Something was wrong'], 500);
        }


        return response()->json(compact('token'));
    }

    public function logout()
    {
//        JWTGuard::logout();


        return response()->json([
            'success' => true
        ]);
    }

    public function create(Request $request)
    {

        $user_data = $request->only('email', 'password', 'name', 'group_id', 'surname', 'level');

        if (!empty($user_data)) {
            try {


//                print_r($user_data); exit();
                $find_user = User::where('email', '=', $user_data['email'])->first();
                if (empty($find_user)) {
                    $user_data['password'] = Hash::make($user_data['password']);
                    $user = User::create($user_data);

                    return response()->json([
                        'user' => $user
                    ]);
                } else {
                    return response()->json(['error' => 'Email is exist'], 401);
                }
            } catch (QueryException $exception) {
                return response()->json(['error' => 'exception' . $exception->getMessage()], 401);
            }
        } else {
            return response()->json(['error' => 'User email or password as no correct'], 401);
        }
    }

    public function delete($user_id)
    {
        if ($user_id > 0) {
            $find_user = User::find($user_id);
            if (!empty($find_user)) {
                if ($find_user->delete()) {
                    return response()->json([
                        'success' => true
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    public function update($user_id)
    {
        return response()->json([
            'action' => 'update'
        ]);
    }

    public function getUsers()
    {
        $users = User::all();
        return response()->json([
            'users' => $users
        ]);
    }

    public function read($user_id)
    {

        $user = User::find($user_id);


        return response()->json([
            'user' => $user
        ]);
    }
}
