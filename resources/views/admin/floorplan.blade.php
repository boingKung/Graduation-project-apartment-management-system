@extends('admin.layout')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>ออกแบบผังห้อง (Design Mode)</h2>
        <button id="save-btn" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> บันทึกตำแหน่ง
        </button>
    </div>

    <div class="card p-3 bg-light" style="overflow: auto;">
        <svg id="floor-canvas" width="{{ $svgWidth }}" height="{{ $svgHeight }}" 
             style="background: white; border: 2px dashed #ccc; cursor: grab;">
            
            <defs>
                <pattern id="smallGrid" width="10" height="10" patternUnits="userSpaceOnUse">
                    <path d="M 10 0 L 0 0 0 10" fill="none" stroke="gray" stroke-width="0.5"/>
                </pattern>
                <pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse">
                    <rect width="100" height="100" fill="url(#smallGrid)"/>
                    <path d="M 100 0 L 0 0 0 100" fill="none" stroke="gray" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />

            @foreach($rooms as $room)
                <g class="draggable" 
                   data-id="{{ $room->id }}" 
                   transform="translate({{ $room->pos_x }}, {{ $room->pos_y }})"
                   style="cursor: pointer;">
                   
                   <rect width="{{ $room->width }}" height="{{ $room->height }}" 
                         rx="5" ry="5" 
                         fill="{{ $room->status == 'available' ? '#28a745' : ($room->status == 'occupied' ? '#dc3545' : '#ffc107') }}" 
                         stroke="#333" stroke-width="2" fill-opacity="0.8"></rect>
                   
                   <text x="{{ $room->width / 2 }}" y="{{ $room->height / 2 }}" 
                         text-anchor="middle" dominant-baseline="middle" 
                         font-weight="bold" fill="white" font-size="14" pointer-events="none">
                       {{ $room->room_number }}
                   </text>
                </g>
            @endforeach
        </svg>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const svg = document.getElementById('floor-canvas');
    let selectedElement = null;
    let offset = { x: 0, y: 0 };

    // --- ฟังก์ชันลากวาง (Drag Logic) ---
    function getMousePosition(evt) {
        const CTM = svg.getScreenCTM();
        if (evt.touches) { evt = evt.touches[0]; }
        return {
            x: (evt.clientX - CTM.e) / CTM.a,
            y: (evt.clientY - CTM.f) / CTM.d
        };
    }

    function startDrag(evt) {
        let target = evt.target;
        // หา parent <g> ที่มี class draggable
        while (target && target.classList && !target.classList.contains('draggable')) {
            target = target.parentNode;
        }
        
        if (target && target.classList && target.classList.contains('draggable')) {
            selectedElement = target;
            offset = getMousePosition(evt);
            
            // อ่านค่า translate ปัจจุบัน
            const transforms = selectedElement.transform.baseVal;
            if (transforms.length === 0 || transforms.getItem(0).type !== SVGTransform.SVG_TRANSFORM_TRANSLATE) {
                const translate = svg.createSVGTransform();
                translate.setTranslate(0, 0);
                selectedElement.transform.baseVal.insertItemBefore(translate, 0);
            }
            
            // คำนวณ offset จริง
            offset.x -= selectedElement.transform.baseVal.getItem(0).matrix.e;
            offset.y -= selectedElement.transform.baseVal.getItem(0).matrix.f;
        }
    }

    function drag(evt) {
        if (selectedElement) {
            evt.preventDefault();
            const coord = getMousePosition(evt);
            const x = coord.x - offset.x;
            const y = coord.y - offset.y;
            
            // Snap to Grid (10px) - ทำให้วางเป็นระเบียบง่ายขึ้น
            const snapX = Math.round(x / 10) * 10;
            const snapY = Math.round(y / 10) * 10;
            
            selectedElement.transform.baseVal.getItem(0).setTranslate(snapX, snapY);
        }
    }

    function endDrag(evt) {
        selectedElement = null;
    }

    // Attach Events (รองรับทั้ง Mouse และ Touch จอสัมผัส)
    svg.addEventListener('mousedown', startDrag);
    svg.addEventListener('mousemove', drag);
    svg.addEventListener('mouseup', endDrag);
    svg.addEventListener('mouseleave', endDrag);
    
    svg.addEventListener('touchstart', startDrag);
    svg.addEventListener('touchmove', drag);
    svg.addEventListener('touchend', endDrag);

    // --- ฟังก์ชันบันทึก (Save Logic) ---
    document.getElementById('save-btn').addEventListener('click', function() {
        const rooms = [];
        document.querySelectorAll('.draggable').forEach(el => {
            const id = el.getAttribute('data-id');
            const transform = el.transform.baseVal.getItem(0).matrix;
            rooms.push({ id: id, x: transform.e, y: transform.f });
        });

        // ส่ง Ajax
        fetch("{{ route('admin.floorplan.save') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ positions: rooms })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการบันทึก');
        });
    });
});
</script>
@endsection