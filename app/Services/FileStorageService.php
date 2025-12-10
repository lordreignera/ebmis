<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileStorageService
{
    /**
     * Store a file either locally or in Digital Ocean Spaces
     * 
     * @param UploadedFile $file
     * @param string $folder - e.g., 'loan-documents', 'member-photos'
     * @param string|null $disk - 'spaces' or null for local
     * @return string - the file path
     */
    public static function storeFile(UploadedFile $file, string $folder, ?string $disk = null): string
    {
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $folder . '/' . $filename;
        
        // Use the configured default disk if not specified
        if ($disk === null) {
            $disk = config('filesystems.default', 'local');
        }
        
        // Check if Spaces is configured (uses standard AWS env variables)
        $useSpaces = !empty(env('AWS_ACCESS_KEY_ID')) && !empty(env('AWS_SECRET_ACCESS_KEY')) && !empty(env('AWS_BUCKET'));
        
        if ($disk === 'spaces' && $useSpaces) {
            try {
                // Store in Digital Ocean Spaces
                Storage::disk('spaces')->put($path, file_get_contents($file->getRealPath()), 'public');
                
                Log::info('File stored in Digital Ocean Spaces', [
                    'path' => $path,
                    'disk' => 'spaces'
                ]);
                
                return $path;
            } catch (\Exception $e) {
                Log::error('Failed to store file in Spaces, falling back to local storage', [
                    'error' => $e->getMessage()
                ]);
                // Fall back to local storage
            }
        }
        
        // Store locally in public/uploads/
        $uploadPath = public_path('uploads/' . $folder);
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $file->move($uploadPath, $filename);
        
        Log::info('File stored locally', [
            'path' => 'uploads/' . $path
        ]);
        
        return 'uploads/' . $path;
    }
    
    /**
     * Delete a file from storage
     * 
     * @param string $path
     * @return bool
     */
    public static function deleteFile(string $path): bool
    {
        $defaultDisk = config('filesystems.default', 'local');
        
        // Try Spaces first if configured
        if ($defaultDisk === 'spaces' && !str_starts_with($path, 'uploads/')) {
            try {
                if (Storage::disk('spaces')->exists($path)) {
                    Storage::disk('spaces')->delete($path);
                    Log::info('File deleted from Spaces', ['path' => $path]);
                    return true;
                }
            } catch (\Exception $e) {
                Log::error('Failed to delete from Spaces', ['error' => $e->getMessage()]);
            }
        }
        
        // Try local storage (both new and old locations)
        $locations = [
            public_path($path),
            storage_path('app/public/' . str_replace('uploads/', '', $path))
        ];
        
        foreach ($locations as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info('File deleted locally', ['path' => $filePath]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the URL for a file
     * 
     * @param string $path
     * @return string
     */
    public static function getFileUrl(string $path): string
    {
        $defaultDisk = config('filesystems.default', 'local');
        
        // If using Spaces and path doesn't start with 'uploads/', it's in Spaces
        if ($defaultDisk === 'spaces' && !str_starts_with($path, 'uploads/')) {
            try {
                return Storage::disk('spaces')->url($path);
            } catch (\Exception $e) {
                Log::error('Failed to get Spaces URL', ['error' => $e->getMessage()]);
                // Fall back to local
                return asset($path);
            }
        }
        
        // Return local URL
        return asset($path);
    }
    
    /**
     * Check if file exists
     * 
     * @param string $path
     * @return bool
     */
    public static function fileExists(string $path): bool
    {
        $defaultDisk = config('filesystems.default', 'local');
        
        // Check Spaces
        if ($defaultDisk === 'spaces' && !str_starts_with($path, 'uploads/')) {
            try {
                return Storage::disk('spaces')->exists($path);
            } catch (\Exception $e) {
                Log::error('Failed to check Spaces existence', ['error' => $e->getMessage()]);
                return false;
            }
        }
        
        // Check local - try both public/uploads and storage/app/public
        return file_exists(public_path($path)) || file_exists(storage_path('app/public/' . str_replace('uploads/', '', $path)));
    }
}
