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
        body { font-family: 'THSarabunNew'; font-size: 16pt; line-height: 1.2; margin: 0; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid black; padding: 5px 10px; }
        .table thead th { background-color: #f2f2f2; }
        .bg-light { background-color: #f9f9f9; }
        .text-danger { color: #d9534f; }
        .ps-3 { padding-left: 20px; }
        .ps-4 { padding-left: 40px; }
    </style>
</head>
<body>
    <div class="text-center">
        <h2 class="fw-bold" style="margin-bottom: 5px;">รายรับ {{ $apartment->name ?? 'อาทิตย์ อพาร์ทเม้นท์' }}</h2>
        <div>ตั้งแต่วันที่ {{ $thai_startDate }} ถึงวันที่ {{ $thai_endDate }}</div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th width="50%">รายการ</th>
                <th width="25%">รายย่อย (บาท)</th>
                <th width="25%">ยอดรวมสุทธิ (บาท)</th>
            </tr>
        </thead>
        <tbody>
            {{-- 1. ยอดค้างรับ --}}
            @php
                $outstandingByItem = $outstandingDetails->flatMap->details->groupBy('name')->map->sum('subtotal');
            @endphp
            <tr class="bg-light fw-bold">
                <td colspan="3">ยอดค้างรับ (เดือน {{ $displayDate }})</td>
            </tr>
            @foreach ($outstandingByItem as $itemName => $subtotal)
                <tr>
                    <td class="ps-4">- {{ $itemName }}</td>
                    <td class="text-end">{{ number_format($subtotal, 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
            <tr class="fw-bold">
                <td class="ps-3 text-danger">ยอดรวมค้างรับ</td>
                <td></td>
                <td class="text-end text-danger">{{ number_format($outstandingAmount, 2) }}</td>
            </tr>

            {{-- 2. ค่ามัดจำ --}}
            @php
                $depositData = $incomeByGroup->get('ค่ามัดจำ');
                $depositTotal = $depositData ? $depositData->flatten()->sum('amount') : 0;
                $cashTotal = 0;
            @endphp
            @if ($depositTotal > 0)
                @foreach ($depositData as $bName => $items)
                    <tr>
                        <td class="ps-3">ค่ามัดจำ ({{ $bName }})</td>
                        <td class="text-end">{{ number_format($items->sum('amount'), 2) }}</td>
                        <td></td>
                    </tr>
                @endforeach
                <tr class="fw-bold">
                    <td class="ps-3 text-success">รวมค่ามัดจำ</td>
                    <td></td>
                    <td class="text-end">{{ number_format($depositTotal, 2) }}</td>
                </tr>
                @php $cashTotal += $depositTotal; @endphp
            @endif

            {{-- 3. ค่าเช่าและค่าไฟตามตึก --}}
            @php
                $allBuildings = $incomeByGroup->flatMap(fn($b) => array_keys($b->toArray()))->unique();
            @endphp
            @foreach ($allBuildings as $bName)
                @php
                    $bRent = $incomeByGroup->get('ค่าเช่าห้อง')?->get($bName)?->sum('amount') ?? 0;
                    $bElec = $incomeByGroup->get('ค่าไฟ')?->get($bName)?->sum('amount') ?? 0;
                    $bSubTotal = $bRent + $bElec;
                @endphp
                @if ($bSubTotal > 0)
                    <tr class="fw-bold"><td colspan="3" class="ps-3">ข้อมูลตึก: {{ $bName ?: 'ตึกทั่วไป' }}</td></tr>
                    <tr><td class="ps-4">- ค่าเช่า</td><td class="text-end">{{ number_format($bRent, 2) }}</td><td></td></tr>
                    <tr><td class="ps-4">- ค่าไฟ</td><td class="text-end">{{ number_format($bElec, 2) }}</td><td></td></tr>
                    <tr class="bg-light"><td class="ps-4 fw-bold">รวมค่าเช่าและค่าไฟ ({{ $bName }})</td><td></td><td class="text-end fw-bold">{{ number_format($bSubTotal, 2) }}</td></tr>
                    @php $cashTotal += $bSubTotal; @endphp
                @endif
            @endforeach

            {{-- 4. อื่นๆ --}}
            @foreach ($incomeByGroup->except(['ค่าเช่าห้อง', 'ค่าไฟ', 'ค่ามัดจำ']) as $catName => $buildings)
                @php $catTotal = 0; @endphp
                @foreach ($buildings as $bName => $items)
                    @php $amt = $items->sum('amount'); $catTotal += $amt; @endphp
                    <tr><td class="ps-4">{{ $catName }} {{ $bName ? "($bName)" : "" }}</td><td class="text-end">{{ number_format($amt, 2) }}</td><td></td></tr>
                @endforeach
                <tr class="fw-bold"><td class="ps-3">รวม{{ $catName }}</td><td></td><td class="text-end">{{ number_format($catTotal, 2) }}</td></tr>
                @php $cashTotal += $catTotal; @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr >
                <th colspan="2" class="text-center">รวมยอดรับเงินจริง (เงินสด/โอน)</th>
                <th class="text-end">{{ number_format($cashTotal, 2) }}</th>
            </tr>
            <tr >
                <th colspan="2" class="text-center">รวมรายรับทั้งสิ้น (ยอดรับจริง + ยอดค้าง)</th>
                <th class="text-end" style="font-size: 16pt;">{{ number_format($cashTotal + $outstandingAmount, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>