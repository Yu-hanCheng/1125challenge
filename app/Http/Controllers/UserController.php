<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User; 
use App\Reward; 
use App\UserReward;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return response()->json(['id'=>$request->user->id,'name'=>$request->user->name,'money'=>$request->user->money,'role'=>$request->user->role,'cost'=>$request->user->cost],200);
    }
    
    public function history(Request $request)
    {
        if ($request->user->role) {
            // history
            $history = UserReward::where('hunter_id',$request->user->id)
            ->join('rewards','rewards.id', '=', 'reward_id')
            ->select('rewards.name','rewards.done')
            ->get();
        }else {
            //post
            $history = null;
        }
        $posts=Reward::where('user_id',$request->user->id)
        ->select('name','done')
        ->get();

        return response()->json(['history'=>$history,'posts'=>$posts],200);

    }
    public function login(Request $request)
    {
        return response()->json(['user'=> $request->user->only('name','role','money','remember_token')],200);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $out = new \Symfony\Component\Console\Output\ConsoleOutput();
        $out->writeln("store".$request->role);
        $va = Validator::make($request->all(), [
            'name' => 'required|unique:users,name|max:15',
            'account' => 'required|unique:users,account|max:15',
            'role' => 'required|integer|between:0,1',
        ]);
        if ($va->fails()) {
            return response()->json(['result'=>$va->errors()],416);
        }
        $user = User::create([
            'name' => $request->name,
            'account' => $request->account,
            'password' => Hash::make($request->password),
            'role'=>$request->role,
        ]);

        return response()->json(['result'=>"Register successfully"],201);
        
        // return response()->json(['result'=>"please enter name and password."],400);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
