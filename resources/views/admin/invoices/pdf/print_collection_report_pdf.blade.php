@php
    // คำนวณจำนวนคอลัมน์ทั้งหมด (ลำดับ, ห้อง, ว่าง, เช่า, ชื่อ + รายการค่าใช้จ่าย + รวมเงิน, วันที่รับเงิน + สถานะ)
    $totalCols = 5 + count($show_columns) + 2 + ($show_payment_status ? 1 : 0);

    // 🌟 จัดการขนาดฟอนต์ให้เหมาะสมกับจำนวนคอลัมน์ (ปรับให้ละเอียดขึ้น)
    if ($totalCols >= 16) {
        $dynamicFontSize = '8pt';
    } elseif ($totalCols >= 13) {
        $dynamicFontSize = '9pt';
    } elseif ($totalCols >= 10) {
        $dynamicFontSize = '11pt';
    } else {
        $dynamicFontSize = '13pt'; // ขนาดปกติเมื่อคอลัมน์น้อย
    }
@endphp
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
            line-height: 1.1;
            margin: 0;
            padding: 0;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th,
        .table td {
            border: 1px solid black;
            padding: 3px 2px;
            font-size: 16pt;
            word-wrap: break-word;
        }

        .table th {
            font-weight: bold;
            background-color: #f0f0f0;
            vertical-align: middle;
        }

        .header-title {
            font-size: 24pt;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="header-title fw-bold">รายงานเก็บเงินประจำเดือน {{ $thai_month }} {{ $apartment->name ?? 'อาทิตย์ อพาร์ทเม้นท์' }}</div>
    </div>

    <table class="table" autosize="1">
        <thead>
            <tr>
                <th width="4%">ลำดับ</th>
                <th width="6%">ห้อง</th>
                <th width="5%">ว่าง</th>
                <th width="5%">เช่า</th>
                <th width="16%">ชื่อ-นามสกุล</th>
                
                {{-- ปล่อยให้คอลัมน์ค่าใช้จ่ายเฉลี่ยพื้นที่ที่เหลือกันเอง --}}
                @foreach ($show_columns as $colName)
                    <th>{{ $colName }}</th>
                @endforeach
                
                <th width="9%">รวมเงิน</th>
                <th width="10%">วันที่รับเงิน</th>
                @if($show_payment_status)
                    <th width="9%">สถานะการชำระ</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rooms as $index => $room)
                @php
                    $rowTotal = $room->expense_details->only($show_columns)->sum();
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center fw-bold">{{ $room->room_number }}</td>
                    
                    <td class="text-center">{{ !$room->report_is_occupied ? '1' : '-' }}</td>
                    <td class="text-center">{{ $room->report_is_occupied ? '1' : '-' }}</td>
                    
                    <td style="padding-left: 5px;">
                        @if ($room->report_tenant_name !== '-')
                            {{ $room->report_tenant_name }}
                        @else
                            <span style="color: #888;">-</span>
                        @endif
                    </td>

                    @foreach ($show_columns as $colName)
                        <td class="text-end">
                            {{ isset($room->expense_details[$colName]) ? number_format($room->expense_details[$colName], 0) : '-' }}
                        </td>
                    @endforeach
                    <td class="text-end fw-bold">
                        {{ $rowTotal > 0 ? number_format($rowTotal, 0) : '-' }}
                    </td>
                    <td class="text-center">{{ $room->payment_date_display }}</td>
                    
                    @if($show_payment_status)
                        <td class="text-center fw-bold">{{ $room->payment_status }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot style="background-color: #f9f9f9; font-weight: bold;">
            <tr>
                <td colspan="2" class="text-center">รวม</td>
                <td class="text-center">{{ $rooms->where('report_is_occupied', false)->count() ?: '-' }}</td>
                <td class="text-center">{{ $rooms->where('report_is_occupied', true)->count() ?: '-' }}</td>
                <td class="text-center">{{ $rooms->where('report_is_occupied', true)->count() }} ราย</td>

                @foreach ($show_columns as $colName)
                    <td class="text-end">
                        @php
                            $columnSum = $rooms->sum(function ($r) use ($colName) {
                                return $r->expense_details->get($colName) ?? 0;
                            });
                        @endphp
                        {{ number_format($columnSum, 0) }}
                    </td>
                @endforeach

                <td class="text-end">
                    @php
                        $grandTotal = $rooms->sum(function ($r) use ($show_columns) {
                            return $r->expense_details ? $r->expense_details->only($show_columns)->sum() : 0;
                        });
                    @endphp
                    {{ number_format($grandTotal, 0) }}
                </td>
                <td></td>
                @if($show_payment_status)
                    <td></td>
                @endif
            </tr>
        </tfoot>
    </table>
</body>
</html>