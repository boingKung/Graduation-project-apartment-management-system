<table>
    <thead>
        {{-- หัวรายงาน แถวที่ 1-2 --}}
        <tr>
            <th colspan="4" style="font-size: 16px; font-weight: bold; text-align: center;">
                งบรับ - จ่าย {{ $apartment->name ?? 'อาทิตย์อพาร์ทเม้นท์' }}
            </th>
        </tr>
        <tr>
            <th colspan="4" style="font-size: 12px; text-align: center;">
                ประจำเดือน {{ $displayDate }} (ตั้งแต่วันที่ {{ $thai_startDate }} ถึงวันที่ {{ $thai_endDate }})
            </th>
        </tr>
        
        {{-- หัวตาราง แถวที่ 3 --}}
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2; width: 25px;">รายการ</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2; width: 35px;">รายละเอียด</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2; width: 20px;">รายรับ (บาท)</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2; width: 20px;">รายจ่าย (บาท)</th>
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
                {{-- ใช้ valign="center" เพื่อให้อยู่กึ่งกลางตอนผสานเซลล์ --}}
                <td rowspan="2" style="font-weight: bold; text-align: center; vertical-align: center; background-color: #fafafa;">
                    {{ $buildingName }}
                </td>
                <td>ค่าเช่าห้อง</td>
                <td style="text-align: right;">{{ $rentSum }}</td>
                <td></td>
            </tr>
            <tr>
                <td>ค่าไฟฟ้า</td>
                <td style="text-align: right;">{{ $elecSum }}</td>
                <td></td>
            </tr>
        @endforeach

        {{-- ส่วนที่ 2: รายรับอื่นๆ --}}
        @foreach($otherIncome as $name => $amount)
        <tr>
            <td colspan="2">{{ $name }}</td>
            <td style="text-align: right;">{{ $amount }}</td>
            <td></td>
        </tr>
        @endforeach

        {{-- ส่วนที่ 3: ยอดค้างรับ --}}
        <tr>
            <td colspan="2">เก็บค่าเช่าคงค้างเดือน {{ $displayDate }}</td>
            <td style="text-align: right;">{{ $outstandingAmount }}</td>
            <td></td>
        </tr>

        {{-- ส่วนที่ 4: รายจ่าย --}}
        @foreach($expenseByCats as $name => $amount)
        <tr>
            <td colspan="2" style="color: #555555;">{{ $name }}</td>
            <td></td>
            <td style="text-align: right; color: #d9534f;">{{ $amount }}</td>
        </tr>
        @endforeach
    </tbody>
    
    <tfoot>
        @php
            $totalIn = $buildingIncome->flatten()->sum('amount') + $otherIncome->sum() + $outstandingAmount;
            $totalEx = $expenseByCats->sum();
        @endphp
        <tr>
            <td colspan="2" style="text-align: center; font-weight: bold; background-color: #eeeeee;">รวมยอดทั้งหมด</td>
            <td style="text-align: right; font-weight: bold; background-color: #eeeeee;">{{ $totalIn }}</td>
            <td style="text-align: right; font-weight: bold; background-color: #eeeeee; color: #d9534f;">{{ $totalEx }}</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; font-weight: bold; background-color: #dddddd;">รายรับสุทธิ (กำไร/ขาดทุน):</td>
            <td colspan="2" style="text-align: right; font-weight: bold; background-color: #dddddd; font-size: 16px;">
                {{ $totalIn - $totalEx }}
            </td>
        </tr>
    </tfoot>
</table>