<table>
    <thead>
        {{-- หัวรายงาน แถวที่ 1-3 --}}
        <tr>
            <th colspan="4" style="font-size: 16px; font-weight: bold; text-align: center;">
                รายจ่าย {{ $apartment->name ?? 'อาทิตย์ อพาร์ทเม้นท์' }}
            </th>
        </tr>
        <tr>
            <th colspan="4" style="font-size: 12px; text-align: center;">
                ประจำเดือน {{ $displayDate }}
            </th>
        </tr>
        <tr>
            <th colspan="4" style="font-size: 12px; text-align: center;">
                ตั้งแต่วันที่ {{ $thai_startDate }} ถึงวันที่ {{ $thai_endDate }}
            </th>
        </tr>
        
        {{-- หัวตาราง --}}
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">หมวดหมู่</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">รายการรายละเอียด</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">รายย่อย (บาท)</th>
            <th style="font-weight: bold; text-align: center; background-color: #f2f2f2;">รวมหมวด (บาท)</th>
        </tr>
    </thead>
    <tbody>
        @php $grandTotal = 0; @endphp
        
        @forelse($expenseByGroup as $catName => $transactions)
            @php 
                $catTotal = $transactions->sum('amount');
                $grandTotal += $catTotal;
                $rowCount = $transactions->count();
            @endphp
            
            @foreach($transactions as $index => $t)
            <tr>
                {{-- คอลัมน์ที่ 1: ชื่อหมวดหมู่ (ผสานเซลล์แนวตั้งตามจำนวนรายการในหมวด) --}}
                @if($index === 0)
                    <td rowspan="{{ $rowCount }}" style="font-weight: bold; text-align: center; vertical-align: center; background-color: #f9f9f9;">
                        {{ $catName }}
                    </td>
                @endif
                
                {{-- คอลัมน์ที่ 2: รายละเอียด (ใส่ Title และ Description ในช่องเดียวกัน) --}}
                <td>
                    {{ $t->title }}
                    @if($t->description)
                        <br>
                        - {{ $t->description }}
                    @endif
                </td>
                
                {{-- คอลัมน์ที่ 3: รายย่อย --}}
                <td style="text-align: right;">
                    {{ $t->amount }}
                </td>
                
                {{-- คอลัมน์ที่ 4: รวมหมวด (ผสานเซลล์เช่นกัน) --}}
                @if($index === 0)
                    <td rowspan="{{ $rowCount }}" style="font-weight: bold; text-align: right; vertical-align: center;">
                        {{ $catTotal }}
                    </td>
                @endif
            </tr>
            @endforeach
        @empty
            <tr>
                <td colspan="4" style="text-align: center;">ไม่พบข้อมูลรายจ่ายในช่วงเวลาที่เลือก</td>
            </tr>
        @endforelse
    </tbody>
    
    <tfoot>
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: center; background-color: #eeeeee;">รวมรายจ่ายทั้งสิ้น (เงินสด/เงินโอน)</td>
            <td style="font-weight: bold; text-align: right; font-size: 16px; background-color: #eeeeee; color: #d9534f;">
                {{ $grandTotal }}
            </td>
        </tr>
    </tfoot>
</table>