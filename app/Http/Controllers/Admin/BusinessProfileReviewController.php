<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BusinessProfileReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        
        $profiles = BusinessProfile::with('user')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return Inertia::render('Admin/BusinessProfiles/Index', [
            'profiles' => $profiles,
            'filters' => ['status' => $status],
        ]);
    }
    
    public function show(BusinessProfile $businessProfile)
    {
        $businessProfile->load('user');
        
        return Inertia::render('Admin/BusinessProfiles/Show', [
            'profile' => $businessProfile,
        ]);
    }
    
    public function approve(Request $request, BusinessProfile $businessProfile)
    {
        $businessProfile->update([
            'status' => 'approved',
        ]);
        
        return back()->with('success', 'Business Account approved successfully.');
    }
    
    public function reject(Request $request, BusinessProfile $businessProfile)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000'
        ]);
        
        $businessProfile->update([
            'status' => 'rejected',
        ]);
        
        // Log reason somewhere if needed or send email
        
        return back()->with('success', 'Business Account application rejected.');
    }
}
