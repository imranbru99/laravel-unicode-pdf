<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>فاتورة مبيعات</title>
    <style>
        body {
            font-family: 'Noto Sans Arabic', 'Amiri', sans-serif;
            color: #1a202c;
            margin: 25px;
            direction: rtl;
            text-align: right;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #38a169;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #276749;
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
            text-align: right;
        }
        th {
            background-color: #f0fff4;
            color: #22543d;
        }
        .total-row {
            font-weight: bold;
            background-color: #f7fafc;
        }
        .text-left {
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>فاتورة مبيعات رقمية</h1>
        <p>رقم الفاتورة: #{{ $invoice_no ?? '١٢٣٤٥' }} | التاريخ: {{ $date ?? '٠١ سبتمبر ٢٠٢٦' }}</p>
    </div>

    <div class="invoice-details">
        <p><strong>اسم العميل:</strong> {{ $customer_name ?? 'محمد أحمد المحمود' }}</p>
        <p><strong>رقم الطلب:</strong> {{ $order_id ?? 'INV-98765' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>الرقم</th>
                <th>اسم المنتج</th>
                <th>الكمية</th>
                <th>السعر الفردي</th>
                <th>المجموع الكلي</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>١</td>
                <td>جهاز حاسوب محمول فائق السرعة</td>
                <td>١</td>
                <td>٤٬٥٠٠ ر.س</td>
                <td>٤٬٥٠٠ ر.س</td>
            </tr>
            <tr>
                <td>٢</td>
                <td>شاشة عرض بدقة عالية 4K</td>
                <td>١</td>
                <td>١٬٥٠٠ ر.س</td>
                <td>١٬٥٠٠ ر.س</td>
            </tr>
            <tr class="total-row">
                <td colspan="4">المجموع الإجمالي:</td>
                <td>٦٬٠٠٠ ر.س</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: center; color: #718096; font-size: 13px;">
        شكراً لتعاملكم معنا! نتمنى لكم يوماً سعيداً.
    </div>
</body>
</html>
