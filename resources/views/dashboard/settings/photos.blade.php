@extends('layouts.dashboard')
@section('title', 'Photos')
@section('heading', 'Settings')
@section('content')
@include('dashboard.settings.nav')
@php $b = $currentBusiness; @endphp
<div class="max-w-5xl space-y-6">
    <p class="text-sm text-ink-600">Pictures appear on your customer page in every layout. Use JPG, PNG or WebP up to 5 MB. We resize them and remove hidden details such as location and camera info before saving. Only upload pictures you own or have permission to use, and ask people before posting photos of them.</p>

    <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
        <section class="card space-y-4">
            <h2 class="h2">Logo</h2>
            <div class="flex items-center gap-4">
                <x-biz-mark :business="$b" class="h-20 w-20 rounded-2xl text-2xl" />
                <p class="text-xs text-ink-600">Square works best. Shown in your page header. Without a logo we show your first letter on your brand color.</p>
            </div>
            <form method="POST" action="{{ route('dashboard.settings.photos.upload', 'logo') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                @csrf
                <label class="sr-only" for="logo">Choose a logo</label>
                <input id="logo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required class="text-sm">
                <button class="btn btn-primary btn-sm">Upload logo</button>
            </form>
            @if ($b->logo_path)<form method="POST" action="{{ route('dashboard.settings.photos.remove', 'logo') }}">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Remove logo</button></form>@endif
        </section>

        <section class="card space-y-4">
            <h2 class="h2">Cover photo</h2>
            @if ($b->cover_path)
                <img src="{{ $b->coverUrl() }}" @srcset($b->cover_path, '(min-width: 1024px) 600px, 100vw') alt="Current cover photo" class="aspect-[16/7] w-full rounded-2xl object-cover ring-1 ring-line">
            @else
                <div class="dots flex aspect-[16/7] w-full items-center justify-center rounded-2xl bg-tile text-sm text-ink-500">No cover photo yet</div>
            @endif
            <p class="text-xs text-ink-600">A wide photo of your shop, work or products (landscape, at least 1200 px wide). Each layout places it in its welcome area.</p>
            <form method="POST" action="{{ route('dashboard.settings.photos.upload', 'cover') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                @csrf
                <label class="sr-only" for="cover">Choose a cover photo</label>
                <input id="cover" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required class="text-sm">
                <button class="btn btn-primary btn-sm">Upload cover</button>
            </form>
            @if ($b->cover_path)<form method="POST" action="{{ route('dashboard.settings.photos.remove', 'cover') }}">@csrf @method('DELETE')<button class="text-sm text-red-700 underline">Remove cover photo</button></form>@endif
        </section>
    </div>
    <x-field-error name="photo" />

    <section class="card">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="h2">Gallery</h2>
                <p class="mt-1 text-sm text-ink-600">Up to {{ $max }} sample photos of your work, shop or products. {{ $photos->count() }} of {{ $max }} used.</p>
            </div>
            @if ($photos->count() < $max)
                <form method="POST" action="{{ route('dashboard.settings.photos.gallery') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <label class="sr-only" for="gallery">Choose photos</label>
                    <input id="gallery" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" required class="text-sm">
                    <button class="btn btn-primary btn-sm">Add photos</button>
                </form>
            @endif
        </div>
        <x-field-error name="photos" />
        @error('photos.*')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        @if ($photos->isEmpty())
            <div class="mt-5"><x-empty title="No photos yet">Add a few pictures so customers can see your work.</x-empty></div>
        @else
            <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($photos as $photo)
                    <li class="rounded-2xl border border-line p-2">
                        <img src="{{ $photo->url() }}" @srcset($photo->path, '(min-width: 1024px) 220px, 50vw') loading="lazy" alt="{{ $photo->caption ?: 'Gallery photo '.$loop->iteration }}" class="aspect-[4/3] w-full rounded-xl object-cover">
                        <form method="POST" action="{{ route('dashboard.settings.photos.gallery.update', $photo->id) }}" class="mt-2 flex gap-1.5">
                            @csrf @method('PUT')
                            <label class="sr-only" for="cap-{{ $photo->id }}">Caption</label>
                            <input id="cap-{{ $photo->id }}" name="caption" value="{{ $photo->caption }}" maxlength="120" placeholder="Caption (optional)" class="input h-9 py-1 text-xs">
                            <button class="btn btn-secondary btn-sm h-9 min-h-0 px-3">Save</button>
                        </form>
                        <form method="POST" action="{{ route('dashboard.settings.photos.gallery.destroy', $photo->id) }}" class="mt-1.5">@csrf @method('DELETE')<button class="text-xs text-red-700 underline">Remove</button></form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
@endsection
