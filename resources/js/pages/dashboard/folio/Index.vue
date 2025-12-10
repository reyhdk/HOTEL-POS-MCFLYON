<template>
  <div class="d-flex flex-column gap-5 anim-fade-in">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 mb-4 anim-slide-down">
       <div>
          <h1 class="text-gray-900 fw-bolder fs-2 m-0">Folio Tamu</h1> 
          <p class="text-gray-500 fw-bold fs-7 m-0">Kelola pembayaran & check-out kamar</p>
       </div>

       <div class="position-relative w-100 w-md-300px">
          <i class="bi bi-search position-absolute top-50 ms-4 translate-middle-y text-gray-500 fs-7"></i>
          <input 
             type="text" 
             v-model="searchQuery" 
             class="form-control form-control-solid ps-12 rounded-pill h-45px" 
             placeholder="Cari Kamar / Tamu..."
          />
       </div>
    </div>

    <div class="position-relative min-h-300px">
       
       <div v-if="loading" class="py-20 text-center">
          <div class="spinner-border text-orange mb-3" role="status"></div>
          <p class="text-gray-500 fw-semibold">Memuat data folio...</p>
       </div>

       <div v-else-if="filteredFolios.length === 0" class="card card-dashed border-gray-300 bg-light rounded-4 py-15 text-center anim-fade-up">
          <div class="mb-4">
             <i class="bi bi-person-x fs-3x text-orange opacity-50"></i>
          </div>
          <h4 class="text-gray-800 fw-bold">Tidak Ada Data</h4>
          <span class="text-gray-400 fs-7">
             {{ searchQuery ? 'Tidak ditemukan tamu dengan kata kunci tersebut.' : 'Belum ada tamu yang check-in saat ini.' }}
          </span>
       </div>

       <div v-else class="row g-4">
          <TransitionGroup name="staggered-list" tag="div" class="contents" style="display: contents;">
             <div 
                class="col-12 col-md-6 col-xl-4" 
                v-for="(folio, index) in filteredFolios" 
                :key="folio.id"
                :style="{ '--delay': `${index * 0.1}s` }"
             >
                <div class="h-100 anim-staggered-item">
                   
                   <div 
                      class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden hover-elevate"
                      :class="{ 'ring-2 ring-orange': expandedFolioId === folio.id }"
                   >
                      <div class="position-absolute top-0 start-0 h-100 w-4px bg-orange rounded-start"></div>

                      <div class="card-body p-6 d-flex flex-column align-items-center text-center">
                         
                         <div class="symbol symbol-65px mb-4">
                            <div class="symbol-label bg-light-orange text-orange rounded-circle">
                               <i class="bi bi-door-open fs-2x"></i>
                            </div>
                         </div>

                         <div class="mb-4 w-100">
                            <h3 class="fs-2 fw-bolder text-gray-800 mb-1">
                               Kamar {{ folio.room_number }}
                            </h3>
                            <div class="d-flex justify-content-center align-items-center">
                               <i class="bi bi-person-fill text-gray-400 fs-7 me-1"></i>
                               <span class="text-gray-600 fw-bold fs-6 text-truncate mw-100">
                                  {{ folio.guest_name }}
                               </span>
                            </div>
                         </div>

                         <div class="separator separator-dashed border-gray-300 w-100 mb-5"></div>

                         <div class="mb-6 w-100">
                            <div class="text-gray-400 fs-9 fw-bold text-uppercase ls-1 mb-1">Total Tagihan</div>
                            <div class="fs-1 fw-bolder text-gray-800 lh-1">
                               {{ formatCurrency(folio.total_bill) }}
                            </div>
                         </div>

                         <div class="d-flex gap-2 w-100 mt-auto">
                            <button 
                               @click="toggleDetails(folio.id)" 
                               class="btn btn-sm btn-light-primary flex-grow-1 fw-bold fs-7"
                            >
                               <i class="bi" :class="expandedFolioId === folio.id ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                               {{ expandedFolioId === folio.id ? 'Tutup' : 'Rincian' }}
                            </button>
                            
                            <button 
                               @click="processFolioCheckout(folio)" 
                               class="btn btn-sm btn-success flex-grow-1 fw-bold fs-7 shadow-sm"
                               :disabled="isCheckoutLoading"
                            >
                               <span v-if="isCheckoutLoading" class="spinner-border spinner-border-sm me-1"></span>
                               <i v-else class="bi bi-check-circle-fill me-1"></i> Check-out
                            </button>
                         </div>

                         <div v-if="expandedFolioId === folio.id" class="w-100 mt-5 pt-4 border-top border-gray-200 anim-expand text-start">
                            
                            <div v-if="!folio.orders || folio.orders.length === 0" class="text-center text-gray-400 fs-8 py-2">
                               Belum ada pesanan tambahan.
                            </div>

                            <div v-else class="d-flex flex-column gap-3 max-h-200px overflow-auto custom-scroll pe-2">
                               <div v-for="order in folio.orders" :key="order.id" class="bg-light rounded-2 p-3 border border-gray-200">
                                  <div class="d-flex justify-content-between align-items-center mb-2">
                                     <span class="badge badge-light-warning text-warning fw-bold shadow-sm fs-9">Order #{{ order.id }}</span>
                                     <span class="text-gray-500 fs-9">{{ formatDateShort(order.created_at) }}</span>
                                  </div>
                                  <div class="d-flex flex-column gap-1">
                                     <div v-for="item in order.items" :key="item.id" class="d-flex justify-content-between fs-8 text-gray-700">
                                        <span>{{ item.menu.name }} <span class="text-gray-400 ms-1">x{{ item.quantity }}</span></span>
                                        <span class="fw-bold text-gray-800">{{ formatCurrency(item.price * item.quantity) }}</span>
                                     </div>
                                  </div>
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

// --- INTERFACES ---
interface MenuItem { id: number; name: string; }
interface OrderItem { id: number; price: number; quantity: number; menu: MenuItem; }
interface Order { id: number; total_price: number; items: OrderItem[]; created_at: string; status: string; }
interface FolioData { 
    id: number; 
    room_number: string; 
    guest_name: string; 
    total_bill: number; 
    orders: Order[];
}

// --- STATE ---
const folios = ref<FolioData[]>([]);
const loading = ref(true);
const isCheckoutLoading = ref(false);
const searchQuery = ref("");
const expandedFolioId = ref<number | null>(null);

// --- LOGIC ---
const getFolios = async () => {
  try {
    loading.value = true;
    const { data } = await ApiService.get("/folios");
    folios.value = data;
  } catch (error) {
    console.error("Fetch Error:", error);
  } finally {
    loading.value = false;
  }
};

const filteredFolios = computed(() => {
   if (!searchQuery.value) return folios.value;
   const term = searchQuery.value.toLowerCase();
   return folios.value.filter(f => 
      f.room_number.toLowerCase().includes(term) || 
      f.guest_name.toLowerCase().includes(term)
   );
});

const toggleDetails = (id: number) => {
   expandedFolioId.value = expandedFolioId.value === id ? null : id;
};

const processFolioCheckout = (folio: FolioData) => {
    let swalConfig: any = {
        title: "Konfirmasi Check-out",
        html: `Tamu <strong>${folio.guest_name}</strong> (Kamar ${folio.room_number}) akan check-out.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Check-out",
        cancelButtonText: "Batal",
        buttonsStyling: false,
        customClass: { confirmButton: "btn btn-primary fw-bold", cancelButton: "btn btn-light fw-bold" }
    };

    if (folio.total_bill > 0) {
        swalConfig = {
           ...swalConfig,
           title: "Tagihan Belum Lunas",
           html: `
             <div class="d-flex flex-column align-items-center mb-3">
                <span class="fs-1 fw-bolder text-orange mb-1">${formatCurrency(folio.total_bill)}</span>
                <span class="text-gray-500 fs-7">Total yang harus dibayar</span>
             </div>
             <p class="text-gray-600">Lanjutkan pembayaran dan proses check-out untuk <strong>${folio.guest_name}</strong>?</p>
           `,
           icon: "info",
           confirmButtonText: "Bayar & Check-out",
           customClass: { confirmButton: "btn btn-success fw-bold", cancelButton: "btn btn-light fw-bold" }
        };
    }

    Swal.fire(swalConfig).then((result) => {
        if (result.isConfirmed) executeCheckout(folio);
    });
};

const executeCheckout = async (folio: FolioData) => {
    isCheckoutLoading.value = true;
    try {
        await ApiService.post(`/folios/${folio.id}/checkout`, {});
        Swal.fire({ icon: 'success', title: "Berhasil!", text: "Tamu berhasil check-out.", timer: 2000, showConfirmButton: false });
        getFolios(); 
    } catch (error: any) {
        Swal.fire("Gagal", error.response?.data?.message || "Error saat check-out.", "error");
    } finally {
        isCheckoutLoading.value = false;
    }
};

// Helpers
const formatCurrency = (v: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);
const formatDateShort = (d: string) => new Date(d).toLocaleTimeString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });

onMounted(getFolios);
</script>

<style scoped>
/* --- THEME COLORS --- */
/* Kita definisikan warna orange yang konsisten */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }

/* Override Metronic bg-light-orange agar tetap terlihat bagus di kedua mode */
.bg-light-orange { background-color: #fff5f0 !important; }

/* Khusus Dark Mode: Sesuaikan background light-orange agar tidak terlalu menyilaukan */
[data-bs-theme="dark"] .bg-light-orange {
    background-color: rgba(255, 107, 0, 0.15) !important;
}

.ring-2 { box-shadow: 0 0 0 2px #ff6b00 !important; }

/* --- INTERAKSI KARTU --- */
.hover-elevate {
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease;
}
.hover-elevate:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important;
}

/* --- ANIMASI --- */
.anim-fade-in { animation: fadeIn 0.8s ease-out forwards; }
.anim-slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(-20px); }
.anim-staggered-item { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(40px); animation-delay: var(--delay); }
.anim-expand { animation: expandDown 0.3s ease-out forwards; transform-origin: top; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
@keyframes expandDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

/* Scrollbar */
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
.max-h-200px { max-height: 200px; }
.fs-2x { font-size: 2rem !important; }
</style>