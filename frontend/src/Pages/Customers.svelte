<script>
    import { onMount } from "svelte";
    import { writable, get } from "svelte/store";
    import { derived } from "svelte/store";
    import toastr from "toastr";
    import AddCustomerModal from "../Components/AddCustomerModal.svelte"; // Modal to add a new customer
    import EditCustomerModal from "../Components/EditCustomerModal.svelte"; // Modal to edit an existing customer
    import { authToken } from "../stores/auth"; // Access token for authorization

    // Basic variables and store definitions
    let customers = writable([]); // Store for customer data
    let subscriptions = writable([]); // Store for subscription data
    let searchTerm = writable(""); // Store for search term input
    let sortOrder = writable("asc"); // Store for sorting order (ascending/descending)
    let sortColumn = writable(null); // Store for sorting column
    let selectedSubscription = writable(0); // Store for the selected subscription filter
    let loading = writable(false); // Store for loading state
    let showModal = false; // State to toggle AddCustomerModal visibility

    // Function to fetch customers from the API
    async function fetchCustomers() {
        loading.set(true);
        const res = await fetch("http://localhost:8000/api/customers", {
            headers: { Authorization: "Bearer " + $authToken },
        });
        if (res.ok) {
            let data = await res.json();
            data = data.map((customer) => ({
                ...customer,
                adres: `${customer.street} ${customer.house_number}, ${customer.postal_code} ${customer.city}`,
                statusLabel: customer.deleted_at ? "Archived" : "Active", // Set the status label based on deleted_at
            }));
            customers.set(data);
        } else {
            console.error("Failed to fetch customers");
        }
        loading.set(false);
    }

    // Function to fetch subscriptions from the API
    async function fetchSubscriptions() {
        const res = await fetch("http://localhost:8000/api/subscriptions", {
            headers: { Authorization: "Bearer " + $authToken },
        });
        if (res.ok) {
            subscriptions.set(await res.json()); // Update the subscriptions store with fetched data
        } else {
            console.error("Failed to fetch subscriptions"); // Log an error if fetching fails
        }
    }

    // Fetch customers and subscriptions when the component is mounted
    onMount(() => {
        fetchCustomers();
        fetchSubscriptions();
    });

    // Function to filter and sort customers based on search term, selected subscription, and sorting options
    const sortedAndFilteredCustomers = derived([customers, searchTerm, selectedSubscription, sortOrder, sortColumn], ([$customers, $searchTerm, $selectedSubscription, $sortOrder, $sortColumn]) => {
        return $customers
            .filter((customer) => {
                // Check if the customer's data matches the search term (case-insensitive)
                const matchesSearch = customer.name.toLowerCase().includes($searchTerm.toLowerCase()) || customer.email.toLowerCase().includes($searchTerm.toLowerCase()) || customer?.adres.toLowerCase().includes($searchTerm.toLowerCase()) || customer.subscriptions.some((sub) => sub.name.toLowerCase().includes($searchTerm.toLowerCase()));

                // Check if the customer matches the selected subscription filter
                const matchesSubscription = $selectedSubscription ? customer.subscriptions.some((sub) => sub.id === $selectedSubscription) : true;

                return matchesSearch && matchesSubscription; // Only return customers who match both conditions
            })
            .sort((a, b) => {
                if (!$sortColumn) return 0; // Do nothing if no column is selected for sorting

                // Determine sort direction (ascending or descending)
                const dir = $sortOrder === "asc" ? 1 : -1;

                // Make values case-insensitive for sorting
                const aValue = typeof a[$sortColumn] === "string" ? a[$sortColumn].toLowerCase() : a[$sortColumn];
                const bValue = typeof b[$sortColumn] === "string" ? b[$sortColumn].toLowerCase() : b[$sortColumn];

                // Compare the values of the selected column and return the result
                if (aValue > bValue) return dir;
                if (aValue < bValue) return -dir;
                return 0;
            });
    });

    // Function to sort the data based on the selected column and toggle the sorting order
    function sortData(column) {
        sortColumn.set(column); // Set the column to sort by
        sortOrder.update((current) => (current === "asc" ? "desc" : "asc")); // Toggle sorting order
    }

    // --- Pagination ---
    let currentPage = writable(1); // Store for the current page
    const itemsPerPage = 10; // Number of items per page

    // Derived store for paginated customers based on current page
    const paginatedCustomers = derived([sortedAndFilteredCustomers, currentPage], ([$sortedAndFilteredCustomers, $currentPage]) => {
        const startIndex = ($currentPage - 1) * itemsPerPage; // Calculate the start index for pagination
        return $sortedAndFilteredCustomers.slice(startIndex, startIndex + itemsPerPage); // Slice the customers array to get the current page data
    });

    // Derived store for calculating total pages
    const totalPages = derived(sortedAndFilteredCustomers, ($sortedAndFilteredCustomers) => {
        return Math.ceil($sortedAndFilteredCustomers.length / itemsPerPage) || 1; // Calculate total pages based on the number of items per page
    });

    // Function to go to the previous page
    function prevPage() {
        currentPage.update((n) => Math.max(n - 1, 1)); // Decrease page number but ensure it does not go below 1
    }

    // Function to go to the next page
    function nextPage() {
        const t = get(totalPages); // Get the total number of pages
        currentPage.update((n) => Math.min(n + 1, t)); // Increase page number but ensure it does not exceed total pages
    }

    // --- Edit Modal Functionality ---
    let showEditModal = false; // State to show/hide the edit modal
    let editCustomer = { id: "", name: "", email: "", adres: "", street: "", house_number: "", postal_code: "", city: "", subscriptions: [] }; // Edited customer data

    // Open the edit modal with the selected customer
    function openEditModal(customer) {
        editCustomer = { ...customer }; // Make a copy of the customer data to avoid direct mutation
        showEditModal = true; // Show the modal
    }

    // Close the edit modal
    function closeEditModal() {
        showEditModal = false; // Hide the modal
    }

    // Update the customer store with the updated customer data after a successful edit
    function handleUpdate(event) {
        const updatedCustomer = event.detail; // Get the updated customer data from the event
        customers.update(
            (current) => current.map((c) => (c.id === updatedCustomer.id ? updatedCustomer : c)) // Replace the old customer data with the updated one
        );
        closeEditModal(); // Close the modal
    }

    // --- Delete Functionality ---
    // Function to delete a customer after confirmation
    async function deleteCustomer(customer) {
        if (confirm("Are you sure you want to delete this customer?")) {
            const response = await fetch(`http://localhost:8000/api/customers/${customer.id}`, {
                method: "DELETE",
                headers: {
                    Authorization: "Bearer " + $authToken, // Pass the auth token in the header
                    Accept: "application/json",
                },
            });
            if (response.ok) {
                customers.update((current) => current.filter((c) => c.id !== customer.id)); // Remove the deleted customer from the store
                toastr.success("Customer archived successfully"); // Show success notification
            } else {
                const responseData = await response.json();
                if (responseData.errors) {
                    let message = "Failed to archive customer: ";
                    for (const [field, errors] of Object.entries(responseData.errors)) {
                        message += `${field} - ${errors.join(", ")}. `;
                    }
                    toastr.error(message, "Error"); // Show detailed error message
                } else {
                    toastr.error("Failed to archive customer", "Error"); // General error message
                }
            }
        }
    }

    // Functie om klanten te archiveren
    async function archiveCustomer(customer) {
        if (confirm("Are you sure you want to archive this customer?")) {
            const response = await fetch(`http://localhost:8000/api/customers/${customer.id}`, {
                method: "DELETE",
                headers: {
                    Authorization: "Bearer " + $authToken,
                    Accept: "application/json",
                },
            });
            if (response.ok) {
                customers.update((all) => all.map((c) => (c.id === customer.id ? { ...c, deleted_at: new Date().toISOString() } : c)));
                toastr.success("Customer archived successfully");
            } else {
                toastr.error("Failed to archive customer");
            }
        }
    }

    // Functie om klanten te herstellen

    async function restoreCustomer(customer) {
    if (confirm("Are you sure you want to restore this customer?")) {
        const response = await fetch(`http://localhost:8000/api/customers/${customer.id}/restore`, {
            method: "POST",
            headers: {
                Authorization: "Bearer " + $authToken,
                Accept: "application/json",
            },
        });
        if (response.ok) {
            customers.update((all) => all.map((c) => (c.id === customer.id ? { ...c, deleted_at: null } : c)));
            toastr.success("Customer restored successfully");
        } else {
            const errorData = await response.json();
            toastr.error("Failed to restore customer: " + (errorData.message || "Unknown error"));
        }
    }
}

</script>

<!-- Add Customer Modal -->
<AddCustomerModal {showModal} {customers} />

<!-- Edit Customer Modal -->
<EditCustomerModal show={showEditModal} customer={editCustomer} allSubs={$subscriptions} on:update={handleUpdate} on:close={closeEditModal} />

<!-- Main content for customer management -->
<div class="w-full mx-auto max-w-7xl">
    <div class="bg-neutral-900 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-white mb-6">Customer Management</h1>

        <!-- Search and subscription filter controls -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <input id="search" type="text" bind:value={$searchTerm} placeholder="Search by name, email, address or subscription..." class="w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-white placeholder-gray-500 focus:outline-none focus:ring focus:border-indigo-500" />
            </div>
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

        <!-- Customer table displaying filtered and sorted customers -->
        <div class="overflow-x-auto w-full">
            {#if $loading}
                <div class="w-full flex justify-center py-4">
                    <div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full border-blue-600 border-t-transparent" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            {/if}

            <table class="min-w-full w-full divide-y divide-gray-700">
                <thead class="bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Customer Name</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Email Address</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Address</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Subscriptions</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>

                <tbody class="bg-neutral-900 divide-y divide-gray-700">
                    {#each $paginatedCustomers as customer}
                        <tr>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-white">{customer.name}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-gray-300">{customer.email}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-gray-300">{customer.adres}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-left">
                                {#each customer.subscriptions as sub}
                                    <span class="inline-block bg-indigo-800 text-indigo-200 px-2 py-1 rounded-full text-xs font-medium mr-1">{sub.name}</span>
                                {/each}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-white">{customer.statusLabel}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-left">
                                <button class="px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition duration-300" on:click={() => openEditModal(customer)}>Edit</button>
                                {#if customer.deleted_at}
                                    <button class="ml-2 px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition duration-300" on:click={() => restoreCustomer(customer)}>Restore</button>
                                {:else}
                                    <button class="ml-2 px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition duration-300" on:click={() => archiveCustomer(customer)}>Archive</button>
                                {/if}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- Pagination controls -->
        <div class="flex items-center justify-between mt-4">
            <button on:click={prevPage} disabled={$currentPage === 1} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50">Previous</button>
            <span class="text-white">Page {$currentPage} of {$totalPages}</span>
            <button on:click={nextPage} disabled={$currentPage === $totalPages} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50">Next</button>
        </div>
    </div>
</div>
