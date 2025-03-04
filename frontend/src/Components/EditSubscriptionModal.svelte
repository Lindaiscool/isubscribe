<script>
    import { createEventDispatcher } from "svelte";
    import { authToken } from "../stores/auth.js";
    import toastr from "toastr";

    export let show = false; // Controls the visibility of the modal
    export let subscription = { id: "", name: "", description: "", price: 0, vat: 0, start_date: "", end_date: "" }; // Holds the subscription data to be edited

    const dispatch = createEventDispatcher(); // Used to send events from the modal to the parent component

    // Handles the submission of the subscription edit form
    async function handleEditSubmit(event) {
        event.preventDefault(); // Prevents the default form submission behavior

        // Check if start_date and end_date are provided
        if (!subscription.start_date || !subscription.end_date) {
            toastr.error("Start date and end date are required"); // Show error message if dates are missing
            return;
        }

        // Prepare the updated subscription data
        const updatedSubscription = {
            id: subscription.id,
            name: subscription.name,
            description: subscription.description,
            price: subscription.price,
            vat: subscription.vat,
            start_date: subscription.start_date,
            end_date: subscription.end_date,
        };

        // Send the updated subscription data to the server via a PUT request
        const response = await fetch(`http://localhost:8000/api/subscriptions/${subscription.id}`, {
            method: "PUT", // Specifies that it's a PUT request
            headers: {
                "Content-Type": "application/json", // We are sending JSON data
                Authorization: "Bearer " + $authToken, // Include the bearer token in the Authorization header
                Accept: "application/json", // Expecting a JSON response from the server
            },
            body: JSON.stringify(updatedSubscription), // Sending the updated subscription data as JSON
        });

        // Parse the server's response
        const responseData = await response.json();
        if (response.ok) {
            toastr.success("Subscription updated successfully"); // Show success message on successful update
            dispatch("update", updatedSubscription); // Dispatch the updated subscription data to the parent component
        } else {
            toastr.error("Failed to update subscription", "Error"); // Show error message if update fails
        }
    }

    // Closes the modal when the user clicks outside or presses the cancel button
    function closeModal() {
        dispatch("close"); // Dispatch the close event to the parent component
    }
</script>

{#if show && subscription}
    <!-- Display modal if 'show' is true and 'subscription' is defined -->
    <!-- Modal overlay to dim the background -->
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-10" on:click={closeModal}>
        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_static_element_interactions -->
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" on:click|stopPropagation>
            <h2 class="text-xl font-semibold mb-4">Edit Subscription</h2>

            <!-- Form to edit the subscription data -->
            <form on:submit={handleEditSubmit} class="space-y-4">
                <div>
                    <!-- Label and input field for the subscription name -->
                    <!-- svelte-ignore a11y_label_has_associated_control -->
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" bind:value={subscription.name} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <div>
                    <!-- Label and textarea for the subscription description -->
                    <!-- svelte-ignore a11y_label_has_associated_control -->
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea bind:value={subscription.description} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required></textarea>
                </div>

                <div>
                    <!-- Label and input field for the subscription price -->
                    <!-- svelte-ignore a11y_label_has_associated_control -->
                    <label class="block text-sm font-medium text-gray-700">Price (€)</label>
                    <input type="number" step="0.01" bind:value={subscription.price} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <div>
                    <!-- Label and input field for the VAT percentage -->
                    <!-- svelte-ignore a11y_label_has_associated_control -->
                    <label class="block text-sm font-medium text-gray-700">VAT (%)</label>
                    <input type="number" bind:value={subscription.vat} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <div>
                    <!-- Label and input field for the start date -->
                    <!-- svelte-ignore a11y_label_has_associated_control -->
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" bind:value={subscription.start_date} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <div>
                    <!-- Label and input field for the end date -->
                    <!-- svelte-ignore a11y_label_has_associated_control -->
                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" bind:value={subscription.end_date} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required />
                </div>

                <!-- Buttons to either save the changes or cancel the operation -->
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400" on:click={closeModal}>Cancel</button>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>
{/if}
