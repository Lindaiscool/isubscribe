<script>
    import { authToken } from "../../stores/auth.js"; // Import authentication token for handling user sessions
    import page from "page"; // Import page.js for client-side routing
</script>

<!-- Hero Section with a background gradient and a call-to-action -->
<section class="bg-gradient-to-r from-indigo-500 to-purple-600 py-20 px-6 md:px-12 text-center">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">Manage all your subscriptions easily with i-Subscribe</h1>
        <p class="mt-4 text-lg text-gray-200">Simplify your subscription management and gain full control over billing and customer management, all from a single central platform.</p>
        {#if $authToken}
            <!-- Show link to secure pages if the user is logged in -->
            <a href="/invoices" class="mt-6 inline-block bg-white text-indigo-600 px-6 py-3 rounded-full text-lg font-semibold hover:bg-gray-200 transition"> Go to Invoices </a>
        {:else}
            <!-- Show link to registration if the user is not logged in -->
            <a href="/register" class="mt-6 inline-block bg-white text-indigo-600 px-6 py-3 rounded-full text-lg font-semibold hover:bg-gray-200 transition"> Sign Up </a>
        {/if}
    </div>
</section>

<!-- Benefits Section -->
<section class="py-16 px-6 md:px-12">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center mb-8">Why choose i-Subscribe?</h2>
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Benefit 1: Easy subscription management -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center">
                <i class="fas fa-tasks text-indigo-400 text-4xl"></i>
                <h3 class="text-xl font-semibold mt-4">Easy Subscription Management</h3>
                <p class="text-gray-400 mt-2">Create and manage various subscription models with a user-friendly interface.</p>
            </div>

            <!-- Benefit 2: Manual invoicing -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center">
                <i class="fas fa-credit-card text-indigo-400 text-4xl"></i>
                <h3 class="text-xl font-semibold mt-4">Manual Invoicing</h3>
                <p class="text-gray-400 mt-2">Easily generate invoices with a click, giving you time to adjust details before sending.</p>
            </div>

            <!-- Benefit 3: User and access management -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center">
                <i class="fas fa-user-cog text-indigo-400 text-4xl"></i>
                <h3 class="text-xl font-semibold mt-4">User and Access Management</h3>
                <p class="text-gray-400 mt-2">Easily manage customer accounts and adjust settings as needed.</p>
            </div>

            <!-- Benefit 4: Clear payment reminders -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-lg text-center">
                <i class="fas fa-bell text-indigo-400 text-4xl"></i>
                <h3 class="text-xl font-semibold mt-4">Clear Payment Reminders</h3>
                <p class="text-gray-400 mt-2">Keep customers informed with simple notifications about outstanding payments.</p>
            </div>
        </div>
    </div>
</section>
