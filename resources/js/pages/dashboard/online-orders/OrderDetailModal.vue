<template>
  <div class="modal fade" tabindex="-1" ref="modalRef">
    <div class="modal-dialog modal-dialog-centered"> 
      
      <div class="modal-content overflow-hidden shadow-lg modal-anim rounded-4" v-if="order">
        
        <div class="bg-gray-100 border-bottom">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                   <div class="d-flex flex-column">
                      <h5 class="fw-bolder text-dark m-0 d-flex align-items-center">
                         <i class="bi bi-receipt text-orange me-2 fs-4"></i> Order #{{ order.id }}
                      </h5>
                      <span class="text-gray-500 fs-8 fw-bold mt-1 ms-1">{{ formatDateTime(order.created_at) }}</span>
                   </div>
                   <span :class="getStatusBadge(order.status)" class="badge px-3 py-2 fw-bold text-uppercase rounded-pill fs-9 shadow-sm">
                     {{ order.status }}
                   </span>
                </div>

                <div class="bg-white rounded-3 p-3 shadow-sm border border-dashed border-gray-300 overflow-auto custom-scroll" style="max-height: 250px;">
                  <table class="table align-middle gs-0 gy-2 mb-0"> 
                    <tbody class="fs-8 fw-semibold text-gray-700">
                      <tr v-for="item in order.items" :key="item.id" class="border-bottom border-dashed border-gray-100 last-no-border">
                        <td class="ps-0">
                          <div class="d-flex flex-column">
                             <span class="text-dark fw-bold">{{ item.menu.name }}</span>
                             <span class="text-gray-400 fs-9">{{ formatCurrency(item.price) }}</span>
                          </div>
                        </td>
                        <td class="text-center px-2">
                          <span class="badge badge-light fw-bold fs-9 text-gray-600 px-2 py-1 border border-gray-200">x{{ item.quantity }}</span>
                        </td>
                        <td class="text-end pe-0">
                          <span class="text-dark fw-bold">{{ formatCurrency(item.price * item.quantity) }}</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                   <span class="text-gray-600 fw-bold fs-7 text-uppercase ls-1">Total Tagihan</span>
                   <span class="text-orange fw-bolder fs-3">{{ formatCurrency(order.total_price) }}</span>
                </div>
            </div>
        </div>

        <div class="p-4 bg-white position-relative">
          
          <button type="button" class="btn btn-sm btn-icon btn-light-danger position-absolute top-0 end-0 m-3 rounded-circle" style="width: 28px; height: 28px;" @click="closeModal">
             <i class="bi bi-x fs-4"></i>
          </button>

          <div class="d-flex align-items-center mb-4 p-3 bg-light-orange rounded-3 border border-orange-subtle">
             <div class="symbol symbol-35px me-3">
                <div class="symbol-label bg-orange text-white fw-bold fs-6 rounded-circle">
                   {{ getGuestName(order).charAt(0).toUpperCase() }}
                </div>
             </div>
             <div class="d-flex flex-column">
                <span class="fw-bold text-dark fs-7">{{ getGuestName(order) }}</span>
                <span class="text-gray-500 fs-9 fw-semibold d-flex align-items-center">
                   <i class="bi bi-door-open fs-9 me-1 text-orange"></i>
                   {{ order.room?.room_number ? `Kamar ${order.room.room_number}` : 'POS / Umum' }}
                </span>
             </div>
          </div>

          <div v-if="order.status !== 'paid' && order.status !== 'cancelled'" class="d-flex flex-column gap-3">
             
             <div class="dropdown d-grid" v-if="['pending', 'processing', 'delivering'].includes(order.status)">
                <button class="btn btn-light fw-bold dropdown-toggle fs-8 py-2 text-gray-600 border border-gray-200 bg-hover-light-gray" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-gear-fill me-1 text-gray-400"></i> Update Status Dapur
                </button>
                <ul class="dropdown-menu shadow-sm fs-8 p-1 border-0 rounded-3" style="min-width: 100%;">
                  <li><button class="dropdown-item rounded-2 p-2 text-warning fw-bold mb-1" @click="updateStatus('processing')"><i class="bi bi-fire me-2"></i>Mulai Masak</button></li>
                  <li><button class="dropdown-item rounded-2 p-2 text-info fw-bold mb-1" @click="updateStatus('delivering')"><i class="bi bi-bicycle me-2"></i>Antar Pesanan</button></li>
                  <li><button class="dropdown-item rounded-2 p-2 text-success fw-bold" @click="updateStatus('completed')"><i class="bi bi-check-circle me-2"></i>Selesai</button></li>
                </ul>
             </div>
             
             <div class="d-grid gap-2">
                <button 
                   @click="handlePayment('cash')" 
                   class="btn btn-orange w-100 py-2 shadow-sm d-flex align-items-center justify-content-center btn-active-scale"
                   :disabled="isSubmitting"
                >
                   <i class="bi bi-cash-stack fs-4 text-white me-2"></i>
                   <div class="d-flex flex-column text-start lh-1">
                      <span class="fs-7 fw-bold">BAYAR TUNAI</span>
                      <span class="fs-9 opacity-75 fw-normal">Selesaikan & Arsipkan</span>
                   </div>
                </button>

                <button 
                   @click="handlePayment('qris')" 
                   class="btn btn-outline btn-outline-dashed btn-outline-primary w-100 py-2 d-flex align-items-center justify-content-center text-primary fw-bold"
                   :disabled="isSubmitting"
                >
                   <i class="bi bi-qr-code-scan fs-5 me-2"></i>
                   <span class="fs-8">QRIS / Midtrans</span>
                </button>
             </div>
          </div>

          <div v-if="order.status === 'paid'" class="alert alert-success d-flex align-items-center p-3 mb-0 rounded-3 bg-light-success border-success border-dashed">
              <i class="bi bi-check-circle-fill fs-2 text-success me-3"></i>
              <div class="d-flex flex-column">
                  <span class="fw-bold text-success fs-7">LUNAS</span>
                  <span class="fs-9 text-gray-600">Tersimpan di Riwayat Transaksi.</span>
              </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { Modal } from "bootstrap";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

declare global { interface Window { snap: any; } }

const props = defineProps({
    order: { type: Object, required: false, default: null },
    show: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'orderUpdated']);
const modalRef = ref<HTMLElement | null>(null);
let modalInstance: Modal | null = null;
const isSubmitting = ref(false);

const Toast = Swal.mixin({
    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
    didOpen: (toast) => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); }
});

onMounted(() => {
    if (modalRef.value) {
        modalInstance = new Modal(modalRef.value);
        modalRef.value.addEventListener('hidden.bs.modal', () => { if (!isSubmitting.value) emit('close'); });
        if (props.show) modalInstance.show();
    }
});

watch(() => props.show, (val) => { if (modalInstance) val ? modalInstance.show() : modalInstance.hide(); });
const closeModal = () => { if(!isSubmitting.value) modalInstance?.hide(); };

const formatCurrency = (val: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val);
const formatDateTime = (dateStr: string) => new Date(dateStr).toLocaleString('id-ID', { day: 'numeric', month: 'numeric', hour: '2-digit', minute: '2-digit' });
const getGuestName = (order: any) => order?.guest?.name || order?.user?.name || 'Tamu Umum';

const getStatusBadge = (status: string) => {
    const map: Record<string, string> = { pending: 'bg-warning text-dark', processing: 'bg-info text-white', delivering: 'bg-primary text-white', completed: 'bg-success text-white', paid: 'bg-dark text-white', cancelled: 'bg-danger text-white' };
    return map[status] || 'bg-secondary';
};

const updateStatus = async (status: string) => {
    isSubmitting.value = true;
    try {
        await ApiService.put(`/admin/orders/${props.order.id}/status`, { status });
        props.order.status = status; 
        emit('orderUpdated');
        Toast.fire({ icon: 'success', title: `Status: ${status}` });
    } catch (e) { Toast.fire({ icon: 'error', title: "Gagal update status" }); } finally { isSubmitting.value = false; }
};

const handlePayment = async (method: string) => {
    isSubmitting.value = true;
    try {
        const response = await ApiService.post(`/admin/orders/${props.order.id}/pay`, { payment_method: method });

        if (response.data.status === 'midtrans_initiated' && response.data.snap_token) {
            if (typeof window.snap === 'undefined') { Swal.fire("Error", "Midtrans Error.", "error"); isSubmitting.value = false; return; }
            window.snap.pay(response.data.snap_token, {
                onSuccess: async function() {
                    try { await ApiService.post(`/admin/orders/${props.order.id}/pay`, { payment_method: 'midtrans_success' }); } catch(e) {}
                    closeModal(); emit('orderUpdated'); Toast.fire({ icon: 'success', title: 'Pembayaran Berhasil!' }); isSubmitting.value = false;
                },
                onPending: function() { closeModal(); Toast.fire({ icon: 'info', title: 'Menunggu Pembayaran' }); isSubmitting.value = false; },
                onError: function() { Toast.fire({ icon: 'error', title: 'Pembayaran Gagal' }); isSubmitting.value = false; },
                onClose: function() { isSubmitting.value = false; }
            });
            return; 
        }

        if (response.data.status === 'success') {
             closeModal(); emit('orderUpdated'); Toast.fire({ icon: 'success', title: 'Lunas (Tunai)' }); isSubmitting.value = false;
        }
    } catch (error: any) {
        Swal.fire("Gagal", error.response?.data?.message || "Terjadi kesalahan.", "error");
        isSubmitting.value = false;
    }
};
</script>

<style scoped>
/* ORANGE THEME */
.text-orange { color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff5f0 !important; }
.border-orange-subtle { border-color: rgba(255, 107, 0, 0.2) !important; }

/* BUTTONS */
.btn-orange { background: #ff6b00; color: white; border: none; transition: all 0.2s ease; }
.btn-orange:hover { background: #e65e00; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255, 107, 0, 0.2); }
.btn-active-scale:active { transform: scale(0.98); }
.bg-hover-light-gray:hover { background-color: #f8f9fa; color: #333; }

/* MODAL ANIMATION */
.modal-anim { animation: zoomIn 0.2s ease-out; }
@keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

/* SCROLLBAR */
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
.last-no-border:last-child { border-bottom: 0 !important; }

/* TYPOGRAPHY */
.fs-7 { font-size: 0.95rem !important; }
.fs-8 { font-size: 0.85rem !important; }
.fs-9 { font-size: 0.75rem !important; }
</style>,,ll