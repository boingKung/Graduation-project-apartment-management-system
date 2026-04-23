@extends('admin.layout')
@section('title', 'รายการจองห้องพัก')
@section('content')

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-0">รายการจองห้องพัก</h3>
                    <p class="text-muted small">ตรวจสอบรายละเอียดการจอง และสลิปมัดจำ</p>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.registrations.show') }}">
            <div class="card border-0 shadow-sm mb-4">
                <div
                    class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i
                            class="bi bi-clock-history text-warning me-2"></i>ผู้เช่าที่รอการดำเนินการ</h6>
                    <a href="{{ route('admin.registrations.show') }}"
                        class="btn btn-light btn-sm text-muted border shadow-sm px-3">
                        <i class="bi bi-eraser-fill me-1"></i> ล้างค่า
                    </a>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small">
                                <tr>
                                    <th class="text-center" style="width: 15%;">วันที่จอง</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>เบอร์โทรศัพท์</th>
                                    <th class="text-end">ยอดมัดจำ (บาท)</th>
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                                {{-- Inline Filter --}}
                                <tr class="bg-white border-bottom shadow-sm">
                                    <td></td>
                                    <td class="py-2 px-1"><input type="text" name="filter_name"
                                            class="form-control form-control-sm bg-light border-0" placeholder="ค้นหาชื่อ"
                                            value="{{ request('filter_name') }}"
                                            onkeydown="if(event.key === 'Enter') this.form.submit();"></td>
                                    <td class="py-2 px-1"><input type="text" name="filter_phone"
                                            class="form-control form-control-sm bg-light border-0" placeholder="เบอร์โทร"
                                            value="{{ request('filter_phone') }}"
                                            onkeydown="if(event.key === 'Enter') this.form.submit();"></td>
                                    <td></td>
                                    <td class="py-2 px-1">
                                        <select name="filter_status"
                                            class="form-select form-select-sm bg-light border-0 fw-bold text-center px-1"
                                            onchange="this.form.submit()">
                                            <option value="">ทั้งหมด</option>
                                            <option value="รออนุมัติ"
                                                {{ request('filter_status') == 'รออนุมัติ' ? 'selected' : '' }}>รออนุมัติ
                                            </option>
                                            <option value="ยกเลิกการจอง"
                                                {{ request('filter_status') == 'ยกเลิกการจอง' ? 'selected' : '' }}>
                                                ยกเลิกการจอง</option>
                                        </select>
                                    </td>
                                    <td><button type="submit" class="d-none"></button></td>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($registrations as $item)
                                    <tr class="{{ $item->status === 'ยกเลิกการจอง' ? 'table-danger text-muted' : '' }}">
                                        <td class="text-center small">{{ $item->thai_created_at }}</td>
                                        <td class="fw-semibold">
                                            {{ $item->first_name }} {{ $item->last_name }}
                                            @if ($item->line_id)
                                                <i class="bi bi-line text-success ms-1" title="ผูกไลน์แล้ว"></i>
                                            @endif
                                        </td>
                                        <td>{{ $item->phone }}</td>
                                        <td
                                            class="text-end fw-bold {{ $item->status === 'ยกเลิกการจอง' ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($item->deposit_amount, 2) }}
                                        </td>
                                        <td class="text-center">
                                            @if ($item->status === 'รออนุมัติ')
                                                <span
                                                    class="badge bg-warning text-dark px-3 py-2 rounded-pill">รออนุมัติ</span>
                                            @else
                                                <span class="badge bg-danger px-3 py-2 rounded-pill">ยกเลิกการจอง</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button type="button" class="btn btn-info btn-sm px-3 text-white shadow-sm"
                                                    title="ดูข้อมูลทั้งหมด"
                                                    onclick='openViewModal(@json($item))'>
                                                    <i class="bi bi-person-lines-fill me-1"></i> ดูข้อมูล
                                                </button>

                                                @if ($item->status == 'รออนุมัติ')
                                                    <button type="button" class="btn btn-outline-danger btn-sm px-2"
                                                        title="ยกเลิกการจอง"
                                                        onclick="confirmCancel({{ $item->id }}, '{{ $item->first_name }}')">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">ไม่พบข้อมูลการจอง</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="p-3 bg-white border-top">{{ $registrations->links() }}</div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Form ซ่อนสำหรับกดยกเลิก --}}
    <form id="cancelForm" method="POST" class="d-none">
        @csrf @method('PUT')
    </form>

    {{-- 🌟 MODAL: ดูข้อมูลแบบละเอียด (VIEW) อัปเดตใหม่ --}}
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-xl"> {{-- 🌟 ขยายเป็น modal-xl ให้กว้างขึ้นเพื่อรองรับข้อมูลใหม่ --}}
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-badge-fill me-2"></i>ข้อมูลผู้จองแบบละเอียด</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        {{-- ส่วนที่ 1: ข้อมูลส่วนตัว (อัปเดตฟิลด์ใหม่) --}}
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold text-info border-bottom pb-2 mb-3"><i class="bi bi-person-fill me-1"></i>ข้อมูลส่วนตัว</h6>
                                    <div class="row g-2">
                                        <div class="col-8">
                                            <small class="text-muted d-block">ชื่อ-นามสกุล</small>
                                            <span class="fw-bold fs-6 text-dark" id="v_name"></span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">อายุ</small>
                                            <span class="fw-semibold text-dark" id="v_age"></span>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <small class="text-muted d-block">เลขบัตรประชาชน</small>
                                            <span class="fw-semibold text-dark" id="v_idcard"></span>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <small class="text-muted d-block">วันที่ออกบัตร</small>
                                            <span class="fw-semibold text-dark" id="v_idcard_issue"></span>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <small class="text-muted d-block">วันหมดอายุบัตร</small>
                                            <span class="fw-semibold text-dark" id="v_idcard_expiry"></span>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <small class="text-muted d-block">สถานที่ออกบัตร</small>
                                            <span class="fw-semibold text-dark" id="v_idcard_place"></span>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <small class="text-muted d-block">จังหวัดที่ออกบัตร</small>
                                            <span class="fw-semibold text-dark" id="v_idcard_province"></span>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <small class="text-muted d-block">สถานที่ทำงาน</small>
                                            <span class="fw-semibold text-dark" id="v_workplace"></span>
                                        </div>
                                        <div class="col-12 mt-3"><hr class="my-1 border-light"></div>
                                        <div class="col-4 mt-2">
                                            <small class="text-muted d-block">เบอร์โทรศัพท์</small>
                                            <span class="fw-semibold text-dark" id="v_phone"></span>
                                        </div>
                                        <div class="col-4 mt-2">
                                            <small class="text-muted d-block">ผู้อยู่อาศัย</small>
                                            <span class="fw-semibold text-dark" id="v_resident"></span>
                                        </div>
                                        <div class="col-4 mt-2">
                                            <small class="text-muted d-block">ที่จอดรถ</small>
                                            <span class="fw-bold" id="v_parking"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ส่วนที่ 2: ที่อยู่ตามทะเบียนบ้าน (อัปเดตฟิลด์ใหม่) --}}
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold text-info border-bottom pb-2 mb-3"><i class="bi bi-house-door-fill me-1"></i>ที่อยู่ตามทะเบียนบ้าน</h6>
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <small class="text-muted d-block">เลขที่</small>
                                            <span class="fw-semibold text-dark" id="v_address_no"></span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">หมู่ที่</small>
                                            <span class="fw-semibold text-dark" id="v_moo"></span>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted d-block">ตรอก/ซอย</small>
                                            <span class="fw-semibold text-dark" id="v_alley"></span>
                                        </div>
                                        <div class="col-12 mt-3">
                                            <small class="text-muted d-block">ถนน</small>
                                            <span class="fw-semibold text-dark" id="v_street"></span>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <small class="text-muted d-block">ตำบล/แขวง</small>
                                            <span class="fw-semibold text-dark" id="v_sub_district"></span>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <small class="text-muted d-block">อำเภอ/เขต</small>
                                            <span class="fw-semibold text-dark" id="v_district"></span>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <small class="text-muted d-block">จังหวัด</small>
                                            <span class="fw-semibold text-dark" id="v_province"></span>
                                        </div>
                                        <div class="col-6 mt-3">
                                            <small class="text-muted d-block">รหัสไปรษณีย์</small>
                                            <span class="fw-semibold text-dark" id="v_postal"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ส่วนที่ 3: สลิปเงินมัดจำ --}}
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i
                                            class="bi bi-cash-coin me-1"></i>ข้อมูลการชำระเงินมัดจำ</h6>
                                    <div class="row align-items-center">
                                        <div class="col-md-4 text-center border-end">
                                            <div class="mb-3">
                                                <small class="text-muted d-block">ยอดเงินที่ชำระ (บาท)</small>
                                                <span class="fs-3 fw-bold text-success" id="v_deposit"></span>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">ช่องทางชำระเงิน</small>
                                                <span class="badge bg-secondary px-3 py-2" id="v_payment_method"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-8 text-center">
                                            <small class="text-muted d-block mb-2">หลักฐานการโอนเงิน (สลิป)</small>
                                            <div id="v_slip_container"
                                                class="bg-white border rounded p-2 d-inline-block shadow-sm"
                                                style="min-height: 150px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-white">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill shadow-sm"
                        data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // ดูข้อมูลแบบละเอียด
        function openViewModal(data) {
            // ข้อมูลส่วนตัว
            document.getElementById('v_name').innerText = data.first_name + ' ' + data.last_name;
            document.getElementById('v_age').innerText = data.age ? data.age + ' ปี' : '-';
            document.getElementById('v_idcard').innerText = data.id_card || '-';
            
            // 🌟 เปลี่ยนมาใช้ตัวแปรภาษาไทยที่แปลงมาจาก Controller แล้ว
            document.getElementById('v_idcard_issue').innerText = data.thai_id_card_issue;
            document.getElementById('v_idcard_expiry').innerText = data.thai_id_card_expiry;
            document.getElementById('v_idcard_place').innerText = data.id_card_issue_place || '-';
            document.getElementById('v_idcard_province').innerText = data.id_card_issue_province || '-';
            
            // ข้อมูลที่ทำงาน และการติดต่อ
            document.getElementById('v_workplace').innerText = data.workplace || '-';
            document.getElementById('v_phone').innerText = data.phone || '-';
            document.getElementById('v_resident').innerText = (data.resident_count || 1) + ' คน';
            document.getElementById('v_parking').innerHTML = data.has_parking ?
                '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>ต้องการ</span>' :
                '<span class="text-muted">ไม่ต้องการ</span>';

            // ข้อมูลที่อยู่
            document.getElementById('v_address_no').innerText = data.address_no || '-';
            document.getElementById('v_moo').innerText = data.moo || '-';
            document.getElementById('v_alley').innerText = data.alley || '-';
            document.getElementById('v_street').innerText = data.street || '-';
            document.getElementById('v_sub_district').innerText = data.sub_district || '-';
            document.getElementById('v_district').innerText = data.district || '-';
            document.getElementById('v_province').innerText = data.province || '-';
            document.getElementById('v_postal').innerText = data.postal_code || '-';

            // ข้อมูลมัดจำ
            document.getElementById('v_deposit').innerText = Number(data.deposit_amount).toLocaleString('th-TH', {
                minimumFractionDigits: 2
            });
            document.getElementById('v_payment_method').innerText = data.deposit_payment_method || 'ไม่ได้ระบุ';

            // รูปสลิป
            let slipContainer = document.getElementById('v_slip_container');
            if (data.deposit_slip) {
                slipContainer.innerHTML =
                    `<a href="/storage/${data.deposit_slip}" target="_blank" title="คลิกเพื่อดูรูปเต็ม"><img src="/storage/${data.deposit_slip}" class="img-fluid rounded" style="max-height: 250px; object-fit: contain; transition: transform .2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'"></a>`;
            } else {
                slipContainer.innerHTML =
                    `<div class="text-muted py-4"><i class="bi bi-image fs-1 d-block mb-2 text-black-50"></i>ไม่มีรูปสลิปแนบมา</div>`;
            }

            new bootstrap.Modal(document.getElementById('viewModal')).show();
        }

        // กดยกเลิกการจอง
        function confirmCancel(id, name) {
            Swal.fire({
                title: 'ยกเลิกการจอง?',
                html: `คุณต้องการยกเลิกการจองของ <b>"${name}"</b> ใช่หรือไม่?<br><small class="text-danger">การเชื่อมต่อ LINE จะถูกยกเลิกด้วย แต่ประวัติยังคงอยู่</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยันการยกเลิก',
                cancelButtonText: 'ปิด'
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = "{{ route('admin.registrations.cancel', ':id') }}";
                    url = url.replace(':id', id);
                    let form = document.getElementById('cancelForm');
                    form.action = url;
                    form.submit();
                }
            });
        }
    </script>
@endpush