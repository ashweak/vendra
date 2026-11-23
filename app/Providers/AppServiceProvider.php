<?php

namespace App\Providers;

use App\Support\CurrentBusiness;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One active business per request; never shared across requests or queue jobs.
        $this->app->scoped(CurrentBusiness::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.vendra');
        Paginator::defaultSimpleView('pagination.vendra');

        // <img src="…" @srcset($path, '(min-width: 1024px) 33vw, 100vw')> adds the smaller copies of an upload.
        \Illuminate\Support\Facades\Blade::directive('srcset', fn (string $args) => "<?php echo \\App\\Services\\ImageStore::attrs($args); ?>");
    }
}
