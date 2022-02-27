<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;

class User{

}

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Gate::define('creator', function ($user) {
            return collect($user['rls'])->contains('CREATOR');
        });
        Gate::define('moderator', function ($user) {
            return collect($user['rls'])->contains('MODERATOR');
        });
        Gate::define('applicant', function ($user) {
            return collect($user['rls'])->contains('APPLICANT');
        });
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            $token = $request->bearerToken();
            if ($token) {
                try {
                    $decoded = (array)JWT::decode($token, new Key(env('PUBLIC_KEY'), 'RS256'));
                    if(Carbon::parse($decoded['exp'])->lt(Carbon::now()))
                        return null;
                    return $decoded;
                } catch (\Throwable $e) {
                    return null;
                }
            }
        });
    }
}
