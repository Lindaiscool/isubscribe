<script>
    import { authToken } from "../../stores/auth.js"; // Importing the authentication token store

    // Asynchronous function to handle user logout
    const logout = async () => {
        let token;
        // Subscribe to the authToken store and retrieve the current token
        authToken.subscribe((t) => (token = t))();

        // Perform a POST request to the logout API endpoint
        const response = await fetch("http://localhost/Linda/i-Subscribe/backend/public/api/logout", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
        });

        // Check the response status to determine if the logout was successful
        if (response.ok) {
    const responseData = await response.json();
    authToken.set(null); // Clear the token from the store upon successful logout
    toastr.success("Uitloggen is gelukt!", "Succesvol Uitgelogd"); // Toastr voor succesvol uitloggen
    window.location.href = "/login"; // Redirect the user to the login page
} else {
    if (!response.bodyUsed) {
        toastr.error("Network error or server is not responding.", "Logout Error");
    } else {
        const responseData = await response.json(); // Nu weten we dat het een fout is, proberen we de response te parsen.
        if (responseData.errors) {
            let message = "Logout failed: ";
            for (const [field, errors] of Object.entries(responseData.errors)) {
                message += `${field} - ${errors.join(", ")}. `;
            }
            toastr.error(message, "Logout Error");
        } else {
            toastr.error("Logout failed!", "Logout Error");
        }
    }
}
    };
</script>

<!-- Logout button that triggers the logout function when clicked -->
<button on:click={logout}>Logout</button>
