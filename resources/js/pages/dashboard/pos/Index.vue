<template>
  <div class="d-flex flex-column flex-xl-row gap-7 h-100 anim-fade-in p-2 position-relative">
    
    <!-- Kolom Kiri: Konten Utama (Menu, Table, Online, Kitchen) -->
    <div class="flex-lg-row-fluid">
      <div class="card card-custom border-0 shadow-lg rounded-4 overflow-hidden h-100">
        
        <!-- Header Navigasi Tab (Sekarang Icon Only & Hemat Ruang) -->
        <div class="card-header border-0 pt-6 px-8 bg-adaptive-light z-index-1 pb-0 min-h-auto">
            <div class="d-flex align-items-center justify-content-between w-100 overflow-auto pb-3">
              <ul class="nav nav-pills nav-pills-custom gap-3 border-0 flex-nowrap">
                <li class="nav-item">
                  <a href="#" :class="getTabClass('menu')" @click.prevent="activeTab = 'menu'" title="Menu & POS">
                    <i class="bi bi-grid-fill fs-3" :class="getIconClass('menu')"></i>
                  </a>
                </li>
                <li class="nav-item position-relative">
                  <a href="#" :class="getTabClass('online')" @click.prevent="activeTab = 'online'" title="Pesanan Online">
                    <i class="bi bi-cloud-download-fill fs-3" :class="getIconClass('online')"></i>
                    <span v-if="onlineOrdersCount > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white shadow-sm px-2">
                      {{ onlineOrdersCount }}
                    </span>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="#" :class="getTabClass('tables')" @click.prevent="activeTab = 'tables'" title="Kelola Meja">
                    <i class="bi bi-layout-three-columns fs-3" :class="getIconClass('tables')"></i>
                  </a>
                </li>
                <!-- Tab Estimasi Dapur -->
                <li class="nav-item">
                  <a href="#" :class="getTabClass('estimasi')" @click.prevent="activeTab = 'estimasi'" title="Dapur & Estimasi">
                    <i class="bi bi-stopwatch-fill fs-3" :class="getIconClass('estimasi')"></i>
                  </a>
                </li>
              </ul>
            </div>
        </div>

        <!-- Body Konten -->
        <div class="card-body px-8 py-6 h-100">
            <KeepAlive>
                <component 
                    :is="activeComponent"
                    @add-to-cart="addToCart"
                    @import-order="importOrderToCart"
                    @select-table="selectTableFromLayout"
                    @update-count="updateOnlineCount"
                />
            </KeepAlive>
        </div>
      </div>
    </div>

    <!-- Kolom Kanan: Keranjang / POS (Sidebar) -->
    <div class="flex-column-auto w-100 w-xl-400px transition-all">
       <div class="card card-custom border-0 shadow-lg rounded-4 h-100 position-sticky top-0 bg-adaptive-light">
          
          <!-- Loading Overlay Spinner menutupi seluruh Card Pesanan Baru -->
          <div v-if="isRefreshing" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center rounded-4 overlay-bg" style="z-index: 100;">
              <div class="spinner-border text-orange mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
              <span class="fw-bolder fs-5 text-gray-800">Memperbarui Data...</span>
          </div>

          <div class="card-header border-0 pt-6 px-7 pb-0 bg-adaptive-light">
             <div class="w-100 d-flex justify-content-between align-items-start">
                <h3 class="card-title fw-bolder text-adaptive-dark flex-column align-items-start">
                  <span class="fs-4">Pesanan Baru</span>
                  <span class="text-gray-400 fs-8 mt-1 fw-bold">Items dalam keranjang</span>
                </h3>
                <div class="card-toolbar d-flex gap-2">
                  <!-- Tombol Refresh Tujuan Pesanan -->
                  <button @click="fetchGlobalData" class="btn btn-icon btn-sm btn-light-primary rounded-circle shadow-sm hover-scale transition-all" title="Refresh Tujuan Pesanan" :disabled="isRefreshing">
                    <i class="bi bi-arrow-clockwise" :class="{ 'spin': isRefreshing }"></i>
                  </button>
                  <!-- Tombol Hapus Keranjang -->
                  <button @click="clearCart" class="btn btn-icon btn-sm btn-light-danger rounded-circle shadow-sm hover-scale" title="Kosongkan Keranjang">
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </div>
             </div>
          </div>

          <div class="card-body px-7 pt-4 d-flex flex-column h-100">
             <!-- Pilihan Lokasi (Meja/Kamar) -->
             <div class="mb-6 bg-light p-4 rounded-4 border border-dashed border-gray-300 position-relative">
                <label class="form-label fs-8 fw-bold text-uppercase text-gray-500 mb-3 d-block">Tujuan Pesanan</label>
                <el-tabs v-model="deliveryType" class="compact-tabs mb-3 w-100">
                   <el-tab-pane label="Kamar Hotel" name="room"></el-tab-pane>
                   <el-tab-pane label="Dine In (Meja)" name="table"></el-tab-pane>
                </el-tabs>
                <el-select v-model="selectedLocationId" filterable placeholder="Pilih Nomor..." class="w-100 custom-el-select shadow-sm" size="large">
                   <template v-if="deliveryType === 'room'">
                      <el-option v-for="room in occupiedRooms" :key="room.id" :label="getRoomLabel(room)" :value="room.id">
                         <div class="d-flex justify-content-between">
                            <span class="fw-bold">{{ room.room_number }}</span>
                            <span class="text-muted fs-9">{{ room.check_ins?.[0]?.guest?.name }}</span>
                         </div>
                      </el-option>
                   </template>
                   <template v-else>
                      <el-option v-for="table in tables" :key="table.id" :label="table.name" :value="table.id">
                          <span class="float-start fw-bold">{{ table.name }}</span>
                          <span class="float-end fs-9 badge" :class="table.status === 'available' ? 'badge-light-success text-success' : 'badge-light-danger text-danger'">{{ table.status_label }}</span>
                      </el-option>
                   </template>
                </el-select>
             </div>

             <!-- List Item Keranjang -->
             <div class="flex-grow-1 overflow-auto custom-scroll mb-5 pe-2" style="max-height: 380px;">
                <div v-if="cart.length === 0" class="d-flex flex-column align-items-center justify-content-center h-100 text-center py-10 opacity-50">
                   <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png" width="60" class="mb-3 grayscale" alt="Empty">
                   <span class="text-gray-500 fw-bold fs-7">Keranjang kosong</span>
                </div>
                <div v-for="item in cart" :key="item.menu_id" class="d-flex align-items-center mb-3 bg-adaptive-light rounded-3 p-3 shadow-sm border border-adaptive-border transition-all hover-elevate-up">
                   <div class="symbol symbol-40px me-3">
                      <div class="symbol-label bg-orange text-white fw-bold fs-7">{{ item.quantity }}x</div>
                   </div>
                   <div class="flex-grow-1">
                      <div class="fw-bolder text-adaptive-dark fs-7 line-clamp-1">{{ item.name }}</div>
                      <div class="text-adaptive-secondary fs-9 fw-bold">{{ formatCurrency(item.price) }}</div>
                   </div>
                   <div class="d-flex align-items-center bg-adaptive-lighter rounded-pill p-1">
                      <button @click="updateQuantity(item, -1)" class="btn btn-icon btn-xs btn-white text-danger rounded-circle shadow-sm w-25px h-25px"><i class="bi bi-dash"></i></button>
                      <button @click="updateQuantity(item, 1)" class="btn btn-icon btn-xs btn-white text-success rounded-circle shadow-sm w-25px h-25px ms-1"><i class="bi bi-plus"></i></button>
                   </div>
                </div>
             </div>

             <!-- Footer Total & Aksi -->
             <div class="mt-auto border-top border-adaptive-border pt-5 bg-adaptive-light z-index-2">
                <div class="d-flex justify-content-between align-items-center mb-5">
                   <span class="text-adaptive-secondary fw-bold fs-5">Total Bayar</span>
                   <span class="text-orange fw-bolder fs-2">{{ formatCurrency(totalPrice) }}</span>
                </div>
                
                <div class="d-grid grid-cols-2 gap-3 mb-4 d-flex">
                    <button @click="selectedPaymentMethod = 'cash'" :class="selectedPaymentMethod === 'cash' ? 'bg-success text-white border-success' : 'bg-adaptive-lighter text-adaptive-dark border-adaptive-border'" class="btn btn-outline border-2 fw-bold flex-grow-1 py-3 rounded-3 d-flex align-items-center justify-content-center gap-2 transition-all">
                        <i class="bi bi-cash fs-4" :class="selectedPaymentMethod === 'cash' ? 'text-white' : 'text-success'"></i> CASH
                    </button>
                    <button @click="selectedPaymentMethod = 'midtrans'" :class="selectedPaymentMethod === 'midtrans' ? 'bg-primary text-white border-primary' : 'bg-adaptive-lighter text-adaptive-dark border-adaptive-border'" class="btn btn-outline border-2 fw-bold flex-grow-1 py-3 rounded-3 d-flex align-items-center justify-content-center gap-2 transition-all">
                        <i class="bi bi-qr-code-scan fs-4" :class="selectedPaymentMethod === 'midtrans' ? 'text-white' : 'text-primary'"></i> QRIS
                    </button>
                </div>

                <button @click="submitOrder" class="btn btn-orange w-100 py-3 rounded-3 shadow-lg hover-scale fw-bold fs-6 d-flex justify-content-center align-items-center gap-2" :disabled="!canProcessOrder || processing">
                   <span v-if="processing" class="spinner-border spinner-border-sm"></span>
                   <i v-else class="bi bi-check-circle-fill"></i> 
                   {{ processing ? 'Memproses...' : 'Proses Pembayaran' }}
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
import { useThemeStore } from "@/stores/theme";

// Import Component
import MenuTab from "./components/MenuTab.vue";
import OnlineOrdersTab from "./components/OnlineOrdersTab.vue";
import TablesTab from "./components/TablesTab.vue";
import EstimasiTab from "./components/EstimasiTab.vue";

const themeStore = useThemeStore();
const activeTab = ref<'menu' | 'online' | 'tables' | 'estimasi'>('menu');

const activeComponent = computed(() => {
    switch(activeTab.value) {
        case 'menu': return MenuTab;
        case 'online': return OnlineOrdersTab;
        case 'tables': return TablesTab;
        case 'estimasi': return EstimasiTab;
        default: return MenuTab;
    }
});

const deliveryType = ref<'room' | 'table'>('room');
const selectedLocationId = ref<number | null>(null);
const selectedPaymentMethod = ref<'cash' | 'midtrans' | null>(null);
const cart = ref<any[]>([]);
const occupiedRooms = ref<any[]>([]);
const tables = ref<any[]>([]);
const onlineOrdersCount = ref(0);
const processing = ref(false); 
const isRefreshing = ref(false); 

// Tonton perubahan tab, jika berubah, refresh data di sidebar pesanan baru
watch(activeTab, () => {
    fetchGlobalData();
});

const addToCart = (menu: any) => {
    const item = cart.value.find(i => i.menu_id === menu.id);
    if(item) { item.quantity++; }
    else cart.value.push({ menu_id: menu.id, name: menu.name, price: menu.price, quantity: 1 });
};

const updateQuantity = (item: any, n: number) => {
    const qty = item.quantity + n;
    if (qty > 0) item.quantity = qty;
    else cart.value = cart.value.filter(c => c.menu_id !== item.menu_id);
};

const importOrderToCart = (order: any) => {
    cart.value = order.items.map((i: any) => ({ menu_id: i.menu_id, name: i.menu?.name, price: i.price, quantity: i.quantity }));
    if(order.room_id) { deliveryType.value = 'room'; selectedLocationId.value = order.room_id; }
    else if(order.table_id) { deliveryType.value = 'table'; selectedLocationId.value = order.table_id; }
    
    activeTab.value = 'menu';
};

const selectTableFromLayout = (t: any) => {
    activeTab.value = 'menu';
    deliveryType.value = 'table';
    selectedLocationId.value = t.id;
};

const clearCart = () => { cart.value = []; selectedPaymentMethod.value = null; };
const totalPrice = computed(() => cart.value.reduce((total, item) => total + item.price * item.quantity, 0));
const canProcessOrder = computed(() => selectedLocationId.value !== null && cart.value.length > 0 && selectedPaymentMethod.value);

const updateOnlineCount = (count: number) => { onlineOrdersCount.value = count; };

const submitOrder = async () => {
    if(!canProcessOrder.value) return;
    
    processing.value = true;
    try {
        const res = await ApiService.post('/orders', {
            items: cart.value.map(i => ({ menu_id: i.menu_id, quantity: i.quantity })),
            payment_method: selectedPaymentMethod.value,
            room_id: deliveryType.value === 'room' ? selectedLocationId.value : null,
            table_id: deliveryType.value === 'table' ? selectedLocationId.value : null
        });

        if (selectedPaymentMethod.value === 'midtrans' && res.data.snap_token) {
            (window as any).snap.pay(res.data.snap_token, {
                onSuccess: function (result: any) {
                    Swal.fire('Berhasil!', 'Pembayaran QRIS Diterima.', 'success');
                    clearCart();
                },
                onPending: function (result: any) {
                    Swal.fire('Pending', 'Selesaikan pembayaran QRIS Anda.', 'info');
                    clearCart();
                },
                onError: function (result: any) {
                    Swal.fire('Gagal', 'Pembayaran QRIS gagal.', 'error');
                },
                onClose: function () {
                    Swal.fire('Dibatalkan', 'Anda menutup layar pembayaran.', 'warning');
                    clearCart();
                }
            });
        } else {
            Swal.fire({ icon: 'success', title: 'Pesanan Dibuat', text: 'Bahan baku telah terpotong', showConfirmButton: false, timer: 1500 });
            clearCart(); 
        }

    } catch(e) { 
        Swal.fire('Error', 'Gagal memproses pesanan', 'error'); 
    } finally {
        processing.value = false;
    }
};

const fetchGlobalData = async () => {
    isRefreshing.value = true;
    try {
        const [roomsRes, tablesRes] = await Promise.all([
            ApiService.get("/pos/occupied-rooms"),
            ApiService.get("/pos/tables"),
        ]);
        occupiedRooms.value = roomsRes.data || [];
        tables.value = tablesRes.data || [];
    } catch (e) { 
        console.error(e); 
    } finally {
        setTimeout(() => { isRefreshing.value = false; }, 500); 
    }
};

const getTabClass = (tabName: string) => {
    const isActive = activeTab.value === tabName;
    const common = "nav-link d-flex align-items-center justify-content-center w-50px h-50px rounded-circle fw-bold transition-all p-0";
    return isActive ? `${common} bg-orange text-white shadow-md` : `${common} bg-light text-gray-600 hover-scale`;
};
const getIconClass = (tabName: string) => activeTab.value === tabName ? "text-white" : "text-gray-500";
const formatCurrency = (v: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);
const getRoomLabel = (r: any) => `${r.room_number}`;

onMounted(() => { fetchGlobalData(); setInterval(fetchGlobalData, 30000); });
</script>

<style scoped>
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.btn-orange { background: #ff6b00; color: white; border: none; transition: all 0.2s; }
.btn-orange:hover { background: #e05e00; transform: translateY(-2px); }
.hover-scale:hover { transform: scale(1.05); }
.anim-fade-in { animation: fadeIn 0.5s ease forwards; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* Animasi Spin untuk icon refresh */
.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Overlay Background untuk Loading Card */
.overlay-bg {
    background-color: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(2px);
}

/* Adaptive Light/Dark Mode Classes */
.bg-adaptive-light { background-color: #ffffff; }
.bg-adaptive-lighter { background-color: #f5f5f5; }
.text-adaptive-dark { color: #1f2937; }
.text-adaptive-secondary { color: #6b7280; }
.border-adaptive-border { border-color: #e5e7eb; }

/* DARK MODE SUPPORT */
[data-bs-theme="dark"] .bg-adaptive-light { 
    background-color: #1e1e2d !important; 
}
[data-bs-theme="dark"] .bg-adaptive-lighter {
    background-color: #2b2b40 !important;
}
[data-bs-theme="dark"] .overlay-bg {
    background-color: rgba(30, 30, 45, 0.8) !important;
}
[data-bs-theme="dark"] .text-adaptive-dark {
    color: #ffffff !important;
}
[data-bs-theme="dark"] .text-adaptive-secondary {
    color: #cdcdde !important;
}
[data-bs-theme="dark"] .border-adaptive-border {
    border-color: #323248 !important;
}
[data-bs-theme="dark"] .card {
    background-color: #1e1e2d;
    border-color: #323248;
}
[data-bs-theme="dark"] .card-header {
    background-color: #1e1e2d !important;
    border-color: #323248;
}
[data-bs-theme="dark"] .card-body {
    background-color: #1e1e2d;
}
[data-bs-theme="dark"] :deep(.el-tabs) {
    --el-border-color: #323248;
}
[data-bs-theme="dark"] :deep(.el-tabs__nav-wrap) {
    background-color: #1e1e2d;
}
[data-bs-theme="dark"] :deep(.el-input__wrapper),
[data-bs-theme="dark"] :deep(.el-select__wrapper) {
    background-color: #1b1b29 !important;
    border-color: #323248 !important;
    color: #ffffff;
}
[data-bs-theme="dark"] :deep(.el-input__wrapper.is-focus),
[data-bs-theme="dark"] :deep(.el-select__wrapper.is-focus) {
    border-color: #ff6b00 !important;
    background-color: #1b1b29 !important;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown) {
    background-color: #1e1e2d !important;
    border-color: #323248 !important;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown__item) {
    color: #cdcdde;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown__item:hover) {
    background-color: #2b2b40 !important;
    color: #ff6b00;
}
[data-bs-theme="dark"] :deep(.el-option-group__title) {
    color: #cdcdde;
}
[data-bs-theme="dark"] :deep(.el-tab-pane__content) {
    background-color: #1e1e2d;
}
</style>