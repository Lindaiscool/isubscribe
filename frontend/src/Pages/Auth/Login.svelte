<script>
    // Import the authToken store to manage the authentication token globally
    import { authToken } from "../../stores/auth.js";
    // Import the page library for programmatic navigation
    import page from "page";
    // Import the toastr library for displaying notifications
    import toastr from "toastr";

    // Local state variables for holding the email and password input from the user
    let email = "";
    let password = "";

    // Asynchronous function to handle the login process
    const login = async () => {
        // Send a POST request to the login API endpoint
        const response = await fetch("http://localhost/Linda/i-Subscribe/backend/public/api/login", {

            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({
                email,
                password,
            }),
        });

        // Parse the JSON response body
        const data = await response.json();

        // Check if the response status is OK (i.e., status code 200 range)
        if (response.ok) {
            // Set the received authentication token to the global authToken store
            authToken.set(data.token);
            page("/");
            toastr.success("Login successful!", "Success");
        } else {
    if (data.errors) {
        let message = "Login failed: ";
        for (const [field, errors] of Object.entries(data.errors)) {
            message += `${field} - ${errors.join(", ")}. `;
        }
        toastr.error(message, "Login Error");
    } else {
        const defaultMsg = data.message || "Login failed!";
        toastr.error(defaultMsg, "Login Error");
    }
}

    };
</script>

<div class="min-h-96 flex flex-col items-center justify-center">
    <h1 class="mb-5">Login</h1>
    <form on:submit|preventDefault={login} class="w-full max-w-xs">
        <!-- Form fields -->
        <div class="mb-4">
            <input type="email" id="email" bind:value={email} placeholder="Email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        <div class="mb-6">
            <input type="password" id="password" bind:value={password} placeholder="Password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300">Login</button>
                <p class="mt-4 text-sm text-gray-600">
                    No account yet? <a href="/register" class="text-blue-500 hover:text-blue-700">Register</a>
                </p>
    </form>
</div>

