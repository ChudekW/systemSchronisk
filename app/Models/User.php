<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int|null $organization_id
 * @property bool $is_active
 * @property string $organization_role
 * @property Carbon|null $invited_at
 * @property Carbon|null $invitation_expires_at
 * @property Carbon|null $invitation_accepted_at
 * @property bool $is_super_admin
 * @property bool $is_organization_admin
 * @property-read Organization|null $organization
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'invitation_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public const TEAM_ROLES = ['admin' => 'Administrator', 'caretaker' => 'Opiekun', 'veterinarian' => 'Weterynarz'];

    protected $attributes = ['is_active' => true, 'organization_role' => 'caretaker'];

    public function teamRole(): string
    {
        return $this->is_organization_admin ? 'admin' : $this->organization_role;
    }

    public function hasPendingInvitation(): bool
    {
        return $this->invited_at !== null && $this->invitation_accepted_at === null;
    }

    public function invitationIsValid(): bool
    {
        return $this->hasPendingInvitation() && $this->invitation_token !== null
            && $this->invitation_expires_at?->isFuture() === true
            && $this->organization?->is_active === true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'invited_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'is_organization_admin' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
