<script>
    // Initialize reactive variables for form fields
    let email = "";
    let password = "";
    let name = "";
    let password_confirmation = "";
    import page from "page";

    // Asynchronous function to handle user registration
    const register = async () => {
        // Send a POST request to the registration API endpoint
        const response = await fetch("http://localhost:8000/api/register", {
            method: "POST",
            headers: {
                "Content-Type": "application/json", // Set content type to JSON
                Accept: "application/json", // Specify that the client expects JSON response
            },
            body: JSON.stringify({
                email,
                password,
                name,
                password_confirmation,
            }),
        });
        page("/login");

        // Parse the JSON response from the server
        const data = await response.json();
        console.log(data); // Log the response data for debugging purposes
    };
</script>

<h1 class="mb-5">Register</h1>
<!-- Form for user registration, prevents default form submission to handle via JavaScript -->
<form on:submit|preventDefault={register}>
    <label for="email">Email</label>
    <input type="email" id="email" bind:value={email} />

    <label for="name">Name</label>
    <input type="text" id="name" bind:value={name} />

    <label for="password">Password</label>
    <input type="password" id="password" bind:value={password} />

    <label for="password_confirmation">Password Confirmation</label>
    <input
        type="password"
        id="password_confirmation"
        bind:value={password_confirmation}
    />

    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300">Register</button>
</form>
