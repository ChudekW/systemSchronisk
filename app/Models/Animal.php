<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AnimalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

#[Fillable(['registration_number', 'name', 'species', 'breed', 'sex', 'birth_date', 'birth_date_estimated', 'color', 'weight_kg', 'microchip', 'admitted_at', 'admission_reason', 'found_location', 'location', 'status', 'sterilization_status', 'health_notes', 'behavior_notes', 'care_notes', 'description'])]
#[Hidden(['photo_path'])]
class Animal extends Model
{
    /** @use HasFactory<AnimalFactory> */
    use HasFactory;

    use Searchable;

    /** @return array<string, int|string|null> */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'organization_id' => (int) $this->organization_id,
            'name' => $this->name,
            'registration_number' => $this->registration_number,
            'microchip' => $this->microchip,
            'breed' => $this->breed,
            'color' => $this->color,
            'location' => $this->location,
            'species' => $this->species,
            'status' => $this->status,
            'admitted_at' => CarbonImmutable::parse($this->admitted_at)->getTimestamp(),
        ];
    }

    public const SPECIES = ['dog' => 'Pies', 'cat' => 'Kot', 'rabbit' => 'Królik', 'rodent' => 'Gryzoń', 'bird' => 'Ptak', 'other' => 'Inny gatunek'];

    public const SEXES = ['male' => 'Samiec', 'female' => 'Samica', 'unknown' => 'Nieustalona'];

    public const STATUSES = ['admitted' => 'Przyjęte', 'quarantine' => 'Kwarantanna', 'treatment' => 'W leczeniu', 'ready' => 'Gotowe do adopcji', 'reserved' => 'Zarezerwowane', 'foster' => 'Dom tymczasowy', 'adopted' => 'Adoptowane', 'returned' => 'Zwrócone właścicielowi', 'transferred' => 'Przekazane', 'deceased' => 'Zmarłe'];

    public const ADMISSION_REASONS = ['found' => 'Znalezione', 'intervention' => 'Interwencja', 'owner_surrender' => 'Przekazane przez właściciela', 'transfer' => 'Transfer ze schroniska', 'adoption_return' => 'Powrót z adopcji', 'other' => 'Inny powód'];

    public const STERILIZATION = ['unknown' => 'Brak danych', 'yes' => 'Po kastracji / sterylizacji', 'no' => 'Niekastrowane / niesterylizowane'];

    protected function casts(): array
    {
        return ['birth_date' => 'date:Y-m-d', 'admitted_at' => 'date:Y-m-d', 'birth_date_estimated' => 'boolean', 'weight_kg' => 'decimal:2'];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<AnimalStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(AnimalStatusHistory::class);
    }
}
