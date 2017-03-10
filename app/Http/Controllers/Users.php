<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class Users extends Controller
{
    //

    public function Login()
    {
        return response()->json([
            'action' => 'login'
        ]);
    }

    public function Logout()
    {
        return response()->json([
            'action' => 'logout'
        ]);
    }

    public function Create()
    {
        return response()->json([
            'action' => 'create'
        ]);
    }

    public function Delete($user_id)
    {
        return response()->json([
            'action' => 'delete'
        ]);
    }

    public function Update($user_id)
    {
        return response()->json([
            'action' => 'update'
        ]);
    }

    public function Read($user_id)
    {
        return response()->json([
            'action' => 'read'
        ]);
    }
}
