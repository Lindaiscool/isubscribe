<script>
    import { onMount } from "svelte";
    import { writable, derived, get } from "svelte/store";
    import { authToken } from "../stores/auth.js"; // Importeer de authToken store
    import toastr from "toastr";

    let isGenerating = writable(false);
    let isGenerated = writable(false); // Flag voor bijwerken
    let invoices = writable([]);
    let searchTerm = writable("");
    let loading = writable(false);
    let selectedSubscription = writable("");
    let subscriptions = writable([]);
    let currentPage = writable(1);
    const itemsPerPage = 10;

    // Herstel de status van 'isGenerating' en 'isGenerated' bij het laden van de pagina
    onMount(() => {
        const storedSent = localStorage.getItem("isGenerated");

        if (storedSent === "true") {
            isGenerated.set(true);
        }
        fetchData();
    });

    // Functie om facturen en abonnementen opnieuw op te halen
    const fetchData = async () => {
        try {
            const subResponse = await fetch("http://localhost:8000/api/subscriptions", {
                headers: {
                    "Content-Type": "application/json",
                    Authorization: "Bearer " + $authToken,
                    Accept: "application/json",
                },
            });
            if (subResponse.ok) {
                const subData = await subResponse.json();
                subscriptions.set(subData);
            } else {
                throw new Error(`API fout: ${subResponse.statusText}`);
            }

            const invResponse = await fetch("http://localhost:8000/api/invoices", {
                headers: {
                    "Content-Type": "application/json",
                    Authorization: "Bearer " + $authToken,
                    Accept: "application/json",
                },
            });
            if (invResponse.ok) {
                const invData = await invResponse.json();
                invoices.set(invData);
            } else {
                throw new Error(`API fout: ${invResponse.statusText}`);
            }
        } catch (error) {
            console.error("Fout bij het ophalen van data:", error);
        } finally {
            loading.set(false);
        }
    };

    // Functie om nieuwe facturen te genereren
    const generateAllInvoices = async () => {
    isGenerating.set(true);
    localStorage.setItem("isGenerating", "true"); // Sla de status op in localStorage
    try {
        const response = await fetch("http://localhost:8000/api/generate-invoices", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + $authToken, // Zorg ervoor dat je het juiste token gebruikt
            },
        });

        if (!response.ok) {
            throw new Error(`API fout: ${response.statusText}`);
        }

        const responseData = await response.json();

        // Als facturen al zijn gegenereerd, toon dan een waarschuwing, anders een succesmelding
        if (responseData.message && responseData.message.includes("Facturen zijn al gegenereerd")) {
            toastr.warning(responseData.message); // Toont een waarschuwing als facturen al bestaan
        } else {
            toastr.success(responseData.message); // Successmelding als facturen succesvol zijn gegenereerd
        }

        isGenerated.set(true);
        localStorage.setItem("isGenerated", "true"); // Markeer als verzonden
    } catch (error) {
        console.error("Er is een fout opgetreden bij het genereren van de facturen:", error);
        toastr.error("Er is een fout opgetreden bij het genereren van de facturen."); // Foutmelding
    } finally {
        isGenerating.set(false);
        localStorage.removeItem("isGenerating"); // Verwijder de status na afronden
    }
};




    // Functie om facturen bij te werken
    // Functie om facturen bij te werken
    const updateAllInvoices = async () => {
        isGenerating.set(true);
        localStorage.setItem("isGenerating", "true");
        try {
            // Markeer alle niet-verzonden facturen als verzonden
            const unsentInvoices = $invoices.filter((invoice) => !invoice.sent);

            if (unsentInvoices.length > 0) {
                // Haal de facturen die niet verzonden zijn en update de status naar verzonden
                const response = await fetch("http://localhost:8000/api/send-invoices", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: "Bearer " + $authToken,
                    },
                    body: JSON.stringify({
                        invoice_ids: unsentInvoices.map((invoice) => invoice.id),
                    }),
                });

                if (!response.ok) {
                    throw new Error(`Fout bij het verzenden van facturen: ${response.statusText}`);
                }

                // Facturen zijn nu verzonden
                alert("De facturen zijn succesvol bijgewerkt!");
                isGenerated.set(true); // Markeer dat de facturen zijn bijgewerkt
                localStorage.setItem("isGenerated", "true");
            } else {
                alert("Er zijn geen facturen om bij te werken.");
            }
        } catch (error) {
            console.error("Er is een fout opgetreden bij het bijwerken van de facturen:", error);
            alert("Er is een fout opgetreden bij het bijwerken van de facturen.");
        } finally {
            isGenerating.set(false);
            localStorage.removeItem("isGenerating");
        }
    };

    // Functie om te bepalen of er ongewenste facturen zijn die nog niet verzonden zijn
    const canUpdate = derived(invoices, ($invoices) => {
        // Controleer of er al facturen zijn die niet verzonden zijn
        return $invoices.some((invoice) => !invoice.sent); // Retourneer true als er niet-verzonden facturen zijn
    });

    // Filtered and sorted invoices based on search term and selected subscription
    const filteredAndSortedInvoices = derived([invoices, searchTerm, selectedSubscription], ([$invoices, $searchTerm, $selectedSubscription]) => {
        return $invoices
            .filter((invoice) => {
                let searchContent = `${invoice.invoicenumber} ${invoice.customer.name} ${(invoice.subscriptions?.map((sub) => sub.name) || []).join(" ")}`.toLowerCase();
                const matchesSearch = searchContent.includes($searchTerm.toLowerCase());
                const matchesSubscription = $selectedSubscription ? invoice.customer.subscriptions.some((sub) => sub.id === $selectedSubscription) : true;
                return matchesSearch && matchesSubscription;
            })
            .sort((a, b) => a.invoicenumber.localeCompare(b.invoicenumber));
    });

    // Derived store for paginated invoices
    const paginatedInvoices = derived([filteredAndSortedInvoices, currentPage], ([$invoices, $currentPage]) => {
        const startIndex = ($currentPage - 1) * itemsPerPage;
        return $invoices.slice(startIndex, startIndex + itemsPerPage);
    });

    // Calculate total pages based on filtered and sorted invoices
    const totalPages = derived(filteredAndSortedInvoices, ($filteredAndSortedInvoices) => {
        return Math.ceil($filteredAndSortedInvoices.length / itemsPerPage);
    });

    // Functions to navigate between pages
    function prevPage() {
        currentPage.update((n) => Math.max(n - 1, 1));
    }

    function nextPage() {
        const t = get(totalPages);
        currentPage.update((n) => Math.min(n + 1, t));
    }
</script>

<div class="mb-4">
    <!-- Genereer Alle Facturen Knop -->
    {#if $isGenerating}
        <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10 mb-4" on:click={generateAllInvoices} disabled={$isGenerating || $isGenerated}>Generating...</button>
    {:else if $isGenerated && !$canUpdate}
        <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10 mb-4" on:click={generateAllInvoices} disabled={$isGenerating}>Genereer Alle Facturen</button>
    {:else}
        <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10 mb-4" on:click={updateAllInvoices} disabled={$isGenerating || !$canUpdate}>Update</button>
    {/if}
</div>

<div class="w-full mx-auto max-w-7xl">
    <div class="bg-neutral-900 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-white mb-6">Customer Management</h1>

        <!-- Search and subscription filter controls -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <input type="text" bind:value={$searchTerm} placeholder="Search by customer name or subscription..." class="w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-white placeholder-gray-500 focus:outline-none focus:ring focus:border-indigo-500" />
            </div>
            <select id="subscriptionFilter" bind:value={$selectedSubscription} class="block w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-gray-500 focus:outline-none focus:ring focus:border-indigo-500">
                <option value="" class="text-white">All Subscriptions</option>
                {#each $subscriptions as subscription}
                    <option value={subscription.id} class="text-white">{subscription.name}</option>
                {/each}
            </select>
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
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">invoicenumber</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">subscription(s)</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">invoicedate</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">duedate</th>
                    </tr>
                </thead>
                <tbody class="bg-neutral-900 divide-y divide-gray-700">
                    {#each $paginatedInvoices as invoice}
                        <tr>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-white">{invoice.invoicenumber}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-gray-300">{invoice.customer.name}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-gray-300">
                                {#each invoice.customer.subscriptions as subscription}
                                    <span class="inline-block bg-indigo-800 text-indigo-200 px-2 py-1 rounded-full text-xs font-medium mr-1">{subscription.name}</span>
                                {/each}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-gray-300">{new Date(invoice.invoicedate).toLocaleDateString()}</td>
                            <td class="px-4 py-4 whitespace-nowrap text-left text-gray-300">{new Date(invoice.duedate).toLocaleDateString()}</td>
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
