<script>
    export let invoiceData;

    function formatCurrency(value) {
        return new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(value);
    }
</script>


<div class="container">
    <h2>Factuur</h2>
    <p>Factuurnummer: {$invoiceData.invoice_number}</p>
    <p>Factuurdatum: {$invoiceData.invoice_date}</p>
    <p>Vervaldatum: {$invoiceData.expiration_date}</p>

    <div>
        <h3>Klantinformatie</h3>
        <p>{$invoiceData.customer.customer_name}</p>
        <p>{$invoiceData.customer.address}</p>
        <p>{$invoiceData.customer.postcode} {$invoiceData.customer.city}</p>
        <p>{$invoiceData.customer.country}</p>
    </div>

    <h3>Abonnementen</h3>
    <table>
        <thead>
            <tr>
                <th>Abonnement</th>
                <th>Omschrijving</th>
                <th>Prijs (excl. BTW)</th>
                <th>BTW (%)</th>
            </tr>
        </thead>
        <tbody>
            {#each invoiceData.subscriptions as subscription}
                <tr>
                    <td>{subscription.name}</td>
                    <td>{subscription.description}</td>
                    <td>€ {subscription.price.toFixed(2)}</td>
                    <td>{subscription.vat}%</td>
                </tr>
            {/each}
        </tbody>
    </table>

    <div class="totals">
        <p>Totaal excl. BTW: € {$invoiceData.total_excl_vat.toFixed(2)}</p>
        {#each Object.entries(invoiceData.vat_amounts) as [rate, amount]}
            <p>BTW {rate}%: € {amount.toFixed(2)}</p>
        {/each}
        <p class="total-label"><strong>Totaal incl. BTW: € {$invoiceData.total_incl_vat.toFixed(2)}</strong></p>
    </div>
</div>

<style>
    .container {
        font-family: sans-serif;
    }







    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-bottom: 14rem;
    }

    th,
    td {
        border-bottom: 1px solid #ccc;
        padding: 4px;
        text-align: left;
    }

    th {
        border-bottom: 1px solid black;
    }

    .total-label {
        display: inline-block;
        position: relative;
        font-size: 13px;
    }

    .total-label::before,
    .total-label::after {
        content: "";
        position: absolute;
        bottom: -2px;
        width: 100%;
        border-bottom: 1px solid black;
    }

    .total-label::after {
        top: -2px;
        border-top: 1px solid black;
    }
</style>
