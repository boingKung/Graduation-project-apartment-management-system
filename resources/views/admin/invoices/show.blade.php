@extends('admin.layout')
@section('title', 'ออกใบแจ้งหนี้และจัดการบิลประจำเดือน')
@section('content')
    <div class="container-fluid py-4">
        {{-- 1. Header & Control Panel (Professional Look) --}}
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-text me-2"></i>ระบบจัดการใบแจ้งหนี้
                        </h4>
                        <p class="text-muted small mb-0">จัดการออกใบเสร็จรับเงินและแจ้งเตือนผู้เช่าประจำเดือน</p>
                    </div>
                    {{-- แสดงรอบเดือนแบบทางการ --}}
                    <div class="bg-light border rounded px-4 py-2 text-center">
                        <span class="d-block small text-muted text-uppercase fw-bold">รอบเดือน</span>
                        <span class="fs-5 fw-bold text-primary">{{ $thai_billing_month }}</span>
                    </div>
                </div>
                <hr class="text-muted mt-3 mb-0">
            </div>

            <div class="card-body p-4">
                <div class="row align-items-end justify-content-center g-4">
                    {{-- โซนที่ 1: เลือกรอบเดือน --}}
                    <div class="col-xl-3 col-lg-4 border-end-lg pe-lg-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-2">1. เลือกรอบเดือน</label>
                        <form method="GET" action="{{ route('admin.invoices.show') }}" class="d-flex gap-2">
                            <input type="month" name="billing_month" class="form-control form-control-sm border-secondary"
                                value="{{ $billing_month }}" onchange="this.form.submit()">
                            <a href="{{ route('admin.invoices.show') }}" class="btn btn-outline-secondary btn-sm"
                                title="กลับเดือนปัจจุบัน">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </form>
                    </div>

                    {{-- โซนที่ 2: ดำเนินการสร้างบิล --}}
                    <div class="col-xl-4 col-lg-5 border-end-lg pe-lg-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-2">2.
                            ดำเนินการสร้างบิล</label>
                        <form action="{{ route('admin.invoices.insertInvoicesAll') }}" method="POST"
                            id="createAllInvoicesForm">
                            @csrf
                            <input type="hidden" name="billing_month" value="{{ $billing_month }}">
                            {{-- ซ่อนช่องวันที่ไว้รับค่าจาก Modal --}}
                            <input type="hidden" name="issue_date" id="hidden_issue_date_all">

                            <button type="button" class="btn btn-primary btn-sm w-100 fw-bold"
                                onclick="openCreateAllModal()">
                                <i class="bi bi-magic me-1"></i> สร้างบิลทุกห้อง (ห้องที่จดมิเตอร์แล้ว)
                            </button>
                        </form>
                    </div>

                    {{-- โซนที่ 3: แจ้งเตือนผู้เช่า --}}
                    <div class="col-xl-3 col-lg-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-2">3. การแจ้งเตือน</label>
                        <form action="{{ route('admin.invoice.sendInvoiceAll') }}" method="POST" id="sendAllInvoicesForm">
                            @csrf
                            <input type="hidden" name="billing_month" value="{{ $billing_month }}">
                            <button type="button" class="btn btn-info btn-sm w-100 fw-bold" onclick="confirmSendAll()">
                                <i class="bi bi-send me-1"></i> ส่งบิลทุกห้อง (ที่ยังไม่ส่ง)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Status Summary Bar --}}
        @php
            $allRooms = $groupedRooms->flatten();
            $countNoMeter = $allRooms->where('can_create_invoice', false)->count();
            $countNoBill = $allRooms->where('can_create_invoice', true)->whereNull('invoice_id')->count();
            $countWaitSend = $allRooms->where('invoice_status', 'กรุณาส่งบิล')->count();
            $countUnpaid = $allRooms->whereIn('invoice_status', ['ค้างชำระ', 'ชำระบางส่วน'])->count();
            $countPending = $allRooms->where('invoice_status', 'รอตรวจสอบ')->count();
            $countPaid = $allRooms->where('invoice_status', 'ชำระแล้ว')->count();
            @endphp
        <div class="row g-2 mb-3">
            <div class="col">
                {{-- แก้ไขตรงนี้ให้ส่งค่า search_status เป็น 'ยังไม่จดมิเตอร์' --}}
                <a href="{{ route('admin.invoices.show', ['billing_month' => $billing_month, 'search_status' => 'ยังไม่จดมิเตอร์']) }}"
                    class="card border-0 shadow-sm h-100 text-decoration-none {{ $searchStatus == 'ยังไม่จดมิเตอร์' ? 'border-danger border-2' : '' }}">
                    <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger bg-opacity-10"
                            style="width:36px;height:36px;">
                            <i class="bi bi-speedometer2 text-danger"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold text-dark lh-1">{{ $countNoMeter }}</div>
                            <small class="text-muted" style="font-size:.7rem;">ยังไม่จดมิเตอร์</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.invoices.show', ['billing_month' => $billing_month, 'search_status' => 'ยังไม่ได้สร้างบิล']) }}"
                    class="card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                            style="width:36px;height:36px;">
                            <i class="bi bi-file-earmark-plus text-primary"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold text-dark lh-1">{{ $countNoBill }}</div>
                            <small class="text-muted" style="font-size:.7rem;">รอสร้างบิล</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.invoices.show', ['billing_month' => $billing_month, 'search_status' => 'กรุณาส่งบิล']) }}"
                    class="card border-0 shadow-sm h-100 text-decoration-none {{ $searchStatus == 'กรุณาส่งบิล' ? 'border-info border-2' : '' }}">
                    <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-info bg-opacity-10"
                            style="width:36px;height:36px;">
                            <i class="bi bi-send text-info"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold text-dark lh-1">{{ $countWaitSend }}</div>
                            <small class="text-muted" style="font-size:.7rem;">รอส่งบิล</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.invoices.show', ['billing_month' => $billing_month, 'search_status' => 'ค้างชำระ']) }}"
                    class="card border-0 shadow-sm h-100 text-decoration-none {{ $searchStatus == 'ค้างชำระ' ? 'border-warning border-2' : '' }}">
                    <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning bg-opacity-10"
                            style="width:36px;height:36px;">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold text-dark lh-1">{{ $countUnpaid }}</div>
                            <small class="text-muted" style="font-size:.7rem;">ค้างชำระ</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.invoices.show', ['billing_month' => $billing_month, 'search_status' => 'รอตรวจสอบ']) }}"
                    class="card border-0 shadow-sm h-100 text-decoration-none {{ $searchStatus == 'รอตรวจสอบ' ? 'border-warning border-2' : '' }}">
                    <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning bg-opacity-10"
                            style="width:36px;height:36px;">
                            <i class="bi bi-hourglass-split text-warning"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold text-dark lh-1">{{ $countPending }}</div>
                            <small class="text-muted" style="font-size:.7rem;">รอตรวจสอบ</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="{{ route('admin.invoices.show', ['billing_month' => $billing_month, 'search_status' => 'ชำระแล้ว']) }}"
                    class="card border-0 shadow-sm h-100 text-decoration-none {{ $searchStatus == 'ชำระแล้ว' ? 'border-success border-2' : '' }}">
                    <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-success bg-opacity-10"
                            style="width:36px;height:36px;">
                            <i class="bi bi-check-circle text-success"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold text-dark lh-1">{{ $countPaid }}</div>
                            <small class="text-muted" style="font-size:.7rem;">ชำระแล้ว</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- 3. Filter + Report (รวมเป็น card เดียว) --}}
        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="card-body py-2 px-3">
                <form method="GET" action="{{ route('admin.invoices.show') }}"
                    class="d-flex flex-wrap align-items-center gap-2" id="searchFilterForm">
                    <input type="hidden" name="billing_month" value="{{ $billing_month }}">

                    <i class="bi bi-funnel text-primary"></i>

                    <select name="search_building" class="form-select form-select-sm" style="width:150px;"
                        onchange="this.form.submit()">
                        <option value="">ทุกอาคาร</option>
                        @foreach ($buildings as $b)
                            <option value="{{ $b->id }}" {{ $searchBuilding == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}</option>
                        @endforeach
                    </select>

                    <div class="input-group input-group-sm" style="width:140px;">
                        <span class="input-group-text bg-light border-0 text-muted px-2"><i
                                class="bi bi-door-closed"></i></span>
                        <input type="text" name="search_room" class="form-control bg-light border-0"
                            placeholder="เลขห้อง" value="{{ $searchRoom }}" onchange="this.form.submit()">
                    </div>

                    <div class="input-group input-group-sm" style="width:140px;">
                        <span class="input-group-text bg-light border-0 text-muted px-2">฿≥</span>
                        <input type="number" name="search_price" class="form-control bg-light border-0"
                            placeholder="ยอดขั้นต่ำ" value="{{ $searchPrice }}" onchange="this.form.submit()">
                    </div>

                    <select name="search_status" class="form-select form-select-sm" style="width:150px;"
                        onchange="this.form.submit()">
                        <option value="">ทุกสถานะ</option>
                        <option value="ยังไม่จดมิเตอร์" {{ $searchStatus == 'ยังไม่จดมิเตอร์' ? 'selected' : '' }}>
                            ยังไม่จดมิเตอร์</option>
                        <option value="ยังไม่ได้สร้างบิล" {{ $searchStatus == 'ยังไม่ได้สร้างบิล' ? 'selected' : '' }}>
                            ยังไม่ได้สร้างบิล</option>
                        <option value="กรุณาส่งบิล" {{ $searchStatus == 'กรุณาส่งบิล' ? 'selected' : '' }}>รอส่งบิล
                        </option>
                        <option value="ค้างชำระ" {{ $searchStatus == 'ค้างชำระ' ? 'selected' : '' }}>ค้างชำระ</option>
                        <option value="ชำระบางส่วน" {{ $searchStatus == 'ชำระบางส่วน' ? 'selected' : '' }}>ชำระบางส่วน</option>
                        <option value="รอตรวจสอบ" {{ $searchStatus == 'รอตรวจสอบ' ? 'selected' : '' }}>รอตรวจสอบ</option>
                        <option value="ชำระแล้ว" {{ $searchStatus == 'ชำระแล้ว' ? 'selected' : '' }}>ชำระแล้ว</option>
                    </select>

                    <a href="{{ route('admin.invoices.show', ['billing_month' => $billing_month]) }}"
                        class="btn btn-light btn-sm text-muted border px-2" title="ล้างค่า">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>

                    {{-- ปุ่มรายงาน --}}
                    <div class="d-flex gap-2 ms-auto">
                        <a href="{{ route('admin.invoices.collectionReport', ['billing_month' => $billing_month]) }}"
                            class="btn btn-outline-info btn-sm fw-bold px-3">
                            <i class="bi bi-bar-chart-line me-1"></i> รายงาน
                        </a>
                        <a href="{{ route('admin.invoices.print_invoice_details_all', ['billing_month' => $billing_month]) }}"
                            target="_blank" class="btn btn-outline-secondary btn-sm fw-bold px-3">
                            <i class="bi bi-printer me-1"></i> พิมพ์ทั้งหมด
                        </a>
                    </div>
                </form>
            </div>
        </div>
        {{-- 3. การแสดงผลแบบ Box แยกตามตึก (Action-Oriented UX) --}}
        @forelse ($groupedRooms as $buildingName => $roomsInBuilding)
            <div class="mb-5">
                <div class="d-flex align-items-center mb-4 pb-2 border-bottom border-secondary">
                    <i class="bi bi-building fs-4 text-primary me-2"></i>
                    <h4 class="fw-bold text-dark mb-0">{{ $buildingName }}</h4>
                    <span class="badge bg-light text-dark border ms-3">{{ $roomsInBuilding->count() }} ห้อง</span>
                </div>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
                    @foreach ($roomsInBuilding as $r)
                        {{-- Logic กำหนดสีและพฤติกรรมของกล่องตามสถานะ --}}
                        @php
                            $cardState = 'light'; // Default
                            $isClickable = false;
                            $detailUrl = '#';

                            if (!$r->can_create_invoice) {
                                $cardState = 'danger'; // แดง: ยังไม่จดมิเตอร์
                            } elseif (!$r->invoice_id) {
                                $cardState = 'primary'; // น้ำเงิน: จดแล้ว รอสร้างบิล
                            } elseif ($r->invoice_status == 'กรุณาส่งบิล') {
                                $cardState = 'info'; // ฟ้า: สร้างบิลแล้ว รอการส่ง (แยกออกมาใช้สี info)
                                $isClickable = true;
                                $detailUrl = route('admin.invoices.details', $r->invoice_id);
                            } elseif (in_array($r->invoice_status, ['ค้างชำระ', 'ชำระบางส่วน'])) {
                                $cardState = 'warning'; // เหลือง: ค้างจ่าย/จ่ายไม่ครบ
                                $isClickable = true;
                                $detailUrl = route('admin.invoices.details', $r->invoice_id);
                            } elseif ($r->invoice_status == 'ชำระแล้ว') {
                                $cardState = 'success'; // เขียว: จบงาน
                                $isClickable = true;
                                $detailUrl = route('admin.invoices.details', $r->invoice_id);
                            } elseif ($r->invoice_status == 'รอตรวจสอบ') {
                                $isClickable = true;
                                $detailUrl = route('admin.invoices.details', $r->invoice_id);
                            }
                        @endphp

                        <div class="col">
                            {{-- ทำให้ Card คลิกได้ถ้ามี URL --}}
                            <div class="card h-100 shadow-sm border-{{ $cardState }} border-2 position-relative"
                                style="transition: transform 0.2s, box-shadow 0.2s; cursor: {{ $isClickable ? 'pointer' : 'default' }};"
                                onmouseover="this.style.transform='translateY(-5px)'; this.classList.add('shadow');"
                                onmouseout="this.style.transform='translateY(0)'; this.classList.remove('shadow');"
                                onclick="{{ $isClickable ? "window.location.href='{$detailUrl}'" : '' }}">

                                {{-- Ribbon สถานะด้านบน (จัดการสีตัวอักษรให้เหมาะกับพื้นหลัง) --}}
                                <div
                                    class="bg-{{ $cardState }} text-{{ in_array($cardState, ['warning', 'info', 'light']) ? 'dark' : 'white' }} text-center py-1 fw-bold small rounded-top">
                                    {{ $r->invoice_status }}
                                </div>

                                <div class="card-body p-3 d-flex flex-column">
                                    {{-- เลขห้อง --}}
                                    <div class="text-center mb-3">
                                        <h3 class="fw-bold mb-0 text-dark">{{ $r->room_number }}</h3>
                                    </div>

                                    {{-- ยอดเงินเน้นๆ --}}
                                    <div class="text-center mb-3 p-2 bg-light rounded">
                                        <small class="text-muted d-block mb-1"
                                            style="font-size: 0.75rem;">ยอดเรียกเก็บสุทธิ</small>
                                        <h4 class="fw-bold mb-0 text-dark">
                                            {{ number_format($r->display_total, 2) }} <span
                                                class="fs-6 text-muted">฿</span>
                                        </h4>
                                    </div>

                                    {{-- Badge สถานะมิเตอร์ --}}
                                    <div class="text-center mb-3">
                                        <span
                                            class="badge bg-{{ $r->meter_color }} bg-opacity-10 text-{{ $r->meter_color }} border border-{{ $r->meter_color }} w-100 py-2">
                                            <i class="bi bi-speedometer2 me-1"></i> {{ $r->meter_status }}
                                        </span>
                                    </div>

                                    {{-- ปุ่ม Action ด้านล่าง (หยุด Event Bubble เพื่อไม่ให้เผลอกดกล่อง) --}}
                                    <div class="mt-auto pt-2 border-top">
                                        @if (!$r->can_create_invoice)
                                            <button type="button" class="btn btn-danger btn-sm w-100 fw-bold"
                                                onclick="event.stopPropagation(); window.location.href='{{ route('admin.meter_readings.insertForm', ['billing_month' => $billing_month, 'search_room' => $r->room_number]) }}'">
                                                <i class="bi bi-pencil-fill me-1"></i> จดมิเตอร์
                                            </button>
                                        @elseif(!$r->invoice_id)
                                            <form action="{{ route('admin.invoice.insertInvoiceOne') }}" method="POST"
                                                id="InsertOneInvoice_{{ $r->id }}">
                                                @csrf
                                                <input type="hidden" name="tenant_id" value="{{ $r->tenant_id }}">
                                                <input type="hidden" name="room_id" value="{{ $r->id }}">
                                                <input type="hidden" name="billing_month" value="{{ $billing_month }}">
                                                <input type="hidden" name="issue_date"
                                                    id="issue_date_{{ $r->id }}">

                                                <button type="button" class="btn btn-primary btn-sm w-100 fw-bold"
                                                    onclick="event.stopPropagation(); openCreateInvoiceModal('{{ $r->id }}', '{{ $r->room_number }}')">
                                                    <i class="bi bi-magic me-1"></i> สร้างบิล
                                                </button>
                                            </form>
                                        @elseif($r->invoice_status == 'กรุณาส่งบิล')
                                            <div class="d-flex gap-1 ">

                                                {{-- ฟอร์มสำหรับการส่งบิล --}}
                                                <form action="{{ route('admin.invoice.sendInvoiceOne') }}" method="POST"
                                                    id="sendInvoiceOne_{{ $r->id }}" class="w-75">
                                                    @csrf
                                                    <input type="hidden" name="invoice_id"
                                                        value="{{ $r->invoice_id }}">
                                                    <button type="button"
                                                        class="btn btn-info text-dark btn-sm w-100 fw-bold"
                                                        onclick="event.stopPropagation(); confirmSendInvoiceOne({{ $r->id }})">
                                                        <i class="bi bi-send me-1"></i> ส่งบิล
                                                    </button>
                                                </form>

                                                {{-- ฟอร์มสำหรับการลบบิล --}}
                                                <form
                                                    action="{{ route('admin.invoices.deleteInvoiceOne', $r->invoice_id) }}"
                                                    method="POST" id="delete-form-{{ $r->invoice_id }}" class="w-25">
                                                    @csrf
                                                    <button type="button" class="btn btn-outline-danger btn-sm w-100"
                                                        title="ลบบิล"
                                                        onclick="event.stopPropagation(); confirmDeleteInvoiceOne('{{ $r->invoice_id }}')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif($r->invoice_status == 'ค้างชำระ')
                                            <div class="d-flex justify-content-around text-center text-muted small pb-1">
                                                <i class="bi bi-hand-index-thumb"></i> คลิกเพื่อดูรายละเอียด
                                                <form
                                                    action="{{ route('admin.invoices.deleteInvoiceOne', $r->invoice_id) }}"
                                                    method="POST" id="delete-form-{{ $r->invoice_id }}" class="w-25">
                                                    @csrf
                                                    <button type="button" class="btn btn-outline-danger btn-sm w-100"
                                                        title="ลบบิล"
                                                        onclick="event.stopPropagation(); confirmDeleteInvoiceOne('{{ $r->invoice_id }}')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            {{-- กรณีค้างชำระ หรือ จ่ายแล้ว จะแสดงข้อความแทนปุ่ม --}}
                                            <div class="text-center text-muted small pb-1">
                                                <i class="bi bi-hand-index-thumb"></i> คลิกเพื่อดูรายละเอียด
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="col-12 mt-4 mb-5">
                <div class="card border-0 shadow-sm text-center py-5 rounded-3">
                    <div class="card-body py-5">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 80px; height: 80px;">
                            <i class="bi bi-inboxes text-secondary fs-1"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">ไม่พบข้อมูลที่ค้นหา</h4>
                        <p class="text-muted mb-4">
                            ไม่มีรายการใบแจ้งหนี้หรือห้องพักที่ตรงกับเงื่อนไขการค้นหาของคุณ<br>โปรดตรวจสอบเลขห้อง สถานะ
                            หรือยอดเงินอีกครั้ง</p>

                        <a href="{{ route('admin.invoices.show', ['billing_month' => $billing_month]) }}"
                            class="btn btn-primary px-4 fw-bold shadow-sm">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> ล้างตัวกรองทั้งหมด
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
    {{-- Modal จดมิเตอร์ --}}
    <div class="modal fade" id="meterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">จดมิเตอร์ห้อง <span id="modalRoomNumber"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.invoice.insertInvoiceMeterReadingOne') }}" method="POST"
                    id="meterModalForm">
                    @csrf
                    <input type="hidden" name="room_id" id="modal_room_id">
                    <input type="hidden" name="tenant_id" id="modal_tenant_id">
                    <input type="hidden" name="room_number" id="modal_room_number_hidden">
                    <input type="hidden" name="billing_month" value="{{ $billing_month }}">

                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <div class="mb-3">
                                <label for="" class="form-label">เลือกวันที่จดมิเตอร์</label>
                                <input type="date" class="form-control" name="reading_date" id="reading_date"
                                    value="{{ date('Y-m-t', strtotime($billing_month)) }}" required>
                            </div>
                            <label class="fw-bold text-info mb-2"><i class="bi bi-droplet-fill"></i> มิเตอร์น้ำ</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted">เลขครั้งก่อน (ยกมา)</small>
                                    <input type="number" name="water_prev" id="water_prev" class="form-control fw-bold"
                                        placeholder="ระบุเลขเริ่มต้น" oninput="calculateModalUsed()">
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">เลขครั้งนี้</small>
                                    <input type="number" name="water_current" id="water_current"
                                        class="form-control border-info fw-bold" required oninput="calculateModalUsed()">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fw-bold text-danger mb-2"><i class="bi bi-lightning-charge-fill"></i>
                                มิเตอร์ไฟฟ้า</label>
                            <div class="row g-2 mt-2">
                                <div class="col-6">
                                    <small class="text-muted">เลขครั้งก่อน (ยกมา)</small>
                                    <input type="number" name="electric_prev" id="electric_prev"
                                        class="form-control fw-bold" placeholder="ระบุเลขเริ่มต้น" required
                                        oninput="calculateModalUsed()">
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">เลขครั้งนี้</small>
                                    <input type="number" name="electric_current" id="electric_current"
                                        class="form-control border-danger fw-bold" required
                                        oninput="calculateModalUsed()">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-secondary mb-0 text-center py-2" id="usageSummary">
                            หน่วยที่ใช้: น้ำ 0 | ไฟ 0
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="button" class="btn btn-primary px-4"
                            onclick="checkAndSubmit()">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Modal สร้างบิลรายห้อง --}}
    <div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-calendar-check me-2"></i>ระบุวันที่ออกบิล</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="mb-3 small text-muted">ห้อง <span id="modal_room_display" class="fw-bold text-dark"></span>
                        | รอบเดือน {{ $thai_billing_month }}</p>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase">วันที่ออกใบแจ้งหนี้</label>
                        <input type="date" id="modal_issue_date"
                            class="form-control form-control-lg border-2 text-center"
                            value="{{ date('Y-m-t', strtotime($billing_month)) }}">
                    </div>
                    <input type="hidden" id="current_room_id"> {{-- สำหรับเก็บ ID ห้องพักชั่วคราว --}}

                    <button type="button" class="btn btn-primary w-100 fw-bold py-2" onclick="confirmFromModal()">
                        ดำเนินการต่อ <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal สร้างบิลทั้งหมด --}}
    <div class="modal fade" id="createAllInvoicesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-calendar-check me-2"></i>ระบุวันที่ออกบิลทั้งหมด</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="mb-3 small text-muted">รอบเดือน {{ $thai_billing_month }}</p>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary text-uppercase">กรุณาเลือกวันที่ <span
                                class="text-danger">*</span></label>
                        <input type="date" id="modal_issue_date_all"
                            class="form-control form-control-lg border-2 text-center text-primary fw-bold"
                            value="{{ date('Y-m-t', strtotime($billing_month)) }}" required>
                    </div>
                    <button type="button" class="btn btn-primary w-100 fw-bold py-2 shadow-sm"
                        onclick="confirmCreateAllFromModal()">
                        ดำเนินการต่อ <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
 
        // 🌟 ฟังก์ชันเปิด Modal สร้างบิลทั้งหมด
        function openCreateAllModal() {
            var myModal = new bootstrap.Modal(document.getElementById('createAllInvoicesModal'));
            myModal.show();
        }

        // 🌟 ฟังก์ชันเมื่อกดยืนยันใน Modal สร้างบิลทั้งหมด
        function confirmCreateAllFromModal() {
            // ดึงวันที่จาก Modal
            const issueDate = document.getElementById('modal_issue_date_all').value;

            if (!issueDate) {
                Swal.fire('กรุณาเลือกวันที่', 'โปรดระบุวันที่ออกใบแจ้งหนี้ก่อนดำเนินการ', 'warning');
                return;
            }

            // ปิด Modal ก่อนโชว์ SweetAlert เพื่อไม่ให้ซ้อนกัน
            bootstrap.Modal.getInstance(document.getElementById('createAllInvoicesModal')).hide();

            // แปลงรูปแบบวันที่โชว์ใน SweetAlert ให้ดูง่าย
            const displayDate = new Date(issueDate).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            Swal.fire({
                title: 'ยืนยันสร้างบิลทั้งหมด?',
                html: `ระบบจะสร้างบิลรอบเดือน <b>{{ $thai_billing_month }}</b><br>โดยระบุวันที่ออกบิลเป็น: <span class="text-primary fw-bold">${displayDate}</span>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง, เริ่มสร้างบิล',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // นำวันที่จาก Modal ไปใส่ในฟอร์มหลักที่ซ่อนไว้
                    document.getElementById('hidden_issue_date_all').value = issueDate;

                    Swal.fire({
                        title: 'กำลังสร้างบิล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    // สั่ง Submit ฟอร์มหลัก
                    document.getElementById('createAllInvoicesForm').submit();
                }
            });
        }

        function confirmSendInvoiceOne(id) {

            // 2. ค้นหาฟอร์มที่ต้องการส่ง
            const form = document.getElementById('sendInvoiceOne_' + id);

            Swal.fire({
                title: 'ยืนยันการส่งใบบิลค่าเช่า ?',
                text: "ส่งบิลให้กับผู้เช่า ",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังดำเนินการ...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    form.submit();
                }
            });
        }

        function confirmSendAll() {
            Swal.fire({
                title: 'ยืนยันการส่งบิลทั้งหมด?',
                text: "ระบบจะเปลี่ยนสถานะบิลรอบเดือนนี้เป็น 'ค้างชำระ' เพื่อให้ผู้เช่ารับทราบ",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754', // สีเขียว Success
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง, ส่งบิลเลย',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังดำเนินการ...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('sendAllInvoicesForm').submit();
                }
            });
        }

        function confirmDeleteInvoiceOne(invoiceId) {
            Swal.fire({
                title: 'ยืนยันการลบบิล?',
                text: "รายการค่าใช้จ่ายและข้อมูลในบิลนี้จะถูกลบออกถาวร!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // แสดง Loading ระหว่างรอ
                    Swal.fire({
                        title: 'กำลังลบข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    // สั่ง Submit ฟอร์มที่มี ID ตรงกัน
                    document.getElementById('delete-form-' + invoiceId).submit();
                }
            });
        }

        function openCreateInvoiceModal(id, roomNumber) {
            document.getElementById('current_room_id').value = id;
            document.getElementById('modal_room_display').innerText = roomNumber;

            // ตั้งค่า Default Date เป็นวันนี้ หรือตามวันที่ในช่อง Input หลักด้านบน (ถ้ามี)
            document.getElementById('modal_issue_date').value = "{{ date('Y-m-t', strtotime($billing_month)) }}";

            var myModal = new bootstrap.Modal(document.getElementById('createInvoiceModal'));
            myModal.show();
        }

        // 2. ฟังก์ชันตรวจสอบและเรียก SweetAlert ยืนยัน (Confirm)
        function confirmFromModal() {
            const id = document.getElementById('current_room_id').value;
            const selectedDate = document.getElementById('modal_issue_date').value;
            const roomNumber = document.getElementById('modal_room_display').innerText;

            if (!selectedDate) {
                Swal.fire('กรุณาเลือกวันที่', 'โปรดระบุวันที่ต้องการออกใบแจ้งหนี้', 'warning');
                return;
            }

            // ปิด Modal ก่อนแสดงความยินยัน
            bootstrap.Modal.getInstance(document.getElementById('createInvoiceModal')).hide();

            // แปลงวันที่โชว์แบบไทย
            const dateThai = new Date(selectedDate).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            Swal.fire({
                title: 'ยืนยันสร้างใบแจ้งหนี้?',
                html: `ห้อง <b>${roomNumber}</b><br>วันที่ออกบิล: <span class="text-primary">${dateThai}</span>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'ตกลง, สร้างบิล',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // ส่งค่าไปยังฟอร์มตัวจริง
                    const form = document.getElementById('InsertOneInvoice_' + id);
                    form.querySelector('input[name="issue_date"]').value = selectedDate;

                    Swal.fire({
                        title: 'กำลังสร้างบิล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    form.submit();
                }
            });
        }
        // ทำการจำตำแหน่ง y
        // 1. จำตำแหน่งหน้าจอก่อนที่เว็บจะโหลดใหม่ (ก่อนฟอร์มถูกส่ง)
        window.addEventListener("beforeunload", function(e) {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        });

        // 2. เมื่อเว็บโหลดเสร็จ ให้เลื่อนกลับมาที่ตำแหน่งเดิมทันที
        document.addEventListener("DOMContentLoaded", function(event) {
            let scrollpos = sessionStorage.getItem('scrollPosition');
            if (scrollpos) {
                // เลื่อนหน้าจอกลับไปตำแหน่งที่บันทึกไว้
                window.scrollTo({
                    top: parseInt(scrollpos),
                    behavior: "instant" // ใช้ instant เพื่อไม่ให้เห็นแอนิเมชันการเลื่อน (ดูเนียนกว่า)
                });
                // ลบค่าทิ้ง เพื่อไม่ให้กระทบการเข้าหน้านี้ในครั้งถัดไป
                sessionStorage.removeItem('scrollPosition');
            }
        });
    </script>
@endpush
