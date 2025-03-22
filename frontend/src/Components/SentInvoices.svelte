<script>
    // Importing necessary functions and stores
    import { onMount } from "svelte"; // onMount is used to run code when the component is mounted
    import { writable, derived } from "svelte/store"; // Importing writable and derived stores

    // Declaring input properties and reactive variables
    export let invoices = []; // Array to hold the invoices passed from the parent component
    let searchTerm = writable(""); // Writable store for the search term entered by the user
    let per_page = 5; // Number of invoices to display per page
    let sortOrder = writable("asc"); // Writable store for the sorting order (ascending by default)
    let sortColumn = writable(null); // Writable store for the column being sorted
    let selectedInvoices = 0; // Variable to track selected invoices (adjust as needed)
    let currentPage = writable(1); // Writable store to track the current page number

    // Create a writable store for filtered invoices
    let filteredInvoices = writable([]);

    // onMount lifecycle function to initialize filteredInvoices with all invoices
    onMount(() => {
        filteredInvoices.set(invoices); // Set filteredInvoices to the initial invoices array
    });

    // Create a derived store for filtered and sorted invoices
    const filteredAndSortedInvoices = derived([filteredInvoices, searchTerm, sortOrder, sortColumn], ([$filteredInvoices, $searchTerm, $sortOrder, $sortColumn]) => {
        const searchTermLower = $searchTerm.toLowerCase(); // Convert search term to lowercase for case-insensitive search
        const filtered = $filteredInvoices.filter((invoice) => {
            // Filter invoices based on the search term and selected invoice ID
            const idMatch = invoice.id.toString().toLowerCase().includes(searchTermLower);
            const customerMatch = invoice.customer && invoice.customer.name && invoice.customer.name.toLowerCase().includes(searchTermLower);
            return (idMatch || customerMatch) && (!selectedInvoices || invoice.id === selectedInvoices) && invoice.sent === 1;
        });

        // Sorting logic for filtered invoices
        const sorted = filtered.sort((a, b) => {
            if (!$sortColumn) return 0; // No sorting if no column is selected
            const dir = $sortOrder === "asc" ? 1 : -1; // Determine sort direction based on sortOrder
            let aValue, bValue;

            if ($sortColumn === "id") {
                // For "id" column, sort as integers
                aValue = parseInt(a.id, 10);
                bValue = parseInt(b.id, 10);
            } else if ($sortColumn === "customer") {
                // For "customer" column, sort by customer name
                aValue = a.customer && a.customer.name ? a.customer.name.toLowerCase() : "";
                bValue = b.customer && b.customer.name ? b.customer.name.toLowerCase() : "";
            } else {
                // For other columns, sort as strings
                aValue = a[$sortColumn] ? a[$sortColumn].toString().toLowerCase() : "";
                bValue = b[$sortColumn] ? b[$sortColumn].toString().toLowerCase() : "";
            }

            // Compare values based on the selected direction
            return aValue > bValue ? dir : aValue < bValue ? -dir : 0;
        });

        return sorted; // Return the sorted invoices
    });

    // Create a derived store for paginated invoices
    const paginatedInvoices = derived([filteredAndSortedInvoices, currentPage], ([$filteredAndSortedInvoices, $currentPage]) => {
        const startIndex = ($currentPage - 1) * per_page; // Calculate starting index for the current page
        return Array.isArray($filteredAndSortedInvoices) ? $filteredAndSortedInvoices.slice(startIndex, startIndex + per_page) : [];
    });

    // Create a derived store for the total number of pages
    const pages = derived(filteredAndSortedInvoices, ($filteredAndSortedInvoices) => {
        return Math.ceil($filteredAndSortedInvoices.length / per_page) || 1; // Calculate total pages
    });

    // Function to handle the search input change and filter invoices
    const search = (e) => {
        const term = e.target.value; // Get the search term
        searchTerm.set(term); // Update the searchTerm store

        if (term === "") {
            // If search term is empty, reset filteredInvoices to all invoices
            filteredInvoices.set(invoices);
        } else {
            // Filter invoices based on the search term
            filteredInvoices.set(
                invoices.filter((inv) => {
                    return inv.id.toString().includes(term) || inv.customer_id.toString().includes(term);
                })
            );
        }

        // Reset currentPage to 1 when search changes to avoid out-of-bounds pages
        currentPage.set(1);
    };

    // Function to handle sorting when clicking on a column header
    function sortData(column) {
        sortColumn.set(column); // Set the selected column for sorting
        sortOrder.update((current) => (current === "asc" ? "desc" : "asc")); // Toggle sort order (ascending/descending)
    }
</script>

<div class="w-full mx-auto max-w-7xl mt-10">
    <div class="bg-neutral-900 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-white mb-6">Sent Invoices</h1>

        <!-- Search bar for filtering invoices -->
        <div class="flex justify-center mb-8">
            <div class="w-full md:w-96">
                <input id="search" type="text" bind:value={$searchTerm} placeholder="Search by invoice number, customer name, or subscription..." class="w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-white placeholder-gray-500 focus:outline-none focus:ring focus:border-indigo-500" />
            </div>
        </div>

        <!-- Table displaying invoices -->
        <div class="overflow-x-auto w-full">
            <table class="min-w-full w-full divide-y divide-gray-700">
                <thead class="bg-zinc-800">
                    <tr>
                        <!-- Table headers for sorting invoices -->
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("id")}>Invoice Number</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("customer")}>Customer</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider hidden sm:table-cell">Subscription(s)</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider hidden sm:table-cell">Invoice Date</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider hidden sm:table-cell">Due Date</th>
                    </tr>
                </thead>
                <tbody class="bg-neutral-900 divide-y divide-gray-700">
                    {#each $paginatedInvoices as inv} <!-- Loop through paginated invoices and display them -->
                        <tr>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300">{inv?.id}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300">
                                <a href={"http://localhost:8000/invoice/" + inv.id + "/pdf"} target="_blank" class="underline hover:text-blue-400">
                                    {inv.customer.name || "No customer"}
                                </a>
                            </td>

                            <td class="px-4 py-4 text-left whitespace-nowrap text-left hidden sm:table-cell">
                                {#if inv.sent} <!-- Check if the invoice is sent -->
                                    {#if inv.subscriptions_snapshot} <!-- Display subscriptions snapshot if available -->
                                        {#each JSON.parse(inv.subscriptions_snapshot) as subscription}
                                            <span class="inline-block bg-indigo-800 text-indigo-200 px-2 py-1 rounded-full text-xs font-medium mr-1">
                                                {subscription.name}
                                            </span>
                                        {/each}
                                    {:else}
                                        <span>No snapshot available</span>
                                    {/if}
                                {:else}
                                    {#each inv.customer.subscriptions as subscription} <!-- Display customer subscriptions -->
                                        <span class="inline-block bg-indigo-800 text-indigo-200 px-2 py-1 rounded-full text-xs font-medium mr-1">
                                            {subscription.name}
                                        </span>
                                    {/each}
                                {/if}
                            </td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv?.startdate}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv?.duedate}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- Pagination controls -->
        <div class="flex items-center justify-between mt-4">
            <button on:click={() => currentPage.update((n) => Math.max(n - 1, 1))} disabled={$currentPage === 1} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50">Previous</button>
            <span class="text-white">Page {$currentPage} of {$pages}</span>
            <button on:click={() => currentPage.update((n) => Math.min(n + 1, $pages))} disabled={$currentPage === $pages} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50">Next</button>
        </div>
    </div>
</div>
