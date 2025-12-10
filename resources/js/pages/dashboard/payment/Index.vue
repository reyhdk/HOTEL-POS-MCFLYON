<template>
  <div class="d-flex flex-column gap-5 anim-fade-in">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 mb-2 anim-slide-down">
       <div>
          <div class="d-flex align-items-center mb-1">
            <span class="symbol symbol-40px me-3">
              <span class="symbol-label bg-light-orange text-orange rounded-circle">
                <i class="bi bi-receipt-cutoff fs-2"></i>
              </span>
            </span>
            <h1 class="text-gray-900 fw-bolder fs-2 m-0">Daftar Tagihan</h1>
          </div>
          <p class="text-gray-500 fw-bold fs-7 m-0 ms-12">Kelola pesanan yang belum dibayar</p>
       </div>
    </div>

    <div class="position-relative min-h-300px">
       
       <div v-if="loading" class="py-20 text-center">
          <div class="spinner-border text-orange mb-3" role="status"></div>
          <p class="text-gray-500 fw-semibold">Memuat data tagihan...</p>
       </div>

       <div v-else-if="pendingOrders.length === 0" class="card card-dashed border-gray-300 bg-light rounded-4 py-15 text-center anim-fade-up">
          <div class="mb-4">
             <i class="bi bi-check-circle fs-3x text-success opacity-50"></i>
          </div>
          <h4 class="text-gray-800 fw-bold">Tidak Ada Tagihan</h4>
          <span class="text-gray-400 fs-7">Semua pesanan telah lunas.</span>
       </div>

       <div v-else class="row g-4">
          <TransitionGroup name="staggered-list" tag="div" class="contents" style="display: contents;">
             <div 
                class="col-12 col-md-6 col-xl-4" 
                v-for="(order, index) in pendingOrders" 
                :key="order.id"
                :style="{ '--delay': `${index * 0.1}s` }"
             >
                <div class="h-100 anim-staggered-item">
                   
                   <div 
                      class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden hover-elevate"
                      :class="{ 'ring-2 ring-orange': expandedOrderId === order.id }"
                   >
                      <div class="position-absolute top-0 start-0 h-100 w-4px bg-orange rounded-start"></div>

                      <div class="card-body p-6 d-flex flex-column">
                         
                         <div class="d-flex justify-content-between align-items-start mb-4">
                            <div class="d-flex align-items-center">
                               <div class="symbol symbol-45px me-3">
                                  <div class="symbol-label bg-light-orange text-orange rounded-circle">
                                     <span class="fw-bold fs-5">#{{ order.id }}</span>
                                  </div>
                               </div>
                               <div>
                                  <div class="text-gray-400 fs-9 fw-bold text-uppercase ls-1">Kamar</div>
                                  <div class="text-gray-800 fw-bolder fs-5">{{ order.room.room_number }}</div>
                               </div>
                            </div>
                            <span class="badge badge-light-warning text-warning fw-bold px-3 py-2">Belum Lunas</span>
                         </div>

                         <div class="separator separator-dashed border-gray-300 w-100 mb-4"></div>

                         <div class="mb-6 text-center">
                            <div class="text-gray-400 fs-9 fw-bold text-uppercase ls-1 mb-1">Total Tagihan</div>
                            <div class="fs-1 fw-bolder text-gray-800 lh-1">
                               {{ formatCurrency(order.total_price) }}
                            </div>
                         </div>

                         <div class="d-flex flex-column gap-2 mt-auto">
                            <div class="d-flex gap-2">
                                <button 
                                   @click="toggleDetails(order.id)" 
                                   class="btn btn-sm btn-light-primary flex-grow-1 fw-bold fs-7"
                                >
                                   <i class="bi" :class="expandedOrderId === order.id ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                   {{ expandedOrderId === order.id ? 'Tutup' : 'Rincian' }}
                                </button>
                                
                                <button 
                                   @click="processPayment(order)" 
                                   class="btn btn-sm btn-success flex-grow-1 fw-bold fs-7 shadow-sm"
                                >
                                   <i class="bi bi-wallet2 me-1"></i> Bayar
                                </button>
                            </div>
                            
                            <button 
                               @click="cancelOrder(order)" 
                               class="btn btn-sm btn-light-danger fw-bold fs-7 w-100"
                            >
                               <i class="bi bi-x-circle me-1"></i> Batalkan Pesanan
                            </button>
                         </div>

                         <div v-if="expandedOrderId === order.id" class="w-100 mt-4 pt-4 border-top border-gray-200 anim-expand">
                            <div class="d-flex flex-column gap-2 max-h-200px overflow-auto custom-scroll pe-2">
                               <div v-for="item in order.items" :key="item.id" class="d-flex justify-content-between align-items-center bg-light rounded p-2 fs-8 border border-gray-200">
                                  <div class="d-flex flex-column">
                                     <span class="text-gray-800 fw-bold">{{ item.menu.name }}</span>
                                     <span class="text-gray-500">{{ item.quantity }} x {{ formatCurrency(item.price) }}</span>
                                  </div>
                                  <span class="fw-bolder text-gray-800">{{ formatCurrency(item.quantity * item.price) }}</span>
                               </div>
                            </div>
                         </div>

                      </div>
                   </div>
                </div>
             </div>
          </TransitionGroup>
       </div>
    </div>

    <ReceiptModal :order="selectedOrderForReceipt" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import ReceiptModal from "./ReceiptModal.vue";

interface Order {
  id: number;
  total_price: number;
  room: { room_number: string };
  items: { id: number; price: number; quantity: number; menu: { name: string } }[];
}

const pendingOrders = ref<Order[]>([]);
const loading = ref(true);
const selectedOrderForReceipt = ref<Order | null>(null);
const expandedOrderId = ref<number | null>(null); // State untuk toggle rincian

const getPendingOrders = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/pending-orders");
    pendingOrders.value = response.data;
  } catch (error) {
    console.error("Gagal memuat tagihan:", error);
    Swal.fire("Error!", "Gagal memuat data tagihan.", "error");
  } finally {
    loading.value = false;
  }
};

const toggleDetails = (id: number) => {
   expandedOrderId.value = expandedOrderId.value === id ? null : id;
};

const processPayment = (order: Order) => {
  Swal.fire({
    title: "Proses Pembayaran",
    html: `Bayar tagihan Pesanan <strong>#${order.id}</strong> sejumlah <br/><span class="fs-1 text-success fw-bold">${formatCurrency(order.total_price)}</span>?`,
    icon: "question",
    showCancelButton: true,
    buttonsStyling: false,
    confirmButtonText: "Ya, Terima Tunai",
    cancelButtonText: "Batal",
    customClass: {
      confirmButton: "btn fw-bold btn-success",
      cancelButton: "btn fw-bold btn-light",
    },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.post(`/orders/${order.id}/pay`, { payment_method: 'cash' });
        
        selectedOrderForReceipt.value = order;
        const modalEl = document.getElementById("kt_modal_receipt");
        if (modalEl) {
            const modal = new Modal(modalEl);
            modal.show();
        }

        getPendingOrders();
        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Pembayaran diterima', timer: 1500, showConfirmButton: false });

      } catch (error) {
        Swal.fire("Error!", "Gagal memproses pembayaran.", "error");
        console.error(error);
      }
    }
  });
};

const cancelOrder = (order: Order) => {
  Swal.fire({
    title: "Batalkan Pesanan?",
    html: `Pesanan <strong>#${order.id}</strong> akan dibatalkan secara permanen.`,
    icon: "warning",
    showCancelButton: true,
    buttonsStyling: false,
    confirmButtonText: "Ya, Batalkan",
    cancelButtonText: "Kembali",
    customClass: {
      confirmButton: "btn fw-bold btn-danger",
      cancelButton: "btn fw-bold btn-light",
    },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.post(`/orders/${order.id}/cancel`);
        Swal.fire("Dibatalkan!", "Pesanan telah berhasil dibatalkan.", "success");
        getPendingOrders();
      } catch (error) {
        Swal.fire("Error!", "Gagal membatalkan pesanan.", "error");
        console.error(error);
      }
    }
  });
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

onMounted(() => {
  getPendingOrders();
});
</script>

<style scoped>
/* --- THEME COLORS --- */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }

/* Light/Dark Mode Color Logic */
.bg-light-orange { background-color: #fff5f0 !important; }
[data-bs-theme="dark"] .bg-light-orange {
    background-color: rgba(255, 107, 0, 0.15) !important;
}

.ring-2 { box-shadow: 0 0 0 2px #ff6b00 !important; }

/* Dark Mode Text Fixes */
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e4e6ef !important; }
[data-bs-theme="dark"] .card { background-color: #1e1e2d; }
[data-bs-theme="dark"] .bg-light { background-color: #2b2b40 !important; }

/* --- INTERAKSI KARTU --- */
.hover-elevate {
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
}
.hover-elevate:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
}

/* --- ANIMASI --- */
.anim-fade-in { animation: fadeIn 0.8s ease-out forwards; }
.anim-slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(-20px); }
.anim-staggered-item { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(30px); animation-delay: var(--delay); }
.anim-expand { animation: expandDown 0.3s ease-out forwards; transform-origin: top; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
@keyframes expandDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

/* Scrollbar */
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
.max-h-200px { max-height: 200px; }
</style>