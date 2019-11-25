<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reward;
use App\User;
use App\UserReward;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class RewardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rewards = Reward::where('done',0)->get();
        return response()->json(['reward'=>$rewards],200);
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
        $va = Validator::make($request->all(), [
            'name' => 'required|unique:rewards,name|max:15',
            'bonus' => ['required',
                        'max:15'],
            'descript' => 'required|max:205',
            'category' => 'required|integer|between:1,2',
        ]);
        if ($va->fails()) {
            return response()->json(['result'=>$va->errors()],416);
        }

        if ($request->user->cost + $request->bonus >$request->user->money) {
            return response()->json(['result'=>["bonus"=> [
                "The bonus is over you can afford."]]],416);
        }

        $reward = Reward::create([
            'name' => $request->name,
            'bonus' => $request->bonus,
            'descript' => $request->descript,
            'category'=>$request->category,
            'user_id'=>$request->user->id,
        ]);
        User::where('id',$request->user->id)->update(['cost'=>$request->user->cost + $request->bonus ]);
        return response()->json(['reward'=>$reward ],201);
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
        $reward = Reward::where('id',$id)->first();
        if ($reward->hunters) {
            $reward->update(['hunters'=>$request->user->name]);
        }else {
            $reward->update(['hunters'=>$reward->hunters.','.$request->user->name]);
        }
        
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
            'id', $id)->first();
        if (!$reward->hunters) {
            return response()->json(['result'=>"You don't hunter the reward"],403);
        }
        $reward->update(['done'=>1]);
        $user_reward = UserReward::where(
            'reward_id', $id)->delete();

        $hunter = User::where('id',$request->user->id)->update(['money'=>$request->user->money + $reward->bonus ]);
        $user = User::where('id',$reward->user_id)
                ->update(['money'=>$request->user->money - $reward->bonus, 
                        'cost'=>$request->user->cost - $reward->bonus, ]);
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
