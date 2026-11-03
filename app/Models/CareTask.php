<?php

namespace App\Models;

use Database\Factories\CareTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'assignee_id', 'due_date', 'priority', 'status'])]
class CareTask extends Model
{
    /** @use HasFactory<CareTaskFactory> */
    use HasFactory;

    public const STATUSES = ['todo' => 'Do wykonania', 'in_progress' => 'W trakcie', 'done' => 'Wykonane'];

    public const PRIORITIES = ['low' => 'Niski', 'normal' => 'Normalny', 'high' => 'Wysoki'];

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return HasMany<CareTaskHistory, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(CareTaskHistory::class);
    }
}
