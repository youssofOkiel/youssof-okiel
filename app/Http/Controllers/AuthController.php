<?php

namespace App\Http\Controllers;

use App\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;
use Validator;

class AuthController extends Controller
{
    //
    public function  signup(Request $request )
    {
        $validator = Validator::make($request -> all() ,[
            'name'=>'required | max:50 | unique:users',
            'email' => 'required |email| unique:users',
            'password' =>'required | min:4'
        ]);

        if( $validator->fails() )
        {
           return response()->json($validator->errors());
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        $user = new User([
            'name'=> $name,
            'email' => $email,
            'password' => bcrypt($password)
        ]);
        if ($user->save())
        {
            //signin is a new field added by magic method this only for me cannot stored

            $user->signin = [
                'href' => 'api/v1/user/signin',
                'method' => 'POST',
                'params' =>'email ,password'
            ];

            $response = [
                'msg' => 'user created',
                'user'=> $user
            ];
            return response()->json($response, 201);
        }

        $response = [
            'msg' => 'An error occurred'
        ];

        return response()->json($response, 405);

    }

    public function  signin(Request $request )
    {
        $validator = Validator::make($request -> all() ,[
            'email' => 'required |email',
            'password' =>'required | min:4'
        ]);

        if( $validator->fails() )
        {
            return response()->json($validator->errors());
        }

        $email = $request->input('email');
        $password = $request->input('password');

        $credentials = $request->only(['email' , 'password']);

        try
        {
            if (! $token = JWTAuth::attempt($credentials))
            {
                return response()->json(['msg' => 'invalid data']);
            }

        } catch (JWTException $e)
        {
             return Response()->json($e , 500);
        }

        return response()->json( [ 'token' => $token], 200);
    }
}
