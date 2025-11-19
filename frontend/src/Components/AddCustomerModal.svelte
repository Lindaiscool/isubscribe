<script>
    import { onMount } from "svelte";
    import MultiSelect from "./MultiSelect.svelte";
    import { authToken } from "../stores/auth.js";
    import toastr from "toastr";

    export let showModal = false; // Controls the visibility of the modal
    export let customers; // The list of customers to be updated when a new customer is added

    // Toggle the modal visibility
    function toggleModal() {
        showModal = !showModal; // Toggle the showModal state
    }

    // Variables to store customer input data
    let name = "";
    let email = "";

    // Address fields
    let street = "";
    let house_number = "";
    let postal_code = "";
    let city = "";

    let addressError = ""; // Holds address validation error message
    let formError = ""; // Holds form validation error message

    let allSubs = []; // All available subscriptions for the customer
    let selectedSubs = []; // Subscriptions selected by the customer

    // Fetch available subscriptions on component mount
    onMount(async () => {
        const res = await fetch("http://localhost/Linda/i-Subscribe/backend/public/api/subscriptions", {
            headers: {
                Authorization: "Bearer " + $authToken, // Include the authentication token in the request
                Accept: "application/json",
            },
        });
        allSubs = await res.json(); // Store the fetched subscriptions in the 'allSubs' array
    });

    // Function to validate the address fields
    function validateAddress() {
        if (!street.trim() || !house_number.trim() || !postal_code.trim() || !city.trim()) {
            addressError = "All address fields are required.";
            return false;
        }
        if (!/\d/.test(house_number)) {
            addressError = "House number must contain at least one digit.";
            return false;
        }
        if (!/^\d{4}\s?[A-Za-z]{2}$/.test(postal_code)) {
            addressError = "Postal code must consist of 4 digits and 2 letters (e.g., 1234 AB).";
            return false;
        }
        addressError = ""; // Clear error if validation passes
        return true;
    }

    // Handle the form submission for creating a new customer
    async function handleSubmit(event) {
        event.preventDefault(); // Stop de standaard formulieractie
        formError = ""; // Reset eventuele vorige foutmeldingen

        // Validatie voor de naam en e-mail
        if (!name.trim()) {
            toastr.error("Name is required", "Validation Error");
            return;
        }
        if (!email.trim() || !/\S+@\S+\.\S+/.test(email)) {
            toastr.error("Please enter a valid email", "Validation Error");
            return;
        }

        // Validatie van adresvelden
        if (!validateAddress()) {
            toastr.error(addressError, "Validation Error"); // Toon specifieke foutmelding voor adres
            return;
        }

        // Verzamel alle gegevens
        const customerData = {
            name,
            email,
            street,
            house_number,
            postal_code,
            city,
            subscriptions: Object.values(selectedSubs),
        };

        const response = await fetch("http://localhost/Linda/i-Subscribe/backend/public/api/customers", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + $authToken,
                Accept: "application/json",
            },
            body: JSON.stringify(customerData),
        });

        const responseData = await response.json();
        if (response.ok) {
            toggleModal();
            customers.update((current) => [
                ...current,
                {
                    id: responseData.customer.id,
                    name: responseData.customer.name,
                    email: responseData.customer.email,
                    adres: `${responseData.customer.street} ${responseData.customer.house_number}, ${responseData.customer.postal_code} ${responseData.customer.city}`,
                    subscriptions: responseData.customer.subscriptions,
                },
            ]);
            toastr.success("Customer created successfully");
        } else {
            // Toon gedetailleerde foutmeldingen van de server
            if (responseData.errors) {
                let message = "Failed to create customer: ";
                for (const [field, errors] of Object.entries(responseData.errors)) {
                    message += `${field} - ${errors.join(", ")}. `;
                }
                toastr.error(message, "Error");
            } else {
                toastr.error("Failed to create customer", "Error");
            }
        }
    }
</script>


<button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10 mb-8" on:click={toggleModal}> Add Customer </button>

{#if showModal}
    <!-- Modal overlay, displayed when 'showModal' is true -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-10" on:click={toggleModal} on:keydown={(e) => e.key === "Enter" && toggleModal()} aria-label="Close modal">
        <!-- Modal content that stops propagation when clicked inside -->
        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" on:click|stopPropagation role="dialog" aria-modal="true">
            <h2 class="text-xl font-semibold">Add New Customer</h2>
            <!-- Customer form -->
            <form on:submit={handleSubmit} class="space-y-4">
                <!-- Name input -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="name" bind:value={name} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Full Name" required />
                </div>
                <!-- Email input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" bind:value={email} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Email Address" required />
                </div>
                <!-- Address fields with grid layout -->
                <div class="grid grid-cols-3 gap-4 mb-2">
                    <div class="col-span-2">
                        <label for="street" class="block text-sm text-gray-700">Street</label>
                        <input type="text" id="street" bind:value={street} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Street" required />
                    </div>
                    <div>
                        <label for="house_number" class="block text-sm text-gray-700">House number</label>
                        <input type="text" id="house_number" bind:value={house_number} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="House nr" required />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 mb-2">
                    <div>
                        <label for="postal_code" class="block text-sm text-gray-700">Postal code</label>
                        <input type="text" id="postal_code" bind:value={postal_code} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="1234 AB" required />
                    </div>
                    <div class="col-span-2">
                        <label for="city" class="block text-sm text-gray-700">City</label>
                        <input type="text" id="city" bind:value={city} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="City" required />
                    </div>
                </div>

                <!-- MultiSelect component for subscriptions -->
                <MultiSelect options={allSubs} bind:selected={selectedSubs} />

                <!-- Submit and Cancel buttons -->
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400 transition duration-300" on:click={toggleModal}>Cancel</button>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300">Submit</button>
                </div>
            </form>
        </div>
    </div>
{/if}
