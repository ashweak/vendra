@props(['name' => 'Lumina Studio', 'ask' => 'Pa-book po ng haircut bukas 3pm', 'answer' => 'Bakante po ang Haircut bukas, 3:00 PM. I-tap ang “Review & confirm”.', 'item' => 'Haircut', 'price' => '₱250.00', 'meta' => 'With Joy · 45 min'])
<div {{ $attributes->merge(['class' => 'phone']) }}>
    <div class="phone-screen">
        <div class="flex items-center gap-2.5 border-b border-line bg-white px-4 pt-6 pb-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-900 text-sun"><x-icon name="spark" class="h-4 w-4" /></span>
            <span class="leading-tight"><span class="block text-sm font-semibold">{{ $name }}</span><span class="block text-[11px] text-leaf">● AI assistant online</span></span>
        </div>
        <div class="dots space-y-2.5 px-3.5 py-4 text-[13px]">
            <div class="ml-auto w-fit max-w-[85%] rounded-2xl rounded-br-md bg-ink-900 px-3.5 py-2 text-white">{{ $ask }}</div>
            <div class="w-fit max-w-[92%] rounded-2xl rounded-bl-md bg-white px-3.5 py-2.5 shadow-sm">
                <p>{{ $answer }}</p>
                <div class="mt-2.5 rounded-xl bg-sun-soft p-3">
                    <p class="flex justify-between font-semibold"><span>{{ $item }}</span><span>{{ $price }}</span></p>
                    <p class="text-[11px] text-ink-600">{{ $meta }}</p>
                    <span class="btn btn-primary btn-sm mt-2 w-full">Review & confirm</span>
                </div>
            </div>
            <div class="w-fit rounded-full bg-white px-3 py-1.5 text-[11px] text-ink-500 shadow-sm">Nothing is booked until you confirm</div>
        </div>
    </div>
</div>
