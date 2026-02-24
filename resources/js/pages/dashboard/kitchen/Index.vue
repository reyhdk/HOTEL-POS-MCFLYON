<template>
  <!-- Tambahkan class kds-index-container sebagai pembungkus utama -->
  <div class="kds-index-container d-flex flex-column gap-5 anim-fade-in">
    
    <!-- HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 mb-2 anim-slide-down">
       <div>
          <h1 class="text-gray-900 fw-bolder fs-2 m-0">Monitor Dapur (KDS)</h1>
          <p class="text-gray-500 fw-bold fs-7 m-0">Pesanan Hari Ini: {{ currentDate }}</p>
       </div>

       <div class="d-flex flex-column flex-sm-row align-items-center gap-3 w-100 w-md-auto">
          <div class="nav nav-pills bg-light rounded-pill p-1 shadow-sm">
             <button 
                v-for="(label, key) in statusOptions" :key="key"
                @click="setFilterStatus(key as string)"
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
                v-model="searchInput" 
                @input="handleSearch"
                class="form-control form-control-solid ps-10 rounded-pill border-0"
                placeholder="Cari Kamar / Tamu..."
             />
          </div>
       </div>
    </div>

    <!-- PANEL DAFTAR KOKI -->
    <div class="bg-white rounded-4 shadow-sm p-4 d-flex flex-column flex-md-row justify-content-between align-items-center border border-gray-200">
       <div class="d-flex align-items-center mb-3 mb-md-0">
          <div class="symbol symbol-50px me-3">
             <div class="symbol-label bg-light-primary rounded-3">
                 <i class="bi bi-people-fill fs-1 text-primary"></i>
             </div>
          </div>
          <div>
             <h5 class="fw-bolder text-gray-800 m-0">Koki yang Bertugas Hari Ini</h5>
             <span class="text-gray-500 fs-8">Pilih koki yang sedang shift untuk pembagian antrean pesanan</span>
          </div>
       </div>
       <div class="d-flex align-items-center flex-wrap gap-3">
          <div v-if="isLoadingChefs" class="spinner-border spinner-border-sm text-primary" role="status"></div>
          
          <template v-else>
             <!-- Dropdown Multi-select -->
             <div class="dropdown">
                <button class="btn btn-sm btn-primary dropdown-toggle fw-bold shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                    <i class="bi bi-person-check-fill me-1"></i> Pilih Koki ({{ selectedChefs.length }})
                </button>
                <div class="dropdown-menu p-4 shadow-lg border-0 rounded-4" style="min-width: 260px; z-index: 1056;">
                    <h6 class="fw-bold text-gray-800 mb-3">Daftar Koki Tersedia</h6>
                    <div class="d-flex flex-column gap-3 overflow-auto custom-scroll" style="max-height: 200px;">
                        <div v-for="chef in allChefs" :key="chef.id" class="form-check form-check-custom form-check-solid form-check-sm">
                            <input class="form-check-input" type="checkbox" :value="chef.id" v-model="selectedChefIds" :id="'chef_' + chef.id" @change="saveChefSelection" />
                            <label class="form-check-label fw-semibold text-gray-700 cursor-pointer" :for="'chef_' + chef.id">
                                {{ chef.name }}
                            </label>
                        </div>
                        <div v-if="allChefs.length === 0" class="text-gray-500 fs-8">
                            Tidak ada data koki di sistem.
                        </div>
                    </div>
                </div>
             </div>
             
             <!-- Tampilan Koki Terpilih -->
             <div class="d-flex flex-wrap gap-2 bg-light p-2 rounded-3 align-items-center min-w-200px border border-dashed border-gray-300">
                 <span v-if="selectedChefs.length === 0" class="badge badge-light-danger fs-8">
                     <i class="bi bi-exclamation-triangle text-danger me-1"></i> Belum ada Koki dipilih
                 </span>
                 <span 
                    v-for="chef in selectedChefs" 
                    :key="chef.id" 
                    class="badge badge-primary bg-primary text-white fs-8 py-2 px-3 shadow-sm d-flex align-items-center gap-1"
                 >
                     {{ chef.name }}
                     <i class="bi bi-x fs-6 cursor-pointer ms-1 text-white opacity-75 hover-opacity-100" @click="removeChef(chef.id)"></i>
                 </span>
             </div>
          </template>
       </div>
    </div>

    <!-- LIST PESANAN -->
    <div class="position-relative min-h-300px">
       
       <div v-if="isLoadingOrders" class="py-20 text-center">
          <div class="spinner-border text-orange mb-3" role="status"></div>
          <p class="text-gray-500 fw-semibold">Memuat pesanan hari ini...</p>
       </div>

       <div v-else-if="orders.length === 0" class="card card-dashed border-gray-300 bg-light rounded-4 py-15 text-center anim-fade-up">
           <div class="mb-4">
              <div class="symbol symbol-60px">
                 <div class="symbol-label bg-light-warning text-warning rounded-circle">
                    <i class="bi bi-inbox fs-2x"></i>
                 </div>
              </div>
           </div>
           <h4 class="text-gray-800 fw-bold">Tidak Ada Pesanan</h4>
           <span class="text-gray-400 fs-7">Belum ada pesanan masuk dengan kriteria tersebut hari ini.</span>
       </div>

       <div v-else class="row g-4">
          <TransitionGroup name="staggered-list" tag="div" class="contents" style="display: contents;">
             
             <div 
                class="col-12 col-md-6 col-lg-4 col-xl-3" 
                v-for="(order, index) in orders" 
                :key="order.id"
                :style="{ '--delay': `${index * 0.05}s` }"
             >
                <div class="h-100 anim-staggered-item"> 
                    
                    <div 
                       class="card h-100 border-0 shadow-sm rounded-4 cursor-pointer hover-elevate position-relative overflow-hidden card-adaptive"
                       @click="openDetail(order)"
                    >
                       <div class="position-absolute top-0 start-0 h-100 w-4px rounded-start" :class="getLineColor(order.status)"></div>

                       <div class="card-body p-5 d-flex flex-column">
                           
                           <!-- Header Card -->
                           <div class="d-flex justify-content-between align-items-center w-100 mb-4">
                              <span class="badge px-3 py-1 rounded-pill fs-9 fw-bolder text-uppercase" :class="getStatusClass(order.status)">
                                 {{ getStatusLabel(order.status) }}
                              </span>
                              <span class="text-gray-500 fs-9 fw-bold bg-light px-2 py-1 rounded d-flex align-items-center">
                                 <i class="bi bi-clock me-1"></i> {{ formatTime(order.created_at) }}
                              </span>
                           </div>

                           <!-- Content Card -->
                           <div class="text-center mb-4 flex-grow-1">
                              <h3 class="fw-bolder text-gray-900 fs-4 mb-1">
                                 {{ getLocationInfo(order) }}
                              </h3>
                              <div class="text-gray-500 fw-semibold fs-8 text-truncate mw-100 px-2 mb-3">
                                 <i class="bi bi-person me-1"></i> {{ getGuestName(order) }}
                              </div>
                              
                              <div class="d-flex align-items-center justify-content-center text-orange fw-bold fs-7 bg-light-orange rounded px-3 py-2 mb-2">
                                 <i class="bi bi-egg-fried me-2 fs-5"></i>
                                 <span>{{ order.items?.length || 0 }} Item Masakan</span>
                              </div>
                           </div>

                           <div class="separator separator-dashed border-gray-300 w-100 mb-3"></div>

                           <!-- Footer Card: INFO ESTIMASI & KOKI -->
                           <div class="d-flex align-items-center w-100 mt-auto justify-content-between">
                              <div class="d-flex align-items-center overflow-hidden">
                                 <div class="symbol symbol-25px symbol-circle me-2 flex-shrink-0">
                                    <span class="symbol-label bg-primary text-white fs-9 fw-bold">
                                        {{ order.assigned_chef_name ? order.assigned_chef_name.charAt(0).toUpperCase() : '?' }}
                                    </span>
                                 </div>
                                 <span class="text-gray-800 fw-bold fs-9 text-truncate" style="max-width: 100px;">
                                     {{ order.assigned_chef_name || 'Tunggu Koki' }}
                                 </span>
                              </div>
                              <span class="badge badge-light-danger fw-bold fs-9 flex-shrink-0">
                                  <i class="bi bi-stopwatch text-danger me-1"></i>{{ order.calculated_estimation || 0 }} mnt
                              </span>
                           </div>

                       </div>
                    </div>
                </div>
             </div>
          </TransitionGroup>
       </div>

       <!-- Pagination Backend -->
       <div class="d-flex flex-stack flex-wrap pt-10 mt-5 border-top border-gray-200" v-if="pagination.last_page > 1">
           <div class="fs-7 fw-bold text-gray-500">
              Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }} - 
              {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari {{ pagination.total }} pesanan
           </div>
           
           <ul class="pagination mb-0">
               <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                   <button class="page-link cursor-pointer" @click="changePage(pagination.current_page - 1)"><i class="bi bi-chevron-left"></i></button>
               </li>
               <li class="page-item" v-for="page in pagination.last_page" :key="page" :class="{ active: pagination.current_page === page }">
                   <button class="page-link cursor-pointer" @click="changePage(page)">{{ page }}</button>
               </li>
               <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                   <button class="page-link cursor-pointer" @click="changePage(pagination.current_page + 1)"><i class="bi bi-chevron-right"></i></button>
               </li>
           </ul>
       </div>
    </div>

    <!-- Modal hanya view -->
    <OrderDetailModal ref="detailModalRef" />

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import OrderDetailModal from "./OrderDetailModal.vue"; 

const orders = ref<any[]>([]);

// State untuk Koki
const allChefs = ref<any[]>([]); 
const selectedChefIds = ref<number[]>([]); // Menyimpan ID koki yang dicentang

const isLoadingOrders = ref(true);
const isLoadingChefs = ref(true);
const detailModalRef = ref<InstanceType<typeof OrderDetailModal> | null>(null);

const currentDate = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const searchInput = ref('');
let searchTimeout: any = null;

const filters = reactive({ search: '', status: '', page: 1, per_page: 12 });
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 12, total: 0 });

const statusOptions: Record<string, string> = {
    '': 'Semua', 'pending': 'Antrean Baru', 'processing': 'Dimasak', 'delivering': 'Diantar'
};

// Computed property untuk memetakan ID ke object koki yang terpilih
const selectedChefs = computed(() => {
    return allChefs.value.filter(chef => selectedChefIds.value.includes(chef.id));
});

// Menyimpan state chef aktif ke local storage agar tab POS estimasi bisa sinkron & tidak reset
const saveChefSelection = () => {
    localStorage.setItem('kds_active_chef_ids', JSON.stringify(selectedChefIds.value));
    localStorage.setItem('kitchen_chef_count', selectedChefIds.value.length.toString());
    
    // Trigger event agar tab estimasi sadar ada perubahan jika di window yang sama
    window.dispatchEvent(new Event('storage'));
    calculateQueue();
};

const fetchChefs = async () => {
    isLoadingChefs.value = true;
    try {
        const { data } = await ApiService.get('/kitchen/chefs');
        allChefs.value = data; 
        
        // Load data koki yang sebelumnya dicentang dari local storage (Mencegah reset)
        const savedChefIds = localStorage.getItem('kds_active_chef_ids');
        if (savedChefIds) {
            selectedChefIds.value = JSON.parse(savedChefIds);
        } else {
            // Jika belum ada riwayat, centang semua by default
            selectedChefIds.value = data.map((chef: any) => chef.id);
            saveChefSelection();
        }
    } catch (e) {
        console.error("Gagal mengambil data koki", e);
    } finally {
        isLoadingChefs.value = false;
    }
};

const removeChef = (id: number) => {
    selectedChefIds.value = selectedChefIds.value.filter(chefId => chefId !== id);
    saveChefSelection();
};

const fetchOrders = async () => {
  isLoadingOrders.value = true;
  try {
    const params = new URLSearchParams({
        page: filters.page.toString(),
        per_page: filters.per_page.toString(),
        status: filters.status,
        search: filters.search
    });

    const { data } = await ApiService.get(`/kitchen/orders?${params.toString()}`);
    
    orders.value = data.data; 
    pagination.current_page = data.current_page;
    pagination.last_page = data.last_page;
    pagination.per_page = data.per_page;
    pagination.total = data.total;

    calculateQueue();

  } catch (e) { 
      console.error(e); 
  } finally { 
      isLoadingOrders.value = false; 
  }
};

const calculateQueue = () => {
    let activeChefs = selectedChefs.value;
    let activeChefsCount = activeChefs.length > 0 ? activeChefs.length : 1;
    let chefWorkloads = Array(activeChefsCount).fill(0);

    let activeOrders = orders.value.filter(o => ['pending', 'processing', 'paid'].includes(o.status));
    let sortedActiveOrders = [...activeOrders].reverse();

    sortedActiveOrders.forEach(order => {
        let chosenChefIndex = 0;
        let minWorkload = chefWorkloads[0];
        
        for (let i = 1; i < chefWorkloads.length; i++) {
            if (chefWorkloads[i] < minWorkload) {
                minWorkload = chefWorkloads[i];
                chosenChefIndex = i;
            }
        }

        let orderCookingTime = 0;
        if (order.items && order.items.length > 0) {
            order.items.forEach((item: any) => {
                const estTimePerMenu = item.menu?.cooking_estimation_time || 10;
                const tambahanWaktuPorsi = (item.quantity - 1) * 2; 
                orderCookingTime += (estTimePerMenu + tambahanWaktuPorsi);
            });
        }
        orderCookingTime = Math.max(5, orderCookingTime);

        order.calculated_estimation = minWorkload + orderCookingTime;
        
        // Distribusi nama koki berdasarkan centang
        if (activeChefs.length > 0) {
            order.assigned_chef_name = activeChefs[chosenChefIndex].name;
        } else {
            order.assigned_chef_name = `Tunggu Koki`;
        }

        chefWorkloads[chosenChefIndex] += orderCookingTime;
    });
};

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filters.search = searchInput.value;
        filters.page = 1; 
        fetchOrders();
    }, 500);
};

const setFilterStatus = (status: string) => {
    filters.status = status;
    filters.page = 1;
    fetchOrders();
}

const changePage = (page: number) => {
    if (page < 1 || page > pagination.last_page) return;
    filters.page = page;
    fetchOrders();
};

const openDetail = (order: any) => { 
    if (detailModalRef.value) detailModalRef.value.open(order);
};

// Utilities Helpers
const getGuestName = (o: any) => o.guest?.name || o.user?.name || 'Tamu Umum';
const getLocationInfo = (o: any) => {
    if (o.room) return `Kamar ${o.room.room_number}`;
    if (o.table) return `Meja ${o.table.name}`;
    return 'Take Away / POS';
}
const formatTime = (d: string) => new Date(d).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');

const getStatusLabel = (s: string) => statusOptions[s] || s;

const getStatusClass = (s: string) => {
    const map: Record<string, string> = {
        pending: 'badge-light-danger text-danger', 
        processing: 'badge-light-warning text-warning',
        delivering: 'badge-light-primary text-primary', 
        completed: 'badge-light-success text-success',
        paid: 'badge-light-dark text-gray-700'
    };
    return map[s] || 'badge-light text-gray-500';
};

const getLineColor = (s: string) => {
    const map: Record<string, string> = {
        pending: 'bg-danger', processing: 'bg-warning', delivering: 'bg-primary', completed: 'bg-success', paid: 'bg-dark'
    };
    return map[s] || 'bg-secondary';
}

const initData = async () => {
    await fetchChefs(); 
    await fetchOrders(); 
};

onMounted(initData);
</script>

<style scoped>
/* Base Styles Tetap Scoped */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff5f0 !important; }

.card-adaptive { background-color: #fff; }

.hover-elevate { transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease; }
.hover-elevate:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
.transition-all { transition: all 0.3s ease; }
.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.anim-fade-in { animation: fadeIn 0.8s ease-out forwards; }
.anim-slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(-20px); }
.anim-fade-up { animation: fadeUp 0.6s ease-out forwards; opacity: 0; }
.anim-staggered-item { 
    animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
    opacity: 0; 
    transform: translateY(30px); 
    animation-delay: var(--delay);
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
</style>

<style>
/* Dark Mode Overrides Tidak Menggunakan Scoped
   Penggunaan .kds-index-container mencegah CSS bocor ke halaman lain 
*/
[data-bs-theme="dark"] .kds-index-container .card-adaptive { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .kds-index-container .bg-light-orange { background-color: rgba(255, 107, 0, 0.15) !important; }

[data-bs-theme="dark"] .kds-index-container .bg-white { background-color: #1e1e2d !important; border-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-index-container .bg-light { background-color: #2b2b40 !important; }

[data-bs-theme="dark"] .kds-index-container .border-gray-100 { border-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-index-container .border-gray-200 { border-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-index-container .border-gray-300 { border-color: #323248 !important; }

[data-bs-theme="dark"] .kds-index-container .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-800 { color: #e4e6ef !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-700 { color: #b5b5c3 !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-600 { color: #a1a5b7 !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-500 { color: #7e8299 !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-400 { color: #565674 !important; }

[data-bs-theme="dark"] .kds-index-container .hover-text-gray-800:hover { color: #ffffff !important; }
[data-bs-theme="dark"] .kds-index-container .hover-elevate:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.3) !important; }

/* Komponen Form & Dropdown */
[data-bs-theme="dark"] .kds-index-container .form-control { background-color: #2b2b40 !important; color: #ffffff !important; border-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-index-container .dropdown-menu { background-color: #1e1e2d !important; border: 1px solid #2b2b40 !important; }
[data-bs-theme="dark"] .kds-index-container .form-check-label { color: #e4e6ef !important; }
[data-bs-theme="dark"] .kds-index-container .form-check-input { background-color: #2b2b40 !important; border-color: #323248 !important; }

/* Pagination Bootstrap */
[data-bs-theme="dark"] .kds-index-container .page-link { background-color: #1e1e2d !important; border-color: #2b2b40 !important; color: #ffffff !important; }
[data-bs-theme="dark"] .kds-index-container .page-item.disabled .page-link { background-color: #151521 !important; color: #7e8299 !important; border-color: #2b2b40 !important; }
[data-bs-theme="dark"] .kds-index-container .page-item.active .page-link { background-color: #ff6b00 !important; color: #ffffff !important; border-color: #ff6b00 !important; }

[data-bs-theme="dark"] .kds-index-container .custom-scroll::-webkit-scrollbar-thumb { background: #474761 !important; }
</style>