<table>
    <thead>
        {{-- หัวรายงาน แถวที่ 1-2 --}}
        <tr>
            <th colspan="3" style="font-size: 16px; font-weight: bold; text-align: center;">
                รายรับ {{ $apartment->name ?? 'อาทิตย์ อพาร์ทเม้นท์' }}
            </th>
        </tr>
        <tr>
            <th colspan="3" style="font-size: 12px; text-align: center;">
                ตั้งแต่วันที่ {{ $thai_startDate }} ถึงวันที่ {{ $thai_endDate }}
            </th>
        </tr>
        
        {{-- หัวตาราง --}}
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">รายการ</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">รายย่อย (บาท)</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">ยอดรวมสุทธิ (บาท)</th>
        </tr>
    </thead>
    <tbody>
        {{-- 1. ยอดค้างรับ --}}
        @php
            $outstandingByItem = $outstandingDetails->flatMap->details->groupBy('name')->map->sum('subtotal');
        @endphp
        <tr>
            <td colspan="3" style="font-weight: bold; background-color: #f9f9f9;">ยอดค้างรับ (เดือน {{ $displayDate }})</td>
        </tr>
        @foreach ($outstandingByItem as $itemName => $subtotal)
            <tr>
                <td style="padding-left: 20px;">- {{ $itemName }}</td>
                <td style="text-align: right;">{{ $subtotal }}</td>
                <td></td>
            </tr>
        @endforeach
        <tr>
            <td style="font-weight: bold; color: #d9534f; padding-left: 10px;">ยอดรวมค้างรับ</td>
            <td></td>
            <td style="text-align: right; font-weight: bold; color: #d9534f;">{{ $outstandingAmount }}</td>
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
                    <td style="padding-left: 10px;">ค่ามัดจำ ({{ $bName }})</td>
                    <td style="text-align: right;">{{ $items->sum('amount') }}</td>
                    <td></td>
                </tr>
            @endforeach
            <tr>
                <td style="font-weight: bold; color: #5cb85c; padding-left: 10px;">รวมค่ามัดจำ</td>
                <td></td>
                <td style="text-align: right; font-weight: bold;">{{ $depositTotal }}</td>
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
                <tr>
                    <td colspan="3" style="font-weight: bold; padding-left: 10px;">ข้อมูลตึก: {{ $bName ?: 'ตึกทั่วไป' }}</td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">- ค่าเช่า</td>
                    <td style="text-align: right;">{{ $bRent }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">- ค่าไฟ</td>
                    <td style="text-align: right;">{{ $bElec }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td style="font-weight: bold; background-color: #f9f9f9; padding-left: 20px;">รวมค่าเช่าและค่าไฟ ({{ $bName }})</td>
                    <td style="background-color: #f9f9f9;"></td>
                    <td style="text-align: right; font-weight: bold; background-color: #f9f9f9;">{{ $bSubTotal }}</td>
                </tr>
                @php $cashTotal += $bSubTotal; @endphp
            @endif
        @endforeach

        {{-- 4. อื่นๆ --}}
        @foreach ($incomeByGroup->except(['ค่าเช่าห้อง', 'ค่าไฟ', 'ค่ามัดจำ']) as $catName => $buildings)
            @php $catTotal = 0; @endphp
            
            @foreach ($buildings as $bName => $items)
                @php 
                    $amt = $items->sum('amount'); 
                    $catTotal += $amt; 
                @endphp
                <tr>
                    <td style="padding-left: 20px;">{{ $catName }} {{ $bName ? "($bName)" : "" }}</td>
                    <td style="text-align: right;">{{ $amt }}</td>
                    <td></td>
                </tr>
            @endforeach
            <tr>
                <td style="font-weight: bold; padding-left: 10px;">รวม{{ $catName }}</td>
                <td></td>
                <td style="text-align: right; font-weight: bold;">{{ $catTotal }}</td>
            </tr>
            @php $cashTotal += $catTotal; @endphp
        @endforeach
    </tbody>
    
    <tfoot>
        <tr>
            <td colspan="2" style="font-weight: bold; text-align: center; background-color: #eeeeee;">รวมยอดรับเงินจริง (เงินสด/โอน)</td>
            <td style="font-weight: bold; text-align: right; background-color: #eeeeee;">{{ $cashTotal }}</td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold; text-align: center; background-color: #dddddd;">รวมรายรับทั้งสิ้น (ยอดรับจริง + ยอดค้าง)</td>
            <td style="font-weight: bold; text-align: right; font-size: 16px; background-color: #dddddd;">{{ $cashTotal + $outstandingAmount }}</td>
        </tr>
    </tfoot>
</table>