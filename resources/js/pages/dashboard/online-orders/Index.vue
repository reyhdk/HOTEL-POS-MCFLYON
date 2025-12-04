<template>
  <div class="d-flex flex-column gap-5 page-container">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 mb-2 anim-slide-down">
       <div>
          <h1 class="text-dark fw-bolder fs-2 m-0">Live Orders</h1>
          <p class="text-gray-400 fw-bold fs-7 m-0">Kitchen Display System</p>
       </div>

       <div class="d-flex align-items-center gap-3 w-100 w-md-auto">
          <div class="d-none d-xl-flex bg-white p-1 rounded-pill shadow-sm anim-fade-in delay-100">
             <button 
                v-for="(label, key) in statusOptions" :key="key"
                @click="filters.status = key"
                class="btn btn-sm rounded-pill px-3 fw-bold transition-fast fs-8"
                :class="filters.status === key ? 'bg-orange text-white' : 'text-gray-500 hover-text-dark'"
             >
                {{ label }}
             </button>
          </div>

          <div class="position-relative w-100 w-md-200px anim-fade-in delay-200">
             <i class="bi bi-search position-absolute top-50 ms-3 translate-middle-y text-gray-400 fs-8"></i>
             <input 
                type="text" 
                v-model="filters.search" 
                class="form-control form-control-solid ps-9 rounded-pill border-0 bg-white shadow-sm fs-7"
                placeholder="Cari..."
             />
          </div>
       </div>
    </div>

    <div class="row g-4">
       
       <div v-if="isLoading && orders.length === 0" class="col-12 py-10 text-center">
          <div class="spinner-border text-orange" role="status"></div>
       </div>

       <div v-else-if="filteredOrders.length === 0" class="col-12 py-15 text-center anim-fade-up">
           <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" class="opacity-25 h-100px mb-3" alt="Empty">
           <div class="text-gray-400 fw-bold">Belum ada pesanan untuk status ini.</div>
       </div>

       <TransitionGroup name="staggered-list" tag="div" class="contents" style="display: contents;">
          <div 
             class="col-12 col-md-6 col-lg-4 col-xl-3" 
             v-for="(order, index) in filteredOrders" 
             :key="order.id"
             :style="{ '--delay': `${index * 0.1}s` }" 
          >
             <div class="h-100 anim-staggered-item"> 
                 <div 
                    class="card h-100 border-0 shadow-sm rounded-4 cursor-pointer hover-float bg-white position-relative overflow-hidden"
                    @click="showDetails(order)"
                 >
                    <div class="card-body p-5 d-flex flex-column align-items-center">
                        
                        <div class="d-flex justify-content-between align-items-center w-100 mb-4">
                           <span class="badge px-3 py-2 rounded-pill fs-9 fw-bolder text-uppercase" :class="getStatusClass(order.status)">
                              {{ order.status }}
                           </span>
                           <span class="text-gray-400 fs-8 fw-bold">
                              <i class="bi bi-clock me-1 fs-9"></i> {{ formatTime(order.created_at) }}
                           </span>
                        </div>

                        <div class="mb-4 text-center w-100">
                           <div class="symbol symbol-65px mb-3">
                              <div class="symbol-label bg-light-orange text-orange rounded-circle">
                                 <i class="bi bi-door-open fs-2x"></i>
                              </div>
                           </div>
                           
                           <h3 class="fw-bolder text-dark fs-3 mb-1">
                              {{ order.room ? `Kamar ${order.room.room_number}` : 'Take Away' }}
                           </h3>
                           <div class="text-gray-400 fw-bold fs-8 text-truncate mw-100 px-2">
                              {{ getGuestName(order) }}
                           </div>
                        </div>

                        <div class="separator separator-dashed border-gray-300 w-100 mb-4"></div>

                        <div class="d-flex justify-content-between align-items-center w-100 mt-auto">
                           <div class="d-flex align-items-center text-orange fw-bold fs-7">
                              <i class="bi bi-basket-fill me-2 fs-6"></i>
                              <span>{{ order.items.length }} Item</span>
                           </div>
                           <div class="text-dark fw-bolder fs-5">
                              {{ formatPrice(order.total_price) }}
                           </div>
                        </div>
                    </div>
                 </div>
             </div>
          </div>
       </TransitionGroup>

    </div>

    <OrderDetailModal 
      v-if="selectedOrder"
      :show="selectedOrder !== null" 
      :order="selectedOrder" 
      @close="selectedOrder = null"
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
const selectedOrder = ref<any | null>(null);
const filters = reactive({ search: '', status: '' });

const statusOptions: Record<string, string> = {
    '': 'All', 'pending': 'Pending', 'processing': 'Cook', 'delivering': 'Send', 'completed': 'Done', 'paid': 'Paid'
};

const fetchOrders = async () => {
  isLoading.value = true;
  try {
    const { data } = await ApiService.get("/online-orders");
    orders.value = data.filter((o: any) => o.status !== 'cancelled');
  } catch (e) { console.error(e); } 
  finally { isLoading.value = false; }
};

const showDetails = (order: any) => { selectedOrder.value = order; };

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
        pending: 'bg-light-warning text-warning', processing: 'bg-light-info text-info',
        delivering: 'bg-light-primary text-primary', completed: 'bg-light-success text-success',
        paid: 'bg-light-dark text-gray-600'
    };
    return map[s] || 'bg-light text-gray-500';
};

onMounted(fetchOrders);
</script>

<style scoped>
/* UTILS */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff5f0 !important; }
.hover-text-dark:hover { color: #181c32 !important; }
.fs-2x { font-size: 2rem !important; }

/* CARD HOVER */
.hover-float { transition: transform 0.25s ease, box-shadow 0.25s ease; }
.hover-float:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important; }
.separator-dashed { border-bottom-style: dashed !important; border-bottom-width: 1px !important; }

/* --- ANIMATIONS START --- */

/* 1. Header Slide Down */
.anim-slide-down {
    animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
    transform: translateY(-20px);
}

/* 2. Generic Fade In */
.anim-fade-in {
    animation: fadeIn 0.8s ease-out forwards;
    opacity: 0;
}

/* 3. Staggered Item Animation (Kartu muncul satu-satu) */
.anim-staggered-item {
    animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
    transform: translateY(30px);
    animation-delay: var(--delay); /* Delay dinamis dari v-for index */
}

/* Keyframes Definitions */
@keyframes slideDown {
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    to { opacity: 1; }
}

@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}

/* Utility delays */
.delay-100 { animation-delay: 0.1s; }
.delay-200 { animation-delay: 0.2s; }

/* Transition Group (untuk animasi saat filter berubah/hapus) */
.staggered-list-enter-active,
.staggered-list-leave-active {
  transition: all 0.4s ease;
}
.staggered-list-enter-from,
.staggered-list-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
/* --- ANIMATIONS END --- */
</style>