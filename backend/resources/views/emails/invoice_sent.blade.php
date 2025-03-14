<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->id }}</title>
</head>
<body>
    <h1>Invoice #{{ $invoice->id }}</h1>
    <p>Date: {{ \Carbon\Carbon::parse($invoice->sentdate)->format('d-m-Y') }}</p>
    <p>Download your invoice PDF</p>
    <p>Thank you for your business!</p>
</body>
</html>
