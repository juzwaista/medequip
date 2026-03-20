<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedEquip | Medical Equipment Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 text-gray-800">

    <nav class="bg-white border-b shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">

            <div class="flex items-center space-x-2">
                <span class="text-xl font-bold text-blue-700">MedEquip</span>
                <span class="text-sm text-gray-500 hidden md:block">
                    Medical Equipment Marketplace
                </span>
            </div>

            @auth
                <div class="flex items-center space-x-6 text-sm font-medium">

                    <a href="{{ route('dashboard') }}" class="hover:text-blue-700">Home</a>

                    @if(auth()->user()->role === 'distributor')
                        <a href="{{ route('owner.dashboard') }}" class="hover:text-blue-700">Seller Dashboard</a>
                        <a href="{{ route('owner.distributors.index') }}" class="hover:text-blue-700">Business Profile</a>
                    @elseif(in_array(auth()->user()->role, ['admin', 'super_admin']))
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-700">
                            Admin Dashboard
                        </a>
                        <a href="{{ route('admin.verifications.index') }}" class="hover:text-blue-700">
                            Verifications
                        </a>
                    @endif


                    <span class="h-5 w-px bg-gray-300"></span>

                    <span class="text-gray-600">{{ auth()->user()->name }}</span>

                    <a href="{{ route('profile.edit') }}" class="hover:text-blue-700">Profile</a>

                    <form method="POST" action="{{ route('logout') }}"
                        onsubmit="return confirm('Are you sure you want to logout?')">
                        @csrf
                        <button class="hover:text-red-600">Logout</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-8">
        @yield('content')
    </main>

</body>

</html>