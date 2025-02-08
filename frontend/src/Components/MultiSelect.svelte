<script>
    import { onMount, onDestroy } from "svelte";
    import { writable } from "svelte/store";
    import MultiSelect from "../Components/MultiSelect.svelte";

    // Exports the options and selection so they can be bound from the parent component
    export let options = [];

    // The current selection, an array of the selected options' ids
    export let selected = [];

    // Placeholder text when nothing is selected
    const placeholder = "Select multiple options...";

    // State to track if the dropdown is open or not
    let isOpen = false;

    // Toggles the dropdown open/close
    function toggleDropdown() {
        isOpen = !isOpen;
    }

    // Toggles an option on or off (we use option.id instead of option.value)
    function toggleOption(option) {
        if (selected.includes(option.id)) {
            selected = selected.filter((item) => item !== option.id);
        } else {
            selected = [...selected, option.id];
        }
    }

    // Closes the dropdown if a click occurs outside the dropdown
    function handleClickOutside(event) {
        const dropdown = document.querySelector(".custom-select");
        if (dropdown && !dropdown.contains(event.target)) {
            isOpen = false;
        }
    }

    // Registers and unregisters the click event listener when the component mounts and unmounts
    onMount(() => {
        document.addEventListener("click", handleClickOutside);
    });
    onDestroy(() => {
        document.removeEventListener("click", handleClickOutside);
    });

    // Calculates a string with the names of the selected options for display
    $: displaySelected = selected
        .map((id) => options.find((o) => o.id === id)?.name)
        .filter(Boolean)
        .join(", ");
</script>

<div class="custom-select relative w-full">
    <!-- Toggle button -->
    <button
        type="button"
        on:click={toggleDropdown}
        aria-expanded={isOpen}
        class="hs-select-disabled:pointer-events-none hs-select-disabled:opacity-50 relative py-3 ps-4 pe-9 flex gap-x-2 text-nowrap w-full cursor-pointer bg-white border border-gray-200 rounded-lg text-start text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-black"
    >
        {#if selected.length === 0}
            <span class="text-gray-400">{placeholder}</span>
        {:else}
            <span>{displaySelected}</span>
        {/if}

        <!-- Dropdown arrow markup -->
        <div class="absolute top-1/2 right-3 -translate-y-1/2">
            <svg
                class="shrink-0 size-3.5 text-gray-500"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                {#if isOpen}
                    <path d="M7 15l5-5 5 5" />
                    <!-- Up arrow when the dropdown is open -->
                {:else}
                    <path d="M7 9l5 5 5-5" />
                    <!-- Down arrow when the dropdown is closed -->
                {/if}
            </svg>
        </div>
    </button>

    <!-- Dropdown options -->
    {#if isOpen}
        <div
            class="mt-2 z-50 w-full max-h-72 p-1 space-y-0.5 bg-white border border-gray-200 rounded-lg overflow-hidden overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300"
        >
            {#each options as option}
                <button
                    type="button"
                    class="py-2 px-4 w-full text-sm text-gray-800 cursor-pointer hover:bg-gray-100 rounded-lg focus:outline-none focus:bg-gray-100 flex justify-between items-center"
                    on:click={() => toggleOption(option)}
                    on:keydown={(e) =>
                        e.key === "Enter" && toggleOption(option)}
                    role="option"
                    aria-selected={selected.includes(option.id)}
                >
                    <span>{option.name}</span>
                    <!-- Display option name -->
                    {#if selected.includes(option.id) && option.id !== ""}
                        <span class="hs-selected:block">
                            <svg
                                class="shrink-0 size-3.5 text-black"
                                xmlns="http://www.w3.org/2000/svg"
                                width="24"
                                height="24"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <polyline points="20 6 9 17 4 12" />
                                <!-- Checkmark when the option is selected -->
                            </svg>
                        </span>
                    {/if}
                </button>
            {/each}
        </div>
    {/if}
</div>
