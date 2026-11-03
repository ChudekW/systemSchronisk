<?php

namespace App\Models;

use Database\Factories\AdoptionApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['candidate_name', 'email', 'phone', 'housing', 'candidate_notes', 'coordinator_id', 'status', 'adopted_on'])]
class AdoptionApplication extends Model
{
    /** @use HasFactory<AdoptionApplicationFactory> */
    use HasFactory;

    public const STATUSES = ['new' => 'Nowe', 'reviewing' => 'Weryfikacja', 'meeting' => 'Spotkanie', 'approved' => 'Zgoda na adopcję', 'completed' => 'Adopcja zakończona', 'returned' => 'Powrót z adopcji', 'rejected' => 'Odmowa', 'withdrawn' => 'Rezygnacja / zamknięte'];

    public const OPEN = ['new', 'reviewing', 'meeting', 'approved'];

    public const TRANSITIONS = ['new' => ['reviewing', 'rejected', 'withdrawn'], 'reviewing' => ['meeting', 'rejected', 'withdrawn'], 'meeting' => ['reviewing', 'approved', 'rejected', 'withdrawn'], 'approved' => ['meeting', 'completed', 'rejected', 'withdrawn'], 'completed' => [], 'returned' => [], 'rejected' => [], 'withdrawn' => []];

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    /** @return HasMany<AdoptionHistory, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(AdoptionHistory::class);
    }

    /** @return HasMany<AdoptionAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(AdoptionAttachment::class);
    }

    /** @return HasMany<CareTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(CareTask::class);
    }
}
