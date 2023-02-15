<?php

use App\User;
use App\Message;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware('auth')->group(function () {
	Route::get('messages/{user}', 'MessageController@show');
	Route::post('messages', 'MessageController@store');
	Route::get('users', function () {
		$users = User::where('id', '<>', auth()->id())->get();
		$unreadIds = Message::select(\DB::raw('`from` as sender_id, count(`from`) as messages_count'))
            ->where('to', auth()->id())
            ->where('read', false)
            ->groupBy('from')
            ->get();

        $users = $users->map(function ($user) use($unreadIds) {
        	$contactUnread = $unreadIds->where('sender_id', $user->id)->first();
        	$user->unread = $contactUnread ? $contactUnread->messages_count : 0;
        	return $user;
        });
		return $users;
	});
});

Route::get('/home', 'HomeController@index')->name('home');
