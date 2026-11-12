<fieldset class="mt-6 space-y-4">
    <legend class="h2">Your details</legend>
    <p class="text-xs text-gray-500">We only ask for what this request needs. Never share passwords, PINs or card numbers.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="name">Full name</label><input id="name" name="name" class="input" required maxlength="120" value="{{ old('name', $data['name'] ?? '') }}" autocomplete="name"><x-field-error name="name" /></div>
        <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" class="input" required value="{{ old('email', $data['email'] ?? '') }}" autocomplete="email"><p class="hint">Used to send you a secure link to track this request.</p><x-field-error name="email" /></div>
        <div><label class="label" for="phone">Mobile number {{ ($phoneRequired ?? false) ? '' : '(optional)' }}</label><input id="phone" name="phone" class="input" @if($phoneRequired ?? false) required @endif maxlength="40" value="{{ old('phone', $data['phone'] ?? '') }}" autocomplete="tel" placeholder="09XX XXX XXXX"><x-field-error name="phone" /></div>
    </div>
    <label class="flex items-start gap-2 text-sm text-gray-700">
        <input type="checkbox" name="marketing_consent" value="1" class="mt-1" @checked(old('marketing_consent'))>
        <span>Optional: send me promotions from {{ $business->name }}. (Not required to submit.)</span>
    </label>
</fieldset>
