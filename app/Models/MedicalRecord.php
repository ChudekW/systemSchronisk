<?php

namespace App\Models;

use Database\Factories\MedicalRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kind', 'title', 'occurred_on', 'performed_by', 'description', 'recommendations', 'weight_kg', 'correction_reason'])]
class MedicalRecord extends Model
{
    /** @use HasFactory<MedicalRecordFactory> */
    use HasFactory;

    public const KINDS = ['visit' => 'Wizyta', 'vaccination' => 'Szczepienie', 'deworming' => 'Odrobaczanie', 'procedure' => 'Zabieg', 'weight' => 'Pomiar masy ciała', 'observation' => 'Obserwacja opiekuna'];

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<MedicalAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(MedicalAttachment::class);
    }

    /** @return HasMany<MedicalRecord, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(self::class, 'correction_of_id');
    }

    /** @return BelongsTo<CareTask, $this> */
    public function careTask(): BelongsTo
    {
        return $this->belongsTo(CareTask::class);
    }
}
