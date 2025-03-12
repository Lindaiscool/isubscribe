<script>
    // Importing necessary functions and components
    import { onMount } from "svelte"; // onMount is used to run code after the component is mounted
    import { authToken } from "../stores/auth"; // Importing authToken store for authorization
    import Invoice from "../Pages/pdf/invoice.svelte"; // Importing the Invoice component for PDF generation
    import { writable } from "svelte/store"; // Importing writable from Svelte for reactive variables

    // Declaring reactive variables
    let showInvoices = []; // Array to hold the invoices to be displayed
    let invoices = []; // Array to store all invoices
    let filteredInvoices = []; // Array to store filtered invoices based on certain criteria
    let searchTerm = writable(""); // Writable store to hold the search term
    let sortOrder = writable("asc"); // Writable store to define the sort order (ascending by default)
    let sortColumn = writable(null); // Writable store to define the current column for sorting
    let currentPage = writable(1); // Writable store to track the current page number
    const per_page = 10; // Number of invoices per page
    let selectedInvoices = 0; // Variable to track selected invoices
    let loading = writable(false); // Writable store to track loading state

    // Function to generate invoices
    const generateInvoices = async () => {
        loading.set(true); // Set loading state to true while generating invoices
        try {
            const res = await fetch("http://localhost:8000/api/generate-invoices", {
                method: "POST", // Sending a POST request to generate invoices
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${$authToken}`, // Using authorization token
                },
                body: JSON.stringify({ invoicedate: new Date().toISOString() }), // Sending invoice date
            });
            const data = await res.json();
            if (res.ok) {
                fetchInvoices(); // Fetch invoices after generation
            } else {
                console.error(data.error || "Failed to generate invoices.");
            }
        } catch (error) {
            console.error("An error occurred while generating invoices.", error);
        } finally {
            loading.set(false); // Set loading state to false after the process is complete
        }
    };

    // Function to filter invoices by "sent" status
    const filterInvoices = () => {
        filteredInvoices = invoices.filter((i) => {
            if (i.sent === 0) {
                return i; // Return only unsent invoices
            }
        });
    };

    // onMount lifecycle function to fetch data when the component mounts
    onMount(() => {
        filterInvoices(); // Filter invoices when the component mounts
        generateInvoices(); // Generate invoices
        fetchInvoices(); // Fetch existing invoices
    });

    // Reactive statement to filter and sort invoices based on search, selection, and sort criteria
    $: filteredAndSortedInvoices = (() => {
        const st = $searchTerm.toLowerCase(); // Convert search term to lowercase for case-insensitive search
        const filtered = filteredInvoices.filter((invoice) => {
            // Filter invoices based on search term and selected invoice ID
            const idMatch = invoice.id.toString().toLowerCase().includes(st);
            const customerMatch = invoice.customer && invoice.customer.name && invoice.customer.name.toLowerCase().includes(st);
            return (idMatch || customerMatch) && (!selectedInvoices || invoice.id === selectedInvoices);
        });

        // If a sort column is set, sort the filtered invoices
        if ($sortColumn) {
            return filtered.sort((a, b) => {
                const dir = $sortOrder === "asc" ? 1 : -1; // Ascending or descending sort direction
                let aValue, bValue;
                if ($sortColumn === "customer") {
                    aValue = a.customer && a.customer.name ? a.customer.name.toLowerCase() : "";
                    bValue = b.customer && b.customer.name ? b.customer.name.toLowerCase() : "";
                } else {
                    aValue = a[$sortColumn] ? a[$sortColumn].toString().toLowerCase() : "";
                    bValue = b[$sortColumn] ? b[$sortColumn].toString().toLowerCase() : "";
                }
                if (aValue > bValue) return dir;
                if (aValue < bValue) return -dir;
                return 0;
            });
        }
        return filtered; // Return the filtered invoices if no sorting is applied
    })();

    // Calculate the number of pages based on filtered and sorted invoices
    $: pages = Math.ceil(filteredAndSortedInvoices.length / per_page) || 1;

    // Reactive statement to calculate the invoices for the current page
    $: paginatedInvoices = (() => {
        const startIndex = ($currentPage - 1) * per_page; // Calculate the starting index for pagination
        return filteredAndSortedInvoices.slice(startIndex, startIndex + per_page); // Return invoices for the current page
    })();

    // Function to fetch invoices from the server
    async function fetchInvoices() {
        loading.set(true); // Set loading state to true while fetching data
        const res = await fetch("http://localhost:8000/api/invoices", {
            method: "GET", // Using GET method to fetch invoices
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${$authToken}`, // Using authorization token
            },
        });
        const data = await res.json();
        if (res.ok) {
            invoices = data.invoices; // Update invoices array with fetched data
            filterInvoices(); // Filter invoices after fetching
        } else {
            console.error("Failed to fetch invoices", data);
        }
        loading.set(false); // Set loading state to false after fetching is complete
    }

    // Function to handle sorting when clicking on table headers
    function sortData(column) {
        console.log("Sorting by:", column); // Log the column being sorted
        sortColumn.set(column); // Set the current column for sorting
        sortOrder.update((current) => (current === "asc" ? "desc" : "asc")); // Toggle sort order
    }

    // Function to mark selected invoices as "sent"
    const makeDefinite = async () => {
        const unsentInvoices = filteredAndSortedInvoices.filter((invoice) => invoice.sent === 0); // Get unsent invoices
        if (unsentInvoices.length === 0) {
            alert("No unsent invoices to mark as sent.");
            return;
        }
        const invoiceIds = unsentInvoices.map((invoice) => invoice.id); // Extract the IDs of unsent invoices
        const res = await fetch("http://localhost:8000/api/update-invoices", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${$authToken}`,
            },
            body: JSON.stringify({ invoice_ids: invoiceIds }), // Send the list of invoice IDs to be updated
        });
        const response = await res.json();
        if (res.ok) {
            alert("Invoices successfully updated!");
            fetchInvoices(); // Fetch updated invoices
            filterInvoices(); // Filter invoices after updating
            location.reload(); // Reload the page after update
        } else {
            alert(response.error || "Failed to update invoices");
        }
    };
</script>

<!-- Button to mark invoices as "sent" -->
<button on:click={makeDefinite} class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-700 transition duration-300 mt-10 mb-8"> Make Definite </button>

<div class="w-full mx-auto max-w-7xl">
    <div class="bg-neutral-900 shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-white mb-6">Concept Invoices</h1>
        <div class="flex justify-center mb-8">
            <div class="w-full md:w-96">
                <input id="search" type="text" bind:value={$searchTerm} placeholder="Search by invoice number, customer name, or subscription..." class="w-full px-4 py-2 border border-gray-700 rounded-md shadow-sm bg-zinc-800 text-white placeholder-gray-500 focus:outline-none focus:ring focus:border-indigo-500" />
            </div>
        </div>
        <div class="overflow-x-auto w-full">
            {#if $loading} <!-- Show loading spinner if data is being fetched -->
                <div class="w-full flex justify-center py-4">
                    <div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full border-blue-600 border-t-transparent" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            {/if}
            <table class="min-w-full w-full divide-y divide-gray-700">
                <thead class="bg-zinc-800">
                    <tr>
                        <!-- Table headers for sorting invoices -->
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("id")}> Invoice Number </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer" on:click={() => sortData("customer")}> Customer </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider"> Subscription(s) </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider"> Invoice Date </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider"> Due Date </th>
                    </tr>
                </thead>
                <tbody class="bg-neutral-900 divide-y divide-gray-700">
                    {#each paginatedInvoices as inv} <!-- Display the invoices in rows -->
                        <tr>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">{inv.id}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">
                                <a href={"http://localhost:8000/invoice/" + inv.id + "/pdf"} target="_blank" class="underline hover:text-blue-400">
                                    {inv.customer.name || "No customer"}
                                </a>
                            </td>
                            <td class="px-4 py-4 text-left whitespace-nowrap">
                                {#each inv.customer.subscriptions as subscription}
                                    <span class="inline-block bg-indigo-800 text-indigo-200 px-2 py-1 rounded-full text-xs font-medium mr-1">
                                        {subscription.name}
                                    </span>
                                {/each}
                            </td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">
                                {inv.startdate}
                            </td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300 hidden sm:table-cell">
                                {inv.duedate}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between mt-4">
            <button on:click={() => currentPage.update((n) => Math.max(n - 1, 1))} disabled={$currentPage === 1} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50"> Previous </button>
            <span class="text-white">Page {$currentPage} of {pages}</span> <!-- Show the current page number -->
            <button on:click={() => currentPage.update((n) => Math.min(n + 1, pages))} disabled={$currentPage === pages} class="px-3 py-1 bg-gray-800 text-white rounded disabled:opacity-50"> Next </button>
        </div>
    </div>
</div>
