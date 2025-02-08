<script>
    import { onMount } from "svelte";
    import MultiSelect from "./MultiSelect.svelte";
    import { authToken } from "../stores/auth.js";

    export let showModal = false;
    export let customers = [];
    function toggleModal() {
        showModal = !showModal;
    }
    let name = "";
    let email = "";
    let adres = "";
    let allSubs = [];

    let selectedSubs = [];
    onMount(async () => {
        const res = await fetch("http://localhost:8000/api/subscriptions", {
            headers: {
                Authorization: "Bearer " + $authToken,
                Accept: "application/json",
            },
        });
        allSubs = await res.json();
    });

    async function handleSubmit(event) {
        event.preventDefault(); // Prevent the page from reloading
        const customerData = {
            name,
            email,
            adres,
            subscriptions: Object.values(selectedSubs),
        };

        const response = await fetch("http://localhost:8000/api/customers", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': "Bearer " + $authToken,
            Accept: "application/json",
            },
            body: JSON.stringify(customerData)
        });

        const responseData = await response.json();  // Always parse response to JSON
if (response.ok) {
    toggleModal();  // Close the modal on success
    console.log('Customer created successfully');
    console.log(responseData);
    customers = [...customers, {
        id: responseData.customer.id,
        name: responseData.customer.name,
        email: responseData.customer.email,
        adres: responseData.customer.adres,
        subscriptions: responseData.customer.subscriptions,
    }] // Add the new customer to the list
} else {
    console.error('Failed to create customer', responseData);
    alert('Failed to create customer: ' + JSON.stringify(responseData.errors));  // Display errors to the user
}
    }
</script>

<button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10" on:click={toggleModal}> Add Customer </button>

{#if showModal}
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-10" on:click={toggleModal} on:keydown={(e) => e.key === "Enter" && toggleModal()} aria-label="Close modal">
        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" on:click|stopPropagation role="dialog" aria-modal="true">
            <h2 class="text-xl font-semibold">Add New Customer</h2>
            <form on:submit={handleSubmit} class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="name" bind:value={name} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Full Name" />
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" bind:value={email} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Email Address" />
                </div>
                <div>
                    <label for="adres" class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" id="adres" bind:value={adres} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Physical Address" />
                </div>

                <MultiSelect options={allSubs} bind:selected={selectedSubs} />
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400 transition duration-300" on:click={toggleModal}> Cancel </button>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300"> Submit </button>
                </div>
            </form>
        </div>
    </div>
{/if}
