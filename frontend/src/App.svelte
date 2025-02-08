<script>
    import "./app.css"; // Import the main stylesheet for global styles
    import router from "page"; // Import the page library for client-side routing
    import Nav from "./Components/Nav.svelte"; // Import the navigation component
    import { authToken } from "./stores/auth.js"; // Import the authentication token store

    // Reactive variable to track the current URL path
    let currentRoute = window.location.pathname;
    // Reactive variable to hold the currently active Svelte component based on the route
    let currentComponent = null;
    // Variable to hold route parameters if any
    let params;

    // Define the route for the homepage
    router("/", (ctx) => {
        currentRoute = "/";
        // Dynamically import the home page component when this route is navigated to
        import("./Pages/Home/home.svelte").then((module) => {
            currentComponent = module.default;
        });
    });

    // Define the route for the customers page
    router("/customers", (ctx) => {
      if (!$authToken) {
        router("/login");
        return;
      }
        currentRoute = "/customers";
        // Dynamically import the customers page component when this route is navigated to
        import("./Pages/Customers.svelte").then((module) => {
            currentComponent = module.default;
        });
    });

    // Define the route for the registration page
    router("/register", (ctx) => {
        currentRoute = "/register";
        // Dynamically import the registration page component when this route is navigated to
        import("./Pages/Auth/Register.svelte").then((module) => {
            currentComponent = module.default;
        });
    });

    // Define the route for the login page
    router("/login", (ctx) => {
        currentRoute = "/login";
        // Dynamically import the login page component when this route is navigated to
        import("./Pages/Auth/Login.svelte").then((module) => {
            currentComponent = module.default;
        });
    });

    // Start the router to enable the defined routes
    router.start();
</script>

<!-- Render the navigation component -->
<Nav></Nav>

<!-- Render the current Svelte component based on the active route -->
<svelte:component this={currentComponent} {params}></svelte:component>
