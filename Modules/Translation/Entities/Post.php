<?php

namespace Modules\Translation\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * This is the model class for table "posts".
 */
class Post extends Model
{
    use SoftDeletes;

    protected $table = 'posts';

    protected $fillable = [
        'id',
        'name',
        'email',
        'status',
        'file_id',
        'type',
        'balance',
        'user_id',
        'email_verified_at',
        'password',
        'remember_token',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
