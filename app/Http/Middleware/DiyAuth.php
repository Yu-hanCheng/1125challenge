<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Support\Facades\Auth;

class DiyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $out = new \Symfony\Component\Console\Output\ConsoleOutput();
        try {
            
            if (Auth::guard('api')->user()) {
                $out->writeln("* auth ok:* ");
                $request->merge(['user' => Auth::user()]);
            }else {
                $out->writeln("* no remember:* ");
                // return "fail";
                try {
                    $credentials = $request->only('account', 'password');
                    if (Auth::attempt($credentials,true)) {
                        
                        $request->merge(['user' => Auth::user()]);
                        return $next($request);
                    }else {
                        return response()->json(['result'=>'The account or password  is unavailable. Please login again.'],401);
                    }
                } catch (\Throwable $th) {
                    $out->writeln("* attempt error:* ".$th);
                }
            }
            
            return $next($request);
        } catch (\Throwable $th) {
            $out->writeln("* first error:* ".$th);
        
        }

    }
}
