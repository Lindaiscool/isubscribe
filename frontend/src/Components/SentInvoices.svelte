<script>
    import { onMount } from "svelte";
    import { writable, derived } from "svelte/store";

    export let invoices = [];
    let searchTerm = writable("");
    let per_page = 5;
    let sortOrder = writable("asc");
    let sortColumn = writable(null);
    let selectedInvoices = 0; // Adjust as needed
    let currentPage = writable(1);

    // Make filteredInvoices a writable store
    let filteredInvoices = writable([]);

    onMount(() => {
        // Ensure filteredInvoices is the same as invoices initially
        filteredInvoices.set(invoices);
    });

    // Filter and sort invoices
    const filteredAndSortedInvoices = derived(
        [filteredInvoices, searchTerm, sortOrder, sortColumn],
        ([$filteredInvoices, $searchTerm, $sortOrder, $sortColumn]) => {
            const searchTermLower = $searchTerm.toLowerCase();
            const filtered = $filteredInvoices.filter((invoice) => {
                const idMatch = invoice.id.toString().toLowerCase().includes(searchTermLower);
                const customerMatch = invoice.customer && invoice.customer.name && invoice.customer.name.toLowerCase().includes(searchTermLower);
                return (idMatch || customerMatch) && (!selectedInvoices || invoice.id === selectedInvoices);
            });

            // Sorting logic
            const sorted = filtered.sort((a, b) => {
    if (!$sortColumn) return 0;
    const dir = $sortOrder === "asc" ? 1 : -1;
    let aValue, bValue;

    if ($sortColumn === "id") {
        aValue = parseInt(a.id, 10); // Convert to integer for numeric comparison
        bValue = parseInt(b.id, 10); // Convert to integer for numeric comparison
    } else if ($sortColumn === "customer") {
        aValue = a.customer && a.customer.name ? a.customer.name.toLowerCase() : "";
        bValue = b.customer && b.customer.name ? b.customer.name.toLowerCase() : "";
    } else {
        aValue = a[$sortColumn] ? a[$sortColumn].toString().toLowerCase() : "";
        bValue = b[$sortColumn] ? b[$sortColumn].toString().toLowerCase() : "";
    }

    return aValue > bValue ? dir : aValue < bValue ? -dir : 0;
});

            return sorted;
        }
    );

    // Derived store for paginated invoices
    const paginatedInvoices = derived(
        [filteredAndSortedInvoices, currentPage],
        ([$filteredAndSortedInvoices, $currentPage]) => {
            const startIndex = ($currentPage - 1) * per_page;
            return Array.isArray($filteredAndSortedInvoices) ? $filteredAndSortedInvoices.slice(startIndex, startIndex + per_page) : [];
        }
    );

    // Derived store for page count
    const pages = derived(filteredAndSortedInvoices, ($filteredAndSortedInvoices) => {
        return Math.ceil($filteredAndSortedInvoices.length / per_page) || 1;
    });

    const search = (e) => {
        const term = e.target.value;
        searchTerm.set(term);

        if (term === "") {
            filteredInvoices.set(invoices);
        } else {
            filteredInvoices.set(invoices.filter((inv) => {
                return inv.id.toString().includes(term) || inv.customer_id.toString().includes(term);
            }));
        }

        // Reset currentPage to 1 when search changes to avoid out-of-bounds pages
        currentPage.set(1);
    };

    function sortData(column) {
        sortColumn.set(column);
        sortOrder.update((current) => (current === "asc" ? "desc" : "asc"));
    }
</script>

<div class="w-full mx-auto max-w-7xl mt-10">
    <div class="bg-neutral-900 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-white mb-6">sent invoices</h1>

        <!-- Search bar and filter controls -->
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
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("id")}>Invoice Number</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("customer")}>Customer</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Subscription(s)</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Invoice Date</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Due Date</th>
                    </tr>
                </thead>
                <tbody class="bg-neutral-900 divide-y divide-gray-700">
                    {#each $paginatedInvoices as inv}
                        <tr>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv?.id}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv?.customer?.name}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-left">
                                {#if inv.sent}
                                    {#if inv.subscriptions_snapshot}
                                        {#each JSON.parse(inv.subscriptions_snapshot) as subscription}
                                            <span class="inline-block bg-indigo-800 text-indigo-200 px-2 py-1 rounded-full text-xs font-medium mr-1">
                                                {subscription.name}
                                            </span>
                                        {/each}
                                    {:else}
                                        <span>No snapshot available</span>
                                    {/if}
                                {:else}
                                    {#each inv.customer.subscriptions as subscription}
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
