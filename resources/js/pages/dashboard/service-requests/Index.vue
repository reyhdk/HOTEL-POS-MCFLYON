<template>
  <div class="card card-flush shadow-sm">
    <div class="card-header mt-5">
      <h3 class="card-title">Manajemen Permintaan Layanan</h3>
      <div class="card-toolbar">
        <select v-model="statusFilter" @change="fetchServiceRequests(1)" class="form-select form-select-solid w-200px">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    <div class="card-body pt-0">
      <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
          <thead>
            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
              <th>Kamar</th>
              <th>Nama Tamu</th>
              <th>Permintaan</th>
              <th>Jumlah</th>
              <th>Catatan</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody class="fw-semibold text-gray-600">
            <tr v-if="isLoading">
              <td colspan="7" class="text-center py-10">Memuat data...</td>
            </tr>
            <tr v-else-if="requests.length === 0">
              <td colspan="7" class="text-center py-10">Tidak ada permintaan layanan.</td>
            </tr>
            <tr v-for="req in requests" :key="req.id">
              <td><span class="badge badge-light-primary">{{ req.room.room_number }}</span></td>
              <td>{{ req.user.name }}</td>
              <td>{{ req.service_name }}</td>
              <td>{{ req.quantity }}</td>
              <td>{{ req.notes || '-' }}</td>
              <td>
                <span :class="getStatusBadge(req.status)">{{ req.status }}</span>
              </td>
              <td class="text-end">
                <button class="btn btn-sm btn-light-primary" @click="openUpdateStatusModal(req)">
                  Ubah Status
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-5">
          <span class="fs-7 fw-semibold text-muted">
            Menampilkan {{ pagination.from }} - {{ pagination.to }} dari {{ pagination.total }} data
          </span>
          <ul class="pagination">
            <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
              <a class="page-link" href="#" @click.prevent="fetchServiceRequests(pagination.current_page - 1)">‹</a>
            </li>
            <li class="page-item" v-for="page in pagination.last_page" :key="page" :class="{ active: page === pagination.current_page }">
               <a class="page-link" href="#" @click.prevent="fetchServiceRequests(page)">{{ page }}</a>
            </li>
            <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
              <a class="page-link" href="#" @click.prevent="fetchServiceRequests(pagination.current_page + 1)">›</a>
            </li>
          </ul>
        </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

// --- INTERFACES ---
interface ServiceRequest {
  id: number;
  room: { room_number: string };
  user: { name: string };
  service_name: string;
  quantity: number;
  notes: string | null;
  status: 'pending' | 'processing' | 'completed' | 'cancelled';
}

interface Pagination {
  current_page: number;
  last_page: number;
  from: number;
  to: number;
  total: number;
  prev_page_url: string | null;
  next_page_url: string | null;
}

// --- STATE ---
const requests = ref<ServiceRequest[]>([]);
const isLoading = ref(true);
const statusFilter = ref('');

// ▼▼▼ PERBAIKAN DI SINI ▼▼▼
// Berikan nilai awal yang lengkap untuk objek pagination
const pagination = ref<Pagination>({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
  prev_page_url: null,
  next_page_url: null,
});
// ▲▲▲ -------------------- ▲▲▲


// --- API FUNCTIONS ---
const fetchServiceRequests = async (page = 1) => {
  isLoading.value = true;
  try {
    const { data } = await ApiService.get(`/admin/service-requests?page=${page}&status=${statusFilter.value}`);
    requests.value = data.data;
    pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        from: data.from,
        to: data.to,
        total: data.total,
        prev_page_url: data.prev_page_url,
        next_page_url: data.next_page_url,
    };
  } catch (error) {
    Swal.fire("Error", "Gagal memuat data permintaan layanan.", "error");
  } finally {
    isLoading.value = false;
  }
};

const openUpdateStatusModal = async (req: ServiceRequest) => {
    const { value: newStatus } = await Swal.fire({
        title: 'Ubah Status Permintaan',
        input: 'select',
        inputOptions: {
            pending: 'Pending',
            processing: 'Processing',
            completed: 'Completed',
            cancelled: 'Cancelled'
        },
        inputValue: req.status,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
    });

    if (newStatus) {
        try {
            await ApiService.patch(`/admin/service-requests/${req.id}/status`, { status: newStatus });
            Swal.fire('Berhasil!', 'Status berhasil diperbarui.', 'success');
            fetchServiceRequests(pagination.value.current_page); // Refresh tabel di halaman saat ini
        } catch (error) {
            Swal.fire('Gagal', 'Terjadi kesalahan saat memperbarui status.', 'error');
        }
    }
};


// --- HELPER FUNCTIONS ---
const getStatusBadge = (status: string) => {
  const statusMap = {
    pending: "badge-light-warning",
    processing: "badge-light-info",
    completed: "badge-light-success",
    cancelled: "badge-light-danger",
  };
  return `badge ${statusMap[status] || "badge-light-secondary"}`;
};

// --- LIFECYCLE HOOK ---
onMounted(fetchServiceRequests);
</script>