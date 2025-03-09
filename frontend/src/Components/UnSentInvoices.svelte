<script>
    import { onMount } from "svelte";
    import { authToken } from "../stores/auth";
    import Invoice from "../Pages/pdf/invoice.svelte";
    let showInvoices = [];
    // Afgeleide store voor filtering en sortering van de facturen

    import { writable, derived } from "svelte/store";

    let invoices = writable([]);
    let searchTerm = writable("");
    let sortOrder = writable("asc");
    let sortColumn = writable(null);
    let currentPage = writable(1);
    const per_page = 10;
    let selectedInvoices = 0; // Aanpassen afhankelijk van hoe je dit wilt gebruiken
    let loading = writable(false); // Store for loading state

    const generateInvoices = async () => {
        loading.set(true); // Zet de laadtoestand aan
        try {
            const res = await fetch("http://localhost:8000/api/generate-invoices", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${$authToken}`,
                },
                body: JSON.stringify({ invoicedate: new Date().toISOString() }), // Gebruik de huidige datum
            });

            const data = await res.json();
            if (res.ok) {
                console.log(`Invoices generated successfully! ${data.invoices_generated} invoices created.`);
            } else {
                console.error(data.error || "Failed to generate invoices.");
            }
        } catch (error) {
            console.error("An error occurred while generating invoices.", error);
        } finally {
            loading.set(false); // Zet de laadtoestand uit
        }
    };

    onMount(() => {
        // Roep de generateInvoices functie aan zodra de pagina is geladen
        generateInvoices();
    });

    // Return the fully filtered and sorted list
    const filteredAndSortedInvoices = derived([invoices, searchTerm, sortOrder, sortColumn], ([$invoices, $searchTerm, $sortOrder, $sortColumn]) => {
        const searchTermLower = $searchTerm.toLowerCase();
        const filtered = $invoices.filter((invoice) => {
            const idMatch = invoice.id.toString().toLowerCase().includes(searchTermLower);
            const customerMatch = invoice.customer && invoice.customer.name && invoice.customer.name.toLowerCase().includes(searchTermLower);
            return (idMatch || customerMatch) && (!selectedInvoices || invoice.id === selectedInvoices);
        });

        // Sorting logic remains the same
        const sorted = filtered.sort((a, b) => {
            if (!$sortColumn) return 0;
            const dir = $sortOrder === "asc" ? 1 : -1;
            let aValue, bValue;
            if ($sortColumn === "customer") {
                aValue = a.customer && a.customer.name ? a.customer.name.toLowerCase() : "";
                bValue = b.customer && b.customer.name ? b.customer.name.toLowerCase() : "";
            } else {
                aValue = a[$sortColumn] ? a[$sortColumn].toString().toLowerCase() : "";
                bValue = b[$sortColumn] ? b[$sortColumn].toString().toLowerCase() : "";
            }
            return aValue > bValue ? dir : aValue < bValue ? -dir : 0;
        });
        return sorted;
    });

    const pages = derived(filteredAndSortedInvoices, ($filteredAndSortedInvoices) => {
        return Math.ceil($filteredAndSortedInvoices.length / per_page) || 1;
    });

    // Ophalen van de facturen van de server
    async function fetchInvoices() {
        loading.set(true);
        const res = await fetch("http://localhost:8000/api/invoices", {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${$authToken}`,
            },
        });
        const data = await res.json();
        if (res.ok) {
            invoices.set(data.invoices);
        } else {
            console.error("Failed to fetch invoices", data);
        }
        loading.set(false);
    }

    onMount(() => {
        fetchInvoices();
    });

    // Sorteerfunctie die wordt aangeroepen bij het klikken op kolomkoppen
    function sortData(column) {
        console.log("Sorting by:", column); // Add this line to check the column update
        sortColumn.set(column);
        sortOrder.update((current) => (current === "asc" ? "desc" : "asc"));
    }

    const paginatedInvoices = derived([filteredAndSortedInvoices, currentPage], ([$filteredAndSortedInvoices, $currentPage]) => {
        const startIndex = ($currentPage - 1) * per_page;
        return $filteredAndSortedInvoices.slice(startIndex, startIndex + per_page);
    });

    // Functie om de facturen als verzonden te markeren
    const makeDefinite = async () => {
        const unsentInvoices = $filteredAndSortedInvoices.filter((invoice) => invoice.sent === 0);
        if (unsentInvoices.length === 0) {
            alert("No unsent invoices to mark as sent.");
            return;
        }

        const invoiceIds = unsentInvoices.map((invoice) => invoice.id);
        const res = await fetch("http://localhost:8000/api/update-invoices", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${$authToken}`,
            },
            body: JSON.stringify({ invoice_ids: invoiceIds }),
        });

        const response = await res.json();
        if (res.ok) {
            invoices.update((current) => current.map((invoice) => (invoiceIds.includes(invoice.id) ? { ...invoice, sent: 1 } : invoice)));
            alert("All unsent invoices marked as sent.");
        } else {
            alert(response.error || "Failed to update invoices");
        }
    };
</script>

<button on:click={makeDefinite} class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10 mb-8">Make Definite</button>

<div class="w-full mx-auto max-w-7xl">
    <div class="bg-neutral-900 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-white mb-6">Concept Invoices</h1>
        <div class="flex justify-center mb-8">
            <div class="w-full md:w-96">
                <input id="search" type="text" bind:value={$searchTerm} placeholder="Search by invoice number, customer name, or subscription..." class="w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-white placeholder-gray-500 focus:outline-none focus:ring focus:border-indigo-500" />
            </div>
        </div>
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
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv.id}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv.customer.name || "No customer"}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-left">
                                {#each inv.customer.subscriptions as subscription}
                                    <span class="inline-block bg-indigo-800 text-indigo-200 px-2 py-1 rounded-full text-xs font-medium mr-1">{subscription.name}</span>
                                {/each}
                            </td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv.startdate}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv.duedate}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between mt-4">
            <button on:click={() => currentPage.update((n) => Math.max(n - 1, 1))} disabled={$currentPage === 1} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50">Previous</button>
            <span class="text-white">Page {$currentPage} of {$pages}</span>
            <button on:click={() => currentPage.update((n) => Math.min(n + 1, $pages))} disabled={$currentPage === $pages} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50">Next</button>
        </div>
    </div>
</div>
