<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        /* Global styles for the body */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f0f2f5;
            /* Set background color */
            margin: 0;
            /* Remove default margin */
            padding: 0;
            /* Remove default padding */
            color: #374151;
            /* Set text color */
        }

        /* Container for the invoice card */
        .container {
            max-width: 800px;
            /* Set max width for container */
            margin: 2rem auto;
            /* Center the container */
            padding: 1rem;
            /* Add padding inside container */
        }

        /* Card styling */
        .card {
            background-color: #fff;
            /* Set background color */
            border-radius: 1rem;
            /* Round the corners */
            overflow: hidden;
            /* Hide any overflow */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            /* Add shadow effect */
        }

        /* Header section styling */
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            /* Gradient background */
            padding: 2rem;
            /* Add padding inside header */
            text-align: center;
            /* Center the text */
        }

        /* Header title styling */
        .header h1 {
            margin: 0;
            /* Remove margin */
            font-size: 2.5rem;
            /* Set font size */
            font-weight: bold;
            /* Make text bold */
            color: #000000;
            /* Set text color */
        }

        /* Header subtitle styling */
        .header p {
            margin-top: 0.5rem;
            /* Add margin to top */
            font-size: 1.125rem;
            /* Set font size */
            color: #000000;
            /* Set text color */
        }

        /* Content section styling */
        .content {
            padding: 1.5rem 2rem;
            /* Add padding inside content */
        }

        /* Section styling */
        .section {
            margin-bottom: 1.5rem;
            /* Add bottom margin to sections */
        }

        /* Section title styling */
        .section h2 {
            font-size: 1.5rem;
            /* Set font size */
            font-weight: 500;
            /* Set font weight */
            margin-bottom: 0.75rem;
            /* Add bottom margin */
            color: #212225;
            /* Set text color */
            border-bottom: 2px solid #e5e7eb;
            /* Add border bottom */
            padding-bottom: 0.5rem;
            /* Add padding to bottom */
        }

        /* Customer info paragraph styling */
        .customer-info p {
            font-size: 1.125rem;
            /* Set font size */
            margin: 0.25rem 0;
            /* Add margin */
        }

        /* Subscription item styling */
        .subscription-item {
            display: flex;
            /* Use flexbox */
            justify-content: space-between;
            /* Space out items */
            align-items: center;
            /* Align items in center */
            background-color: #f9fafb;
            /* Set background color */
            padding: 1rem;
            /* Add padding */
            border-radius: 0.5rem;
            /* Round corners */
            margin-bottom: 0.75rem;
            /* Add bottom margin */
            transition: background-color 0.3s;
            /* Add transition for hover effect */
        }

        /* Hover effect for subscription item */
        .subscription-item:hover {
            background-color: #f3f4f6;
            /* Change background color on hover */
        }

        /* Subscription item text styling */
        .subscription-item span {
            font-size: 1rem;
            /* Set font size */
        }

        /* Total section styling */
        .totals {
            display: flex;
            /* Use flexbox */
            justify-content: space-between;
            /* Space out items */
            font-size: 1.125rem;
            /* Set font size */
            font-weight: 600;
            /* Set font weight */
            margin-bottom: 1rem;
            /* Add bottom margin */
        }

        /* Payment terms paragraph styling */
        .payment p {
            font-size: 0.875rem;
            /* Set font size */
            color: rgba(55, 65, 81, 0.7);
            /* Set text color with opacity */
            margin: 0.25rem 0;
            /* Add margin */
        }

        /* Footer section styling */
        .footer {
            background-color: #f0f2f5;
            /* Set background color */
            padding: 1rem 2rem;
            /* Add padding */
            text-align: center;
            /* Center the text */
            font-size: 0.875rem;
            /* Set font size */
            color: #6b7280;
            /* Set text color */
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <!-- Content Section -->
            <div class="content">
                <!-- Invoice Information Section -->
                <div class="section invoice-info">
                    <h2>Invoice Number #{{ $invoice->id }}</h2> <!-- Display Invoice Number -->
                    <p>period: {{ \Carbon\Carbon::parse($invoice->startdate)->format('d-m-Y') }} -
                        {{ \Carbon\Carbon::parse($invoice->duedate)->format('d-m-Y') }}</p>
                    <!-- Display Sent Date -->
                </div>

                <!-- Customer Information Section -->
                <div class="section customer-info">
                    <h2>Customer</h2>
                    <p><strong>Name:</strong> {{ $invoice->customer->name }}</p> <!-- Display Customer Name -->
                </div>

                <!-- Subscriptions Section -->
                <div class="section subscriptions">
                    <h2>Subscriptions</h2>
                    @php
                        $totalPriceInclVat = 0; // Initialize the total
                    @endphp

                    @if (isset($subscriptions) && count($subscriptions) > 0)
                        @foreach ($subscriptions as $subscription)
                            @php
                                $priceWithVat = $subscription->price;
                                $priceNoVat = $priceWithVat / (1 + $subscription->vat / 100);
                                $vatAmount = $priceWithVat - $priceNoVat;
                                $basePrice = $priceNoVat;
                                $totalPriceInclVat += $subscription->price;
                            @endphp
                            <div class="subscription-item">
                                <span>{{ $subscription->name }}</span>
                                <span>Base Price: €{{ number_format($basePrice, 2) }}</span>
                                <span>VAT ({{ $subscription->vat }}%): €{{ number_format($vatAmount, 2) }}</span>
                                <span>Total: €{{ number_format($subscription->price, 2) }}</span>
                            </div>
                        @endforeach
                    @else
                        <p>No subscriptions available for this invoice.</p>
                    @endif


                    {{-- Totaal prijs (inclusief btw) weergeven --}}
                    <div class="totals">
                        <p>Total Price (Incl. VAT):</p>
                        <p>€{{ number_format($totalPriceInclVat, 2) }}</p>
                    </div>


                    <!-- Totals Section -->
                    <div class="section">
                        <div class="totals">
                            <p>Total Price (Incl. VAT):</p> <!-- Total Price Label -->
                            <p>€{{ number_format($totalPriceInclVat, 2) }}</p> <!-- Display Total Price -->
                        </div>
                    </div>

                    <!-- Payment Terms Section -->
                    <div class="section payment">
                        <p>{{ $invoice->paymentterms }}</p> <!-- Display Payment Terms -->
                    </div>
                </div>
            </div>
        </div>
</body>

</html>
