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
        const response = await fetch("http://localhost:8000/api/login", {
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

<h1>Login</h1>
<!-- Form for user login; prevent default form submission behavior to handle with Svelte -->
<form on:submit|preventDefault={login}>
    <label for="email">Email</label>
    <input type="email" id="email" bind:value={email} />

    <label for="password">Password</label>
    <input type="password" id="password" bind:value={password} />

    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300">Login</button>
</form>
