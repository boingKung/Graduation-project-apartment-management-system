<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
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
            font-size: 16pt;
            line-height: 1.3;
            margin: 0;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }

        .header-section { margin-bottom: 20px; }
        .header-title { font-size: 20pt; margin-bottom: 5px; }

        .table {
            width: 100%;
            border-collapse: collapse; /* เชื่อมเส้นขอบให้เป็นเส้นเดียว */
        }

        .table th, .table td {
            border: 1px solid black;
            padding: 6px 10px;
        }

        .table thead th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .bg-light { background-color: #fafafa; }
        .text-danger { color: #d9534f; }
        .total-row { background-color: #eeeeee; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header-section text-center">
        <div class="header-title fw-bold">งบรับ - จ่าย {{ $apartment->name ?? 'อาทิตย์อพาร์ทเม้นท์' }}</div>
        <div>ประจำเดือน {{ $displayDate }}</div>
        <div style="font-size: 16pt;">ตั้งแต่วันที่ {{ $thai_startDate }} ถึงวันที่ {{ $thai_endDate }}</div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th width="25%">รายการ</th>
                <th width="35%">รายละเอียด</th>
                <th width="20%">รายรับ (บาท)</th>
                <th width="20%">รายจ่าย (บาท)</th>
            </tr>
        </thead>
        <tbody>
            {{-- 🏢 ส่วนที่ 1: รายรับแยกตามตึก --}}
            @foreach($buildingIncome as $buildingName => $items)
                @php 
                    $rentSum = $items->filter(fn($i) => str_contains($i->title, 'ค่าเช่า'))->sum('amount');
                    $elecSum = $items->filter(fn($i) => str_contains($i->title, 'ค่าไฟ'))->sum('amount');
                @endphp
                <tr>
                    <td rowspan="2" class="fw-bold text-center bg-light">{{ $buildingName }}</td>
                    <td>ค่าเช่าห้อง</td>
                    <td class="text-end">{{ number_format($rentSum, 2) }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>ค่าไฟฟ้า</td>
                    <td class="text-end">{{ number_format($elecSum, 2) }}</td>
                    <td></td>
                </tr>
            @endforeach

            {{-- ส่วนที่ 2: รายรับอื่นๆ --}}
            @foreach($otherIncome as $name => $amount)
            <tr>
                <td colspan="2" style="padding-left: 20px;">{{ $name }}</td>
                <td class="text-end">{{ number_format($amount, 2) }}</td>
                <td></td>
            </tr>
            @endforeach

            {{-- ส่วนที่ 3: ยอดค้างรับ --}}
            <tr>
                <td colspan="2" style="padding-left: 20px;">เก็บค่าเช่าคงค้างเดือน {{ $displayDate }}</td>
                <td class="text-end">{{ number_format($outstandingAmount, 2) }}</td>
                <td></td>
            </tr>

            {{-- ส่วนที่ 4: รายจ่าย --}}
            @foreach($expenseByCats as $name => $amount)
            <tr>
                <td colspan="2" style="padding-left: 20px; color: #555;">{{ $name }}</td>
                <td></td>
                <td class="text-end text-danger">{{ number_format($amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php
                $totalIn = $buildingIncome->flatten()->sum('amount') + $otherIncome->sum() + $outstandingAmount;
                $totalEx = $expenseByCats->sum();
            @endphp
            <tr class="total-row">
                <td colspan="2" class="text-center">รวมยอดทั้งหมด</td>
                <td class="text-end">{{ number_format($totalIn, 2) }}</td>
                <td class="text-end text-danger">{{ number_format($totalEx, 2) }}</td>
            </tr>
            <tr class="total-row" style="background-color: #dddddd;">
                <td colspan="2" class="text-center" style="padding: 15px;">รายรับสุทธิ (กำไร/ขาดทุน):</td>
                <td colspan="2" class="text-end" style="font-size: 16pt;">{{ number_format($totalIn - $totalEx, 2) }} บาท</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 30px; text-align: right; font-size: 16pt;">
        พิมพ์เมื่อวันที่: {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>