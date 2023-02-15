<?php

namespace App\Http\Controllers;

use App\User;
use App\Message;
use App\Events\SendMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request)
    {
    	$message = Message::create([
    		'from' => auth()->id(),
    		'to' => $request->user_id,
            'read' => true,
    		'text' => $request->text
    	]);

    	broadcast(new SendMessage($message))->toOthers();

    	return $message;
    }

    public function show($id)
    {
        // Mark as read
        Message::where('from', $id)->where('to', auth()->id())->update(['read' => true]);

        $messages = Message::where(function ($q) use($id) {
            $q->where('from', $id)
                ->where('to', auth()->id());
        })
        ->orWhere(function ($q) use($id) {
            $q->where('from', auth()->id())
                ->where('to', $id);
        })
        ->get();
        return $messages;
    }
}
