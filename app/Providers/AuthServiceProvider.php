<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{

    private function getUserRoles($user){
        $response = Http::get(config('api.API_USER') . '/user/' . $user);
        if (!$response->successful())
            return collect();
        return collect($response->object()->roles)->pluck('type');
    }

    /**
     * Register any application services.
     *
     * @return void
     */

    public function register()
    {

        Gate::define('creator', function ($user) {
            return $this->getUserRoles($user)->contains('CREATOR');
        });
        Gate::define('moderator', function ($user) {
            return $this->getUserRoles($user)->contains('MODERATOR');
        });
        Gate::define('applicant', function ($user) {
            return $this->getUserRoles($user)->contains('APPLICANT');
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
                    return $decoded['uid'];
                } catch (\Throwable $e) {
                    return null;
                }
            }
        });
    }
}
