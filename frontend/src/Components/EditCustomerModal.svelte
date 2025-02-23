<script>
    import { onMount, createEventDispatcher } from "svelte";
    import { authToken } from "../stores/auth.js";
    import toastr from "toastr";
    import MultiSelect from "./MultiSelect.svelte"; // Component for selecting subscriptions

    export let show = false; // Controls the visibility of the modal
    export let customer = {
        id: "",
        name: "",
        email: "",
        adres: "", // Combined address string
        street: "",
        house_number: "",
        postal_code: "",
        city: "",
        subscriptions: [],
    }; // Customer data with address and subscriptions
    export let allSubs = []; // List of all available subscriptions

    let selectedSubs = []; // Subscriptions selected by the customer

    const dispatch = createEventDispatcher();

    // Update selected subscriptions whenever the customer data changes
    $: if (customer) {
        selectedSubs = customer.subscriptions.map((sub) => sub.id); // Initialize selected subscriptions based on customer data
    }

    // Reactive block for address parsing
    $: if (show && customer && customer.adres && (!customer.street || !customer.house_number || !customer.postal_code || !customer.city)) {
        const parts = customer.adres.split(",");
        if (parts.length === 2) {
            const part1 = parts[0].trim(); // "Street and House number" part
            const part2 = parts[1].trim(); // "Postal code and City" part

            // Split street and house number
            const tokens1 = part1.split(" ");
            customer.house_number = tokens1.pop() || ""; // Last token is the house number
            customer.street = tokens1.join(" "); // Remaining part is the street

            // Use regex to extract postal code and city
            const regex = /^(\d{4}\s?[A-Za-z]{2})\s+(.*)$/;
            const match = part2.match(regex);
            if (match) {
                customer.postal_code = match[1]; // Valid postal code format
                customer.city = match[2]; // City
            } else {
                // Fallback for address splitting
                const tokens2 = part2.split(" ");
                if (tokens2.length >= 2) {
                    customer.postal_code = tokens2[0] + " " + tokens2[1]; // Postal code
                    customer.city = tokens2.slice(2).join(" "); // City
                } else {
                    customer.postal_code = part2;
                    customer.city = "";
                }
            }
        }
    }

    // Handle form submission when customer details are updated
    async function handleEditSubmit(event) {
        event.preventDefault(); // Prevent default form submission behavior

        // Combine the address parts into one string
        const combinedAddress = `${customer.street} ${customer.house_number}, ${customer.postal_code.toUpperCase()} ${customer.city}`;

        // Prepare updated customer data
        const updatedCustomer = {
            id: customer.id,
            name: customer.name,
            email: customer.email,
            street: customer.street,
            house_number: customer.house_number,
            postal_code: customer.postal_code,
            city: customer.city,
            subscriptions: Object.values(selectedSubs),
            adres: combinedAddress, // Add combined address to the object
        };

        // Send PUT request to update the customer data
        const response = await fetch(`http://localhost:8000/api/customers/${customer.id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + $authToken,
                Accept: "application/json",
            },
            body: JSON.stringify(updatedCustomer), // Send updated customer data as JSON
        });

        const responseData = await response.json(); // Parse the response
        if (response.ok) {
            updatedCustomer.subscriptions = responseData.subscriptions; // Attach the updated subscriptions
            toastr.success("Customer updated successfully"); // Show success notification
            dispatch("update", updatedCustomer); // Dispatch event with updated customer data
        } else {
            // Handle errors if update fails
            if (responseData.errors) {
                let message = "Failed to update customer: ";
                for (const [field, errors] of Object.entries(responseData.errors)) {
                    message += `${field} - ${errors.join(", ")}. `;
                }
                toastr.error(message, "Error"); // Show error notification with detailed message
            } else {
                toastr.error("Failed to update customer", "Error"); // General error message
            }
        }
    }

    // Close modal when clicking outside or pressing Escape/Enter
    function closeModal() {
        dispatch("close");
    }
</script>

{#if show && customer}
    <!-- Modal is shown if 'show' is true -->
    <!-- Modal overlay background -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <!-- svelte-ignore a11y_no_noninteractive_tabindex -->
    <div tabindex="0" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-10" on:click={closeModal} on:keydown={(e) => e.key === "Enter" && closeModal()} aria-label="Close modal">
        <!-- Modal content that prevents click events from propagating -->
        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" on:click|stopPropagation role="dialog" aria-modal="true">
            <h2 class="text-xl font-semibold mb-4">Edit Customer</h2>
            <!-- Customer update form -->
            <form on:submit={handleEditSubmit} class="space-y-4">
                <!-- Name input field -->
                <div>
                    <label for="edit-name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="edit-name" bind:value={customer.name} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Full Name" />
                </div>

                <!-- Email input field -->
                <div>
                    <label for="edit-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="edit-email" bind:value={customer.email} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Email Address" />
                </div>

                <!-- Address fields in a grid layout -->
                <div class="grid grid-cols-3 gap-4 mb-2">
                    <div class="col-span-2">
                        <label for="edit-street" class="block text-sm text-gray-700">Street</label>
                        <input type="text" id="edit-street" bind:value={customer.street} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Street" required />
                    </div>
                    <div>
                        <label for="edit-house_number" class="block text-sm text-gray-700">House number</label>
                        <input type="text" id="edit-house_number" bind:value={customer.house_number} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="House nr" required />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-2">
                    <div>
                        <label for="edit-postal_code" class="block text-sm text-gray-700">Postal code</label>
                        <input type="text" id="edit-postal_code" bind:value={customer.postal_code} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="1234 AB" required />
                    </div>
                    <div class="col-span-2">
                        <label for="edit-city" class="block text-sm text-gray-700">City</label>
                        <input type="text" id="edit-city" bind:value={customer.city} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="City" required />
                    </div>
                </div>

                <!-- MultiSelect for selecting subscriptions -->
                <MultiSelect options={allSubs} bind:selected={selectedSubs} />

                <!-- Submit and Cancel buttons -->
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400 transition duration-300" on:click={closeModal}> Cancel </button>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300"> Save </button>
                </div>
            </form>
        </div>
    </div>
{/if}
