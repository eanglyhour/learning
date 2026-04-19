<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use App\Models\ApiRoute;

class SyncApiRoutes extends Command
{
    protected $signature = 'routes:sync';
    protected $description = 'Sync API routes to database';

    public function handle()
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {

            // only api routes
            if (!str_starts_with($route->uri(), 'api')) {
                continue;
            }

            ApiRoute::updateOrCreate(
                [
                    'uri' => $route->uri(),
                    'method' => implode('|', $route->methods()),
                ],
                [
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'middleware' => implode(',', $route->middleware()),
                ]
            );
        }

        $this->info('API Routes synced successfully!');
    }
}
