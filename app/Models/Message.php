<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';
    protected $fillable = [
        'from_id',
        'from_role',
        'to_id',
        'to_role',
        'message',
        'isSeen',
        'delivered_at',
        'seen_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'seen_at' => 'datetime',
    ];

    public function attachments() {
        return $this->hasMany(MessageAttachments::class, 'message_id', 'id');
    }

    public function personal() {
        return $this->hasOne(EmployeePersonal::class, 'employee_id', 'to_id');
    }

}
