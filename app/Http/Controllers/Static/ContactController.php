<?php

namespace App\Http\Controllers\Static;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ContactFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    /**
     * Store the contact form message and notify super admins.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        // Find all Super Admins to notify
        $superAdmins = User::where('role', 'super_admin')->get();

        if ($superAdmins->count() > 0) {
            Notification::send($superAdmins, new ContactFormSubmission($validated));
        }

        return redirect()->back()
            ->with('success', 'Your message has been sent successfully! Our team will get back to you soon.');
    }
}
