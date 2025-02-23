// src/stores.js
import { writable } from 'svelte/store';

export const isLoggedIn = writable(false);

export async function checkLogin() {
    try {
        const response = await fetch('/api/check-login', {
            credentials: 'include', // Belangrijk voor Sanctum om de CSRF cookie te gebruiken
        });
        if (response.ok) {
            const data = await response.json();
            isLoggedIn.set(data.authenticated);
        } else {
            isLoggedIn.set(false);
        }
    } catch (error) {
        console.error('Failed to check login status:', error);
        isLoggedIn.set(false);
    }
}
