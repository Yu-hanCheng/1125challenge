<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User; 
use App\Reward; 
use App\UserReward;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // return response()->json(['id'=>$request->user->id,'name'=>$request->user->name,'money'=>$request->user->money,'role'=>$request->user->role,'cost'=>$request->user->cost],200);
        return response()->json($request->user,200);
    }
    
    public function history(Request $request)
    {
        if ($request->user->role) {
            // history
            $history = UserReward::where('hunter_id',$request->user->id)
            ->join('rewards','rewards.id', '=', 'reward_id')
            ->select('user_rewards.reward_id','rewards.name','rewards.category','rewards.descript','rewards.reported_descript','user_rewards.fee','rewards.chosen','rewards.done')
            ->orderBy('rewards.created_at','desc')
            ->get();
        }else {
            //post
            $history = null;   
        }
        $posts=Reward::where('user_id',$request->user->id)->orderBy('created_at','desc')->get();
        foreach ($posts as $post) {
            $hunters = UserReward::where('reward_id',$post->id)
                ->join('users','users.id', '=', 'user_rewards.hunter_id')
                ->select('users.name','users.experience','users.achieveRate','user_rewards.id as user_rewards_id','user_rewards.fee')->get();
            $post->hunters=$hunters;
        }
        return response()->json(['history'=>$history,'posts'=>$posts],200);
        // return response()->json(['history'=>$history,'posts'=>$posts],200);

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
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'account' => $request->account,
                'password' => Hash::make($request->password),
                'role'=>$request->role,
            ]);
    
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['result'=>$th],500);
        }
        DB::commit();
        
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
    public function earn(Request $request)
    {
        //串金流
        $user=User::find($request->user->id);
        $user->update(['money'=>$user->money+$request->earned]);
        return response()->json($user,201);
    }
    public function shop(Request $request)
    {
        $max_price=0;
        if ($request->user->achieveRate > 1) {
            $max_price=800;
        }else {
            $max_price=300;
        }
        $client = new Client();
        $response = $client->request('GET', 'http://35.234.60.173/api/items');
        
        $list = json_decode($response->getBody())->items;
        $products=[];
        foreach ($list as $element) {
            if ($element->price<$max_price) {
                $products[]=$element;
            }
        }
        return $products;
    }
    public function buy(Request $request, $id)
    {
        //使用者要先轉帳給斯巴達
        $client = new Client();
        $response = $client->request('POST', 'http://35.234.60.173/api/sheepitem',
            ['form_params' => ['account'=>'sparta','item_id'=>$id,'stock'=>2,'api_token'=>'nIAKseLSkT']
            ]);
        return response()->json(['result'=>$response->getBody()],201);
    }

    public function bought_list(Request $request)
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
