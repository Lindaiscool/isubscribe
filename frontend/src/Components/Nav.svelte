<script>
    import { authToken, logout } from "../stores/auth.js";
    import page from "page";
    import { writable } from "svelte/store";

    let isMenuOpen = writable(false);

    const toggleMenu = () => {
        isMenuOpen.update((value) => !value);
    };
</script>

<nav class="bg-neutral-900 top-0 left-0 w-full z-50 py-4">
    <div class="container mx-auto flex items-center justify-between px-6">
        <!-- Burger Menu Button -->
        <button class="block md:hidden text-2xl bg-transparent border-none cursor-pointer text-white" on:click={toggleMenu} aria-label="Toggle menu"> ☰ </button>

        <!-- Navbar Menu -->
        <ul class="hidden md:flex gap-4 text-white">
            <div class="font-bold">
                <a href="/" class="flex items-center">
                    <img src="/logo3.png" alt="logo" width="40" height="30" />
                </a>
            </div>
        </ul>
        <ul class="hidden md:flex gap-4 text-white">
            {#if $authToken}
                <li><a href="/customers" class="text-lg hover:text-indigo-500 transition duration-300" on:click|preventDefault={() => page("/customers")}> Customers</a></li>
                <li><a href="/invoices" class="text-lg hover:text-indigo-500 transition duration-300" on:click|preventDefault={() => page("/invoices")}> Invoices</a></li>
                <li><a href="/subscriptions" class="text-lg hover:text-indigo-500 transition duration-300" on:click|preventDefault={() => page("/subscriptions")}> Subscriptions</a></li>
                <li><button class="text-lg text-neutral-100 hover:text-red-500" on:click={logout}>Logout</button></li>
            {:else}
                <li><a href="/login" class="text-lg text-neutral-100 hover:text-blue-400" on:click|preventDefault={() => page("/login")}> Login</a></li>
                <li><a href="/register" class="text-lg text-neutral-100 hover:text-blue-400" on:click|preventDefault={() => page("/register")}> Register</a></li>
            {/if}
        </ul>
    </div>
</nav>

<!-- Responsive Mobile Menu -->
{#if $isMenuOpen}
    <ul class="md:hidden flex flex-col items-center bg-neutral-900 w-full absolute top-16 left-0 py-4 space-y-2 text-white">
        <div class="font-bold">
            <a href="/" class="flex items-center">
                <img src="/logo3.png" alt="logo" width="40" height="30" />
            </a>
        </div>
        {#if $authToken}
            <li><a href="/customers" class="text-lg hover:text-indigo-500" on:click|preventDefault={() => page("/customers")}> Customers</a></li>
            <li><a href="/invoices" class="text-lg hover:text-indigo-500" on:click|preventDefault={() => page("/invoices")}> Invoices</a></li>
            <li><a href="/subscriptions" class="text-lg hover:text-indigo-500" on:click|preventDefault={() => page("/subscriptions")}> Subscriptions</a></li>
            <li><button class="text-lg text-neutral-100 hover:text-red-500" on:click={logout}>Logout</button></li>
        {:else}
            <li><a href="/login" class="text-lg text-neutral-100 hover:text-blue-400" on:click|preventDefault={() => page("/login")}> Login</a></li>
            <li><a href="/register" class="text-lg text-neutral-100 hover:text-blue-400" on:click|preventDefault={() => page("/register")}> Register</a></li>
        {/if}
    </ul>
{/if}
