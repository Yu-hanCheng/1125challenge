<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reward; 
use App\UserReward;

class RewardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(['reward'=>Reward::where('done',0)->get()],200);
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
        $reward = Reward::create([
            'name' => $request->name,
            'bonus' => $request->bonus,
            'descript' => $request->descript,
            'category'=>$request->category,
            'user_id'=>$request->user->id,
        ]);
        return response()->json(['reward'=>$reward],201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id) //接案
    {
        $relation = UserReward::create(
            ['reward_id'=>$id,
             'hunter_id'=>$request->user->id,
            ]);
        $hunters = UserReward::where('reward_id',$id)
        ->join('users','users.id', '=', 'hunter_id')
        ->select('users.name')
        ->get();
        return response()->json(['hunters'=>$hunters],201);
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
        $reward = Reward::where(
            'id', $id)->update(['done'=>1]);
        return response()->json(['reward'=>$reward],200);
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
