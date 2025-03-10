<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->id }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            color: #374151;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .card {
            background-color: #fff;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
            color: #000000;
        }

        .header p {
            margin-top: 0.5rem;
            font-size: 1.125rem;
            color: #000000;
        }

        .content {
            padding: 1.5rem 2rem;
        }

        .section {
            margin-bottom: 1.5rem;
        }

        .section h2 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            color: #212225;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .customer-info p {
            font-size: 1.125rem;
            margin: 0.25rem 0;
        }

        .subscription-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9fafb;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            transition: background-color 0.3s;
        }

        .subscription-item:hover {
            background-color: #f3f4f6;
        }

        .subscription-item span {
            font-size: 1rem;
        }

        .totals {
            display: flex;
            justify-content: space-between;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .payment p {
            font-size: 0.875rem;
            color: rgba(55, 65, 81, 0.7);
            margin: 0.25rem 0;
        }

        .footer {
            background-color: #f0f2f5;
            padding: 1rem 2rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <!-- Content -->
            <div class="content">
                <div class="section invoice-info">
                    <h2>Invoice Number #{{ $invoice->id }}</h2>
                    <p>Date: {{ \Carbon\Carbon::parse($invoice->sentdate)->format('d-m-Y') }}</p>
                </div>
                <!-- Customer Information -->
                <div class="section customer-info">
                    <h2>Customer</h2>
                    <p><strong>Name:</strong> {{ $invoice->customer->name }}</p>
                </div>
                <!-- Subscriptions -->
                <!-- Subscriptions -->
                <!-- Subscriptions -->
                <!-- Subscriptions -->
                <!-- Subscriptions -->
                <div class="section">
                    <h2>Subscriptions</h2>
                    @php $totalPriceInclVat = 0; @endphp
                    @foreach ($invoice->customer->subscriptions as $subscription)
                        @php
                        $priceWithVat = $subscription->price; // Prijs inclusief BTW
                        $priceNoVat = $priceWithVat / (1 + ($subscription->vat / 100)); // Prijs exclusief BTW
                        $vatAmount = $priceWithVat - $priceNoVat; // BTW bedrag
                        $basePrice = $priceNoVat; // Basisprijs
                        $totalPriceInclVat += $subscription->price;


@endphp
                        <div class="subscription-item">
                            <span>{{ $subscription->name }}</span>
                            <span>Base Price: €{{ number_format($basePrice, 2) }}</span>
                            <span>VAT ({{ $subscription->vat }}%): €{{ number_format($vatAmount, 2) }}</span>
                            <span>Total: €{{ number_format($subscription->price, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <!-- Totals -->
                <div class="section">
                    <div class="totals">
                        <p>Total Price (Incl. VAT):</p>
                        <p>€{{ number_format($totalPriceInclVat, 2) }}</p>
                    </div>
                </div>


                <!-- Payment Terms -->
                <div class="section payment">
                    <h2></h2>
                    <p>{{ $invoice->paymentterms }}</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
