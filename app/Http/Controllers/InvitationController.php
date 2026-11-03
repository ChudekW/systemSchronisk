<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $user = User::where('invitation_token', hash('sha256', $token))->with('organization')->first();
        $valid = $user?->invitationIsValid() === true;
        $response = Inertia::render('auth/AcceptInvitation', [
            'token' => $valid ? $token : null,
            'invitation' => $valid ? ['name' => $user->name, 'email' => $user->email, 'shelter' => $user->organization->shelter_name, 'role' => User::TEAM_ROLES[$user->teamRole()]] : null,
        ])->toResponse($request);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cache-Control', 'no-store, private');
        if (! $valid) {
            $response->setStatusCode(410);
        }

        return $response;
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers(), 'confirmed'],
        ], attributes: ['name' => 'imię i nazwisko', 'password' => 'hasło']);
        $hash = hash('sha256', $token);
        $invited = User::where('invitation_token', $hash)->firstOrFail();
        DB::transaction(function () use ($invited, $hash, $data): void {
            Organization::query()->lockForUpdate()->findOrFail($invited->organization_id);
            $user = User::query()->lockForUpdate()->findOrFail($invited->id);
            abort_unless($user->invitationIsValid() && hash_equals($user->invitation_token, $hash), 410);
            $user->name = $data['name'];
            $user->password = $data['password'];
            $user->email_verified_at = Carbon::now();
            $user->invitation_accepted_at = Carbon::now();
            $user->invitation_token = null;
            $user->invitation_expires_at = null;
            $user->is_active = true;
            $user->save();
        });

        return to_route('login')->with('status', 'Konto zostało aktywowane. Zaloguj się ustawionym hasłem.');
    }
}
