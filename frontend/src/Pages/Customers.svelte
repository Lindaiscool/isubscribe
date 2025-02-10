<script>
    import { onMount } from "svelte";
    import { writable, get } from "svelte/store";
    import { derived } from "svelte/store";
    import toastr from "toastr";

    import MultiSelect from "../Components/MultiSelect.svelte";
    import AddCustomerModal from "../Components/AddCustomerModal.svelte";
    import EditCustomerModal from "../Components/EditCustomerModal.svelte";
    import { authToken } from "../stores/auth";

    // Stores en basisvariabelen
    let customers = writable([]);
    let subscriptions = writable([]);
    let selectedSubscriptions = writable([]);
    let searchTerm = writable("");
    let sortOrder = writable("asc");
    let sortColumn = writable(null);
    let selectedSubscription = writable(0);

    let showModal = false; // Voor AddCustomerModal

    // Ophalen van klanten
    async function fetchCustomers() {
        const res = await fetch("http://localhost:8000/api/customers", {
            headers: { Authorization: "Bearer " + $authToken },
        });
        if (res.ok) {
            let data = await res.json();
            data = data.map((customer) => {
                customer.adres = `${customer.street} ${customer.house_number}, ${customer.postal_code} ${customer.city}`;
                return customer;
            });
            customers.set(data);
        } else {
            console.error("Failed to fetch customers");
        }
    }

    // Ophalen van subscriptions
    async function fetchSubscriptions() {
        const res = await fetch("http://localhost:8000/api/subscriptions", {
            headers: { Authorization: "Bearer " + $authToken },
        });
        if (res.ok) {
            subscriptions.set(await res.json());
        } else {
            console.error("Failed to fetch subscriptions");
        }
    }

    onMount(() => {
        fetchCustomers();
        fetchSubscriptions();
    });

    // Gesorteerde en gefilterde klanten
    const sortedAndFilteredCustomers = derived([customers, searchTerm, selectedSubscription, sortOrder, sortColumn], ([$customers, $searchTerm, $selectedSubscription, $sortOrder, $sortColumn]) => {
        return $customers
            .filter((customer) => {
                const matchesSearch = customer.name.toLowerCase().includes($searchTerm.toLowerCase()) || customer.email.toLowerCase().includes($searchTerm.toLowerCase()) || customer?.adres.toLowerCase().includes($searchTerm.toLowerCase()) || customer.subscriptions.some((sub) => sub.name.toLowerCase().includes($searchTerm.toLowerCase()));
                const matchesSubscription = $selectedSubscription ? customer.subscriptions.some((sub) => sub.id === $selectedSubscription) : true;
                return matchesSearch && matchesSubscription;
            })
            .sort((a, b) => {
                if (!$sortColumn) return 0;
                const dir = $sortOrder === "asc" ? 1 : -1;
                return a[$sortColumn] > b[$sortColumn] ? dir : -dir;
            });
    });

    function sortData(column) {
        sortColumn.set(column);
        sortOrder.update((current) => (current === "asc" ? "desc" : "asc"));
    }

    // --- Paginatie ---
    let currentPage = writable(1);
    const itemsPerPage = 10;

    const paginatedCustomers = derived([sortedAndFilteredCustomers, currentPage], ([$sortedAndFilteredCustomers, $currentPage]) => {
        const startIndex = ($currentPage - 1) * itemsPerPage;
        return $sortedAndFilteredCustomers.slice(startIndex, startIndex + itemsPerPage);
    });

    const totalPages = derived(sortedAndFilteredCustomers, ($sortedAndFilteredCustomers) => {
        return Math.ceil($sortedAndFilteredCustomers.length / itemsPerPage) || 1;
    });

    function prevPage() {
        currentPage.update((n) => Math.max(n - 1, 1));
    }

    function nextPage() {
        const t = get(totalPages);
        currentPage.update((n) => Math.min(n + 1, t));
    }

    // --- Edit Modal Functionaliteit ---
    let showEditModal = false;
    let editCustomer = { id: "", name: "", email: "", adres: "", street: "", house_number: "", postal_code: "", city: "", subscriptions: [] };

    function openEditModal(customer) {
        // Maak een kopie zodat we geen directe mutaties uitvoeren op de originele data
        editCustomer = { ...customer };
        showEditModal = true;
    }

    function closeEditModal() {
        showEditModal = false;
    }

    // Werk de customers store bij wanneer een klant succesvol is bewerkt
    function handleUpdate(event) {
        const updatedCustomer = event.detail;
        customers.update((current) => current.map((c) => (c.id === updatedCustomer.id ? updatedCustomer : c)));
        closeEditModal();
    }

    // --- Delete Functionaliteit ---
    async function deleteCustomer(customer) {
        if (confirm("Are you sure you want to delete this customer?")) {
            const response = await fetch(`http://localhost:8000/api/customers/${customer.id}`, {
                method: "DELETE",
                headers: {
                    Authorization: "Bearer " + $authToken,
                    Accept: "application/json",
                },
            });
            if (response.ok) {
                customers.update((current) => current.filter((c) => c.id !== customer.id));
            } else {
                const responseData = await response.json();
                if (responseData.errors) {
                    let message = "Failed to delete customer: ";
                    for (const [field, errors] of Object.entries(responseData.errors)) {
                        message += `${field} - ${errors.join(", ")}. `;
                    }
                    toastr.error(message, "Error");
                } else {
                    toastr.error("Failed to delete customer", "Error");
                }
            }
        }
    }
</script>

<!-- Add Customer Modal -->
<AddCustomerModal {showModal} bind:customers={$customers} />

<!-- Edit Customer Modal -->
<EditCustomerModal show={showEditModal} customer={editCustomer} allSubs={$subscriptions} on:update={handleUpdate} on:close={closeEditModal} />

<div class="w-full mx-auto">
    <!-- Card Container -->
    <div class="bg-neutral-900 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-white mb-6">Customer Management</h1>

        <!-- Form / Filters -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Search Input -->
            <div>
                <input id="search" type="text" bind:value={$searchTerm} placeholder="Search by name, email or address..." class="w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-white placeholder-gray-500 focus:outline-none focus:ring focus:border-indigo-500" />
            </div>

            <!-- Subscription Filter -->
            <div>
                <select id="subscriptionFilter" bind:value={$selectedSubscription} class="block w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-gray-500 focus:outline-none focus:ring focus:border-indigo-500">
                    <option value={0} hidden>Select Subscription</option>
                    <option value="" class="text-white">All Subscriptions</option>
                    {#each $subscriptions as subscription}
                        <option value={subscription.id} class="text-white">{subscription.name}</option>
                    {/each}
                </select>
            </div>
        </div>

        <!-- Tabel -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700">
                <thead class="bg-zinc-800">
                    <tr>
                        <!-- Naam kolom -->
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("name")}>
                            Customer Name
                            {#if $sortColumn === "name"}
                                {#if $sortOrder === "asc"}
                                    <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                {:else}
                                    <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                {/if}
                            {/if}
                        </th>

                        <!-- Email kolom -->
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("email")}>
                            Email Address
                            {#if $sortColumn === "email"}
                                {#if $sortOrder === "asc"}
                                    <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                {:else}
                                    <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                {/if}
                            {/if}
                        </th>

                        <!-- Adres kolom -->
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("adres")}>
                            Address
                            {#if $sortColumn === "adres"}
                                {#if $sortOrder === "asc"}
                                    <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    </svg>
                                {:else}
                                    <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                {/if}
                            {/if}
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider"> Subscriptions </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider"> Actions </th>
                    </tr>
                </thead>
                <tbody class="bg-neutral-900 divide-y divide-gray-700">
                    {#each $paginatedCustomers as customer}
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-white">
                                {customer.name}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-gray-300">
                                {customer.email}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-gray-300">
                                {customer.adres}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {#each customer.subscriptions as sub}
                                    <span class="inline-block bg-indigo-800 text-indigo-200 px-2 py-1 rounded-full text-xs font-medium mr-1">
                                        {sub.name}
                                    </span>
                                {/each}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                <button class="px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition duration-300" on:click={() => openEditModal(customer)}> Edit </button>
                                <button class="ml-2 px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition duration-300" on:click={() => deleteCustomer(customer)}> Delete </button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- Paginatie Controls -->
        <div class="flex items-center justify-between mt-4">
            <button on:click={prevPage} disabled={$currentPage === 1} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50"> Previous </button>
            <span class="text-white">
                Page {$currentPage} of {$totalPages}
            </span>
            <button on:click={nextPage} disabled={$currentPage === $totalPages} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50"> Next </button>
        </div>
    </div>
</div>
