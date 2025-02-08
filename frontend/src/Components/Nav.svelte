<script>
    import { authToken, logout } from "../stores/auth.js"; // Importing authentication-related stores
    import page from "page"; // Importing page for programmatic routing

    let isMenuOpen = false; // Local state for tracking the visibility of the mobile menu

    // Function to toggle the mobile menu open/close state
    const toggleMenu = () => {
        isMenuOpen = !isMenuOpen;
    };
</script>

<nav>
    <div class="container">
        <!-- Burger Menu Button -->
        <button class="burger" on:click={toggleMenu} aria-label="Toggle menu">
            ☰ <!-- Unicode for the hamburger menu icon -->
        </button>

        <!-- Navbar Menu -->
        <ul class="nav-links" class:is-open={isMenuOpen}>
            <div class="brand">
                <!-- Brand logo with a link to the home page -->
                <a href="/"
                    ><img
                        src="/logo.png"
                        alt="logo"
                        width="40"
                        height="30"
                    /></a
                >
            </div>
            <li>
                <!-- Navigation link to Customers page -->
                <a
                    href="/customers"
                    on:click|preventDefault={() => page("/customers")}
                    >Customers</a
                >
            </li>
            <li>
                <!-- Navigation link to Invoices page -->
                <a
                    href="/invoices"
                    on:click|preventDefault={() => page("/invoices")}
                    >Invoices</a
                >
            </li>
            <li>
                <!-- Navigation link to Subscriptions page -->
                <a
                    href="/subscriptions"
                    on:click|preventDefault={() => page("/subscriptions")}
                    >Subscriptions</a
                >
            </li>
        </ul>
        <ul class="nav-links" class:is-open={isMenuOpen}>
            {#if $authToken}
                <!-- Conditional rendering based on authentication status -->
                <li>
                    <!-- Logout button -->
                    <button on:click={logout}>Logout</button>
                </li>
            {:else}
                <li>
                    <!-- Link to the Login page -->
                    <a
                        href="/login"
                        on:click|preventDefault={() => page("/login")}>Login</a
                    >
                </li>
                <li>
                    <!-- Link to the Registration page -->
                    <a
                        href="/register"
                        on:click|preventDefault={() => page("/register")}
                        >Register</a
                    >
                </li>
            {/if}
        </ul>
    </div>
</nav>

<style>
    /* Navbar styling */
    nav {
        width: 100%;
        background: #333;
        color: white;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        padding: 1rem 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.5rem;
    }

    .brand a {
        font-weight: bold;
        color: white;
        text-decoration: none;
    }

    /* Default menu styling */
    .nav-links {
        list-style: none;
        display: flex;
        gap: 1rem;
        padding: 0;
        margin: 0;
    }

    .nav-links a {
        color: white;
        text-decoration: none;
    }

    .nav-links a:hover {
        text-decoration: underline;
    }

    /* Burger menu button styling */
    .burger {
        display: none;
        font-size: 2rem;
        background: none;
        border: none;
        color: white;
        cursor: pointer;
    }

    /* Responsive styling: Make a burger menu under 428px */
    @media (max-width: 428px) {
        .burger {
            display: block;
        }

        .nav-links {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 70px;
            left: 0;
            width: 100%;
            background: #333;
            padding: 1rem;
        }

        .nav-links.is-open {
            display: flex;
        }

        .nav-links li {
            text-align: center;
            padding: 0.5rem 0;
        }
    }
</style>
