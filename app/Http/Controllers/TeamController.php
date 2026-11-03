<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('team/Index', [
            'members' => $request->user()->organization->users()->where('is_super_admin', false)->orderBy('name')->paginate(20)->through(fn (User $user): array => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                'role' => $user->teamRole(), 'is_active' => $user->is_active,
                'pending' => $user->hasPendingInvitation(),
                'invitation_status' => ! $user->hasPendingInvitation() ? null : ($user->invitation_token === null ? 'cancelled' : ($user->invitation_expires_at?->isPast() ? 'expired' : 'pending')),
                'invitation_expires_at' => $user->invitation_expires_at?->toIso8601String(),
            ]),
            'roles' => User::TEAM_ROLES,
        ]);
    }

    public function store(InviteTeamMemberRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $organization = $this->lockOrganization($request);
            $user = new User($request->safe()->only(['name', 'email']));
            $user->organization()->associate($organization);
            $user->organization_role = $request->validated('role');
            $user->is_organization_admin = $user->organization_role === 'admin';
            $user->is_active = false;
            $user->password = Str::random(64);
            $this->invite($user);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zaproszenie przekazano do wysyłki. Link jest ważny przez 7 dni.']);

        return to_route('team.index');
    }

    public function update(UpdateTeamMemberRequest $request, int $member): RedirectResponse
    {
        DB::transaction(function () use ($request, $member): void {
            $organization = $this->lockOrganization($request);
            $user = $organization->users()->where('is_super_admin', false)->lockForUpdate()->findOrFail($member);
            if ($user->id === $request->user()->id) {
                throw ValidationException::withMessages(['member' => 'Nie możesz zmienić własnej roli ani zablokować własnego konta.']);
            }
            if ($user->hasPendingInvitation() && $request->boolean('is_active')) {
                throw ValidationException::withMessages(['member' => 'Pracownik musi najpierw przyjąć zaproszenie i ustawić hasło.']);
            }
            $user->organization_role = $request->validated('role');
            $user->is_organization_admin = $user->organization_role === 'admin';
            $user->is_active = $request->boolean('is_active');
            if (! $user->is_active) {
                $user->remember_token = Str::random(60);
                if (config('session.driver') === 'database') {
                    DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }
            }
            $user->save();
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zapisano uprawnienia pracownika.']);

        return to_route('team.index');
    }

    public function resend(Request $request, int $member): RedirectResponse
    {
        DB::transaction(function () use ($request, $member): void {
            $organization = $this->lockOrganization($request);
            $user = $organization->users()->where('is_super_admin', false)->lockForUpdate()->findOrFail($member);
            abort_unless($user->hasPendingInvitation(), 409);
            $this->invite($user);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nowe zaproszenie przekazano do wysyłki. Poprzedni link już nie działa.']);

        return to_route('team.index');
    }

    public function cancel(Request $request, int $member): RedirectResponse
    {
        DB::transaction(function () use ($request, $member): void {
            $organization = $this->lockOrganization($request);
            $user = $organization->users()->where('is_super_admin', false)->lockForUpdate()->findOrFail($member);
            abort_unless($user->hasPendingInvitation(), 409);
            $user->invitation_token = null;
            $user->invitation_expires_at = null;
            $user->save();
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Anulowano zaproszenie.']);

        return to_route('team.index');
    }

    private function lockOrganization(Request $request): Organization
    {
        $organization = Organization::query()->lockForUpdate()->findOrFail($request->user()->organization_id);
        Gate::forUser($request->user()->fresh())->authorize('manage-team');

        return $organization;
    }

    private function invite(User $user): void
    {
        $token = Str::random(64);
        $user->invitation_token = hash('sha256', $token);
        $user->invited_at = Carbon::now();
        $user->invitation_expires_at = Carbon::now()->addDays(7);
        $user->save();
        $user->notify((new TeamInvitation($token))->afterCommit());
    }
}
