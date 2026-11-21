<?php

namespace App\Http\Controllers\Dashboard;

use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CustomerController extends DashboardController
{
    public function index(Request $request)
    {
        $this->authorizeAbility('customers.all');
        $q = trim((string) $request->query('q'));
        $customers = $this->business()->customers()
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")))
            ->withCount(['appointments', 'orders', 'quotations', 'serviceRequests'])
            ->latest('last_activity_at')->paginate(30)->withQueryString();

        return view('dashboard.customers.index', compact('customers', 'q'));
    }

    public function show(int $id)
    {
        $this->authorizeAbility('customers.all');
        $c = $this->business()->customers()->findOrFail($id);

        return view('dashboard.customers.show', [
            'customer' => $c,
            'appointments' => $c->appointments()->latest('starts_at')->get(),
            'orders' => $c->orders()->latest()->get(),
            'quotations' => $c->quotations()->latest()->get(),
            'requests' => $c->serviceRequests()->latest()->get(),
            'consents' => $c->consents()->latest('recorded_at')->get(),
            'privacyRequests' => $c->privacyRequests()->with('handler')->latest()->get(),
        ]);
    }

    /** Close a customer's privacy request with a note (owner/manager). */
    public function resolvePrivacy(Request $request, int $id, int $requestId)
    {
        $this->authorizeAbility('exports.create');
        $data = $request->validate(['status' => 'required|in:done,declined', 'resolution' => 'required|string|max:255']);
        $c = $this->business()->customers()->findOrFail($id);
        $pr = $c->privacyRequests()->where('status', 'open')->findOrFail($requestId);
        $pr->update($data + ['handled_by' => $request->user()->id, 'handled_at' => now()]);
        AuditLogger::log($c->business_id, 'privacy.request_'.$data['status'], $pr, ['resolution' => $data['resolution']]);

        return back()->with('status', 'Privacy request updated. Remember to reply to the customer.');
    }

    /** Verified correction/deletion requests are handled by the owner; transactions are anonymized, not deleted. */
    public function anonymize(Request $request, int $id)
    {
        $this->authorizeAbility('exports.create');
        $request->validate(['reason' => 'required|string|max:255', 'confirm' => 'accepted']);
        $c = $this->business()->customers()->findOrFail($id);
        if ($c->appointments()->whereIn('status', ['pending_approval', 'confirmed'])->exists()
            || $c->orders()->whereIn('status', ['pending_approval', 'confirmed', 'preparing', 'ready_for_pickup', 'dispatched'])->exists()) {
            return back()->withErrors(['rule' => 'This customer has open transactions. Resolve them first.']);
        }
        $c->update(['name' => 'Deleted customer #'.$c->id, 'email' => null, 'phone' => null, 'marketing_consent' => false]);
        $c->conversations()->each(fn ($conv) => $conv->messages()->update(['body' => '[removed]']));
        AuditLogger::log($c->business_id, 'customer.anonymized', $c, [], $request->input('reason'));
        $c->privacyRequests()->where('status', 'open')->where('type', 'deletion')
            ->update(['status' => 'done', 'resolution' => 'Personal details removed.', 'handled_by' => $request->user()->id, 'handled_at' => now()]);

        return redirect()->route('dashboard.customers.index')->with('status', 'Customer details removed. Transaction records were kept without identity.');
    }
}
