<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>চালানপত্র / Invoice</title>
    <style>
        body {
            font-family: 'AI-Borno', 'Noto Sans Bengali', 'Noto Sans', sans-serif;
            color: #1a202c;
            margin: 25px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3182ce;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2b6cb0;
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.015em;
        }
        .invoice-details {
            margin-bottom: 20px;
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
            background-color: #ebf8ff;
            color: #2c5282;
            font-weight: 700;
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
        <h1>বিক্রয় চালানপত্র (Invoice)</h1>
        <p>স্মারক নং: #{{ $invoice_no ?? 'ইনভ-২০২৬-১০০১' }} | তারিখ: {{ $date ?? '০১ সেপ্টেম্বর ২০২৬' }}</p>
    </div>

    <div class="invoice-details">
        <p><strong>ক্রেতার নাম:</strong> {{ $customer_name ?? 'মোহাম্মদ ইমরান হোসেন' }}</p>
        <p><strong>ঠিকানা:</strong> {{ $customer_address ?? 'ধানমন্ডি, ঢাকা - ১২০৯, বাংলাদেশ' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ক্রমিক</th>
                <th>পণ্যের বিবরণ (Item Description)</th>
                <th class="text-right">পরিমাণ (Qty)</th>
                <th class="text-right">একক মূল্য (Unit Price)</th>
                <th class="text-right">মোট মূল্য (Total)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>১</td>
                <td>ল্যাপটপ কম্পিউটার (Core i7, 16GB RAM)</td>
                <td class="text-right">১</td>
                <td class="text-right">৳৮৫,০০০</td>
                <td class="text-right">৳৮৫,০০০</td>
            </tr>
            <tr>
                <td>২</td>
                <td>ওয়্যারলেস মাউস ও কিবোর্ড কম্বো</td>
                <td class="text-right">২</td>
                <td class="text-right">৳২,৫০০</td>
                <td class="text-right">৳৫,০০০</td>
            </tr>
            <tr class="total-row">
                <td colspan="4" class="text-right">সর্বমোট (Grand Total):</td>
                <td class="text-right">৳৯০,০০০</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: center; color: #718096; font-size: 13px;">
        ধন্যবাদ! আমাদের সাথে ব্যবসা করার জন্য কৃতজ্ঞতা প্রকাশ করছি।
    </div>
</body>
</html>
