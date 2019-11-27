<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reward;
use App\User;
use App\UserReward;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManagerStatic as Image;
use GuzzleHttp\Client;
class RewardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rewards = Reward::where('chosen',0)->orderBy('created_at','desc')->get();
        foreach ($rewards as $reward) {
            $hunters = UserReward::where('reward_id',$reward->id)
                ->join('users','users.id', '=', 'user_rewards.hunter_id')
                ->select('users.name','users.achieveRate','users.experience','user_rewards.id as user_rewards_id','user_rewards.fee')->get();
            $reward->hunters=$hunters;
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

                DB::beginTransaction();
                try {
                    $reward->update([
                        'hunters'=>$hunter_reward,
                        'bonus'=>$hunter_reward->fee,
                        'chosen'=>1]);
    
                    $user=User::where('id',$reward->user_id)->first();
                    $user->update([
                            'cost'=>$user->cost + $hunter_reward->fee - $reward->budget,
                            ]);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return response()->json(['result'=>$th],500);
                }
                DB::commit();
                
                
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
            return response()->json(['result'=>
                "The budget is over you can afford."],416);
        }

        DB::beginTransaction();
        try {
            $reward = Reward::create([
                'name' => $request->name,
                'budget' => $request->budget,
                'descript' => $request->descript,
                'category'=>$request->category,
                'user_id'=>$request->user->id,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['result'=>$th],500);
        }
        DB::commit();
        
        User::where('id',$request->user->id)->update(['cost'=>$request->user->cost + $request->budget ]);
        return response()->json(['reward'=>Reward::find($reward->id) ],201);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id) //接案
    {
        $post=Reward::where([['user_id','=',$request->user->id],['id','=',$id]])->first();
        if ($post) {
            return response()->json($post,200);
        }
        return response()->json(['result'=>"Permission denied!"],403);
        
    }
    public function hunt(Request $request,$id) //接案
    {
        $va = Validator::make($request->all(), [
            'fee' => 'required|integer',
        ]);
        if ($va->fails()) {
            return response()->json(['result'=>$va->errors()],416);
        }
        $user_reward =UserReward::where([['reward_id','=',$id],['hunter_id','=',$request->user->id]])->first();
        
        DB::beginTransaction();
        try {
            if($user_reward){
                $user_reward->update(['fee'=>$request->fee]);
                return response()->json(['result'=>"Edit fee successfully"],200);
            }
            $reward = Reward::find($id);
            if ($reward->chosen) {
                return response()->json(['result'=>"Not availableh!"],400);
            }
            $relation = UserReward::create(
                ['reward_id'=>$id,
                 'hunter_id'=>$request->user->id,
                 'fee'=>$request->fee,
                ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['result'=>$th],500);
        }
        DB::commit();
        
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
        
        $va = Validator::make($request->all(), [
            'img' => 'required|image',
            'reported_descript' =>'required',
        ]);
        if ($va->fails()) {
            return response()->json(['result'=>$va->errors()],416);
        }
        $reward = Reward::where('id', $id)->first();
        if (json_decode($reward->hunters)->name != $request->user->name) {
            return response()->json(['result'=>"You don't hunter the reward"],403);
        }

        $imageURL = request()->file('img')->store('public');
        DB::beginTransaction();
        try {
            $reward->update(['reported_descript'=>$request->reported_descript,'img'=>asset('storage/' . substr($imageURL, 7))]);
            $user_reward = UserReward::where(
                [['reward_id','=', $id],['hunter_id','!=', $request->user->id]])->delete();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['result'=>$th],500);
        }
        DB::commit();
        
        

        return response()->json(['reward'=>$reward],200);
    }

    public function done(Request $request, $id)
    { //done
        $va = Validator::make($request->all(), [
            'done' => 'required|integer|between:0,1',
        ]);
        if ($va->fails()) {
            return response()->json(['result'=>$va->errors()],416);
        }
        $reward = Reward::where(
            'id', $id)->first();

        if ($reward->user_id != $request->user->id) {
            return response()->json(['result'=>"Permission denied!"],403);
        }elseif (!$reward->reported_descript) {
            return response()->json(['result'=>"The hunter hasn't reported"],403);
        }else {
        
            DB::beginTransaction();
            try {
                $reward->update(['done'=>$request->done]);

                if ($request->done) {
                    $achieve=1;
                    if (!$request->key) {
                        return response()->json(['result'=>"Need the key"],416);
                    }
                }else {
                    $achieve=0;
                }
                $hunter = User::where('name',json_decode($reward->hunters)->name)->first();
                $client = new Client();
                try {
                    $response_origin = $client->request('POST', env('BANK_BASE_URL').'/api/user/transfer', 
                        ['form_params' => ["userID"=>$request->user->bank_account,
                                "key"=>$request->key,
                                "account"=>$hunter->bank_account,
                                "amount"=>strval($reward->bonus),
                                "isShop"=>strval(0)]
                        ]);
                } catch (\Throwable $th) {
                    return response()->json(['result'=>$th],500);
                }     
               //$response
                $response = json_decode($response_origin->getBody());
                $request->user->money=$response->remittance_balance;
                $request->user->cost=$request->user->cost- $reward->bonus;
                $request->user->save();

                $hunter->update(['money'=>$response->payee_balance,
                            'experience'=>$hunter->experience+1,
                            'achieveRate'=>$hunter->achieveRate+$achieve
                            ]);
                      
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json(['result'=>$th],500);
        }
        DB::commit();
            
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
