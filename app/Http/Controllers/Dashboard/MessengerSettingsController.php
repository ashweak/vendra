<?php

namespace App\Http\Controllers\Dashboard;

use App\Services\AuditLogger;
use App\Services\Messenger\MessengerClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Connect a Facebook Page so its Messenger chats come into the Vendra inbox. */
class MessengerSettingsController extends DashboardController
{
    public function show()
    {
        $this->authorizeAbility('settings.manage');

        return view('dashboard.settings.messenger', ['b' => $this->business(), 'enabled' => MessengerClient::enabled()]);
    }

    public function connect(Request $request, MessengerClient $client)
    {
        $this->authorizeAbility('settings.manage');
        abort_unless(MessengerClient::enabled(), 404);
        $data = $request->validate([
            'page_id' => 'required|string|regex:/^\d{5,30}$/',
            'token' => 'required|string|min:20|max:1000',
        ], ['page_id.regex' => 'The Page ID is the number shown in your Page’s About → Page transparency (digits only).']);
        $b = $this->business();
        if (\App\Models\Business::where('messenger_page_id', $data['page_id'])->where('id', '!=', $b->id)->exists()) {
            throw ValidationException::withMessages(['page_id' => 'That Page is already connected to another business.']);
        }
        $page = $client->page($data['token']);
        if (! $page || $page['id'] !== $data['page_id']) {
            throw ValidationException::withMessages(['token' => 'Facebook didn’t accept this token for that Page. Generate a Page access token for the same Page and try again.']);
        }
        $b->forceFill(['messenger_page_id' => $data['page_id'], 'messenger_token' => $data['token']])->save();
        AuditLogger::log($b->id, 'messenger.connected', $b, ['page_id' => $data['page_id'], 'page_name' => $page['name']]);

        return back()->with('status', 'Connected to '.$page['name'].'. New Messenger chats will appear in your inbox.');
    }

    public function disconnect()
    {
        $this->authorizeAbility('settings.manage');
        $b = $this->business();
        $b->forceFill(['messenger_page_id' => null, 'messenger_token' => null])->save();
        AuditLogger::log($b->id, 'messenger.disconnected', $b);

        return back()->with('status', 'Messenger disconnected. Existing conversations stay in your inbox.');
    }
}
