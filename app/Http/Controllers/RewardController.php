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
        $rewards = Reward::where('chosen',null)->get();
        foreach ($rewards as $reward) {
            $hunters = UserReward::where('reward_id',$reward->id)
                ->join('users','users.id', '=', 'user_rewards.hunter_id')
                ->select('users.name','user_rewards.id as user_rewards_id','user_rewards.fee')->get();
            $reward->update(['hunters'=>$hunters]);
        }
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
    public function choose(Request $request,$id)
    {
        $reward = Reward::find($id);
        if ($reward->chosen) {
            return response()->json(['result'=>"The hunter is chosed!"],400);
        }else {
            if ($request->user->id==$reward->user_id) {
                // set hunter
                $hunter_reward = UserReward::where('user_rewards.id',$request->user_reward_id)
                ->join('users','users.id','user_rewards.hunter_id')
                ->select('users.name','user_rewards.fee','user_rewards.hunter_id')
                ->first();
                $user_reward = UserReward::where([
                    ['reward_id','=',$id],
                    ['hunter_id','!=',$hunter_reward->hunter_id],
                ])->delete();
                $reward->update([
                    'hunters'=>$hunter_reward,
                    'bonus'=>$hunter_reward->fee,
                    'chosen'=>1]);

                $user=User::where('id',$reward->user_id)->first();
                $user->update([
                        'cost'=>$user->cost + $hunter_reward->fee - $reward->budget,
                        ]);
                
                return response()->json(['result'=>"choose ok!"],200);
            }else {
                return response()->json(['result'=>"Permission denied!"],403);
            }
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
        $va = Validator::make($request->all(), [
            'name' => 'required|unique:rewards,name|max:15',
            'budget' =>'required',
            'descript' => 'required|max:205',
            'category' => 'required|integer|between:1,2',
        ]);
        if ($va->fails()) {
            return response()->json(['result'=>$va->errors()],416);
        }

        if ($request->user->cost + $request->budget >$request->user->money) {
            return response()->json(['result'=>["bonus"=> [
                "The bonus is over you can afford."]]],416);
        }

        $reward = Reward::create([
            'name' => $request->name,
            'budget' => $request->budget,
            'descript' => $request->descript,
            'category'=>$request->category,
            'user_id'=>$request->user->id,
        ]);
        User::where('id',$request->user->id)->update(['cost'=>$request->user->cost + $request->budget ]);
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
             'fee'=>$request->fee,
            ]);
        // $reward = Reward::where('id',$id)->first();
        // if (!$reward->hunters) {
        //     $reward->update(['hunters'=>$request->user->name]);
        // }else {
        //     $reward->update(['hunters'=>$reward->hunters.','.$request->user->name]);
        // }
        
        // $hunters = UserReward::where('reward_id',$id)
        // ->join('users','users.id', '=', 'hunter_id')
        // ->select('users.name')
        // ->get();
        // return response()->json(['hunters'=>$hunters],201);
        return response()->json(['result'=>"apply successfully"],201);
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
    { //reported
        
        $reward = Reward::where('id', $id)->first();
        if (json_decode($reward->hunters)[0]->name != $request->user->name) {
            return response()->json(['result'=>"You don't hunter the reward"],403);
        }
        $reward->update(['reported'=>1]);
        $user_reward = UserReward::where(
            [['reward_id','=', $id],['hunter_id','!=', $request->user->id]])->delete();

        return response()->json(['reward'=>$reward],200);
    }

    public function done(Request $request, $id)
    { //done
        
        $reward = Reward::where(
            'id', $id)->first();

        if ($reward->user_id != $request->user->id) {
            return response()->json(['result'=>"Permission denied!"],403);
        }elseif (!$reward->reported) {
            return response()->json(['result'=>"The hunter hasn't reported"],403);
        }else {
        
            $reward->update(['done'=>1]);
            $user_reward = UserReward::where(
                'reward_id', $id)->delete();

            $hunter = User::where('name',json_decode($reward->hunters)[0]->name)->update(['money'=>$request->user->money + $reward->bonus ]);
            
            $user = User::where('id',$reward->user_id)
                    ->update(['money'=>$request->user->money - $reward->bonus, 
                            'cost'=>$request->user->cost - $reward->bonus, ]);
        }
        return response()->json(['result'=>"Close the post!"],403);
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
