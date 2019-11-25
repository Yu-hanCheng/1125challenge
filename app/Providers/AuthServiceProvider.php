<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\User; 
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('custom-token', function ($request) {
            $out = new \Symfony\Component\Console\Output\ConsoleOutput();
            
            if (!$request->remember_token) {
                return null;
            }else {
                $user =User::where('remember_token', $request->remember_token)->first();
                if ($user) {
                    Auth::login($user);
                    return $user;
                }
                return null;
            }
        });
    }
}
