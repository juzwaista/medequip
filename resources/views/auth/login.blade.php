<x-guest-layout>

    <h2 class="text-2xl font-bold text-center mb-2">
        Login to MedEquip
    </h2>

    <p class="text-sm text-gray-500 text-center mb-6">
        Access the medical equipment marketplace
    </p>

    {{-- Session status (e.g. password reset link sent) --}}
    @if (session('status'))
        <div class="mb-4 p-3 text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg">
            {{ session('status') }}
        </div>
    @endif

    {{-- Auth error (wrong credentials) --}}
    @if ($errors->has('email') || session('error'))
        <div class="mb-4 p-3 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2">
            <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ $errors->first('email') ?: session('error') ?: 'These credentials do not match our records.' }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 @error('email') border-red-400 @enderror">
                   
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200 @error('password') border-red-400 @enderror">
            @error('password')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="mr-2">
                Remember me
            </label>

            <a href="{{ route('password.request') }}"
               class="text-blue-600 hover:underline">
                Forgot password?
            </a>
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Login
        </button>
    </form>

    <p class="text-sm text-center mt-6">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-blue-600 hover:underline">
            Register
        </a>
    </p>

</x-guest-layout>

