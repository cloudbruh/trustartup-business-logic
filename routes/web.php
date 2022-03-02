<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//info
$router->get('get_startups', 'StartupController@get');
$router->get('current_user', 'UserController@get');
$router->get('get_reviews', 'ReviewController@get');
$router->get('get_rewards', 'RewardController@get');
$router->get('get_applications', 'ApplicationController@get');

//requests
$router->post('request_role', 'DatasetController@requestRole');
$router->post('set_image', 'UserController@setMedia');
$router->post('create_startup', 'StartupController@create');
$router->post('apply_startup', 'ApplicationController@create');
$router->post('apply_worker', 'ApplicationController@apply');
$router->post('manage_worker', 'ApplicationController@manage');
$router->post('create_post', 'PostController@create');
$router->post('create_review', 'ReviewController@create');
$router->post('donate', 'PaymentController@create');

//moderation
$router->get('moderate_startup', 'ModeratorController@showStartups');
$router->get('moderate_user', 'ModeratorController@showUsers');

$router->post('moderate_startup', 'ModeratorController@startup');
$router->post('moderate_user', 'ModeratorController@user');
