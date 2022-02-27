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
$router->get('startups', 'StartupController@get');

//forms
$router->post('create_dataset', 'UserController@dataset');
$router->post('create_startup', 'StartupController@create');
$router->post('apply', 'ApplicationController@create');
$router->post('create_post', 'PostController@create');
$router->post('create_review', 'ReviewController@create');

//moderation
$router->get('moderate_startup', 'ModeratorController@showStartups');
$router->get('moderate_user', 'ModeratorController@showUsers');

$router->post('moderate_startup', 'ModeratorController@startup');
$router->post('moderate_user', 'ModeratorController@user');
