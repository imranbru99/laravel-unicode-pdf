<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <title>चालान / Invoice</title>
    <style>
        body {
            font-family: 'Noto Sans Devanagari', sans-serif;
            color: #1a202c;
            margin: 25px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #d69e2e;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #b7791f;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #cbd5e0;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #fffff0;
            color: #744210;
        }
        .total-row {
            font-weight: bold;
            background-color: #f7fafc;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>बिक्री चालान (Tax Invoice)</h1>
        <p>चालान संख्या: #{{ $invoice_no ?? 'बिल-२०२६-०१' }} | दिनांक: {{ $date ?? '०१ सितम्बर २०२६' }}</p>
    </div>

    <div class="invoice-details">
        <p><strong>ग्राहक का नाम:</strong> {{ $customer_name ?? 'राजेश शर्मा' }}</p>
        <p><strong>पता:</strong> {{ $customer_address ?? 'नई दिल्ली, भारत' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>क्र.सं.</th>
                <th>उत्पाद का विवरण</th>
                <th class="text-right">मात्रा</th>
                <th class="text-right">मूल्य</th>
                <th class="text-right">कुल राशि</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>१</td>
                <td>स्मार्टफोन (१२८ जीबी स्टोरेज)</td>
                <td class="text-right">१</td>
                <td class="text-right">₹२५,०००</td>
                <td class="text-right">₹२५,०००</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" class="text-right">कुल योग:</td>
                <td class="text-right">₹२५,०००</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
