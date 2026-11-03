<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'author_name', 'note', 'snapshot'])]
class CareTaskHistory extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
