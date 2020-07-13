<?php

namespace App\Http\Controllers;

use App\Meeting;
use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations;

class MeetingController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['only' => [
            'store','update','destroy'
        ]]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetings = Meeting::all();
        foreach ($meetings as $meeting)
        {

            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/'. $meeting->id,
                'method' => 'GET'
            ];

            $response = [
                'msg' => 'list of all meetings',
                'meetings'=> $meetings
            ];

            return response()->json($response, 200);
        }

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request -> all() ,[
            'title'=>'required | max:100 ',
            'description' => 'required | min:10',
            'time' => 'required | date_format:Ymd'
        ]);

        if( $validator->fails() )
        {
            return response()->json($validator->errors());
        }

        if (! $user = JWTAuth::parseToken()->authenticate() )
        {
            return response()->json(['msg'=>'user not found'] , 401);
        }
        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meeting = new Meeting( [
            'title'=> $title,
            'description' => $description,
            'time' => $time
        ]);


        if( $meeting->save() )
        {

            $meeting->users()->attach($user_id);

            $meeting->view_meeting = [
            'href' => 'api/v1/meeting/1',
            'method' => 'GET'
                ];
            return response()->json($meeting, 201);
        }

        $response = [
            'msg' => 'some error cannot created',
            'meeting'=> $meeting
        ];

        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id' , $id)->firstOrFail();
        $meeting->view_meeting = [
                'href' => 'api/v1/meeting/id',
                'method' => 'GET'
            ];

        $response = [
            'msg' => 'meeting information',
            'meeting'=> $meeting
        ];

        return response()->json($response, 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all() ,[
            'title'=>'required | max:100 ',
            'description' => 'required | min:10',
            'time' => 'required | date_format:Ymd'
        ]);

        if ($validator->fails())
        {
            return response()->json($validator->errors());
        }

        if (! $user = JWTAuth::parseToken()->authenticate() )
        {
            return response()->json(['msg'=>'user not found'] , 500);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meeting = Meeting::with('users')->findOrFail($id);

        //check if user exist in this meeting
        if (!$meeting->users()->where('users.id' , $user_id)->first())
        {
            return response()->json(['msg' => 'user does not exist in this meeting'] , 404);
        }

        $meeting->title = $title;
        $meeting->description = $description;
        $meeting->time = $time;


        if (!$meeting->update())
        {
            return response()->json(['msg'=>'updating faild'], 404);
        }
        $response = [
            'msg' => 'meeting updated',
            'meeting'=> $meeting,
            'view_meeting' => [
                'href' => 'api/v1/meeting/1',
                'method' => 'GET'
            ]
        ];

        return response()->json($response, 405);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! $user = JWTAuth::parseToken()->authenticate() )
        {
            return response()->json(['msg'=>'user not found'] , 500);
        }

        $meeting = Meeting::findOrFail($id);

        //check if user exist in this meeting
        if (!$meeting->users()->where('users.id' , $user->id)->first())
        {
            return response()->json(['msg' => 'user does not exist in this meeting'] , 404);
        }
        //get all users in this meeting
        $users = $meeting->users;

        $meeting->users()->detach();

        if (!$meeting->delete())
        {
            foreach ($users as $user)
            {
                $meeting->users()->attach($user);
            }
            return response()->json(['msg'=>'deletion fails'], 404);
        }

        $response = [
            'msg' => 'meeting deleted',
            'create'=> [
                'href' => 'api/v1/meeting/1',
                'method' => 'POST',
                'params' =>'title, description, time'
            ]
        ];

        return response()->json($response, 200);
    }
}
