<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\BusinessPhoto;
use App\Services\AuditLogger;
use App\Services\ImageStore;
use Illuminate\Http\Request;

/** Logo, cover photo and gallery for the customer page (owners and managers with settings access). */
class PhotoController extends DashboardController
{
    public function __construct(private ImageStore $images) {}

    public function index()
    {
        $this->authorizeAbility('settings.manage');

        return view('dashboard.settings.photos', ['photos' => $this->business()->photos()->get(), 'max' => BusinessPhoto::MAX]);
    }

    /** Logo (square) or cover (wide). */
    public function upload(Request $request, string $kind)
    {
        $this->authorizeAbility('settings.manage');
        abort_unless(in_array($kind, ['logo', 'cover'], true), 404);
        $request->validate(['photo' => ImageStore::RULES]);
        $b = $this->business();
        $column = $kind.'_path';
        $path = $kind === 'logo'
            ? $this->images->store($request->file('photo'), 'businesses/'.$b->id, 512, 512, square: true, field: 'photo')
            : $this->images->store($request->file('photo'), 'businesses/'.$b->id, 2000, 1200, field: 'photo');
        $old = $b->{$column};
        $b->update([$column => $path]);
        $this->images->delete($old);
        AuditLogger::log($b->id, 'photos.'.$kind.'_updated', $b);

        return back()->with('status', $kind === 'logo' ? 'Logo updated.' : 'Cover photo updated.');
    }

    public function remove(string $kind)
    {
        $this->authorizeAbility('settings.manage');
        abort_unless(in_array($kind, ['logo', 'cover'], true), 404);
        $b = $this->business();
        $column = $kind.'_path';
        $this->images->delete($b->{$column});
        $b->update([$column => null]);
        AuditLogger::log($b->id, 'photos.'.$kind.'_removed', $b);

        return back()->with('status', $kind === 'logo' ? 'Logo removed.' : 'Cover photo removed.');
    }

    public function storeGallery(Request $request)
    {
        $this->authorizeAbility('settings.manage');
        $b = $this->business();
        $room = BusinessPhoto::MAX - $b->photos()->count();
        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:'.max(1, $room)],
            'photos.*' => array_slice(ImageStore::RULES, 1),
        ], ['photos.max' => "You can add {$room} more ".($room === 1 ? 'photo' : 'photos').' (up to '.BusinessPhoto::MAX.' in total).']);
        if ($room < 1) {
            return back()->withErrors(['photos' => 'The gallery is full ('.BusinessPhoto::MAX.' photos). Remove one first.']);
        }
        $sort = (int) $b->photos()->max('sort');
        foreach ($request->file('photos') as $file) {
            $photo = new BusinessPhoto(['path' => $this->images->store($file, 'businesses/'.$b->id.'/gallery', 1600, 1200, field: 'photos'), 'sort' => ++$sort]);
            $photo->business_id = $b->id;
            $photo->save();
        }
        AuditLogger::log($b->id, 'photos.gallery_added', $b, ['count' => count($request->file('photos'))]);

        return back()->with('status', 'Photos added to your gallery.');
    }

    public function updateGallery(Request $request, int $id)
    {
        $this->authorizeAbility('settings.manage');
        $data = $request->validate(['caption' => 'nullable|string|max:120']);
        $this->business()->photos()->findOrFail($id)->update($data);

        return back()->with('status', 'Caption saved.');
    }

    public function destroyGallery(int $id)
    {
        $this->authorizeAbility('settings.manage');
        $photo = $this->business()->photos()->findOrFail($id);
        $this->images->delete($photo->path);
        $photo->delete();
        AuditLogger::log($photo->business_id, 'photos.gallery_removed', $photo);

        return back()->with('status', 'Photo removed.');
    }
}
