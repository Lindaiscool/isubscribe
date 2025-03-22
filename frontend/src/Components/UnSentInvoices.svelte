<script>
    // Import necessary Svelte functions, components, stores, and libraries
    import { onMount } from "svelte"; // onMount is used to run code after the component is mounted
    import { authToken } from "../stores/auth"; // Import the authToken store for managing authentication
    import Invoice from "../Pages/pdf/invoice.svelte"; // Import the Invoice component for generating PDFs
    import { writable } from "svelte/store"; // Import writable for creating reactive variables
    import Swal from "sweetalert2"; // Import SweetAlert2 for custom pop-up dialogs

    // Declare reactive variables for managing invoice data and UI states
    let showInvoices = []; // Array to hold the invoices that are displayed
    let invoices = []; // Array to store all fetched invoices
    let filteredInvoices = []; // Array for filtering invoices based on criteria
    let searchTerm = writable(""); // Writable store to hold the search term entered by the user
    let sortOrder = writable("asc"); // Writable store to determine the sort order (ascending by default)
    let sortColumn = writable(null); // Writable store for the selected column to sort by
    let currentPage = writable(1); // Writable store to track the current page in pagination
    const per_page = 10; // Number of invoices to display per page
    let selectedInvoices = 0; // Variable to track selected invoices
    let loading = writable(false); // Writable store to manage loading state
    import toastr from "toastr"; // Import toastr for displaying notifications

    // onMount lifecycle function to load data when the component mounts
    onMount(() => {
        filterInvoices(); // Filter invoices based on the selected criteria
        generateInvoices(); // Generate invoices when the page loads
        fetchInvoices(); // Fetch existing invoices from the server

        // Display success or error messages after a page reload
        const successMessage = localStorage.getItem("invoiceSuccessMessage");
        const errorMessage = localStorage.getItem("invoiceErrorMessage");

        if (successMessage) {
            toastr.success(successMessage); // Show success notification
            localStorage.removeItem("invoiceSuccessMessage");
        }

        if (errorMessage) {
            toastr.error(errorMessage); // Show error notification
            localStorage.removeItem("invoiceErrorMessage");
        }
    });

    // Function to generate new invoices
    const generateInvoices = async () => {
        loading.set(true); // Set the loading state to true while generating invoices
        try {
            const res = await fetch("http://localhost:8000/api/generate-invoices", {
                method: "POST", // Make a POST request to generate invoices
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${$authToken}`, // Use the authentication token for authorization
                },
                body: JSON.stringify({ invoicedate: new Date().toISOString() }), // Send the current date as the invoice date
            });
            const data = await res.json();
            if (res.ok) {
                fetchInvoices(); // Re-fetch invoices after generation
            } else {
                toastr.error(data.message || "Failed to generate invoices", "Error"); // Show an error message if generation fails
            }
        } catch (error) {
            console.error("An error occurred while generating invoices.", error);
            toastr.error("An error occurred while generating invoices", "Error"); // Show a generic error message
        } finally {
            loading.set(false); // Set the loading state to false once the process is complete
        }
    };

    // Function to filter invoices based on their sent status
    const filterInvoices = () => {
        filteredInvoices = invoices.filter((i) => {
            if (i.sent === 0) {
                return i; // Return only unsent invoices
            }
        });
    };

    // Reactive statement to filter and sort invoices based on the search term, selected invoices, and sorting criteria
    $: filteredAndSortedInvoices = (() => {
        const st = $searchTerm.toLowerCase(); // Convert the search term to lowercase for case-insensitive search
        const filtered = filteredInvoices.filter((invoice) => {
            // Filter invoices based on the search term and selected invoice ID
            const idMatch = invoice.id.toString().toLowerCase().includes(st);
            const customerMatch = invoice.customer && invoice.customer.name && invoice.customer.name.toLowerCase().includes(st);
            return (idMatch || customerMatch) && (!selectedInvoices || invoice.id === selectedInvoices);
        });

        // If a sort column is specified, sort the filtered invoices
        if ($sortColumn) {
            return filtered.sort((a, b) => {
                const dir = $sortOrder === "asc" ? 1 : -1; // Set sorting direction based on the order
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

    // Calculate the total number of pages based on the filtered and sorted invoices
    $: pages = Math.ceil(filteredAndSortedInvoices.length / per_page) || 1;

    // Reactive statement to calculate which invoices to display on the current page
    $: paginatedInvoices = (() => {
        const startIndex = ($currentPage - 1) * per_page; // Calculate the starting index for pagination
        return filteredAndSortedInvoices.slice(startIndex, startIndex + per_page); // Slice the invoices to fit the current page
    })();

    // Function to fetch invoices from the server
    async function fetchInvoices() {
        loading.set(true); // Set the loading state to true while fetching data
        const res = await fetch("http://localhost:8000/api/invoices", {
            method: "GET", // Use GET method to retrieve invoices
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${$authToken}`, // Use the authentication token
            },
        });
        const data = await res.json();
        if (res.ok) {
            invoices = data.invoices; // Update the invoices array with the fetched data
            filterInvoices(); // Apply filtering to the fetched invoices
        } else {
            console.error("Failed to fetch invoices", data); // Log error if fetching fails
        }
        loading.set(false); // Set the loading state to false once fetching is complete
    }

    // Function to handle sorting invoices when a table header is clicked
    function sortData(column) {
        sortColumn.set(column); // Set the selected column for sorting
        sortOrder.update((current) => (current === "asc" ? "desc" : "asc")); // Toggle between ascending and descending order
    }

    // Function to mark invoices as "sent"
    const makeDefinite = async () => {
        const unsentInvoices = filteredAndSortedInvoices.filter((invoice) => invoice.sent === 0);
        if (unsentInvoices.length === 0) {
            toastr.warning("No unsent invoices to mark as sent.", "Warning"); // Show a warning if there are no unsent invoices
            return;
        }
        const invoiceIds = unsentInvoices.map((invoice) => invoice.id);

        // Show a confirmation dialog using SweetAlert2
        const result = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to mark these invoices as sent?",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "Cancel",
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            // Send the request to mark the invoices as "sent"
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
                localStorage.setItem("invoiceSuccessMessage", response.message); // Save the success message in localStorage
                Swal.fire("Success", response.message, "success"); // Show success pop-up
                location.reload(); // Reload the page to reflect changes
            } else {
                Swal.fire("Error", response.message, "error"); // Show an error message if the operation fails
            }
        } else {
            Swal.fire("Cancelled", "The invoices were not marked as sent.", "info"); // Show cancellation message
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
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider  hidden sm:table-cell"> Subscription(s) </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider  hidden sm:table-cell"> Invoice Date </th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-300 uppercase tracking-wider  hidden sm:table-cell"> Due Date </th>
                    </tr>
                </thead>
                <tbody class="bg-neutral-900 divide-y divide-gray-700">
                    {#each paginatedInvoices as inv} <!-- Display the invoices in rows -->
                        <tr>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300">{inv.id}</td>
                            <td class="px-4 py-4 text-left whitespace-nowrap text-base text-gray-300">
                                <a href={"http://localhost:8000/invoice/" + inv.id + "/pdf"} target="_blank" class="underline hover:text-blue-400">
                                    {inv.customer.name || "No customer"}
                                </a>
                            </td>
                            <td class="px-4 py-4 text-left whitespace-nowrap hidden sm:table-cell">
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
