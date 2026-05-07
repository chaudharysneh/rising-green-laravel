<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MakeController extends Controller
{
    public function index()
    {
        return view('crm.makes.index');
    }

    public function image($id)
    {
        try {
            $category = Category::findOrFail($id);
            
            if (!$category->image) {
                Log::warning("No image found for make ID: {$id}");
                abort(404, 'No image associated with this make');
            }

            // Multiple path checking for maximum compatibility
            $possiblePaths = [
                Storage::disk('public')->path($category->image), // Standard Laravel path
                storage_path('app/public/' . $category->image),  // Direct storage path
                public_path('storage/' . $category->image),      // Symlink path
                base_path('storage/app/public/' . $category->image), // Absolute path from root
            ];

            foreach ($possiblePaths as $filePath) {
                if (file_exists($filePath) && is_file($filePath)) {
                    Log::info("Image found at: {$filePath} for make ID: {$id}");
                    
                    // Get file info for proper response
                    $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
                    $fileName = basename($filePath);
                    $fileSize = filesize($filePath);
                    
                    return response()->file($filePath, [
                        'Content-Type' => $mimeType,
                        'Content-Length' => $fileSize,
                        'Cache-Control' => 'public, max-age=31536000', // 1 year cache
                        'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                    ]);
                }
            }

            // If file not found, try to create a placeholder or redirect
            Log::error("Image file not found in any location: {$category->image} for make ID: {$id}");
            Log::error("Checked paths: " . implode(', ', $possiblePaths));
            
            // Check if we can find the file with different extensions
            $pathInfo = pathinfo($category->image);
            $baseName = $pathInfo['dirname'] . '/' . $pathInfo['filename'];
            $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            
            foreach ($extensions as $ext) {
                $testFile = $baseName . '.' . $ext;
                foreach ($possiblePaths as $basePath) {
                    $testPath = dirname($basePath) . '/' . basename($testFile);
                    if (file_exists($testPath)) {
                        Log::info("Found alternative image: {$testPath}");
                        return response()->file($testPath);
                    }
                }
            }
            
            abort(404, 'Image file not found');
            
        } catch (\Exception $e) {
            Log::error("Error serving image for make ID {$id}: " . $e->getMessage());
            abort(404, 'Image not available: ' . $e->getMessage());
        }
    }
}
