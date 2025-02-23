<script>
// @ts-nocheck

    import "./app.css"; // Import the main stylesheet for global styles
    import router from "page"; // Import the page library for client-side routing
    import Nav from "./Components/Nav.svelte"; // Import the navigation component
    import { authToken } from "./stores/auth.js"; // Import the authentication token store
    import Footer from "./Components/Footer.svelte";
    import { onMount } from "svelte";

    // Reactive variable to track the current URL path
    let currentRoute = window.location.pathname;
    // Reactive variable to hold the currently active Svelte component based on the route
    let currentComponent = null;
    // Variable to hold route parameters if any
    let params;

    onMount(() => {
        // Add a listener for the popstate event to handle back and forward browser navigation
        window.addEventListener("popstate", () => {
            currentRoute = window.location.pathname; // Update the current route based on the URL path
        });
    });
    // Define the route for the homepage
    router("/", (ctx) => {
        currentRoute = "/"; // Update the current route to homepage
        // Dynamically import the home page component when this route is navigated to
        import("./Pages/Home/home.svelte").then((module) => {
            currentComponent = module.default; // Set the imported component to currentComponent
        });
    });

    // Define the route for the customers page
    router("/customers", (ctx) => {
      // If the user is not authenticated, redirect to the login page
      if (!$authToken) {
        router("/login"); // Redirect to login page if no auth token is found
        return;
      }
      currentRoute = "/customers"; // Update the current route to the customers page
      // Dynamically import the customers page component when this route is navigated to
      import("./Pages/Customers.svelte").then((module) => {
          currentComponent = module.default; // Set the imported component to currentComponent
      });
    });

    router("/invoices", (ctx) => {
      // If the user is not authenticated, redirect to the login page
      if (!$authToken) {
        router("/login"); // Redirect to login page if no auth token is found
        return;
      }
      currentRoute = "/invoices"; // Update the current route to the invoices page
      // Dynamically import the invoices page component when this route is navigated to
      import("./Pages/Invoices.svelte").then((module) => {
          currentComponent = module.default; // Set the imported component to currentComponent
      });
    });

    router("/subscriptions", (ctx) => {
      // If the user is not authenticated, redirect to the login page
      if (!$authToken) {
        router("/login"); // Redirect to login page if no auth token is found
        return;
      }
      currentRoute = "/subscriptions"; // Update the current route to the subscriptions page
      // Dynamically import the subscriptions page component when this route is navigated to
      import("./Pages/Subscriptions.svelte").then((module) => {
          currentComponent = module.default; // Set the imported component to currentComponent
      });
    });

    // Define the route for the registration page
    router("/register", (ctx) => {
        currentRoute = "/register"; // Update the current route to the registration page
        // Dynamically import the registration page component when this route is navigated to
        import("./Pages/Auth/Register.svelte").then((module) => {
            currentComponent = module.default; // Set the imported component to currentComponent
        });
    });

    // Define the route for the login page
    router("/login", (ctx) => {
        currentRoute = "/login"; // Update the current route to the login page
        // Dynamically import the login page component when this route is navigated to
        import("./Pages/Auth/Login.svelte").then((module) => {
            currentComponent = module.default; // Set the imported component to currentComponent
        });
    });

    // Start the router to enable the defined routes
    router.start(); // This starts the routing mechanism, making the routes functional
</script>

<main class="flex min-h-screen flex-col">
    <Nav />
    <div class="flex-1 mb-8">
        <svelte:component this={currentComponent} {params} />
    </div>
    <Footer />
</main>
<!-- Render the navigation component -->

<!-- Render the current Svelte component based on the active route -->
