<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class DiagnosticController extends Controller
{
    /**
     * Check system status for image serving
     */
    public function check()
    {
        $results = [];
        
        // Check storage directories
        $storageDirectories = [
            'storage/app/public/make',
            'storage/app/public/categories',
            'storage/app/public/products',
            'public/storage',
        ];
        
        foreach ($storageDirectories as $dir) {
            $fullPath = base_path($dir);
            $results['directories'][$dir] = [
                'exists' => is_dir($fullPath),
                'writable' => is_writable($fullPath),
                'path' => $fullPath,
            ];
        }
        
        // Check symlink
        $symlinkPath = public_path('storage');
        $results['symlink'] = [
            'exists' => file_exists($symlinkPath),
            'is_link' => is_link($symlinkPath),
            'target' => is_link($symlinkPath) ? readlink($symlinkPath) : null,
            'path' => $symlinkPath,
        ];
        
        // Check sample images
        $sampleMakes = Category::where('image', '!=', null)->limit(5)->get();
        foreach ($sampleMakes as $make) {
            $possiblePaths = [
                Storage::disk('public')->path($make->image),
                storage_path('app/public/' . $make->image),
                public_path('storage/' . $make->image),
                base_path('storage/app/public/' . $make->image),
            ];
            
            $results['sample_images'][$make->id] = [
                'name' => $make->name,
                'image_field' => $make->image,
                'paths' => [],
            ];
            
            foreach ($possiblePaths as $path) {
                $results['sample_images'][$make->id]['paths'][] = [
                    'path' => $path,
                    'exists' => file_exists($path),
                    'readable' => is_readable($path),
                    'size' => file_exists($path) ? filesize($path) : 0,
                ];
            }
        }
        
        // Check .htaccess files
        $htaccessFiles = [
            'public/storage/.htaccess',
            'storage/app/public/.htaccess',
        ];
        
        foreach ($htaccessFiles as $file) {
            $fullPath = base_path($file);
            $results['htaccess'][$file] = [
                'exists' => file_exists($fullPath),
                'content' => file_exists($fullPath) ? file_get_contents($fullPath) : null,
            ];
        }
        
        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }
    
    /**
     * Create storage directories
     */
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
            'public/storage',
        ];
        
        $results = [];
        
        foreach ($directories as $dir) {
            $fullPath = base_path($dir);
            
            if (!is_dir($fullPath)) {
                $created = mkdir($fullPath, 0755, true);
                $results[$dir] = $created ? 'Created' : 'Failed to create';
            } else {
                $results[$dir] = 'Already exists';
            }
        }
        
        return response()->json([
            'message' => 'Storage directories creation completed',
            'results' => $results
        ]);
    }
    
    /**
     * Create storage symlink
     */
    public function createSymlink()
    {
        $link = public_path('storage');
        $target = storage_path('app/public');
        
        try {
            // Remove existing link/directory if it exists
            if (file_exists($link)) {
                if (is_link($link)) {
                    unlink($link);
                } elseif (is_dir($link)) {
                    // Only remove if empty
                    $files = array_diff(scandir($link), ['.', '..']);
                    if (empty($files)) {
                        rmdir($link);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Directory exists with files, cannot remove'
                        ]);
                    }
                }
            }
            
            // Try to create symlink
            if (@symlink($target, $link)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Symlink created successfully',
                    'link' => $link,
                    'target' => $target
                ]);
            }
            
            // If symlink fails, create directory with .htaccess
            if (!is_dir($link)) {
                mkdir($link, 0755, true);
            }
            
            $htaccessContent = "Options +FollowSymLinks\n";
            $htaccessContent .= "RewriteEngine On\n";
            $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
            $htaccessContent .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
            $htaccessContent .= "RewriteRule ^(.*)$ ../storage/app/public/$1 [L]\n";
            $htaccessContent .= "Options -Indexes\n";
            
            file_put_contents($link . '/.htaccess', $htaccessContent);
            
            return response()->json([
                'success' => true,
                'message' => 'Directory created with .htaccess redirect',
                'link' => $link,
                'target' => $target
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test image route
     */
    public function testImageRoute($id)
    {
        try {
            $category = Category::findOrFail($id);
            
            $info = [
                'make_id' => $id,
                'make_name' => $category->name,
                'image_field' => $category->image,
                'image_url' => $category->image ? route('make.image', $category->id) . '?v=' . optional($category->updated_at)->timestamp : null,
            ];
            
            if (!$category->image) {
                return response()->json(array_merge($info, [
                    'error' => 'No image associated with this make'
                ]));
            }
            
            // Check all possible paths
            $possiblePaths = [
                Storage::disk('public')->path($category->image),
                storage_path('app/public/' . $category->image),
                public_path('storage/' . $category->image),
                base_path('storage/app/public/' . $category->image),
            ];
            
            $pathResults = [];
            foreach ($possiblePaths as $path) {
                $pathResults[] = [
                    'path' => $path,
                    'exists' => file_exists($path),
                    'readable' => is_readable($path),
                    'size' => file_exists($path) ? filesize($path) : 0,
                    'mime_type' => file_exists($path) ? mime_content_type($path) : null,
                ];
            }
            
            return response()->json(array_merge($info, [
                'paths_checked' => $pathResults,
                'storage_disk_path' => Storage::disk('public')->path(''),
                'storage_exists' => Storage::disk('public')->exists($category->image),
            ]));
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}