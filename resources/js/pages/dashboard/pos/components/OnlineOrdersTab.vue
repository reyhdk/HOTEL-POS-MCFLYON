<template>
  <div class="d-flex flex-column h-100">
    <!-- Tambahkan align-items-md-center disini -->
    <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 pb-4 border-bottom mb-4">
      <div class="position-relative flex-grow-1">
        <i class="bi bi-search position-absolute top-50 ms-4 translate-middle-y text-gray-400 fs-6"></i>
        <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 rounded-pill border-0 h-45px bg-light" placeholder="Cari ID Pesanan, Tamu atau No. Kamar..." />
      </div>
      <div class="w-100 w-md-200px">
        <el-select v-model="statusFilter" placeholder="Semua Status" class="w-100 custom-el-select h-45px" clearable>
          <template v-slot:prefix><i class="bi bi-filter-circle text-orange fs-5"></i></template>
          <el-option label="Pending" value="pending" />
          <el-option label="Processing" value="processing" />
          <el-option label="Paid" value="paid" />
          <el-option label="Completed" value="completed" />
          <el-option label="Cancelled" value="cancelled" />
        </el-select>
      </div>
      <div class="d-flex gap-2">
        <button @click="refreshOrders" class="btn btn-light-primary btn-icon w-45px h-45px rounded-3 shadow-sm hover-elevate-up flex-shrink-0" title="Refresh Data" :disabled="loading">
          <i class="bi bi-arrow-clockwise fs-2" :class="{ 'spin': loading }"></i>
        </button>
      </div>
    </div>

    <!-- Summary Stats (Hanya menghitung pesanan Kamar) -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card bg-light-warning border-0 rounded-3 p-3">
          <div class="d-flex align-items-center">
            <div class="symbol symbol-40px symbol-circle bg-white bg-opacity-25 me-3">
              <i class="bi bi-clock-history text-warning fs-3"></i>
            </div>
            <div>
              <span class="text-gray-600 fs-8 fw-bold d-block">Pending</span>
              <span class="text-gray-900 fw-bolder fs-4">{{ pendingCount }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-light-primary border-0 rounded-3 p-3">
          <div class="d-flex align-items-center">
            <div class="symbol symbol-40px symbol-circle bg-white bg-opacity-25 me-3">
              <i class="bi bi-gear text-primary fs-3"></i>
            </div>
            <div>
              <span class="text-gray-600 fs-8 fw-bold d-block">Processing</span>
              <span class="text-gray-900 fw-bolder fs-4">{{ processingCount }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-light-success border-0 rounded-3 p-3">
          <div class="d-flex align-items-center">
            <div class="symbol symbol-40px symbol-circle bg-white bg-opacity-25 me-3">
              <i class="bi bi-check-circle text-success fs-3"></i>
            </div>
            <div>
              <span class="text-gray-600 fs-8 fw-bold d-block">Completed</span>
              <span class="text-gray-900 fw-bolder fs-4">{{ completedCount }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-light-danger border-0 rounded-3 p-3">
          <div class="d-flex align-items-center">
            <div class="symbol symbol-40px symbol-circle bg-white bg-opacity-25 me-3">
              <i class="bi bi-x-circle text-danger fs-3"></i>
            </div>
            <div>
              <span class="text-gray-600 fs-8 fw-bold d-block">Cancelled</span>
              <span class="text-gray-900 fw-bolder fs-4">{{ cancelledCount }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-grow-1 d-flex flex-column custom-scroll overflow-auto">
      <div v-if="loading" class="text-center py-20 anim-fade-in">
         <div class="spinner-border text-orange mb-3" role="status"></div>
         <p class="text-gray-500 fw-semibold">Memperbarui data pesanan kamar...</p>
      </div>

      <div v-else-if="filteredOrders.length === 0" class="d-flex flex-column align-items-center justify-content-center py-20 bg-light rounded-4 border border-dashed border-gray-300 mx-5 flex-grow-1">
         <i class="bi bi-door-open fs-3x text-gray-300 mb-4"></i>
         <h4 class="text-gray-600 fw-bold">Belum ada pesanan dari kamar</h4>
         <p class="text-gray-400">Pesanan khusus dari kamar tamu akan muncul di sini.</p>
      </div>

      <div v-else class="flex-grow-1 d-flex flex-column anim-fade-in">
         <div class="table-responsive">
             <table class="table align-middle table-row-dashed fs-6 gy-5 table-hover mb-0">
             <thead>
                 <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0 bg-light rounded-3">
                     <th class="ps-4 rounded-start-3">ID Order</th>
                     <th>Kamar / Tamu</th>
                     <th>Detail Pesanan</th>
                     <th>Total</th>
                     <th>Status</th>
                     <th>Tanggal</th>
                     <th class="text-end pe-4 rounded-end-3">Aksi</th>
                 </tr>
             </thead>
             <tbody>
                 <tr v-for="order in paginatedOrders" :key="order.id" class="transition-all hover-bg-light">
                     <td class="ps-4">
                        <div class="d-flex flex-column">
                            <span class="fw-bolder text-gray-800">#{{ order.id }}</span>
                            <span class="text-muted fs-8">Order ID: {{ order.order_code || order.id }}</span>
                        </div>
                     </td>
                     <td>
                         <div class="d-flex flex-column">
                         <span class="text-orange fw-bolder fs-6"><i class="bi bi-door-open me-1"></i>Kamar {{ order.room?.room_number }}</span>
                         <span class="text-muted fs-8">{{ order.guest?.name || order.user?.name || 'Tamu' }}</span>
                         </div>
                     </td>
                     <td>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-light-primary fw-bold me-2">{{ order.items?.length }} Item</span>
                            <button @click="viewOrderItems(order)" class="btn btn-icon btn-xs btn-light-info">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                     </td>
                     <td><span class="text-orange fw-bolder">{{ formatCurrency(order.total_price) }}</span></td>
                     <td>
                        <span :class="getStatusClass(order.status)" class="badge fw-bold px-3 py-2 d-inline-flex align-items-center">
                            <i :class="getStatusIcon(order.status)" class="me-1"></i>
                            {{ getStatusLabel(order.status) }}
                        </span>
                     </td>
                     <td>
                        <div class="d-flex flex-column">
                            <span class="fs-8 fw-bold">{{ formatDate(order.created_at) }}</span>
                            <span class="fs-9 text-muted">{{ formatTime(order.created_at) }}</span>
                        </div>
                     </td>
                     <td class="text-end pe-4">
                         <button 
                             @click="$emit('import-order', order)" 
                             class="btn btn-icon btn-light-success btn-sm rounded-circle me-2 shadow-sm" 
                             :disabled="['processing', 'completed', 'cancelled'].includes(order.status)"
                             title="Proses Pesanan"
                         >
                             <i class="bi bi-box-seam"></i>
                         </button>
                         <button @click="viewOrderDetails(order)" class="btn btn-icon btn-light-info btn-sm rounded-circle shadow-sm me-2" title="Lihat Detail">
                             <i class="bi bi-eye"></i>
                         </button>
                         <button v-if="order.status === 'pending'" @click="updateOrderStatus(order, 'processing')" class="btn btn-icon btn-light-warning btn-sm rounded-circle shadow-sm" title="Proses Pesanan">
                             <i class="bi bi-play-fill"></i>
                         </button>
                     </td>
                 </tr>
             </tbody>
             </table>
         </div>

         <div class="d-flex justify-content-between align-items-center mt-auto pt-4 border-top">
             <span class="text-gray-600 fs-7">
                 Halaman {{ page }} dari {{ totalPages }} (Total {{ filteredOrders.length }} pesanan)
             </span>
             <div class="btn-group">
                 <button class="btn btn-sm btn-light-primary fw-bold" :disabled="page === 1" @click="page--">
                     <i class="bi bi-chevron-left"></i> Prev
                 </button>
                 <button class="btn btn-sm btn-light-primary fw-bold" :disabled="page >= totalPages" @click="page++">
                     Next <i class="bi bi-chevron-right"></i>
                 </button>
             </div>
         </div>
      </div>
   </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

const emit = defineEmits(['import-order', 'update-count']);

const onlineOrders = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref("");
const statusFilter = ref("");
const page = ref(1);
const perPage = 10;

const filteredOrders = computed(() => onlineOrders.value.filter(o => {
    const term = searchQuery.value.toLowerCase();
    const matchesSearch = 
        (o.guest?.name || o.user?.name || '').toLowerCase().includes(term) || 
        o.id.toString().includes(term) ||
        (o.room?.room_number || '').toString().includes(term);
    const matchesStatus = statusFilter.value ? o.status === statusFilter.value : true;
    return matchesSearch && matchesStatus;
}));

const pendingCount = computed(() => onlineOrders.value.filter(o => o.status === 'pending').length);
const processingCount = computed(() => onlineOrders.value.filter(o => o.status === 'processing').length);
const completedCount = computed(() => onlineOrders.value.filter(o => o.status === 'completed' || o.status === 'paid').length);
const cancelledCount = computed(() => onlineOrders.value.filter(o => o.status === 'cancelled').length);

const totalPages = computed(() => Math.ceil(filteredOrders.value.length / perPage));
const paginatedOrders = computed(() => {
    const start = (page.value - 1) * perPage;
    return filteredOrders.value.slice(start, start + perPage);
});

watch([searchQuery, statusFilter], () => page.value = 1);

const fetchOrders = async () => {
    try {
        loading.value = true;
        const { data } = await ApiService.get("/online-orders");
        
        // FITUR BARU: Filter hanya pesanan yang berasal dari Kamar (room tidak null / table null)
        const allOrders = data || [];
        onlineOrders.value = allOrders.filter((o: any) => o.room_id !== null || o.room !== null);
        
        const pendingCountValue = onlineOrders.value.filter(o => o.status === 'pending').length;
        emit('update-count', pendingCountValue);
    } catch(e: any) { 
        console.error(e);
    } 
    finally { 
        loading.value = false; 
    }
};

const refreshOrders = async () => {
    await fetchOrders();
};

const viewOrderDetails = (order: any) => {
    const itemsList = order.items.map((i:any) => 
        `<li class="d-flex justify-content-between mb-2">
            <span>${i.menu?.name} x${i.quantity}</span>
            <span class="fw-bold">${formatCurrency(i.price * i.quantity)}</span>
        </li>`
    ).join('');
    
    Swal.fire({ 
        title: `Order #${order.id}`, 
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <h6 class="fw-bold">Detail Pelanggan</h6>
                    <p class="mb-1">Nama: ${order.guest?.name || order.user?.name || 'Tamu'}</p>
                    <p class="mb-0">Kamar: ${order.room ? order.room.room_number : 'Dine In'}</p>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold">Items</h6>
                    <ul class="mb-0">${itemsList}</ul>
                </div>
                <div class="border-top pt-3">
                    <h5 class="fw-bold d-flex justify-content-between">
                        <span>Total:</span>
                        <span>${formatCurrency(order.total_price)}</span>
                    </h5>
                </div>
            </div>
        `,
        confirmButtonText: 'Tutup',
        customClass: { confirmButton: 'btn btn-light', popup: 'text-start' },
        buttonsStyling: false,
        width: '500px'
    });
};

const viewOrderItems = (order: any) => {
    const items = order.items.map((i: any) => `${i.menu?.name} x${i.quantity}`).join(', ');
    Swal.fire({ title: 'Items Pesanan', text: items, icon: 'info', confirmButtonText: 'OK' });
};

const updateOrderStatus = async (order: any, status: string) => {
    try {
        await ApiService.put(`/admin/orders/${order.id}/status`, { status });
        Swal.fire('Sukses', 'Status pesanan berhasil diperbarui', 'success');
        await fetchOrders();
    } catch (e: any) {
        Swal.fire('Error', e.response?.data?.message || 'Gagal update status', 'error');
    }
};

const formatCurrency = (v: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);
const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
const formatTime = (dateString: string) => new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

const getStatusClass = (s: string) => {
    const classes: Record<string, string> = {
        'pending': 'badge-light-warning text-warning',
        'paid': 'badge-light-success text-success',
        'processing': 'badge-light-primary text-primary',
        'completed': 'badge-light-success text-success',
        'cancelled': 'badge-light-danger text-danger'
    };
    return classes[s] || 'badge-light-secondary';
};

const getStatusIcon = (s: string) => {
    const icons: Record<string, string> = {
        'pending': 'bi bi-clock',
        'paid': 'bi bi-check-circle',
        'processing': 'bi bi-gear',
        'completed': 'bi bi-check2-circle',
        'cancelled': 'bi bi-x-circle'
    };
    return icons[s] || 'bi bi-question-circle';
};

const getStatusLabel = (s: string) => {
    const labels: Record<string, string> = {
        'pending': 'Pending',
        'paid': 'Dibayar',
        'processing': 'Diproses',
        'completed': 'Selesai',
        'cancelled': 'Dibatalkan'
    };
    return labels[s] || s;
};

onMounted(() => {
    fetchOrders();
    setInterval(fetchOrders, 30000); 
});
</script>

<style scoped>
.text-orange { color: #ff6b00 !important; }

/* FIX ALIGNMENT EL-SELECT */
:deep(.custom-el-select .el-select__wrapper) {
    min-height: 45px !important;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
}

.custom-scroll::-webkit-scrollbar { width: 6px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e1e1e1; border-radius: 10px; }
.anim-fade-in { animation: fadeIn 0.3s ease forwards; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Adaptive Dark Mode Support */
[data-bs-theme="dark"] .text-gray-500 { color: #cdcdde !important; }
[data-bs-theme="dark"] .text-gray-400 { color: #9a9cae !important; }
[data-bs-theme="dark"] .text-gray-600 { color: #9a9cae !important; }
[data-bs-theme="dark"] .text-gray-300 { color: #5a5c6f !important; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .bg-light { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .bg-light-warning { background-color: rgba(255, 193, 7, 0.15) !important; }
[data-bs-theme="dark"] .bg-light-primary { background-color: rgba(0, 158, 247, 0.15) !important; }
[data-bs-theme="dark"] .bg-light-success { background-color: rgba(23, 198, 83, 0.15) !important; }
[data-bs-theme="dark"] .bg-light-danger { background-color: rgba(248, 40, 90, 0.15) !important; }
[data-bs-theme="dark"] .bg-white { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .card { background-color: #1e1e2d !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .table { background-color: #1e1e2d !important; color: #cdcdde; }
[data-bs-theme="dark"] .table thead th { background-color: #2b2b40 !important; color: #cdcdde; }
[data-bs-theme="dark"] .table tbody tr:hover { background-color: #252530 !important; }
[data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }
[data-bs-theme="dark"] .btn-outline { background-color: #1b1b29 !important; color: #cdcdde !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .btn-outline:hover { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] :deep(.el-input__wrapper) {
    background-color: #1b1b29 !important;
    border-color: #323248 !important;
}
[data-bs-theme="dark"] :deep(.el-input__wrapper.is-focus) {
    border-color: #ff6b00 !important;
    background-color: #1b1b29 !important;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown) {
    background-color: #1e1e2d !important;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown__item) {
    color: #cdcdde;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown__item:hover) {
    background-color: #2b2b40 !important;
}
</style>