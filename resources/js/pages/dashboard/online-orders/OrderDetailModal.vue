<template>
  <Teleport to="body">
    <div 
      class="modal fade" 
      id="kt_modal_order_detail" 
      tabindex="-1" 
      aria-hidden="true" 
      ref="modalElRef" 
      style="z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered"> 
        
        <div class="modal-content shadow-lg rounded-4 border-0 bg-surface">
          
          <div class="modal-header border-bottom border-gray-200 py-4">
              <h5 class="fw-bolder text-gray-900 m-0 d-flex align-items-center">
                 <span class="symbol symbol-30px me-3">
                    <span class="symbol-label bg-light-orange text-orange rounded-circle">
                      <i class="bi bi-receipt fs-4"></i>
                    </span>
                 </span>
                 Detail Pesanan #{{ currentOrder?.id || '-' }}
              </h5>
              <button type="button" class="btn btn-icon btn-sm btn-light-danger rounded-circle" data-bs-dismiss="modal" aria-label="Close">
                 <i class="bi bi-x fs-4"></i>
              </button>
          </div>

          <div class="modal-body p-4" v-if="currentOrder">
              
              <div class="d-flex justify-content-between align-items-center mb-4">
                 <span :class="getStatusBadge(currentOrder.status)" class="badge px-3 py-2 fw-bold text-uppercase rounded-pill fs-8 shadow-sm">
                   {{ currentOrder.status }}
                 </span>
                 <span class="text-gray-500 fs-8 fw-bold bg-light px-2 py-1 rounded">
                    <i class="bi bi-clock me-1"></i> {{ formatDateTime(currentOrder.created_at) }}
                 </span>
              </div>

              <div class="d-flex align-items-center mb-5 p-3 bg-light-orange rounded-3 border border-dashed border-orange">
                 <div class="symbol symbol-40px me-3">
                    <div class="symbol-label bg-orange text-white fw-bold fs-5 rounded-circle shadow-sm">
                       {{ getGuestName(currentOrder).charAt(0).toUpperCase() }}
                    </div>
                 </div>
                 <div class="d-flex flex-column">
                    <span class="fw-bold text-gray-800 fs-6">{{ getGuestName(currentOrder) }}</span>
                    <span class="text-gray-600 fs-8 fw-semibold d-flex align-items-center">
                       <i class="bi bi-door-open fs-8 me-1 text-orange"></i>
                       {{ currentOrder.room?.room_number ? `Kamar ${currentOrder.room.room_number}` : 'Tamu Umum / POS' }}
                    </span>
                 </div>
              </div>

              <div class="table-responsive rounded-3 border border-gray-200 mb-4 custom-scroll" style="max-height: 300px; overflow-y: auto;">
                <table class="table align-middle gs-0 gy-3 mb-0"> 
                  <thead class="bg-light sticky-top">
                     <tr class="fw-bold text-gray-500 fs-9 text-uppercase">
                        <th class="ps-3 min-w-100px">Menu</th>
                        <th class="text-center min-w-50px">Qty</th>
                        <th class="text-end pe-3 min-w-80px">Total</th>
                     </tr>
                  </thead>
                  <tbody class="fw-semibold text-gray-700">
                    <tr v-for="item in currentOrder.items" :key="item.id" class="border-bottom border-gray-100">
                      <td class="ps-3">
                        <div class="d-flex flex-column">
                           <span class="text-gray-800 fw-bold fs-7">{{ item.menu.name }}</span>
                           <span class="text-gray-400 fs-9">{{ formatCurrency(item.price) }}</span>
                        </div>
                      </td>
                      <td class="text-center">
                        <span class="badge badge-light border border-gray-200 text-gray-600 fs-9">x{{ item.quantity }}</span>
                      </td>
                      <td class="text-end pe-3">
                        <span class="text-gray-900 fw-bolder">{{ formatCurrency(item.price * item.quantity) }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 border border-gray-200 mb-4">
                 <span class="text-gray-600 fw-bold fs-7 text-uppercase ls-1">Total Tagihan</span>
                 <span class="text-orange fw-bolder fs-2">{{ formatCurrency(currentOrder.total_price) }}</span>
              </div>

              <div v-if="currentOrder.status !== 'paid' && currentOrder.status !== 'cancelled'" class="d-flex flex-column gap-3 pt-2">
                 
                 <div class="dropdown d-grid" v-if="['pending', 'processing', 'delivering'].includes(currentOrder.status)">
                    <button class="btn btn-outline btn-outline-dashed btn-outline-default text-gray-700 fw-bold dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <i class="bi bi-sliders me-1"></i> Update Status Dapur
                    </button>
                    <ul class="dropdown-menu shadow-lg rounded-3 border-0 p-2 w-100" style="z-index: 1060;">
                      <li><button class="dropdown-item rounded-2 p-2 text-warning fw-bold mb-1" @click="updateStatus('processing')"><i class="bi bi-fire me-2"></i>Sedang Dimasak</button></li>
                      <li><button class="dropdown-item rounded-2 p-2 text-primary fw-bold mb-1" @click="updateStatus('delivering')"><i class="bi bi-bicycle me-2"></i>Sedang Diantar</button></li>
                      <li><button class="dropdown-item rounded-2 p-2 text-success fw-bold" @click="updateStatus('completed')"><i class="bi bi-check-circle me-2"></i>Selesai Disajikan</button></li>
                    </ul>
                 </div>
                 
                 <div class="row g-3">
                    <div class="col-6">
                      <button 
                         @click="handlePayment('cash')" 
                         class="btn btn-orange w-100 py-3 shadow-sm btn-active-scale h-100"
                         :disabled="isSubmitting"
                      >
                         <div class="d-flex flex-column align-items-center lh-1">
                            <i class="bi bi-cash-stack fs-3 text-white mb-1"></i>
                            <span class="fs-7 fw-bold">TUNAI</span>
                         </div>
                      </button>
                    </div>
                    <div class="col-6">
                      <button 
                         @click="handlePayment('qris')" 
                         class="btn btn-light-primary w-100 py-3 border border-primary border-dashed h-100 btn-active-scale"
                         :disabled="isSubmitting"
                      >
                         <div class="d-flex flex-column align-items-center lh-1">
                            <i class="bi bi-qr-code-scan fs-3 mb-1"></i>
                            <span class="fs-7 fw-bold">QRIS</span>
                         </div>
                      </button>
                    </div>
                 </div>
              </div>

              <div v-if="currentOrder.status === 'paid'" class="alert alert-dismissible bg-light-success d-flex flex-column flex-sm-row p-4 mb-0 rounded-3 border-success border-dashed">
                  <i class="bi bi-check-circle-fill fs-2hx text-success me-4 mb-5 mb-sm-0"></i>
                  <div class="d-flex flex-column pe-0 pe-sm-10">
                      <h5 class="mb-1 fw-bold text-success">Pesanan Lunas</h5>
                      <span class="text-gray-600 fs-7">Transaksi ini telah selesai dan tercatat di riwayat.</span>
                  </div>
              </div>

          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { Modal } from "bootstrap";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

declare global { interface Window { snap: any; } }

const emit = defineEmits(['orderUpdated']);
const modalElRef = ref<HTMLElement | null>(null);
const currentOrder = ref<any>(null); 
let modalInstance: Modal | null = null;
const isSubmitting = ref(false);

const Toast = Swal.mixin({
    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
});

onMounted(() => {
    if (modalElRef.value) {
        modalInstance = new Modal(modalElRef.value);
    }
});

// --- FUNGSI UTAMA ---
const open = (orderData: any) => {
    currentOrder.value = orderData; 
    modalInstance?.show(); 
};

const closeModal = () => {
    modalInstance?.hide();
};

// Expose fungsi agar bisa dipanggil Parent
defineExpose({ open });

// --- LOGIC LAINNYA ---
const updateStatus = async (status: string) => {
    if (!currentOrder.value) return;
    isSubmitting.value = true;
    try {
        await ApiService.put(`/admin/orders/${currentOrder.value.id}/status`, { status });
        currentOrder.value.status = status; 
        emit('orderUpdated');
        Toast.fire({ icon: 'success', title: `Status: ${status}` });
    } catch (e) { Toast.fire({ icon: 'error', title: "Gagal update status" }); } finally { isSubmitting.value = false; }
};

const handlePayment = async (method: string) => {
    if (!currentOrder.value) return;
    isSubmitting.value = true;
    try {
        const response = await ApiService.post(`/admin/orders/${currentOrder.value.id}/pay`, { payment_method: method });

        if (response.data.status === 'midtrans_initiated' && response.data.snap_token) {
             if (typeof window.snap === 'undefined') { Swal.fire("Error", "Midtrans JS not loaded.", "error"); isSubmitting.value = false; return; }
             window.snap.pay(response.data.snap_token, {
                onSuccess: function() {
                   closeModal(); emit('orderUpdated'); Swal.fire("Sukses", "Pembayaran Berhasil!", "success"); isSubmitting.value = false;
                },
                onPending: function() { closeModal(); Swal.fire("Pending", "Menunggu pembayaran...", "info"); isSubmitting.value = false; },
                onError: function() { Swal.fire("Gagal", "Pembayaran gagal.", "error"); isSubmitting.value = false; },
                onClose: function() { isSubmitting.value = false; }
            });
            return; 
        }

        if (response.data.status === 'success') {
             closeModal(); emit('orderUpdated'); Toast.fire({ icon: 'success', title: 'Pembayaran Tunai Berhasil' }); isSubmitting.value = false;
        }
    } catch (error: any) {
        Swal.fire("Gagal", "Terjadi kesalahan sistem.", "error");
        isSubmitting.value = false;
    }
};

const formatCurrency = (val: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val);
const formatDateTime = (dateStr: string) => new Date(dateStr).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
const getGuestName = (order: any) => order?.guest?.name || order?.user?.name || 'Tamu Umum';
const getStatusBadge = (status: string) => {
    const map: Record<string, string> = { pending: 'badge-light-warning text-warning', processing: 'badge-light-info text-info', delivering: 'badge-light-primary text-primary', completed: 'badge-light-success text-success', paid: 'badge-light-dark text-gray-800' };
    return map[status] || 'badge-light-secondary';
};
</script>

<style scoped>
/* BACKGROUND ADAPTIF */
.bg-surface { background-color: #ffffff; }
[data-bs-theme="dark"] .bg-surface { background-color: #1e1e2d; color: #ffffff; }

/* ORANGE THEME */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff5f0 !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(255, 107, 0, 0.15) !important; }
.border-dashed { border-style: dashed !important; }

.btn-orange { background: #ff6b00; color: white; border: none; }
.btn-orange:hover { background: #e65e00; }
.btn-active-scale:active { transform: scale(0.95); }

/* SCROLLBAR */
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
</style>