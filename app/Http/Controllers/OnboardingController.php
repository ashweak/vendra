<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Membership;
use App\Models\Subscription;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    public function showBusiness()
    {
        return view('onboarding.business', ['categories' => config('vendra.categories')]);
    }

    /** Step 1: create the business from a category template. The creator becomes its owner. */
    public function storeBusiness(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|in:'.implode(',', array_keys(config('vendra.categories'))),
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:255',
        ]);

        $slug = Str::slug($data['name']) ?: 'business';
        $base = $slug;
        $i = 2;
        while (Business::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $business = Business::create([
            'name' => $data['name'],
            'slug' => $slug,
            'category' => $data['category'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'email' => $request->user()->email,
            'modules' => config('vendra.categories.'.$data['category'].'.modules'),
            'settings' => config('vendra.default_settings'),
            'onboarding_step' => 2,
        ]);
        foreach (range(0, 6) as $d) {
            $business->hours()->create(['weekday' => $d, 'opens_at' => '09:00', 'closes_at' => '18:00', 'is_closed' => $d === 0]);
        }
        $m = new Membership(['user_id' => $request->user()->id, 'role' => 'owner']);
        $m->business_id = $business->id;
        $m->save();
        Subscription::create([
            'business_id' => $business->id, 'plan_name' => 'Local trial', 'status' => 'trial',
            'ai_monthly_limit' => config('vendra.ai.default_monthly_limit'), 'staff_limit' => 3, 'starts_on' => now()->toDateString(),
        ]);
        $request->session()->put('active_business_id', $business->id);
        AuditLogger::log($business->id, 'business.created', $business);

        return redirect()->route('dashboard.onboarding')->with('status', 'Business created. Finish the checklist to activate it.');
    }
}
