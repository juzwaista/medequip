<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class StorageFixController extends Controller
{
    /**
     * Programmatically repair the public/storage symlink.
     * Useful for shared hosting environments like Hostinger.
     */
    public function repair()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->role !== 'super_admin') {
            abort(403);
        }

        // Target is always the same
        $target = storage_path('app/public');
        
        // Potential public folder candidates for shared hosting
        $publicPaths = [
            base_path('public'),
            base_path('public_html'),
            $_SERVER['DOCUMENT_ROOT'] ?? null,
        ];
        
        $results = [];

        foreach (array_filter(array_unique($publicPaths)) as $path) {
            $link = $path . DIRECTORY_SEPARATOR . 'storage';
            
            try {
                if (file_exists($link)) {
                    if (is_link($link)) {
                        unlink($link);
                    } else if (is_dir($link)) {
                        // Rename real folder to backup
                        rename($link, $link . '_backup_' . time());
                    }
                }

                if (symlink($target, $link)) {
                    $results[] = "Successfully created symlink in: $path";
                    \Illuminate\Support\Facades\Log::info("Storage symlink repaired in: $path", ['user_id' => $user->id]);
                } else {
                    $results[] = "Failed to create symlink in: $path (Permission error?)";
                }
            } catch (\Exception $e) {
                $results[] = "Error in $path: " . $e->getMessage();
            }
        }

        if (empty($results)) {
            return redirect()->back()->with('error', 'No valid public directories found to repair.');
        }

        $success = count(array_filter($results, fn($r) => str_contains($r, 'Successfully'))) > 0;
        return redirect()->back()->with($success ? 'success' : 'error', 'Repair Results: ' . implode(' | ', $results));
    }
}
