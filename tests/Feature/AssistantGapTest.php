<?php

namespace Tests\Feature;

use App\Models\AssistantGap;
use App\Models\Conversation;
use App\Models\KnowledgeEntry;
use App\Services\Ai\AssistantService;
use App\Services\Ai\KnowledgeMatcher;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantGapTest extends TestCase
{
    use RefreshDatabase;

    private function chat($b): Conversation
    {
        return app(ConversationService::class)->start($b)[0];
    }

    private function ask(Conversation $c, string $text): array
    {
        app(ConversationService::class)->addMessage($c, 'customer', $text);

        return app(AssistantService::class)->reply($c->fresh(), $text);
    }

    private function faq($b, string $title, string $body): KnowledgeEntry
    {
        $e = new KnowledgeEntry(['kind' => 'faq', 'title' => $title, 'body' => $body, 'is_published' => true]);
        $e->business_id = $b->id;
        $e->save();

        return $e;
    }

    public function test_assistant_answers_from_published_faq_only(): void
    {
        $b = $this->makeBusiness();
        $this->faq($b, 'Parking', 'Free street parking in front of the shop.');
        $draft = $this->faq($b, 'Pets', 'Small pets are welcome.');
        $draft->update(['is_published' => false]);

        $r = $this->ask($this->chat($b), 'May parking po ba kayo?');
        $this->assertSame('Free street parking in front of the shop.', $r['reply']);
        $this->assertContains('search_knowledge', array_column($r['tools'], 'name'));

        $r = $this->ask($this->chat($b), 'Can I bring my pets?');
        $this->assertStringNotContainsString('welcome', $r['reply'], 'unpublished entries are never used');
        $this->assertSame(0, KnowledgeMatcher::match($b, 'Can I bring my pets?')->count());
    }

    public function test_unanswered_questions_are_logged_masked_and_grouped(): void
    {
        $b = $this->makeBusiness();
        $r = $this->ask($this->chat($b), 'Do you have wheelchair access?');
        $this->assertStringContainsString('didn’t quite get', $r['reply']);
        $this->ask($this->chat($b), 'wheelchair access do you have?');      // same question, other words order
        $this->ask($this->chat($b), 'Can you email me the brochure at ana@example.test or 0917 123 4567?');
        $this->ask($this->chat($b), 'asdfgh');                               // gibberish is not saved
        $this->ask($this->chat($b), 'Thanks!');                              // answered normally

        $this->assertSame(2, AssistantGap::count());
        $access = AssistantGap::where('question', 'like', '%wheelchair%')->sole();
        $this->assertSame(2, $access->times_asked);
        $this->assertSame('Can you email me the brochure at [email] or [number]?', AssistantGap::where('question', 'like', '%brochure%')->value('question'));
        $this->assertSame('Ref [reference], call [number]', AssistantGap::mask('Ref APT-7KQ2LM, call +63 917 123 4567'));
    }

    public function test_owner_turns_a_question_into_a_faq_the_assistant_then_uses(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $c = $this->chat($b);
        $this->ask($c, 'Do you have wheelchair access?');
        app(ConversationService::class)->staffReply($c->fresh(), $owner, 'Yes, there is a ramp at the side entrance.');
        $gap = AssistantGap::sole();

        $this->actingAs($owner)->get(route('dashboard.settings.unanswered'))->assertOk()
            ->assertSee('Do you have wheelchair access?')->assertSee('Yes, there is a ramp at the side entrance.')
            ->assertSee('1×', false);
        $this->actingAs($owner)->get(route('dashboard.settings.profile'))->assertSee('Unanswered questions');

        $this->actingAs($owner)->post(route('dashboard.settings.unanswered.answer', $gap->id), ['title' => 'Wheelchair access', 'body' => 'Yes, there is a ramp at the side entrance.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('answered', $gap->fresh()->status);
        $entry = KnowledgeEntry::where('title', 'Wheelchair access')->sole();
        $this->assertTrue($entry->is_published);

        $r = $this->ask($this->chat($b), 'Is there wheelchair access?');
        $this->assertSame('Yes, there is a ramp at the side entrance.', $r['reply']);
        $this->actingAs($owner)->get(route('dashboard.settings.unanswered', ['show' => 'answered']))->assertSee('Wheelchair access');
    }

    public function test_dismiss_reopens_when_asked_again_and_access_is_limited(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $staff = $this->addMember($b, 'staff');
        $this->ask($this->chat($b), 'Do you sell gift cards?');
        $gap = AssistantGap::sole();

        $this->actingAs($staff)->get(route('dashboard.settings.unanswered'))->assertForbidden();
        $this->actingAs($staff)->post(route('dashboard.settings.unanswered.dismiss', $gap->id))->assertForbidden();
        $other = $this->addMember($this->makeBusiness(), 'owner');
        $this->actingAs($other)->post(route('dashboard.settings.unanswered.dismiss', $gap->id))->assertNotFound();

        $this->actingAs($owner)->post(route('dashboard.settings.unanswered.dismiss', $gap->id))->assertSessionHasNoErrors();
        $this->assertSame('dismissed', $gap->fresh()->status);
        $this->ask($this->chat($b), 'gift cards do you sell?');
        $this->assertSame('open', $gap->fresh()->status);
        $this->assertSame(2, $gap->fresh()->times_asked);
    }

    public function test_old_questions_are_removed_by_retention(): void
    {
        $b = $this->makeBusiness();
        $this->ask($this->chat($b), 'Do you sell gift cards?');
        $this->travel(91)->days();
        $this->artisan('vendra:retention')->assertSuccessful();
        $this->assertSame(0, AssistantGap::count());
    }
}
