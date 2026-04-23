<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            size: 9in 11in;
            margin: 0.5in; /* ระยะเผื่อรูหนามเตยด้านข้าง */
        }
        @font-face {
            font-family: 'THSarabunNew';
            font-style: normal;
            font-weight: normal;
            src: url("{{ public_path('fonts/THSarabunNew.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'THSarabunNew';
            font-style: normal;
            font-weight: bold;
            src: url("{{ public_path('fonts/THSarabunNew_Bold.ttf') }}") format('truetype');
        }

        body {
            font-family: 'THSarabunNew';
            font-size: 16pt; /* ขนาด 16pt กำลังดีสำหรับหัวเข็ม */
            line-height: 1.1; /* ปรับเพิ่มเพื่อให้สระไม่ซ้อนกัน */
            color: #000;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th, .table td {
            border: 1px solid black; /* เส้นขอบดำชัดเจน */
            padding: 4px 8px;
        }

        /* ยกเลิกการใช้สีเทาใน thead สำหรับเครื่องหัวเข็ม */
        .table thead th {
            background-color: transparent; 
            border-bottom: 1px solid black; /* ใช้เส้นหนาแทนสีพื้นหลัง */
        }

        /* จัดการระยะบรรทัดในตารางรายการ */
        .item-row td {
            vertical-align: top;
            height: 30px; /* กำหนดความสูงขั้นต่ำเพื่อให้แถวดูสม่ำเสมอ */
        }
    </style>
</head>

<body>
    <div class="text-center" style="margin-bottom: 10px;">
        <h2 class="fw-bold" style="margin: 0;">ใบเสร็จรับเงิน</h2>
        <h3 class="fw-bold" style="margin: 0;">{{ $apartment->name }}</h3>
        <p style="font-size: 16pt; padding: 0;margin: 0;">
            {{ $apartment->address_no }} ต.{{ $apartment->sub_district }}
            อ.{{ $apartment->district }} จ.{{ $apartment->province }} {{ $apartment->postal_code }} 
            <br> โทร {{ $apartment->phone }}</p>

    </div>

    <table style="width: 100%; border: 1px solid black;">
        <tr>
            <td style="width: 60%; border-right: 1px solid black; padding: 10px; vertical-align: top; font-size: 16pt;" >
                <span class="fw-bold text-primary">ชื่อ</span> {{ $invoice->tenant->first_name }}
                {{ $invoice->tenant->last_name }}<br>
                <strong>ที่อยู่ </strong> {{ $invoice->tenant->address_no }}
                หมู่ {{ $invoice->tenant->moo }}
                ต.{{ $invoice->tenant->sub_district }}
                อ.{{ $invoice->tenant->district }}
                จ.{{ $invoice->tenant->province }}
                {{ $invoice->tenant->postal_code }}<br>
                <span class="fw-bold">วันที่จดมิเตอร์</span> {{ $invoice->thai_reading_date }}
            </td>
            <td style="width: 40%; padding: 10px; vertical-align: top;">
                <span class="fw-bold" style="margin-right: 20%;">วันที่</span> {{ $invoice->thai_issue_date }}<br>
                <span class="fw-bold" style="margin-right: 30%;">ห้อง</span> {{ $invoice->tenant->room->room_number }}
            </td>
        </tr>
    </table>

    <table class="table" style="margin-top: 25px">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th width="10%">ลำดับ</th>
                <th width="40%">รายการ</th>
                <th width="15%">จำนวน/หน่วย</th>
                <th width="15%">ราคา</th>
                <th width="20%">จำนวนเงิน</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->details as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->name }} 
                        <span style="margin-left: 20%;">{{ $item->previous_unit }} </span>
                        <span style="margin-left: 20%;">{{  $item->current_unit }}</span>
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">{{ number_format($item->price_per_unit, 2) }}</td>
                    <td class="text-end">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-center fw-bold">{{ $invoice->total_amount_thai }}</td>
                <td class="text-center fw-bold">ยอดเงินสุทธิ</td>
                <td class="text-end fw-bold">{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    <br>
    <br>
    <p style="font-size: 16pt; margin-top: 5px; margin-bottom: 5px; margin-left: 10%;">
        ผู้รับเงิน  ........................................................
    </p>
    <br>
    <p style="font-size: 16pt; margin-top: 5px; margin-bottom: 10px; margin-left: 10%;">
       วันที่  .............................................................
    </p>
    <br>
    <p class="text-center" style="font-size: 16pt; margin-top: 30px;">
        **หมายเหตุ กรุณาชำระเงินภายในวันที่ 5 ของทุกเดือน หากเกินกำหนด ปรับวันละ 50 บาท**
    </p>
</body>

</html>
