<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantOrganizationController extends Controller
{
    public function edit(Request $request): Response
    {
        $organization = $request->user()->organization;

        return Inertia::render('organizations/Edit', [
            'organization' => $organization,
            'members' => $organization->users()->get(['id', 'name', 'email', 'is_organization_admin']),
            'platform' => false,
        ]);
    }

    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $request->user()->organization->update($request->safe()->only(['name', 'shelter_name', 'email', 'phone', 'address']));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zapisano dane Twojej organizacji.']);

        return back();
    }
}
