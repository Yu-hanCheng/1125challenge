<?php

namespace App\Http\Controllers;

use App\Item;
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
        
        $va = Validator::make($request->all(), [
            'name' => 'required|unique:users,name|max:15',
            'account' => 'required|unique:users,account|max:15',
            'role' => 'required|integer|between:0,1',
            'bank_account'=>'required|email'
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
                'bank_account'=>$request->bank_account,
            ]);
    
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['result'=>$th],500);
        }
        DB::commit();
        
        return response()->json(['result'=>"Register successfully"],201);
        
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
        $client = new Client();
        try {
            $response = $client->request('POST', env('BANK_BASE_URL').'/api/shop/transfer', 
            ['form_params' => ["userID"=>env('SPARTA_ACCOUNT'),
                            "key"=>env('SPARTA_KEY'),
                            "account"=>$request->user->bank_account,
                            "amount"=>strval($request->earned),
                            "isShop"=>strval(0)]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['result'=>$th],500);
        }
        $response = json_decode($response->getBody());
        $request->user->money=$response->payee_balance;
        $request->user->save();
        
        return response()->json($request->user,201);
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
        $response = $client->request('GET', env('SHOP_BASE_URL',null).'/api/items');
        
        $list = json_decode($response->getBody())->items;
        $products=[];
        foreach ($list as $element) {
            if ($element->price<$max_price) {
                $products[]=$element;
            }
        }
        return response()->json(['result'=>$products],200);
    }
    public function buy(Request $request)
    {
        $client = new Client();
        $item_info = json_decode($client->request('GET', env('SHOP_BASE_URL').'/api/showitems/'.$request->item_id)->getBody())->data;
        $sum = (int)$item_info->price*$request->count;
        if ($sum > $request->user->money-$request->user->cost ) {
            return response()->json(['result'=>"Can't afford the transaction!"],400);
        }
        //使用者要先轉帳給斯巴達
        try {
            $response = $client->request('POST', env('SHOP_BASE_URL').'/api/sheepitem',
            ['form_params' => ['account'=>'sparta',
            'item_id'=>$request->item_id,
            'stock'=>$request->count,
            'api_token'=>env('SHOP_TOKEN',null),
            'sheep_email'=>env('SPARTA_ACCOUNT',null),
            'key'=>env('SPARTA_KEY',null)]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['result'=>$th],201);
        }
        try {
            
            $response = $client->request('POST', env('BANK_BASE_URL').'/api/user/transfer', 
            ['form_params' => ["userID"=>$request->user->bank_account,
                            "key"=>$request->key,
                            "account"=>env('SPARTA_ACCOUNT'),
                            "amount"=>strval($sum),
                            "isShop"=>strval(1)]
            ]);

            $response = json_decode($response->getBody());
            DB::beginTransaction();
            try {
                Item::create([
                    'user_id'=>$request->user->id,
                    'item_id'=>$request->item_id,
                    'name'=>$item_info->item_name,
                    'price'=>$item_info->price,
                    'img'=>$item_info->pic,
                    "count"=>$request->count,
                ]);
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json(['result'=>$th],500);
            }
            DB::commit();
            $request->user->money=$response->remittance_balance;
            $request->user->save();
            return response()->json(['result'=>"ok",'charging'=>$response->charging],200);
        } catch (\Throwable $th) {
            return response()->json(['result'=>$th],500);
        }
        return response()->json(['result'=>"successful"],201);
    }   

    public function bought(Request $request)
    {
        return Item::where('user_id',$request->user->id)->orderBy('created_at','desc')->get();
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
