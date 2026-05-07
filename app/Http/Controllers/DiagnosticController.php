<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DiagnosticController extends Controller
{
    public function check()
    {
        $diagnostics = [];

        // Check if storage directory exists
        $diagnostics['storage_exists'] = is_dir(storage_path());
        $diagnostics['storage_path'] = storage_path();

        // Check if storage/app/public exists
        $diagnostics['storage_app_public_exists'] = is_dir(storage_path('app/public'));
        $diagnostics['storage_app_public_path'] = storage_path('app/public');

        // Check if storage/app/public/make exists
        $diagnostics['storage_make_exists'] = is_dir(storage_path('app/public/make'));
        $diagnostics['storage_make_path'] = storage_path('app/public/make');

        // Check if storage is writable
        $diagnostics['storage_writable'] = is_writable(storage_path());

        // Check if storage/app/public is writable
        $diagnostics['storage_app_public_writable'] = is_writable(storage_path('app/public'));

        // Check if public/storage symlink exists
        $diagnostics['public_storage_exists'] = is_link(public_path('storage'));
        $diagnostics['public_storage_path'] = public_path('storage');

        // Check if public/storage is a directory
        $diagnostics['public_storage_is_dir'] = is_dir(public_path('storage'));

        // List files in storage/app/public/make
        $make_dir = storage_path('app/public/make');
        if (is_dir($make_dir)) {
            $files = scandir($make_dir);
            $diagnostics['make_files_count'] = count($files) - 2; // Exclude . and ..
            $diagnostics['make_files_sample'] = array_slice($files, 2, 5);
        }

        // Check logs
        $diagnostics['logs_dir_exists'] = is_dir(storage_path('logs'));
        $diagnostics['logs_dir_writable'] = is_writable(storage_path('logs'));
        
        $log_file = storage_path('logs/laravel.log');
        $diagnostics['log_file_exists'] = file_exists($log_file);
        $diagnostics['log_file_path'] = $log_file;
        $diagnostics['log_file_writable'] = is_writable($log_file);

        // Check app.php config
        $diagnostics['app_debug'] = config('app.debug');
        $diagnostics['app_env'] = config('app.env');

        // Check if we can write to storage
        try {
            $test_file = storage_path('test_write.txt');
            file_put_contents($test_file, 'test');
            $diagnostics['can_write_to_storage'] = true;
            unlink($test_file);
        } catch (\Exception $e) {
            $diagnostics['can_write_to_storage'] = false;
            $diagnostics['write_error'] = $e->getMessage();
        }

        return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT);
    }

    public function createStorageDirectories()
    {
        $directories = [
            'storage/app/public/make',
            'storage/app/public/categories',
            'storage/app/public/products',
            'storage/app/public/bom-products',
            'storage/app/public/leads',
            'storage/app/public/customers',
            'storage/app/public/vendors',
            'storage/app/public/users',
            'storage/app/public/avatars',
            'storage/app/public/company',
            'storage/app/public/documents',
            'storage/app/public/estimates',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
        ];

        $results = [];
        foreach ($directories as $dir) {
            $path = base_path($dir);
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                $results[$dir] = 'created';
            } else {
                $results[$dir] = 'exists';
            }
        }

        return response()->json($results);
    }

    public function createSymlink()
    {
        try {
            $link = public_path('storage');
            $target = storage_path('app/public');

            // Remove existing symlink or directory
            if (is_link($link)) {
                unlink($link);
            } elseif (is_dir($link)) {
                rmdir($link);
            }

            // Create symlink
            symlink($target, $link);

            return response()->json([
                'success' => true,
                'message' => 'Symlink created successfully',
                'link' => $link,
                'target' => $target,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testImageRoute($id)
    {
        try {
            $category = \App\Models\Category::findOrFail($id);

            $result = [
                'id' => $category->id,
                'name' => $category->name,
                'image' => $category->image,
                'image_path' => storage_path('app/public/' . $category->image),
                'image_exists' => $category->image ? Storage::disk('public')->exists($category->image) : false,
            ];

            if ($category->image) {
                $full_path = storage_path('app/public/' . $category->image);
                $result['file_exists'] = file_exists($full_path);
                $result['file_readable'] = is_readable($full_path);
                $result['file_size'] = filesize($full_path);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
