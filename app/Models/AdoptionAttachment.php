<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['path', 'name'])]
#[Hidden(['path'])]
class AdoptionAttachment extends Model
{
    /** @return BelongsTo<AdoptionApplication, $this> */
    public function adoptionApplication(): BelongsTo
    {
        return $this->belongsTo(AdoptionApplication::class);
    }
}
