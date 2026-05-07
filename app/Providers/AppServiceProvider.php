<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Observers\CustomerObserver;
use App\Observers\LeadObserver;
use App\Observers\StaffObserver;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Create storage directories if they don't exist
        $this->ensureStorageDirectoriesExist();
        
        // Create storage symlink if it doesn't exist
        $this->createStorageSymlink();

        \App\Models\Deal::observe(\App\Observers\DealObserver::class);
        Customer::observe(CustomerObserver::class);
        Lead::observe(LeadObserver::class);
        User::observe(StaffObserver::class);

        // New Automated Notification Observers
        \App\Models\FollowUp::observe(\App\Observers\FollowUpObserver::class);
        \App\Models\Meeting::observe(\App\Observers\MeetingObserver::class);
        \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        \App\Models\Task::observe(\App\Observers\TaskObserver::class);
        \App\Models\Stage::observe(\App\Observers\StageObserver::class);
        \App\Models\Pipeline::observe(\App\Observers\PipelineObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\SupportTicket::observe(\App\Observers\SupportTicketObserver::class);
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\ProductCategory::observe(\App\Observers\ProductCategoryObserver::class);
        \App\Models\Service::observe(\App\Observers\ServiceObserver::class);

        View::composer(['layouts.app', 'profile.show'], function ($view): void {
            $googleCalendarConnected = false;

            try {
                $googleCalendarConnected = app(GoogleCalendarService::class)->isAuthenticated();
            } catch (\Throwable $e) {
                $googleCalendarConnected = false;
            }

            $view->with('googleCalendarConnected', $googleCalendarConnected);
        });
    }

    /**
     * Ensure all required storage directories exist
     */
    private function ensureStorageDirectoriesExist(): void
    {
        $directories = [
            'app/public/make',
            'app/public/categories',
            'app/public/products',
            'app/public/bom-products',
            'app/public/leads',
            'app/public/customers',
            'app/public/vendors',
            'app/public/users',
            'app/public/avatars',
            'app/public/company',
            'app/public/documents',
            'app/public/estimates',
            'logs',
            'framework/cache',
            'framework/sessions',
            'framework/views',
        ];

        foreach ($directories as $dir) {
            $path = storage_path($dir);
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Create storage symlink if it doesn't exist
     */
    private function createStorageSymlink(): void
    {
        try {
            $link = public_path('storage');
            $target = storage_path('app/public');

            // Check if symlink already exists
            if (is_link($link)) {
                return;
            }

            // If a regular directory exists, remove it
            if (is_dir($link) && !is_link($link)) {
                // Don't remove if it has files, just skip
                if (count(scandir($link)) <= 2) {
                    rmdir($link);
                } else {
                    return;
                }
            }

            // Create the symlink
            if (!is_link($link) && !is_dir($link)) {
                symlink($target, $link);
            }
        } catch (\Throwable $e) {
            // Silently fail - symlink might not be supported on this system
            // Images will still work via the route handler
        }
    }
}
