<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="px-4 py-3" style="width: 80px;">ลำดับ</th>
                    <th class="py-3">ชื่อหมวดหมู่</th>
                    <th class="py-3 text-end px-4">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $index => $item)
                <tr>
                    <td class="px-4 fw-bold text-muted">{{ $index + 1 }}</td>
                    <td><span class="fw-bold text-dark">{{ $item->name }}</span></td>
                    <td class="text-end px-4">
                        <div class="btn-group">
                            <button class="btn btn-outline-warning btn-sm" onclick="openEditModal({{ $item->id }}, '{{ $item->name }}', {{ $item->type_id }})">
                                <i class="bi bi-pencil"></i> แก้ไข
                            </button>
                            {{-- <form action="{{ route('admin.accounting_categories.delete', $item->id) }}" method="POST" id="delete-form-{{ $item->id }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete({{ $item->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form> --}}
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-4 text-muted small">ยังไม่มีข้อมูล</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>