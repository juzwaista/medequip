@extends('layouts.app')

@section('content')
<div class="max-w-4xl space-y-8">

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold">Profile Settings</h1>
        <p class="text-gray-500">
            Manage your account information and security
        </p>
    </div>

    <!-- Profile Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Profile Information</h2>
        @if (session('status') === 'profile-updated')
            <div class="mb-4 p-3 text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg">
                Profile updated successfully.
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name"
                       value="{{ old('name', auth()->user()->name) }}"
                       class="w-full border rounded px-3 py-2 @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email"
                       value="{{ old('email', auth()->user()->email) }}"
                       class="w-full border rounded px-3 py-2 @error('email') border-red-400 @enderror">
                @error('email')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Save Changes
            </button>
        </form>
    </div>

    <!-- Password Update -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-4">Update Password</h2>
        @if (session('status') === 'password-updated')
            <div class="mb-4 p-3 text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg">
                Password updated successfully.
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-1">
                    Current Password
                </label>
                <input type="password" name="current_password"
                       class="w-full border rounded px-3 py-2 @error('current_password') border-red-400 @enderror">
                @error('current_password')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">
                    New Password
                </label>
                <input type="password" name="password"
                       class="w-full border rounded px-3 py-2 @error('password') border-red-400 @enderror">
                @error('password')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">
                    Confirm New Password
                </label>
                <input type="password" name="password_confirmation"
                       class="w-full border rounded px-3 py-2">
            </div>

            <button
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Update Password
            </button>
        </form>
    </div>

</div>
@endsection
