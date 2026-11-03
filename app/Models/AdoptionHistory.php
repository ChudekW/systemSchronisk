<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['author_name', 'note', 'snapshot'])]
class AdoptionHistory extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
