// Import the `mount` function from the 'svelte' library
import { mount } from 'svelte';
// Import the main CSS file for global styling across the application
import './app.css';
// Import the root App component from its file
import App from './App.svelte';

// Initialize the Svelte application by mounting the App component
const app = mount(App, {
  // Specify the DOM element that will serve as the mount point for the Svelte application
  target: document.getElementById('app'),
});

// Export the instantiated app for potential use in other parts of the application
export default app;
