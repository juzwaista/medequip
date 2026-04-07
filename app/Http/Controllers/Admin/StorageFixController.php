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

        $link = public_path('storage');
        $target = storage_path('app/public');

        try {
            if (file_exists($link)) {
                // Remove existing link if broken or a directory
                if (is_link($link)) {
                    unlink($link);
                } else {
                    // If it's a real folder, rename it as backup
                    rename($link, $link . '_backup_' . time());
                }
            }

            // Create new symlink
            if (symlink($target, $link)) {
                Log::info('Storage symlink repaired via Admin Dashboard', ['user_id' => $user->id]);
                return redirect()->back()->with('success', 'Storage link has been successfully repaired! Product images should now load correctly.');
            }

            return redirect()->back()->with('error', 'Failed to create symlink. Please check folder permissions.');
        } catch (\Exception $e) {
            Log::error('Storage symlink repair failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Repair failed: ' . $e->getMessage());
        }
    }
}
