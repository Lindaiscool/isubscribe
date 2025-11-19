<script>
    import { onMount } from "svelte";
    import { writable, get } from "svelte/store";
    import { derived } from "svelte/store";
    import toastr from "toastr";
    // Importing modal components
    // @ts-ignore
    import AddSubscriptionModal from "../Components/AddSubscriptionModal.svelte";
    // @ts-ignore
    import EditSubscriptionModal from "../Components/EditSubscriptionModal.svelte";
    import { authToken } from "../stores/auth";

    // State variables using Svelte stores
    let searchTerm = writable(""); // Stores the search query for filtering subscriptions
    let sortOrder = writable("asc"); // Stores the sorting order (ascending or descending)
    let sortColumn = writable(null); // Stores the column to sort by
    let selectedSubscription = writable(0); // Stores the selected subscription for filtering
    let loading = writable(false); // Tracks whether data is still being loaded
    let showModal = false; // Controls the visibility of the AddSubscriptionModal
    let subscriptions = writable([]); // Holds the list of subscriptions fetched from the API

    // Fetch subscriptions from the API
    async function fetchSubscriptions() {
        loading.set(true); // Set loading state to true to show a loading indicator
        const res = await fetch("http://localhost/Linda/i-Subscribe/backend/public/api/subscriptions", {
            headers: { Authorization: "Bearer " + $authToken }, // Include authentication token in the request
        });
        if (res.ok) {
            let data = await res.json(); // Parse the response data
            subscriptions.set(data); // Update the subscriptions store with the fetched data
        } else {
            console.error("Failed to fetch subscriptions"); // Handle fetch error
        }
        loading.set(false); // Set loading state to false once data is loaded
    }

    // Fetch subscriptions on component mount
    onMount(() => {
        fetchSubscriptions();
    });

    // Sorting and filtering subscriptions based on the search term and selected subscription
    const sortedAndFilteredSubscriptions = derived([subscriptions, searchTerm, selectedSubscription, sortOrder, sortColumn], ([$subscriptions, $searchTerm, $selectedSubscription, $sortOrder, $sortColumn]) => {
        // Filter subscriptions based on the search term and selected subscription
        const filteredSubscriptions = $subscriptions.filter((subscription) => {
            const matchesSearch = subscription.name.toLowerCase().includes($searchTerm.toLowerCase()) || subscription.description.toLowerCase().includes($searchTerm.toLowerCase());
            const matchesSubscription = $selectedSubscription ? subscription.subscriptions.some((sub) => sub.id === $selectedSubscription) : true;

            return matchesSearch && matchesSubscription;
        });

        // Sort the filtered subscriptions based on the selected column and order
        const sortedSubscriptions = filteredSubscriptions.sort((a, b) => {
            if (!$sortColumn) return 0; // Return no sorting if no column is selected
            const dir = $sortOrder === "asc" ? 1 : -1; // Determine sort direction (ascending or descending)
            const aValue = typeof a[$sortColumn] === "string" ? a[$sortColumn].toLowerCase() : a[$sortColumn];
            const bValue = typeof b[$sortColumn] === "string" ? b[$sortColumn].toLowerCase() : b[$sortColumn];

            if (aValue > bValue) return dir;
            if (aValue < bValue) return -dir;
            return 0; // If equal, return 0 to maintain order
        });

        return sortedSubscriptions; // Return the sorted and filtered subscriptions
    });

    // Function to toggle sorting order
    function sortData(column) {
        sortColumn.set(column); // Set the column to sort by
        sortOrder.update((current) => (current === "asc" ? "desc" : "asc")); // Toggle the sorting order
    }

    // Pagination variables
    let currentPage = writable(1); // Tracks the current page number
    const itemsPerPage = 10; // Defines how many items per page to display

    // Paginated subscriptions derived from sorted and filtered subscriptions
    const paginatedSubscriptions = derived([sortedAndFilteredSubscriptions, currentPage], ([$sortedAndFilteredSubscriptions, $currentPage]) => {
        const startIndex = ($currentPage - 1) * itemsPerPage; // Calculate the starting index for the current page
        return $sortedAndFilteredSubscriptions.slice(startIndex, startIndex + itemsPerPage); // Return the slice of subscriptions for the current page
    });

    // Total pages based on the total number of subscriptions and items per page
    const totalPages = derived(sortedAndFilteredSubscriptions, ($sortedAndFilteredSubscriptions) => {
        return Math.ceil($sortedAndFilteredSubscriptions.length / itemsPerPage) || 1; // Calculate total pages
    });

    // Functions to navigate between pages
    function prevPage() {
        currentPage.update((n) => Math.max(n - 1, 1)); // Go to the previous page, but not below 1
    }

    function nextPage() {
        const t = get(totalPages); // Get the total number of pages
        currentPage.update((n) => Math.min(n + 1, t)); // Go to the next page, but not beyond the total number of pages
    }

    // Edit Subscription Modal functionality
    let showEditModal = false; // Controls visibility of the Edit Subscription Modal
    let editSubscription = { id: "", name: "", description: "", price: 0, vat: 0, start_date: "", end_date: "" }; // Stores the subscription to edit

    // Open the edit modal and populate it with the selected subscription's data
    function openEditModal(subscription) {
        editSubscription = { ...subscription }; // Create a copy to avoid direct mutation of the original data
        showEditModal = true; // Show the modal
    }

    // Close the edit modal
    function closeEditModal() {
        showEditModal = false; // Hide the modal
    }

    // Update the subscription in the store after editing
    function handleUpdate(event) {
        const updatedSubscription = event.detail; // Get the updated subscription from the modal

        // Update the subscriptions store with the edited subscription data
        subscriptions.update((current) => current.map((c) => (c.id === updatedSubscription.id ? updatedSubscription : c)));
        closeEditModal(); // Close the modal after updating
    }

    // Delete Subscription functionality
    async function deleteSubscription(subscription) {
        if (confirm("Are you sure you want to delete this subscription?")) {
            // Confirm deletion
            const response = await fetch(`http://localhost/Linda/i-Subscribe/backend/public/api/subscriptions/${subscription.id}`, {
                method: "DELETE", // HTTP method for deletion
                headers: {
                    Authorization: "Bearer " + $authToken, // Authorization header with token
                    Accept: "application/json", // Accept header to expect JSON response
                },
            });
            if (response.ok) {
                subscriptions.update((current) => current.filter((c) => c.id !== subscription.id)); // Remove deleted subscription from the list
                toastr.success("Subscription deleted successfully"); // Show success message
            } else {
                const responseData = await response.json();
                if (responseData.errors) {
                    let message = "Failed to delete subscription: ";
                    for (const [field, errors] of Object.entries(responseData.errors)) {
                        message += `${field} - ${errors.join(", ")}. `;
                    }
                    toastr.error(message, "Error"); // Show detailed error message if deletion fails
                } else {
                    toastr.error("Failed to delete subscription", "Error"); // Show generic error message
                }
            }
        }
    }
</script>

<!-- Add Subscription Modal -->
<AddSubscriptionModal {showModal} {subscriptions} />

<!-- Edit Subscription Modal -->
<EditSubscriptionModal show={showEditModal} bind:subscription={editSubscription} on:update={handleUpdate} on:close={closeEditModal} />

<div class="w-full mx-auto max-w-7xl">
    <div class="bg-neutral-900 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-white mb-6">Subscription Management</h1>

        <!-- Search Bar -->
        <div class="flex justify-center mb-8">
            <div class="w-full md:w-96">
                <input id="search" type="text" bind:value={$searchTerm} placeholder="Search by name or description..." class="w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-white placeholder-gray-500 focus:outline-none focus:ring focus:border-indigo-500" />
            </div>
        </div>
        

        <!-- Subscription Table -->
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
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("name")}> Subscription Name </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer hidden sm:table-cell" on:click={() => sortData("description")}> Description </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer hidden sm:table-cell" on:click={() => sortData("price")}> Price (€) </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer hidden sm:table-cell" on:click={() => sortData("vat")}> VAT (%) </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer hidden sm:table-cell" on:click={() => sortData("start_date")}> Start Date </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer hidden sm:table-cell" on:click={() => sortData("end_date")}> End Date </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-neutral-900 divide-y divide-gray-700">
                    {#each $paginatedSubscriptions as subscription}
                        <tr>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-white">{subscription.name}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{subscription.description}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">€{new Intl.NumberFormat('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(subscription.price)}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{subscription.vat}%</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{subscription.start_date}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{subscription.end_date}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300">
                                <button class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition duration-300" on:click={() => openEditModal(subscription)}>Edit</button>
                                <button class="ml-2 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition duration-300" on:click={() => deleteSubscription(subscription)}>Delete</button>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div class="flex items-center justify-between mt-4">
            <button on:click={prevPage} disabled={$currentPage === 1} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50">Previous</button>
            <span class="text-white">Page {$currentPage} of {$totalPages}</span>
            <button on:click={nextPage} disabled={$currentPage === $totalPages} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50">Next</button>
        </div>
    </div>
</div>
