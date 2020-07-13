<?php

namespace App\Http\Controllers;

use App\Meeting;
use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

class RegistrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      $validator  = Validator::make($request->all() ,[
            'meeting_id'=>'required'
        ]);
        if ($validator->fails())
        {
            return response()->json($validator->errors());
        }

        if (! $user = JWTAuth::parseToken()->authenticate() )
        {
            return response()->json(['msg'=>'user unregistered'] , 404);
        }

        $meeting_id = $request->input('meeting_id');
        $user_id = $user->id;

        $meeting = Meeting::findOrFail($meeting_id);
        $user = User::findOrFail($user_id);

        $dummyMessage = [
            'msg' => 'already registr for meeting ',
            'meeting'=> $meeting,
            'user'=>$user,
            'unregister' => [
                'href' => 'api/v1/meeting/id',
                'method' => 'GET'
            ]
        ];

        if($meeting->users()->where('users.id' , $user->id)->first())
        {
            return response()->json($dummyMessage, 400);
        }

        $user->meetings()->save($meeting);

        $response = [
            'msg' => 'user registr for meeting ',
            'meeting'=> $meeting,
            'user'=>$user,
            'view_meeting' => [
                'href' => 'api/v1/meeting/registration/'.$meeting->id,
                'method' => 'Delete'
            ]
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting= Meeting::findOrFail($id);

        if (! $user = JWTAuth::parseToken()->authenticate() )
        {
            return response()->json(['msg'=>'user not found'] , 500);
        }

        if (!$meeting->users()->where('users.id' , $user->id)->first())
        {
            return response()->json(['msg' => 'user does not exist in this meeting'] , 404);
        }

        $meeting->users()->detach($user->id);
        $response = [
            'msg' => 'user unregister for meeting ',
            'meeting'=> $meeting,
            'user' => $user,
            'register' => [
                'href' => 'api/v1/meeting/registration',
                'method' => 'POST'
            ]
        ];

        return response()->json($response, 200);
    }
}
