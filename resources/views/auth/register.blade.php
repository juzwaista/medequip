<x-guest-layout>

    <h2 class="text-2xl font-bold text-center mb-2">
        Create a MedEquip Account
    </h2>

    <p class="text-sm text-gray-500 text-center mb-6">
        Join the trusted medical equipment marketplace
    </p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" name="name" required
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" required
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" required
                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200">
        </div>

        <!-- Role Selection -->
        <div>
            <label class="block text-sm font-medium mb-2">I am a...</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" name="role" value="customer" checked
                        class="mr-2 text-blue-600 focus:ring-blue-500">
                    <span>Customer</span>
                    <span class="text-xs text-gray-500 ml-2">(Purchase medical equipment)</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="role" value="distributor" class="mr-2 text-blue-600 focus:ring-blue-500">
                    <span>Distributor/Seller</span>
                    <span class="text-xs text-gray-500 ml-2">(Sell and manage inventory)</span>
                </label>
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
            Register
        </button>
    </form>

    <p class="text-sm text-center mt-6">
        Already have an account?
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">
            Login
        </a>
    </p>

</x-guest-layout>