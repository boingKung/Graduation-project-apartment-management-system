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
        .table th, .table td { border: 1px solid black; padding: 5px 10px; vertical-align: top; }
        .table thead th { background-color: #f2f2f2; font-weight: bold; }
        .bg-light { background-color: #f9f9f9; }
        .text-danger { color: #d9534f; }
        .small-desc { font-size: 16pt; color: #555; }
        .text-continued { font-size: 10pt; color: #777; }
    </style>
</head>
<body>
    <div class="text-center">
        <h2 class="fw-bold" style="margin-bottom: 5px;">รายจ่าย {{ $apartment->name ?? 'อาทิตย์ อพาร์ทเม้นท์' }}</h2>
        <div>ประจำเดือน {{ $displayDate }}</div>
        <div style="font-size: 16pt;">ตั้งแต่วันที่ {{ $thai_startDate }} ถึงวันที่ {{ $thai_endDate }}</div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th width="20%">หมวดหมู่</th>
                <th width="40%">รายการรายละเอียด</th>
                <th width="20%">รายย่อย (บาท)</th>
                <th width="20%">รวมหมวด (บาท)</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            
            @forelse($expenseByGroup as $catName => $transactions)
                @php 
                    $catTotal = $transactions->sum('amount');
                    $grandTotal += $catTotal;
                @endphp
                
                @foreach($transactions as $index => $t)
                <tr>
                    {{-- 🌟 คอลัมน์ที่ 1: ชื่อหมวดหมู่ (ไม่ใช้ rowspan แล้ว) --}}
                    <td class="text-center bg-light">
                        @if($index === 0)
                            <span class="fw-bold">{{ $catName }}</span>
                        @else
                            <span class="text-continued">({{ $catName }} - ต่อ)</span>
                        @endif
                    </td>

                    {{-- คอลัมน์ที่ 2: รายละเอียด --}}
                    <td>
                        <div>{{ $t->title }}</div>
                        @if($t->description)
                            <div class="small-desc">- {{ $t->description }}</div>
                        @endif
                    </td>

                    {{-- คอลัมน์ที่ 3: รายย่อย --}}
                    <td class="text-end">{{ number_format($t->amount, 2) }}</td>

                    {{-- 🌟 คอลัมน์ที่ 4: รวมหมวด (ไม่ใช้ rowspan แล้ว) --}}
                    <td class="text-end bg-light">
                        @if($index === 0)
                            <span class="fw-bold">{{ number_format($catTotal, 2) }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="4" class="text-center py-4">ไม่พบข้อมูลรายจ่ายในช่วงเวลาที่เลือก</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-center" style="padding: 10px;">รวมรายจ่ายทั้งสิ้น (เงินสด/เงินโอน)</th>
                <th class="text-end" style="font-size: 16pt;">{{ number_format($grandTotal, 2) }}</th>
            </tr>
        </tfoot>
    </table>

</body>
</html>