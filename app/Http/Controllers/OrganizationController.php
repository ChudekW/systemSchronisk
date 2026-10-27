<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('organizations/Index', [
            'organizations' => Organization::query()->withCount('users')->latest()->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('organizations/Create');
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = DB::transaction(function () use ($request): Organization {
            $organization = Organization::create($request->safe()->only(['name', 'shelter_name', 'email', 'phone', 'address']));
            $user = new User;
            $user->name = $request->validated('admin_name');
            $user->email = $request->validated('admin_email');
            $user->password = $request->validated('admin_password');
            $user->is_organization_admin = true;
            $user->organization()->associate($organization);
            $user->save();

            return $organization;
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Utworzono organizację i konto administratora.']);

        return to_route('organizations.edit', $organization);
    }

    public function edit(Organization $organization): Response
    {
        return Inertia::render('organizations/Edit', [
            'organization' => $organization,
            'members' => $organization->users()->get(['id', 'name', 'email', 'is_organization_admin']),
            'platform' => true,
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zapisano dane organizacji.']);

        return back();
    }
}
