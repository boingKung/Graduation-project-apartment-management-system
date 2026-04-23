@php
    // คำนวณจำนวนคอลัมน์ทั้งหมด เพื่อให้ชื่อหัวตารางผสานช่อง (colspan) ได้พอดี
    $totalCols = 7 + count($show_columns) + ($show_payment_status ? 1 : 0);
@endphp

<table>
    <thead>
        <tr>
            {{-- หัวรายงาน แถวที่ 1 --}}
            <th colspan="{{ $totalCols }}" style="font-size: 16px; font-weight: bold; text-align: center;">
                รายงานเก็บเงินประจำเดือน {{ $thai_month }} {{ $apartment->name ?? 'อาทิตย์ อพาร์ทเม้นท์' }}
            </th>
        </tr>
        <tr>
            {{-- หัวตาราง แถวที่ 2 --}}
            <th style="font-weight: bold; text-align: center;">ลำดับ</th>
            <th style="font-weight: bold; text-align: center;">ห้อง</th>
            <th style="font-weight: bold; text-align: center;">ว่าง</th>
            <th style="font-weight: bold; text-align: center;">เช่า</th>
            <th style="font-weight: bold; text-align: center;">ชื่อ-นามสกุล</th>
            
            @foreach ($show_columns as $colName)
                <th style="font-weight: bold; text-align: center;">{{ $colName }}</th>
            @endforeach
            
            <th style="font-weight: bold; text-align: center;">รวมเงิน</th>
            <th style="font-weight: bold; text-align: center;">วันที่รับเงิน</th>
            
            @if($show_payment_status)
                <th style="font-weight: bold; text-align: center;">สถานะการชำระ</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($rooms as $index => $room)
            @php
                $rowTotal = $room->expense_details->only($show_columns)->sum();
            @endphp
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="text-align: center;">{{ $room->room_number }}</td>
                
                {{-- เปลี่ยนสัญลักษณ์ - เป็นค่าว่าง เพื่อให้ Excel ดูสะอาดตา --}}
                <td style="text-align: center;">{{ !$room->report_is_occupied ? '1' : '' }}</td>
                <td style="text-align: center;">{{ $room->report_is_occupied ? '1' : '' }}</td>
                
                <td>{{ $room->report_tenant_name !== '-' ? $room->report_tenant_name : '' }}</td>

                @foreach ($show_columns as $colName)
                    <td style="text-align: right;">
                        {{-- ส่งเป็นตัวเลขดิบ ไม่ใช้ number_format เพื่อให้คำนวณใน Excel ต่อได้ --}}
                        {{ isset($room->expense_details[$colName]) ? $room->expense_details[$colName] : 0 }}
                    </td>
                @endforeach
                
                <td style="text-align: right; font-weight: bold;">
                    {{ $rowTotal > 0 ? $rowTotal : 0 }}
                </td>
                
                <td style="text-align: center;">{{ $room->payment_date_display !== '-' ? $room->payment_date_display : '' }}</td>
                
                @if($show_payment_status)
                    <td style="text-align: center;">{{ $room->payment_status !== '-' ? $room->payment_status : '' }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align: center; font-weight: bold;">รวม</td>
            <td style="text-align: center; font-weight: bold;">{{ $rooms->where('report_is_occupied', false)->count() ?: 0 }}</td>
            <td style="text-align: center; font-weight: bold;">{{ $rooms->where('report_is_occupied', true)->count() ?: 0 }}</td>
            <td style="text-align: center; font-weight: bold;">{{ $rooms->where('report_is_occupied', true)->count() }} ราย</td>

            @foreach ($show_columns as $colName)
                <td style="text-align: right; font-weight: bold;">
                    @php
                        $columnSum = $rooms->sum(function ($r) use ($colName) {
                            return $r->expense_details->get($colName) ?? 0;
                        });
                    @endphp
                    {{ $columnSum }}
                </td>
            @endforeach

            <td style="text-align: right; font-weight: bold;">
                @php
                    $grandTotal = $rooms->sum(function ($r) use ($show_columns) {
                        return $r->expense_details ? $r->expense_details->only($show_columns)->sum() : 0;
                    });
                @endphp
                {{ $grandTotal }}
            </td>
            <td></td>
            @if($show_payment_status)
                <td></td>
            @endif
        </tr>
    </tfoot>
</table>