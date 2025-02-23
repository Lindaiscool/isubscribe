    import { authToken } from "../stores/auth.js";
export async function fetchInvoices() {
    const response = await fetch('/api/invoices');
    return response.json();
}

const API_BASE_URL = "http://localhost:8000/api";


export async function generateInvoices() {
    if (!token) {
        console.error("Geen token gevonden! Gebruiker is niet ingelogd.");
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/generate-invoices`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error("API Error:", response.status, errorText);
            throw new Error(`Fout bij het genereren van facturen: ${errorText}`);
        }

        const data = await response.json();
        console.log("Facturen succesvol gegenereerd!", data);
        return data;
    } catch (error) {
        console.error("Fout bij API-aanroep:", error);
    }
}




export async function markInvoicesAsSent(invoiceIds) {
    const response = await fetch('/api/invoices/send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ invoice_ids: invoiceIds })
    });
    return response.json();
}
