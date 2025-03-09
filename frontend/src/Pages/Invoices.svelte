<script>
    // @ts-nocheck

    import { onMount } from "svelte";
    import UnSentInvoices from "../Components/UnSentInvoices.svelte";
    import SentInvoices from "../Components/SentInvoices.svelte";
    import { authToken } from "../stores/auth";

    let allInvoices = [];
    let Loading = true;
    onMount(async () => {
        const data = await fetch(`http://localhost:8000/api/invoices`, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${$authToken}`,
            },
        });
        const response = await data.json();

        // Voeg de invoices samen in een enkele array
        allInvoices = [...response.invoices, ...response.sent_invoices];

        console.log(allInvoices); // Controleer de inhoud van allInvoices
        Loading = false;
    });
</script>

<main class="flex-1">
    {#if Loading}
        <div class="w-full flex justify-center py-4">
            <div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full border-blue-600 border-t-transparent" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    {:else}
        <UnSentInvoices invoices={allInvoices.filter((invoice) => invoice.sent === 0)} />
        <SentInvoices invoices={allInvoices.filter((invoice) => invoice.sent === 1)} />
    {/if}
</main>
