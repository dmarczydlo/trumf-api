<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWTGuard;
use App\User;
use Illuminate\Support\Facades\Hash;
use Validator;
use DB;

class UsersController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {

        $creditionals = $request->only('email', 'password');

        try {

            $customClaims = ['email' => $creditionals['email']];
            if (!$token = JWTAuth::attempt($creditionals, $customClaims)) {

                return response()->json(['error' => 'Email lub hasła są nieprawidłowe'], 400);
            }

        } catch (JWTException $exception) {

            return response()->json(['error' => 'Wystąpił błą'], 500);
        }

        if ($token) {
            //check user group;
            $user = User::where('email', $creditionals['email'])->first();

            $output['token'] = $token;
            $output['group'] = $user->group->name;
        }

        return response()->json($output);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        return response()->json([
            'success' => true
        ]);
    }


    public function updateProfile($user_id, Request $request)
    {
        $user_data = $request->only('password', 'avatar');

        //check user exist
        $user = User::find($user_id);

        if (!empty($user)) {

            if (!empty($user_data)) {

                if (isset($user_data['password'])) {
                    if (!empty($user_data['password'])) {
                        $user_data['password'] = Hash::make($user_data['password']);
                    } else {
                        unset($user_data['password']);
                    }
                }

                $user = User::updateOrCreate(['id' => $user_id], $user_data);
                $user->save();

                return response()->json([
                    'success' => true
                ]);
            } else {
                return response()->json(['error' => 'Brak wymaganych danych'], 401);
            }
        } else {
            return response()->json(['error' => 'Brak użytkownika'], 401);
        }

    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {

        $user_data = $request->only('email', 'password', 'name', 'group_id', 'surname', 'level', 'avatar');

        if (!empty($user_data)) {
            try {

                $find_user = User::where('email', '=', $user_data['email'])->first();
                if (empty($find_user)) {
                    $user_data['password'] = Hash::make($user_data['password']);
                    $validator = Validator::make($user_data, [
                        'email' => 'required|email',
                        'group_id' => 'required|numeric',
                        'name' => 'required',
                        'surname' => 'required',
                        'level' => 'required|numeric',

                    ]);

                    if (!$validator->fails()) {
                        $user = User::create($user_data);
                        return response()->json([
                            'user' => $user
                        ]);
                    } else {
                        return response()->json(['error' => 'Brak wymaganych danych'], 401);
                    }

                } else {
                    return response()->json(['error' => 'Email występuje już w bazie'], 401);
                }
            } catch (QueryException $exception) {
                return response()->json(['error' => 'exception' . $exception->getMessage()], 401);
            }
        } else {
            return response()->json(['error' => 'Email lub hasło są nieprawidłowe'], 401);
        }
    }

    /**
     * @param $user_id
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * @param $user_id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($user_id, Request $request)
    {
        $user_data = $request->only('email', 'password', 'name', 'group_id', 'surname', 'level', 'avatar');


        if (isset($user_data['password'])) {
            if (!empty($user_data['password'])) {
                $user_data['password'] = Hash::make($user_data['password']);
            }
        }


        $validator = Validator::make($user_data, [
            'email' => 'required|email',
            'group_id' => 'required|numeric',
            'name' => 'required',
            'surname' => 'required',
            'level' => 'required|numeric',

        ]);

        if (!$validator->fails()) {
            foreach ($user_data as $k => $v) {
                if (empty($v))
                    unset($user_data[$k]);
            }

            $user = User::updateOrCreate(['id' => $user_id], $user_data);
            $user->save();

            return response()->json([
                'user' => $user
            ]);
        } else {
            return response()->json(['error' => 'Brak wymaganych danych'], 401);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers()
    {

        $users = DB::table('users')
            ->join('groups', 'users.group_id', '=', 'groups.id')
            ->select('users.name', 'users.surname', 'users.id', 'users.email', 'users.level', 'groups.name AS group')
            ->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * @param $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function read($user_id)
    {

        $user = User::find($user_id);

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * @param $group_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserFromGroup($group_id)
    {
        $users = User::where('group_id', $group_id)->select('id', 'name', 'surname')->get();
        return response()->json([
            'users' => $users
        ]);
    }
}
