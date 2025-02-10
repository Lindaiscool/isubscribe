<script>
    import { createEventDispatcher } from "svelte";
    import MultiSelect from "./MultiSelect.svelte";
    import { authToken } from "../stores/auth.js";
    import toastr from "toastr";

    export let show = false; // of de modal zichtbaar is
    // De klant wordt binnengehaald met een gecombineerd adres (adres) en losse velden (street, house_number, postal_code, city)
    export let customer = {
        id: "",
        name: "",
        email: "",
        adres: "", // Bijvoorbeeld: "Gasselternijveenschemondstraat 1, 9601 CB Apeldoorn"
        street: "",
        house_number: "",
        postal_code: "",
        city: "",
        subscriptions: [],
    };
    export let allSubs = []; // alle beschikbare subscriptions

    // Geselecteerde subscriptions voor deze klant (als array met id's)
    let selectedSubs = [];

    const dispatch = createEventDispatcher();

    // Als de klant verandert, initialiseer de geselecteerde subscriptions
    $: if (customer) {
        selectedSubs = customer.subscriptions.map((sub) => sub.id);
    }

    // --- Reactieve block voor adres-splitting ---
    // Wanneer de modal open staat en er is een gecombineerde adresstring, maar de losse velden nog leeg zijn,
    // splits dan het adres in onderdelen.
    $: if (show && customer && customer.adres && (!customer.street || !customer.house_number || !customer.postal_code || !customer.city)) {
        const parts = customer.adres.split(",");
        if (parts.length === 2) {
            const part1 = parts[0].trim(); // verwacht: "Gasselternijveenschemondstraat 1"
            const part2 = parts[1].trim(); // verwacht: "9601 CB Apeldoorn"
            // Splits het eerste deel: het laatste token is het huisnummer, de rest is de straat
            const tokens1 = part1.split(" ");
            customer.house_number = tokens1.pop() || "";
            customer.street = tokens1.join(" ");

            // Gebruik een regex om de Nederlandse postcode en de stad uit het tweede deel te halen.
            // Deze regex verwacht 4 cijfers, gevolgd door 2 letters, en daarna de rest (de stad)
            const regex = /^(\d{4}\s?[A-Za-z]{2})\s+(.*)$/;
            const match = part2.match(regex);
            if (match) {
                customer.postal_code = match[1];
                customer.city = match[2];
            } else {
                // Fallback: splits op spaties
                const tokens2 = part2.split(" ");
                if (tokens2.length >= 2) {
                    customer.postal_code = tokens2[0] + " " + tokens2[1];
                    customer.city = tokens2.slice(2).join(" ");
                } else {
                    customer.postal_code = part2;
                    customer.city = "";
                }
            }
        }
    }
    // --- Einde reactieve block ---

    async function handleEditSubmit(event) {
        event.preventDefault();
        // Combineer de vier invoervelden tot één adresstring in het gewenste formaat:
        // "Straat Huisnummer, POSTCODE City"
        const combinedAddress = `${customer.street} ${customer.house_number}, ${customer.postal_code.toUpperCase()} ${customer.city}`;

        const updatedCustomer = {
            id: customer.id,
            name: customer.name,
            email: customer.email,
            street: customer.street,
            house_number: customer.house_number,
            postal_code: customer.postal_code,
            city: customer.city,
            subscriptions: Object.values(selectedSubs),
        };

        const response = await fetch(`http://localhost:8000/api/customers/${customer.id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + $authToken,
                Accept: "application/json",
            },
            body: JSON.stringify(updatedCustomer),
        });

        const responseData = await response.json();
        if (response.ok) {
            // Geef via een event het bijgewerkte klantobject door
            updatedCustomer.subscriptions = responseData.subscriptions;
            dispatch("update", updatedCustomer);
        } else {
    if (responseData.errors) {
        let message = "Failed to update customer: ";
        for (const [field, errors] of Object.entries(responseData.errors)) {
            message += `${field} - ${errors.join(", ")}. `;
        }
        toastr.error(message, "Error");
    } else {
        toastr.error("Failed to update customer", "Error");
    }
}
    }


    function closeModal() {
        dispatch("close");
    }
</script>

<!-- svelte-ignore a11y_no_noninteractive_tabindex -->
<!-- svelte-ignore a11y_no_static_element_interactions -->
<!-- svelte-ignore a11y_no_static_element_interactions -->
{#if show && customer}
    <!-- Overlay -->
    <!-- svelte-ignore a11y_no_noninteractive_tabindex -->
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <div tabindex="0" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-10" on:click={closeModal} on:keydown={(e) => e.key === "Enter" && closeModal()} aria-label="Close modal">
        <!-- svelte-ignore a11y_click_events_have_key_events -->
        <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" on:click|stopPropagation role="dialog" aria-modal="true">
            <h2 class="text-xl font-semibold mb-4">Edit Customer</h2>
            <form on:submit={handleEditSubmit} class="space-y-4">
                <!-- Naam -->
                <div>
                    <label for="edit-name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="edit-name" bind:value={customer.name} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Full Name" />
                </div>
                <!-- Email -->
                <div>
                    <label for="edit-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="edit-email" bind:value={customer.email} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Email Address" />
                </div>
                <!-- Adresvelden met grid-layout -->
                <!-- Rij: Street en House number -->
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
                <!-- Rij: Postal code en City -->
                <div class="grid grid-cols-3 gap-4 mb-2">
                    <div>
                        <label for="edit-postal_code" class="block text-sm text-gray-700">Postcode</label>
                        <input type="text" id="edit-postal_code" bind:value={customer.postal_code} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="1234 AB" required />
                    </div>
                    <div class="col-span-2">
                        <label for="edit-city" class="block text-sm text-gray-700">City</label>
                        <input type="text" id="edit-city" bind:value={customer.city} class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="City" required />
                    </div>
                </div>
                <!-- MultiSelect voor subscriptions -->
                <MultiSelect options={allSubs} bind:selected={selectedSubs} />
                <!-- Form knoppen -->
                <div class="flex justify-end">
                    <button type="button" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400 transition duration-300" on:click={closeModal}> Cancel </button>
                    <button type="submit" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300"> Save </button>
                </div>
            </form>
        </div>
    </div>
{/if}
