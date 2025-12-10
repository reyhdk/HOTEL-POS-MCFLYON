<template>
  <div class="d-flex flex-column gap-5 anim-fade-in">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 mb-4 anim-slide-down">
       <div>
          <h1 class="text-gray-900 fw-bolder fs-2 m-0">Pesanan Masuk</h1>
          <p class="text-gray-500 fw-bold fs-7 m-0">Kitchen Display System (KDS)</p>
       </div>

       <div class="d-flex flex-column flex-sm-row align-items-center gap-3 w-100 w-md-auto">
          <div class="nav nav-pills bg-light rounded-pill p-1 shadow-sm">
             <button 
                v-for="(label, key) in statusOptions" :key="key"
                @click="filters.status = key"
                class="nav-link btn btn-sm rounded-pill fw-bold fs-8 px-4 transition-all"
                :class="filters.status === key ? 'bg-orange text-white shadow-sm' : 'text-gray-500 hover-text-gray-800'"
             >
                {{ label }}
             </button>
          </div>

          <div class="position-relative w-100 w-md-250px">
             <i class="bi bi-search position-absolute top-50 ms-3 translate-middle-y text-gray-400"></i>
             <input 
                type="text" 
                v-model="filters.search" 
                class="form-control form-control-solid ps-10 rounded-pill border-0"
                placeholder="Cari Kamar / Tamu..."
             />
          </div>
       </div>
    </div>

    <div class="position-relative min-h-300px">
       
       <div v-if="isLoading" class="py-20 text-center">
          <div class="spinner-border text-orange mb-3" role="status"></div>
          <p class="text-gray-500 fw-semibold">Memuat pesanan...</p>
       </div>

       <div v-else-if="filteredOrders.length === 0" class="card card-dashed border-gray-300 bg-light rounded-4 py-15 text-center anim-fade-up">
           <div class="mb-4">
              <div class="symbol symbol-60px">
                 <div class="symbol-label bg-light-warning text-warning rounded-circle">
                    <i class="bi bi-inbox fs-2x"></i>
                 </div>
              </div>
           </div>
           <h4 class="text-gray-800 fw-bold">Tidak Ada Pesanan</h4>
           <span class="text-gray-400 fs-7">Belum ada pesanan masuk dengan status yang dipilih.</span>
       </div>

       <div v-else class="row g-4">
          <TransitionGroup name="staggered-list" tag="div" class="contents" style="display: contents;">
             
             <div 
                class="col-12 col-md-6 col-lg-4 col-xl-3" 
                v-for="(order, index) in filteredOrders" 
                :key="order.id"
                :style="{ '--delay': `${index * 0.1}s` }"
             >
                <div class="h-100 anim-staggered-item"> 
                    
                    <div 
                       class="card h-100 border-0 shadow-sm rounded-4 cursor-pointer hover-elevate position-relative overflow-hidden card-adaptive"
                       @click="openDetail(order)"
                    >
                       <div class="position-absolute top-0 start-0 h-100 w-4px bg-orange rounded-start"></div>

                       <div class="card-body p-5 d-flex flex-column align-items-center">
                           
                           <div class="d-flex justify-content-between w-100 mb-4">
                              <span class="badge px-3 py-1 rounded-pill fs-9 fw-bolder text-uppercase" :class="getStatusClass(order.status)">
                                 {{ order.status }}
                              </span>
                              <span class="text-gray-500 fs-9 fw-bold bg-light px-2 py-1 rounded">
                                 {{ formatTime(order.created_at) }}
                              </span>
                           </div>

                           <div class="text-center mb-4">
                              <div class="symbol symbol-60px mb-3">
                                 <div class="symbol-label bg-light-orange text-orange rounded-circle">
                                    <i class="bi bi-receipt-cutoff fs-2x"></i>
                                 </div>
                              </div>
                              <h3 class="fw-bolder text-gray-900 fs-4 mb-1">
                                 {{ order.room ? `Kamar ${order.room.room_number}` : 'Take Away' }}
                              </h3>
                              <div class="text-gray-500 fw-semibold fs-8 text-truncate mw-100 px-2">
                                 <i class="bi bi-person me-1"></i> {{ getGuestName(order) }}
                              </div>
                           </div>

                           <div class="separator separator-dashed border-gray-300 w-100 mb-4"></div>

                           <div class="d-flex justify-content-between align-items-center w-100 mt-auto bg-light rounded-3 px-3 py-2 border border-dashed border-gray-300">
                              <div class="d-flex align-items-center text-orange fw-bold fs-8">
                                 <span class="symbol symbol-20px me-2 bg-orange text-white rounded-circle d-flex align-items-center justify-content-center fs-9 fw-bolder">
                                     {{ order.items.length }}
                                 </span>
                                 <span>Item</span>
                              </div>
                              <div class="text-gray-800 fw-bolder fs-7">
                                 {{ formatPrice(order.total_price) }}
                              </div>
                           </div>
                       </div>
                    </div>
                </div>
             </div>

          </TransitionGroup>
       </div>
    </div>

    <OrderDetailModal 
      ref="detailModalRef"
      @orderUpdated="fetchOrders"
    />

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import OrderDetailModal from "./OrderDetailModal.vue"; 

const orders = ref<any[]>([]);
const isLoading = ref(true);
const filters = reactive({ search: '', status: '' });
const detailModalRef = ref<InstanceType<typeof OrderDetailModal> | null>(null);

const statusOptions: Record<string, string> = {
    '': 'Semua', 'pending': 'Baru', 'processing': 'Proses', 'delivering': 'Antar', 'completed': 'Selesai'
};

const fetchOrders = async () => {
  if(orders.value.length === 0) isLoading.value = true;
  try {
    const { data } = await ApiService.get("/online-orders");
    orders.value = data.filter((o: any) => o.status !== 'cancelled');
  } catch (e) { console.error(e); } 
  finally { isLoading.value = false; }
};

const openDetail = (order: any) => { 
    if (detailModalRef.value) detailModalRef.value.open(order);
};

const filteredOrders = computed(() => {
   return orders.value.filter(order => {
      if (filters.status && order.status !== filters.status) return false;
      if (filters.search) {
         const term = filters.search.toLowerCase();
         return (order.room?.room_number || '').toString().includes(term) || 
                (getGuestName(order)).toLowerCase().includes(term);
      }
      return true;
   });
});

const getGuestName = (o: any) => o.guest?.name || o.user?.name || 'Tamu Umum';
const formatPrice = (v: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);
const formatTime = (d: string) => new Date(d).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');

const getStatusClass = (s: string) => {
    const map: Record<string, string> = {
        pending: 'badge-light-warning text-warning', 
        processing: 'badge-light-info text-info',
        delivering: 'badge-light-primary text-primary', 
        completed: 'badge-light-success text-success',
        paid: 'badge-light-dark text-gray-700'
    };
    return map[s] || 'badge-light text-gray-500';
};

onMounted(fetchOrders);
</script>

<style scoped>
/* COLORS */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff5f0 !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(255, 107, 0, 0.15) !important; }

/* ADAPTIVE CARD */
.card-adaptive { background-color: #fff; }
[data-bs-theme="dark"] .card-adaptive { background-color: #1e1e2d; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .bg-light { background-color: #2b2b40 !important; }

/* HOVER EFFECT */
.hover-elevate { transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease; }
.hover-elevate:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important; }
.transition-all { transition: all 0.3s ease; }

/* --- ANIMASI (SAMA PERSIS DENGAN FOLIO) --- */

/* 1. Page Load Fade In */
.anim-fade-in { animation: fadeIn 0.8s ease-out forwards; }

/* 2. Header Slide Down */
.anim-slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(-20px); }

/* 3. Empty State Fade Up */
.anim-fade-up { animation: fadeUp 0.6s ease-out forwards; opacity: 0; }

/* 4. Staggered Item Animation (Kartu muncul satu-satu) */
.anim-staggered-item { 
    animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
    opacity: 0; 
    transform: translateY(40px); 
    animation-delay: var(--delay); /* Ini kuncinya! */
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

</style>