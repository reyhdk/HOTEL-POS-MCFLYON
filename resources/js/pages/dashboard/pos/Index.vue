<template>
  <div class="d-flex flex-column flex-xl-row gap-5 h-100 anim-fade-in">
    
    <div class="flex-lg-row-fluid">
      <div class="d-flex flex-column gap-5">
        
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-5 mb-2 anim-slide-down">
           <div>
              <h2 class="fw-bolder text-gray-900 m-0">Menu Restoran</h2> <span class="text-gray-500 fw-bold fs-7">Pilih menu untuk ditambahkan ke pesanan</span>
           </div>
           
           <div class="position-relative w-100 w-md-300px">
              <i class="bi bi-search position-absolute top-50 ms-4 translate-middle-y text-gray-500 fs-7"></i>
              <input 
                 type="text" 
                 v-model="searchMenuQuery" 
                 class="form-control form-control-solid ps-12 rounded-pill border-0 shadow-sm h-45px custom-input" 
                 placeholder="Cari Menu..."
              />
           </div>
        </div>

        <div class="position-relative min-h-300px">
           
           <div v-if="loading.menus" class="py-20 text-center">
              <div class="spinner-border text-orange mb-3" role="status"></div>
              <p class="text-gray-500 fw-semibold">Mengambil data menu...</p>
           </div>

           <div v-else-if="filteredMenus.length === 0" class="card border-dashed border-gray-300 bg-light-orange-subtle rounded-4 py-15 text-center anim-fade-up">
              <i class="bi bi-egg-fried fs-3x text-orange opacity-50 mb-3"></i>
              <h4 class="text-gray-800 fw-bold">Menu Tidak Ditemukan</h4>
              <span class="text-gray-400 fs-7">Coba kata kunci pencarian lain.</span>
           </div>

           <div v-else class="row g-4">
              <TransitionGroup name="staggered-list" tag="div" class="contents" style="display: contents;">
                 <div 
                    class="col-12 col-md-4 col-xxl-3" 
                    v-for="(menu, index) in filteredMenus" 
                    :key="menu.id"
                    :style="{ '--delay': `${index * 0.05}s` }"
                 >
                    <div class="h-100 anim-staggered-item">
                       <div 
                          class="card card-custom h-100 border-0 shadow-sm rounded-4 cursor-pointer hover-float position-relative overflow-hidden group-hover"
                          @click="addToCart(menu)"
                          :class="{ 'opacity-50 grayscale': menu.stock <= 0 }"
                       >
                          <div class="position-absolute top-0 end-0 m-3 z-index-1">
                             <span v-if="menu.stock <= 0" class="badge bg-danger shadow-sm">Habis</span>
                             <span v-else-if="menu.stock < 5" class="badge bg-warning text-dark shadow-sm">Sisa {{ menu.stock }}</span>
                          </div>

                          <div class="card-body p-0 d-flex flex-column h-100">
                             <div class="position-relative h-150px overflow-hidden bg-light-gray rounded-top-4">
                                <img 
                                   :src="menu.image_url || '/media/svg/files/blank-image.svg'" 
                                   class="w-100 h-100 object-fit-cover transition-transform" 
                                   alt="Menu"
                                />
                                <div v-if="menu.stock > 0" class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-10 d-flex align-items-center justify-content-center opacity-0 group-hover-show transition-opacity">
                                   <div class="btn btn-icon btn-orange rounded-circle shadow-sm pulse-anim">
                                      <i class="bi bi-plus-lg fs-2 text-white"></i>
                                   </div>
                                </div>
                             </div>

                             <div class="p-4 d-flex flex-column flex-grow-1">
                                <div class="mb-2">
                                   <div class="fw-bolder text-gray-900 fs-6 line-clamp-2 mb-1">{{ menu.name }}</div>
                                   <div class="d-flex justify-content-between align-items-center">
                                      <span class="text-orange fw-bolder fs-5">{{ formatCurrency(menu.price) }}</span>
                                      <span v-if="menu.stock > 0" class="text-gray-500 fs-9 fw-bold">Stok: {{ menu.stock }}</span>
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
    </div>

    <div class="flex-column-auto w-100 w-xl-400px w-xxl-450px">
       <div class="card card-custom border-0 shadow-sm rounded-4 h-100 anim-slide-left position-sticky top-20px" style="z-index: 99;">
          
          <div class="card-header border-0 pt-6 pb-0 px-6">
             <h3 class="card-title fw-bolder text-gray-900 flex-column">
                <span>Pesanan Baru</span>
                <span class="text-gray-500 fs-8 fw-bold mt-1">Isi detail pesanan tamu</span>
             </h3>
             <div class="card-toolbar">
                <span class="badge badge-light-orange text-orange fw-bold fs-7">{{ cart.length }} Items</span>
             </div>
          </div>

          <div class="card-body d-flex flex-column px-6 pt-4 pb-6">
             
             <div class="mb-5">
                <label class="form-label fs-8 fw-bold text-uppercase text-gray-500 required">Pilih Kamar / Meja</label>
                <el-select
                   v-model="selectedRoomId"
                   filterable
                   placeholder="Cari Nomor Kamar..."
                   class="w-100 custom-select"
                   :loading="loading.rooms"
                   clearable
                   size="large"
                >
                   <template #prefix><i class="bi bi-door-open fs-5 text-gray-500"></i></template>
                   <el-option
                      v-for="room in occupiedRooms"
                      :key="room.id"
                      :label="getRoomLabel(room)"
                      :value="room.id"
                   >
                      <span style="float: left">{{ room.room_number }}</span>
                      <span style="float: right; color: #8492a6; font-size: 13px">{{ room.check_ins[0]?.guest?.name }}</span>
                   </el-option>
                </el-select>
             </div>

             <div class="separator separator-dashed border-gray-300 mb-4"></div>

             <div class="flex-grow-1 overflow-auto custom-scroll pe-2 mb-4" style="max-height: calc(100vh - 450px); min-height: 200px;">
                <div v-if="cart.length === 0" class="d-flex flex-column align-items-center justify-content-center h-100 text-center opacity-50">
                   <i class="bi bi-cart-x fs-3x text-gray-300 mb-3"></i>
                   <span class="text-gray-400 fw-bold fs-7">Keranjang kosong</span>
                </div>

                <TransitionGroup name="list">
                   <div v-for="item in cart" :key="item.menu_id" class="d-flex align-items-center mb-4 bg-light-orange-subtle rounded-3 p-3 border border-orange-subtle cart-item-anim">
                      <div class="d-flex flex-column align-items-center me-3 gap-1">
                         <button @click="updateQuantity(item, 1)" class="btn btn-icon btn-xs btn-light shadow-sm text-success rounded-circle hover-scale"><i class="bi bi-chevron-up fs-8"></i></button>
                         <span class="fw-bolder text-gray-800 fs-7">{{ item.quantity }}</span>
                         <button @click="updateQuantity(item, -1)" class="btn btn-icon btn-xs btn-light shadow-sm text-danger rounded-circle hover-scale"><i class="bi bi-chevron-down fs-8"></i></button>
                      </div>
                      
                      <div class="flex-grow-1">
                         <div class="fw-bold text-gray-800 fs-7 line-clamp-1">{{ item.name }}</div>
                         <div class="text-gray-500 fs-9">{{ formatCurrency(item.price) }} / porsi</div>
                      </div>

                      <div class="fw-bolder text-orange fs-7 text-end ps-2">
                         {{ formatCurrency(item.price * item.quantity) }}
                      </div>
                   </div>
                </TransitionGroup>
             </div>

             <div class="mt-auto">
                <div class="bg-light-gray rounded-3 p-4 mb-4">
                   <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-gray-500 fw-bold fs-8">Subtotal</span>
                      <span class="text-gray-800 fw-bold fs-7">{{ formatCurrency(totalPrice) }}</span>
                   </div>
                   <div class="d-flex justify-content-between align-items-center mb-3">
                      <span class="text-gray-500 fw-bold fs-8">Pajak (0%)</span>
                      <span class="text-gray-800 fw-bold fs-7">Rp 0</span>
                   </div>
                   <div class="separator separator-dashed border-gray-400 mb-3"></div>
                   <div class="d-flex justify-content-between align-items-center">
                      <span class="text-gray-900 fw-bolder fs-5">TOTAL</span>
                      <span class="text-orange fw-bolder fs-3">{{ formatCurrency(totalPrice) }}</span>
                   </div>
                </div>

                <button 
                   @click="processOrder" 
                   class="btn btn-orange w-100 py-3 rounded-3 shadow-sm d-flex justify-content-center align-items-center gap-2 btn-active-push" 
                   :disabled="!canProcessOrder"
                >
                   <span v-if="!loading.processing" class="fw-bolder fs-6">Proses Pesanan</span>
                   <span v-else class="d-flex align-items-center">
                      <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                   </span>
                   <i v-if="!loading.processing" class="bi bi-arrow-right-circle-fill fs-4"></i>
                </button>
             </div>

          </div>
       </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import ApiService from "@/core/services/ApiService"; 
import Swal from "sweetalert2";

// --- TYPES & INTERFACES ---
interface Room { id: number; room_number: string; check_ins: any[]; }
interface Menu { id: number; name: string; price: number; image_url: string | null; stock: number; }
interface CartItem { menu_id: number; name: string; price: number; quantity: number; }

// --- STATE ---
const menus = ref<Menu[]>([]);
const occupiedRooms = ref<Room[]>([]);
const cart = ref<CartItem[]>([]);
const loading = ref({ menus: true, rooms: true, processing: false });
const searchMenuQuery = ref("");
const selectedRoomId = ref<number | null>(null);

// --- LOGIC ---
const totalPrice = computed(() => cart.value.reduce((total, item) => total + item.price * item.quantity, 0));
const canProcessOrder = computed(() => selectedRoomId.value !== null && cart.value.length > 0 && !loading.value.processing);

const filteredMenus = computed(() => {
  if (!searchMenuQuery.value) return menus.value;
  return menus.value.filter((menu) => menu.name.toLowerCase().includes(searchMenuQuery.value.toLowerCase()));
});

const fetchMenus = async () => {
  try { loading.value.menus = true; const { data } = await ApiService.get("/menus"); menus.value = data; } 
  catch (e) { console.error(e); } finally { loading.value.menus = false; }
};

const fetchOccupiedRooms = async () => {
  try { loading.value.rooms = true; const { data } = await ApiService.get("/pos/occupied-rooms"); occupiedRooms.value = data; } 
  catch (e) { console.error(e); } finally { loading.value.rooms = false; }
};

const getRoomLabel = (room: Room): string => {
  const guestName = room.check_ins[0]?.guest?.name || 'Tamu';
  return `${room.room_number} - ${guestName}`;
};

const addToCart = (menu: Menu) => {
  if (menu.stock <= 0) return;
  const existingItem = cart.value.find(item => item.menu_id === menu.id);
  if (existingItem) {
    if (existingItem.quantity < menu.stock) existingItem.quantity++;
  } else {
    cart.value.push({ menu_id: menu.id, name: menu.name, price: menu.price, quantity: 1 });
  }
};

const updateQuantity = (item: CartItem, change: number) => {
  const menu = menus.value.find(m => m.id === item.menu_id);
  if (!menu) return;
  const newQuantity = item.quantity + change;
  if (newQuantity > 0 && newQuantity <= menu.stock) { item.quantity = newQuantity; } 
  else if (newQuantity <= 0) { cart.value = cart.value.filter(c => c.menu_id !== item.menu_id); }
};

const processOrder = async () => {
  if (!canProcessOrder.value) return;
  loading.value.processing = true;
  try {
    await ApiService.post('/orders', {
      room_id: selectedRoomId.value,
      items: cart.value.map(i => ({ menu_id: i.menu_id, quantity: i.quantity })),
      payment_method: 'pay_at_checkout'
    });
    Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Pesanan terkirim ke dapur & tagihan masuk folio.', timer: 2000, showConfirmButton: false });
    cart.value = []; selectedRoomId.value = null;
    await fetchMenus(); 
  } catch (error: any) { Swal.fire("Gagal", error.response?.data?.message || "Error memproses pesanan.", "error"); } 
  finally { loading.value.processing = false; }
};

const formatCurrency = (val: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val);

onMounted(() => { fetchMenus(); fetchOccupiedRooms(); });
</script>

<style scoped>
/* --- THEME COLORS --- */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.btn-orange { background: #ff6b00; color: white; border: none; transition: 0.2s; }
.btn-orange:hover { background: #e56000; box-shadow: 0 5px 15px rgba(255, 107, 0, 0.3); }

/* --- LIGHT MODE DEFAULTS --- */
.bg-light-orange { background-color: #fff5f0 !important; }
.bg-light-orange-subtle { background-color: #fff9f6 !important; }
.border-orange-subtle { border-color: rgba(255, 107, 0, 0.15) !important; }
.bg-light-gray { background-color: #f8f9fa; }
.card-custom { background-color: #ffffff; }

/* --- DARK MODE OVERRIDES --- */
[data-bs-theme="dark"] .card-custom { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .bg-light-orange-subtle { background-color: rgba(255, 107, 0, 0.1) !important; border-color: rgba(255, 107, 0, 0.2) !important; }
[data-bs-theme="dark"] .bg-light-gray { background-color: #1b1b29 !important; }
[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #92929f !important; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e1e1e6 !important; }
[data-bs-theme="dark"] .text-gray-500 { color: #7e8299 !important; }

/* --- CARD HOVER & INTERACTIONS --- */
.hover-float { transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
.hover-float:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; z-index: 10; }
.group-hover:hover .group-hover-show { opacity: 1 !important; }
.transition-transform { transition: transform 0.4s ease; }
.group-hover:hover .transition-transform { transform: scale(1.05); }

/* --- UTILITIES --- */
.grayscale { filter: grayscale(100%); }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
.btn-active-push:active { transform: scale(0.97); }
.h-150px { height: 150px; }

/* --- ANIMATIONS --- */
.anim-fade-in { animation: fadeIn 0.8s ease-out forwards; }
.anim-slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(-20px); }
.anim-slide-left { animation: slideLeft 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateX(30px); }
.anim-staggered-item { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(30px); animation-delay: var(--delay); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { to { opacity: 1; transform: translateY(0); } }
@keyframes slideLeft { to { opacity: 1; transform: translateX(0); } }
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

/* Cart Item Animation */
.list-enter-active, .list-leave-active { transition: all 0.3s ease; }
.list-enter-from, .list-leave-to { opacity: 0; transform: translateX(20px); }

/* Pulse for add button */
.pulse-anim { animation: pulse 2s infinite; }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255, 107, 0, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(255, 107, 0, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 107, 0, 0); } }
</style>