<script>
    // @ts-nocheck

    // Importing necessary functions and components
    import { onMount } from "svelte"; // onMount is used to run code after the component is mounted
    import UnSentInvoices from "../Components/UnSentInvoices.svelte"; // Importing component for unsent invoices
    import SentInvoices from "../Components/SentInvoices.svelte"; // Importing component for sent invoices
    import { authToken } from "../stores/auth"; // Importing authToken store for authorization

    // Declaring variables to hold invoices and loading state
    let allInvoices = []; // Array to store all invoices
    let Loading = true; // Boolean to track loading state

    // onMount lifecycle function to fetch invoice data after component mounts
    onMount(async () => {
        // Fetch request to the API to get invoice data
        const data = await fetch(`http://localhost/Linda/i-Subscribe/backend/public/api/invoices`, {
            method: "GET", // Using GET method to fetch data
            headers: {
                "Content-Type": "application/json", // Ensuring the response is in JSON format
                Authorization: `Bearer ${$authToken}`, // Using the auth token for authorization
            },
        });

        // Parse the JSON response to get the invoice data
        const response = await data.json();

        // Combine both unsent and sent invoices into a single array
        allInvoices = [...response.invoices, ...response.sent_invoices];

        console.log(allInvoices); // Log the combined invoices array for debugging purposes
        Loading = false; // Set loading to false after the data is fetched
    });
</script>

<main class="flex-1">
    {#if Loading} <!-- Display a loading spinner if data is being fetched -->
        <div class="w-full flex justify-center py-4">
            <div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full border-blue-600 border-t-transparent" role="status">
                <span class="sr-only">Loading...</span> <!-- Screen reader text for accessibility -->
            </div>
        </div>
    {:else} <!-- Once loading is complete, display the invoice components -->
        <UnSentInvoices bind:invoices={allInvoices} /> <!-- Bind allInvoices to UnSentInvoices component -->
        <SentInvoices bind:invoices={allInvoices} /> <!-- Bind allInvoices to SentInvoices component -->
    {/if}
</main>
