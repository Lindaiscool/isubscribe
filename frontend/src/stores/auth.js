import { writable } from "svelte/store";
import Cookies from "js-cookie";
import page from "page";
// Create a writable store to hold the authentication token, initialized from the browser cookies if available.
export const authToken = writable(Cookies.get("authToken") || null);

// Subscribe to changes in the authToken store.
authToken.subscribe((token) => {
    if (token) {
        // If a token is present, store it in cookies with security settings.
        Cookies.set("authToken", token, { expires: 7, sameSite: "strict", secure: true });
    } else {
        // If the token is null (logged out), remove it from cookies.
        Cookies.remove("authToken");
    }
});

// Function to handle user logout.
export const logout = async () => {
    let token;
    // Retrieve the current value of the authToken to use in the logout request.
    authToken.subscribe((t) => token = t)();

    // Perform the logout operation by making a POST request to the backend API.
    const response = await fetch("http://localhost:8000/api/logout", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Authorization": `Bearer ${token}`, // Use the token for authentication in the API request.
        },
    });

    // After the API response, set the authToken store to null to indicate no user is logged in.
    authToken.set(null);

    // Handle the response: inform the user of the logout status via an alert.
    if (!response.ok) {
        alert("Logout failed!"); // Failure message
    }
    page("/login");
};
