<script>
    import toastr from "toastr";
    import page from "page";

    let email = "";
    let password = "";
    let name = "";
    let password_confirmation = "";

    const register = async () => {
        const response = await fetch("http://localhost:8000/api/register", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({
                email,
                password,
                name,
                password_confirmation,
            }),
        });

        if (response.ok) {
            // If the registration was successful, redirect to login page
            page("/login");
            toastr.success("Registration successful!", "Success");
        } else {
            // Handle errors
            const responseData = await response.json();
            if (responseData.errors) {
                let message = "";
                for (const [field, errors] of Object.entries(responseData.errors)) {
                    message += `${field} - ${errors.join(", ")}. `;
                }
                toastr.error(message, "Registration Error");
            } else {
                toastr.error(responseData.message || "An error occurred during registration", "Registration Error");
            }
        }
    };
</script>

<div class="min-h-96 flex flex-col items-center justify-center">
    <h1 class="mb-5">Register</h1>
    <form on:submit|preventDefault={register} class="w-full max-w-xs">
        <!-- Form fields -->
        <div class="mb-4">
            <input type="email" bind:value={email} placeholder="Email" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        <div class="mb-4">
            <input type="text" bind:value={name} placeholder="Name" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        <div class="mb-4">
            <input type="password" bind:value={password} placeholder="Password" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        <div class="mb-6">
            <input type="password" bind:value={password_confirmation} placeholder="Password Confirmation" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300">Register</button>
        <p class="mt-4 text-sm text-gray-600">
            Already have an account? <a href="/login" class="text-blue-500 hover:text-blue-700">Login</a>
        </p>
    </form>
</div>

