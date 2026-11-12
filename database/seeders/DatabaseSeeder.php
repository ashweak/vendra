<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\CatalogItem;
use App\Models\Membership;
use App\Models\StaffSchedule;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\ConversationService;
use App\Services\CustomerService;
use App\Services\OrderService;
use App\Services\QuotationService;
use App\Services\ServiceRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * FICTIONAL demo data only (P7/P9). All names, emails and numbers are invented.
 * Every account's password is "password". Owners are asked to set up MFA on first sign-in.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create(['name' => 'Vendra Admin', 'email' => 'admin@vendra.test', 'password' => 'password']);
        $admin->forceFill(['is_platform_admin' => true])->save();

        $this->salon();
        $this->retail();
        $this->cleaning();
    }

    private function business(array $attrs, string $ownerName, string $ownerEmail): array
    {
        $b = Business::create($attrs + [
            'settings' => config('vendra.default_settings'),
            'activated_at' => now(),
            'onboarding_step' => 9,
        ]);
        $settings = $b->settings;
        $settings['assistant_tested'] = true;
        $b->update(['settings' => array_merge($settings, $attrs['settings_override'] ?? [])]);

        foreach (range(0, 6) as $d) {
            $b->hours()->create(['weekday' => $d, 'opens_at' => '09:00', 'closes_at' => '19:00', 'is_closed' => $d === 0]);
        }
        $owner = User::create(['name' => $ownerName, 'email' => $ownerEmail, 'password' => 'password']);
        $this->member($b, $owner, 'owner');
        Subscription::create(['business_id' => $b->id, 'plan_name' => 'Local demo', 'status' => 'trial', 'ai_monthly_limit' => 300,
            'staff_limit' => 3, 'starts_on' => now()->toDateString()]);

        return [$b, $owner];
    }

    private function member(Business $b, User $u, string $role): void
    {
        $m = new Membership(['user_id' => $u->id, 'role' => $role]);
        $m->business_id = $b->id;
        $m->save();
    }

    private function item(Business $b, array $data, array $staff = []): CatalogItem
    {
        $i = new CatalogItem($data);
        $i->business_id = $b->id;
        if (isset($data['stock_on_hand'])) {
            $i->stock_on_hand = $data['stock_on_hand'];
        }
        $i->save();
        if ($staff) {
            $i->staff()->sync(array_map(fn ($u) => $u->id, $staff));
        }

        return $i;
    }

    private function knowledge(Business $b, array $entries): void
    {
        foreach ($entries as [$kind, $title, $body]) {
            $b->knowledgeEntries()->create(['kind' => $kind, 'title' => $title, 'body' => $body, 'is_published' => true]);
        }
    }

    private function salon(): void
    {
        [$b, $owner] = $this->business([
            'name' => 'Lumina Hair & Nail Studio', 'slug' => 'lumina-studio', 'category' => 'salon',
            'description' => 'Neighborhood salon for cuts, color, and nail care. Walk-ins welcome when slots are free.',
            'phone' => '0917 000 0101', 'email' => 'hello@lumina.test', 'address' => '12 Sampaguita St., Brgy. Malinis, Quezon City (fictional)',
            'accent_color' => '#7c3aed', 'modules' => ['appointments', 'quotations'],
            'greeting' => 'Hi! I’m Lumina’s AI assistant. Ask me about prices, open times, or bookings — English or Tagalog is fine.',
        ], 'Lara Mendoza', 'owner@lumina.test');
        $b->update(['settings' => array_merge($b->settings, ['booking_approval' => 'auto'])]);

        $maria = User::create(['name' => 'Maria Santos', 'email' => 'manager@lumina.test', 'password' => 'password']);
        $joy = User::create(['name' => 'Joy Reyes', 'email' => 'joy@lumina.test', 'password' => 'password']);
        $ken = User::create(['name' => 'Ken Villanueva', 'email' => 'ken@lumina.test', 'password' => 'password']);
        $this->member($b, $maria, 'manager');
        $this->member($b, $joy, 'staff');
        $this->member($b, $ken, 'staff');
        foreach ([$joy, $ken] as $u) {
            foreach (range(1, 6) as $d) {
                $s = new StaffSchedule(['user_id' => $u->id, 'weekday' => $d, 'starts_at' => $u === $joy ? '09:00' : '11:00', 'ends_at' => $u === $joy ? '17:00' : '19:00']);
                $s->business_id = $b->id;
                $s->save();
            }
        }

        $haircut = $this->item($b, ['type' => 'service', 'name' => 'Haircut', 'category' => 'Hair', 'price_cents' => 25000, 'duration_minutes' => 45, 'description' => 'Wash, cut and blow-dry.'], [$joy, $ken]);
        $this->item($b, ['type' => 'service', 'name' => 'Hair Color', 'category' => 'Hair', 'price_cents' => 180000, 'duration_minutes' => 120, 'requires_approval' => true, 'description' => 'Single-process color. Price may vary for long hair; staff will confirm.'], [$joy]);
        $this->item($b, ['type' => 'service', 'name' => 'Hair Rebond', 'category' => 'Hair', 'price_cents' => 250000, 'duration_minutes' => 180, 'requires_approval' => true], [$joy]);
        $this->item($b, ['type' => 'service', 'name' => 'Hair Spa', 'category' => 'Hair', 'price_cents' => 60000, 'duration_minutes' => 60], [$joy, $ken]);
        $mani = $this->item($b, ['type' => 'service', 'name' => 'Manicure', 'category' => 'Nails', 'price_cents' => 15000, 'duration_minutes' => 30], [$ken]);
        $this->item($b, ['type' => 'service', 'name' => 'Pedicure', 'category' => 'Nails', 'price_cents' => 20000, 'duration_minutes' => 45], [$ken]);

        $this->knowledge($b, [
            ['policy', 'Payment methods', 'We accept cash and GCash at the counter after your service. We never ask for card details or PINs in chat.'],
            ['policy', 'Late arrivals', 'Please arrive 10 minutes early. Bookings more than 15 minutes late may be moved to the next free slot.'],
            ['policy', 'Cancellations', 'Cancel online up to 6 hours before your appointment. Within 6 hours, please message us.'],
            ['faq', 'Do you accept walk-ins?', 'Yes, when a stylist is free. Booking online guarantees your slot.'],
            ['faq', 'Parking', 'There is free street parking in front of the salon (fictional).'],
        ]);

        // Sample activity
        $customers = app(CustomerService::class);
        $appts = app(AppointmentService::class);
        $tomorrow = CarbonImmutable::now($b->timezone)->addDay();
        if ($tomorrow->dayOfWeek === 0) {
            $tomorrow = $tomorrow->addDay();
        }
        $c1 = $customers->resolve($b, ['name' => 'Andrea Cruz', 'email' => 'andrea@example.test', 'phone' => '0918 000 0001', 'marketing_consent' => true]);
        $appts->book($b, $c1, ['catalog_item_id' => $haircut->id, 'start' => $tomorrow->toDateString().' 10:00', 'staff_id' => $joy->id, 'source' => 'phone'], $maria);
        $c2 = $customers->resolve($b, ['name' => 'Bea Lim', 'email' => 'bea@example.test', 'phone' => '0918 000 0002']);
        $appts->book($b, $c2, ['catalog_item_id' => $mani->id, 'start' => $tomorrow->toDateString().' 14:00', 'staff_id' => $ken->id, 'source' => 'walk_in'], $ken);

        $q = app(QuotationService::class)->submit($b, $c2, 'Bridal hair and makeup for 1 bride and 4 bridesmaids on a Saturday next month, at a venue in Marikina.', null);
        $q->update(['status' => 'under_review', 'assigned_to' => $maria->id]);

        $conv = app(ConversationService::class);
        [$c] = $conv->start($b);
        $c->update(['customer_id' => $c1->id]);
        $conv->addMessage($c, 'customer', 'Hello po, pwede po ba discount kung tatlo kami magpapagupit?');
        $conv->addMessage($c, 'ai', 'Discounts and special prices are decided by the owner, so I’ve passed your request to the team. They’ll reply here.');
        $conv->handoff($c, 'Discount or custom price request. Customer asks for a group discount for 3 haircuts.');
    }

    private function retail(): void
    {
        [$b] = $this->business([
            'name' => 'Ganda Beauty Hub', 'slug' => 'ganda-beauty', 'category' => 'retail',
            'description' => 'Affordable skincare and beauty products. Order online for pickup or delivery within the city.',
            'phone' => '0917 000 0202', 'email' => 'shop@ganda.test', 'address' => '45 Mabini Ave., Pasig City (fictional)',
            'accent_color' => '#db2777', 'modules' => ['orders'],
        ], 'Gina Ramos', 'owner@ganda.test');
        $b->update(['settings' => array_merge($b->settings, ['fulfillment_methods' => ['pickup', 'delivery'], 'order_approval' => 'manual'])]);

        $serum = $this->item($b, ['type' => 'product', 'name' => 'Vitamin C Serum 30ml', 'category' => 'Skincare', 'price_cents' => 49900, 'stock_on_hand' => 12]);
        $this->item($b, ['type' => 'product', 'name' => 'Sunscreen SPF50 50ml', 'category' => 'Skincare', 'price_cents' => 35000, 'stock_on_hand' => 3]);
        $toner = $this->item($b, ['type' => 'product', 'name' => 'Niacinamide Toner 100ml', 'category' => 'Skincare', 'price_cents' => 28000, 'stock_on_hand' => 20]);
        $this->item($b, ['type' => 'product', 'name' => 'Lip and Cheek Tint', 'category' => 'Makeup', 'price_cents' => 15000, 'stock_on_hand' => 0]);
        $this->item($b, ['type' => 'product', 'name' => 'Gentle Facial Wash 150ml', 'category' => 'Skincare', 'price_cents' => 22000, 'description' => 'Stock not tracked (made to order).']);

        $this->knowledge($b, [
            ['policy', 'Payment', 'Pay by GCash or cash on pickup. Staff confirm payment before releasing delivery orders.'],
            ['policy', 'Delivery', 'We deliver within Pasig and nearby cities. The fee depends on distance; staff confirm it before your order is confirmed.'],
            ['policy', 'Returns', 'Unopened items may be exchanged within 7 days with proof of purchase. Message us to arrange it.'],
        ]);

        $c = app(CustomerService::class)->resolve($b, ['name' => 'Carla Dizon', 'email' => 'carla@example.test', 'phone' => '0918 000 0003']);
        app(OrderService::class)->place($b, $c, [$serum->id => 1, $toner->id => 2], 'delivery', 'Unit 5, Fictional Tower, Ortigas', null, null);
    }

    private function cleaning(): void
    {
        [$b, $owner] = $this->business([
            'name' => 'Kislap Home Cleaning', 'slug' => 'kislap-cleaning', 'category' => 'cleaning',
            'description' => 'General, deep and aircon cleaning for homes and small offices in Metro Manila.',
            'phone' => '0917 000 0303', 'email' => 'book@kislap.test', 'address' => 'Serves Metro Manila (fictional)',
            'accent_color' => '#0d9488', 'modules' => ['service_requests', 'quotations', 'appointments'],
        ], 'Karlo Bautista', 'owner@kislap.test');
        $b->update(['settings' => array_merge($b->settings, ['booking_approval' => 'manual'])]);

        $this->item($b, ['type' => 'service', 'name' => 'General Cleaning (up to 50 sqm)', 'category' => 'Home', 'price_cents' => 150000, 'duration_minutes' => 180]);
        $this->item($b, ['type' => 'service', 'name' => 'Deep Cleaning (up to 50 sqm)', 'category' => 'Home', 'price_cents' => 350000, 'duration_minutes' => 300]);
        $this->item($b, ['type' => 'service', 'name' => 'Aircon Cleaning (per unit)', 'category' => 'Aircon', 'price_cents' => 80000, 'duration_minutes' => 90]);

        $this->knowledge($b, [
            ['policy', 'Service area', 'Metro Manila only. Areas outside may need a transport fee quoted by staff.'],
            ['faq', 'What should I prepare?', 'Please secure valuables and make sure there is water and electricity on the day.'],
        ]);

        $c = app(CustomerService::class)->resolve($b, ['name' => 'Dan Navarro', 'email' => 'dan@example.test', 'phone' => '0918 000 0004']);
        app(ServiceRequestService::class)->submit($b, $c, [
            'description' => 'Post-renovation cleaning for a 2-bedroom condo, lots of dust on floors and windows.',
            'location' => 'Mandaluyong City', 'preferred_date' => now()->addDays(5)->toDateString(),
        ]);
    }
}
