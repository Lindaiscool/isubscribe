<script>
    import { authToken } from "../stores/auth.js";
    import toastr from "toastr";

    export let showModal = false; // Controls the visibility of the modal
    export let subscriptions; // List of existing subscriptions to update after a new one is added

    // Toggles the visibility of the modal when the button is clicked
    function toggleModal() {
        showModal = !showModal; // Switches the visibility state of the modal
    }

    // Variables to store new subscription data
    let name = "";
    let description = "";
    let price = 0;
    let vat = 0;
    let start_date = "";
    let end_date = "";

    // Function to validate the subscription data
    function validateSubscription() {
        // Validate price - must be a positive number
        if (price <= 0 || isNaN(price)) {
            toastr.error("Price must be a positive number", "Validation Error");
            return false;
        }

        // Validate VAT - must be a positive number
        if (vat < 0 || isNaN(vat)) {
            toastr.error("VAT must be a positive number", "Validation Error");
            return false;
        }

        // Validate start and end dates
        if (!start_date || !end_date) {
            toastr.error("Start date and end date are required", "Validation Error");
            return false;
        }

        // Check if end date is not before the start date
        if (new Date(end_date) < new Date(start_date)) {
            toastr.error("End date cannot be before the start date", "Validation Error");
            return false;
        }

        return true; // All validation checks passed
    }

    // Handle form submission to create a new subscription
    async function handleSubmit(event) {
        event.preventDefault(); // Prevents the default form submission behavior

        // Perform validation before submitting
        if (!validateSubscription()) {
            return; // Stop if validation fails
        }

        // Construct the subscription data object
        const subscriptionData = {
            name,
            description,
            price,
            vat,
            start_date,
            end_date,
        };

        // Send the subscription data to the backend via a POST request
        const response = await fetch("http://localhost/Linda/i-Subscribe/backend/public/api/subscriptions", {
            method: "POST", // Using POST method to create a new resource
            headers: {
                "Content-Type": "application/json", // Indicate that we are sending JSON data
                Authorization: "Bearer " + $authToken, // Pass the authentication token
                Accept: "application/json", // Expecting a JSON response
            },
            body: JSON.stringify(subscriptionData), // Convert the subscription data to JSON and send it in the body
        });

        const responseData = await response.json(); // Parse the JSON response

        if (response.ok) {
            toggleModal(); // Close the modal if the subscription is created successfully
            subscriptions.update((current) => [...current, subscriptionData]); // Update the list of subscriptions
            toastr.success("Subscription created successfully"); // Show success notification
        } else {
            toastr.error("Failed to create subscription", "Error"); // Show error notification if the request fails
        }
    }
</script>

<button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10 mb-8" on:click={toggleModal}>Add Subscription</button>

{#if showModal} <!-- Display the modal if 'showModal' is true -->
    <!-- Modal overlay with background blur -->
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-10" on:click={toggleModal}>
        <!-- Modal content that prevents clicks from propagating -->
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" on:click|stopPropagation>
            <h2 class="text-xl font-semibold">Add New Subscription</h2>

            <!-- Subscription creation form -->
            <form on:submit={handleSubmit} class="space-y-4">
                <!-- Name input field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="name" bind:value={name} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Subscription Name" required />
                </div>
                
                <!-- Description input field -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea bind:value={description} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Description" required></textarea>
                </div>

                <!-- Price input field -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Price (€)</label>
                    <input type="number" step="0.01" id="price" bind:value={price} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <!-- VAT percentage input field -->
                <div>
                    <label for="vat" class="block text-sm font-medium text-gray-700">VAT (%)</label>
                    <input type="number" id="vat" bind:value={vat} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <!-- Start date input field -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" id="start_date" bind:value={start_date} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <!-- End date input field -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" id="end_date" bind:value={end_date} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <!-- Buttons to either cancel or submit the form -->
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400" on:click={toggleModal}>Cancel</button>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700">Submit</button>
                </div>
            </form>
        </div>
    </div>
{/if}
