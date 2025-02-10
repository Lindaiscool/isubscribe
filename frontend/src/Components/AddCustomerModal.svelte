<script>
    import { onMount } from "svelte";
    import MultiSelect from "./MultiSelect.svelte";
    import { authToken } from "../stores/auth.js";
    import toastr from "toastr";

    export let showModal = false;
    export let customers = [];

    function toggleModal() {
        showModal = !showModal;
    }

    // Basisgegevens
    let name = "";
    let email = "";

    // Losse adresvelden
    let street = "";
    let house_number = "";
    let postal_code = "";
    let city = "";

    let addressError = "";
    let formError = "";

    let allSubs = [];
    let selectedSubs = [];

    onMount(() => {
    toastr.success('Component mounted successfully!');
});


    onMount(async () => {
        const res = await fetch("http://localhost:8000/api/subscriptions", {
            headers: {
                Authorization: "Bearer " + $authToken,
                Accept: "application/json",
            },
        });
        allSubs = await res.json();
    });

    function validateAddress() {
        if (!street.trim() || !house_number.trim() || !postal_code.trim() || !city.trim()) {
            addressError = "Alle adresvelden zijn verplicht.";
            return false;
        }
        if (!/\d/.test(house_number)) {
            addressError = "Huisnummer moet tenminste een cijfer bevatten.";
            return false;
        }
        if (!/^\d{4}\s?[A-Za-z]{2}$/.test(postal_code)) {
            addressError = "Postcode moet bestaan uit 4 cijfers en 2 letters (bijv. 1234 AB).";
            return false;
        }
        addressError = "";
        return true;
    }

    async function handleSubmit(event) {
        event.preventDefault();
        toastr.success('Testing toastr on form submission!');
        formError = "";

        if (!validateAddress()) {
            return;
        }

        // Verstuur de losse adresvelden mee
        const customerData = {
            name,
            email,
            street,
            house_number,
            postal_code,
            city,
            subscriptions: Object.values(selectedSubs),
        };

        const response = await fetch("http://localhost:8000/api/customers", {
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
    console.log("Customer created successfully", responseData);
    customers.update(current => [
        ...current,
        {
            id: responseData.customer.id,
            name: responseData.customer.name,
            email: responseData.customer.email,
            adres: `${responseData.customer.street} ${responseData.customer.house_number}, ${responseData.customer.postal_code} ${responseData.customer.city}`,
            subscriptions: responseData.customer.subscriptions,
        },
    ]);
    toastr.success('Customer created successfully');
}
 else {
    if (responseData.errors) {
        let message = "Failed to create customer: ";
        // Loop door alle velden met fouten
        for (const [field, errors] of Object.entries(responseData.errors)) {
            // Voeg elk foutbericht toe aan de hoofdmelding
            message += `${field} - ${errors.join(", ")}. `;
        }
        toastr.error(message, "Error");
    } else {
        // Als er geen specifieke fouten zijn geretourneerd, geef dan een algemene foutmelding
        toastr.error("Failed to create customer", "Error");
    }
}
    }
</script>

<button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10 mb-4" on:click={toggleModal}> Add Customer </button>

<!-- svelte-ignore a11y_no_static_element_interactions -->
{#if showModal}
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-10" on:click={toggleModal} on:keydown={(e) => e.key === "Enter" && toggleModal()} aria-label="Close modal">
        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" on:click|stopPropagation role="dialog" aria-modal="true">
            <h2 class="text-xl font-semibold">Add New Customer</h2>
            <form on:submit={handleSubmit} class="space-y-4">
                <!-- Naam -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="name" bind:value={name} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Full Name" required />
                </div>
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" bind:value={email} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Email Address" required />
                </div>
                <!-- Adresvelden met Tailwind grid -->
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
                {#if addressError}
                    <p class="text-red-500 text-sm mt-1">{addressError}</p>
                {/if}
                <!-- MultiSelect component -->
                <MultiSelect options={allSubs} bind:selected={selectedSubs} />
                <!-- Form knoppen -->
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400 transition duration-300" on:click={toggleModal}>Cancel</button>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300">Submit</button>
                </div>
            </form>
        </div>
    </div>
{/if}
