<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from', 'to', 'read', 'text',
    ];

    public function fromContact() {
    	return $this->hasOne(User::class, 'id', 'from');
    }
}
