<template>
  <div class="row g-5 g-xl-8">
    <div class="col-xl-7">
      <div class="card card-flush h-100">
        <div class="card-header pt-7">
          <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-dark">Pilih Menu</span>
          </h3>
          <div class="d-flex align-items-center position-relative my-1">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
              <span class="path1"></span><span class="path2"></span>
            </i>
            <input
              type="text"
              v-model="searchMenuQuery"
              class="form-control form-control-solid w-250px ps-12"
              placeholder="Cari Menu..."
            />
          </div>
        </div>
        <div class="card-body">
          <div v-if="loading.menus" class="text-center text-muted py-10">
            <span class="spinner-border text-primary"></span>
            <p class="mt-4">Memuat menu...</p>
          </div>
          <div v-else class="row g-4">
            <div v-for="menu in filteredMenus" :key="menu.id" class="col-md-4">
              <div class="card h-100 card-flush" :class="getMenuCardClass(menu)">
                <div class="card-body d-flex flex-column text-center p-5">
                  <div v-if="menu.stock <= 0" class="badge badge-danger position-absolute top-0 start-0 mt-2 ms-2">
                    Stok Habis
                  </div>
                  <div class="m-0">
                    <img :src="menu.image_url || '/media/svg/files/blank-image.svg'" class="rounded-3 mb-4 w-100px h-100px object-fit-cover" :style="{ filter: menu.stock > 0 ? 'none' : 'grayscale(100%)' }" alt="Menu Image" />
                  </div>
                  <div class="fs-6 fw-bold text-dark mb-2">{{ menu.name }}</div>
                  <div class="fs-7 fw-semibold text-muted">{{ formatCurrency(menu.price) }}</div>

                  <div class="fs-8 fw-bold text-gray-500 mt-2">
                    Stok: {{ menu.stock }}
                  </div>

                  <button @click="addToCart(menu)" class="btn btn-sm btn-primary mt-auto" :disabled="menu.stock <= 0">
                    <i class="ki-duotone ki-plus fs-4"></i>
                    {{ menu.stock > 0 ? 'Tambah' : 'Stok Habis' }}
                  </button>
                </div>
              </div>
            </div>
             <div v-if="!loading.menus && filteredMenus.length === 0" class="col-12 text-center text-muted py-10">
                <p>{{ searchMenuQuery ? 'Menu tidak ditemukan.' : 'Tidak ada data menu.' }}</p>
             </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
        <div class="card card-flush h-100">
            <div class="card-header pt-7">
              <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold text-dark">Pesanan Saat Ini</span>
              </h3>
            </div>
            <div class="card-body d-flex flex-column">
               <div class="py-4">
                  <label class="form-label required">Pilih Kamar Tamu</label>
                  <el-select
                    v-model="selectedRoomId"
                    filterable
                    placeholder="Cari atau pilih kamar yang terisi..."
                    class="w-100"
                    :loading="loading.rooms"
                    clearable
                  >
                    <el-option
                      v-for="room in occupiedRooms"
                      :key="room.id"
                      :label="getRoomLabel(room)"
                      :value="room.id"
                    />
                  </el-select>
                </div>
                <div class="separator separator-dashed my-4"></div>

                <div class="flex-grow-1">
                    <div v-if="cart.length === 0" class="text-center text-muted d-flex align-items-center justify-content-center h-100">
                        Keranjang pesanan masih kosong.
                    </div>
                    <div v-else>
                        <div v-for="item in cart" :key="item.menu_id" class="d-flex flex-stack py-4 border-bottom border-gray-300 border-bottom-dashed">
                            <div class="d-flex align-items-center">
                                <div class="ms-2">
                                    <a href="#" class="text-gray-800 text-hover-primary fs-6 fw-bold">{{ item.name }}</a>
                                    <span class="text-muted fw-semibold d-block fs-7">{{ formatCurrency(item.price) }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <button @click="updateQuantity(item, -1)" class="btn btn-icon btn-sm btn-light-danger">-</button>
                                <span class="mx-4 fw-bold">{{ item.quantity }}</span>
                                <button @click="updateQuantity(item, 1)" class="btn btn-icon btn-sm btn-light-success">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto">
                    <div class="separator separator-dashed my-8"></div>
                    <div class="d-flex flex-stack bg-light-primary rounded p-5 mb-5">
                        <div class="fs-6 fw-bold text-gray-800">TOTAL</div>
                        <div class="fs-4 fw-bolder">{{ formatCurrency(totalPrice) }}</div>
                    </div>
                    <button @click="processOrder" class="btn btn-primary w-100" :disabled="!canProcessOrder">
                      <span v-if="!loading.processing" class="indicator-label">Proses Pesanan</span>
                      <span v-if="loading.processing" class="indicator-progress">
                        Memproses... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                      </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import ApiService from "@/core/services/ApiService"; // Gunakan ApiService jika sudah ada
import Swal from "sweetalert2";

// --- INTERFACES (Tipe Data) ---
interface Guest {
  id: number;
  name: string;
}
interface CheckIn {
  id: number;
  guest: Guest;
  is_active: boolean;
}
interface Room {
  id: number;
  room_number: string;
  check_ins: CheckIn[]; // Menggunakan nama relasi dari JSON Laravel (snake_case)
}
interface Menu {
  id: number;
  name: string;
  price: number;
  image_url: string | null;
  stock: number;
}
interface CartItem {
  menu_id: number;
  name: string;
  price: number;
  quantity: number;
}

// --- STATE (Variabel Reaktif) ---
const menus = ref<Menu[]>([]);
const occupiedRooms = ref<Room[]>([]); // State khusus untuk kamar yang ditempati
const cart = ref<CartItem[]>([]);
const loading = ref({ menus: true, rooms: true, processing: false });
const searchMenuQuery = ref("");
const selectedRoomId = ref<number | null>(null);

// --- COMPUTED PROPERTIES ---
const totalPrice = computed(() => {
  return cart.value.reduce((total, item) => total + item.price * item.quantity, 0);
});

const canProcessOrder = computed(() => {
  return selectedRoomId.value !== null && cart.value.length > 0 && !loading.value.processing;
});

const filteredMenus = computed(() => {
  if (!searchMenuQuery.value) return menus.value;
  return menus.value.filter((menu) =>
    menu.name.toLowerCase().includes(searchMenuQuery.value.toLowerCase())
  );
});

// --- FUNGSI-FUNGSI ---
const fetchMenus = async () => {
  try {
    loading.value.menus = true;
    const { data } = await ApiService.get("/menus");
    menus.value = data;
  } catch (error) {
    Swal.fire("Error", "Gagal memuat data menu.", "error");
  } finally {
    loading.value.menus = false;
  }
};

// [DIPERBARUI] Menggunakan API endpoint baru yang lebih efisien
const fetchOccupiedRooms = async () => {
  try {
    loading.value.rooms = true;
    const { data } = await ApiService.get("/pos/occupied-rooms");
    occupiedRooms.value = data;
  } catch (error) {
    Swal.fire("Error", "Gagal memuat data kamar.", "error");
  } finally {
    loading.value.rooms = false;
  }
};

// [Disederhanakan] Logika ini aman karena API hanya mengirim kamar dengan check-in aktif
const getRoomLabel = (room: Room): string => {
  const activeCheckIn = room.check_ins[0];
  const guestName = activeCheckIn?.guest?.name || 'Tamu';
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
  if (newQuantity > 0 && newQuantity <= menu.stock) {
    item.quantity = newQuantity;
  } else if (newQuantity <= 0) {
    cart.value = cart.value.filter(cartItem => cartItem.menu_id !== item.menu_id);
  }
};

const processOrder = async () => {
  if (!canProcessOrder.value) return;
  loading.value.processing = true;
  const orderData = {
    room_id: selectedRoomId.value,
    items: cart.value.map(item => ({ menu_id: item.menu_id, quantity: item.quantity })),
    payment_method: 'pay_at_checkout', // Tambahkan baris ini
};
  try {
    await ApiService.post('/orders', orderData);
    Swal.fire("Berhasil!", "Pesanan berhasil dibuat dan tagihan ditambahkan ke folio kamar.", "success");
    cart.value = [];
    selectedRoomId.value = null;
    await fetchMenus(); // Muat ulang menu untuk update stok
  } catch (error: any) {
    const message = error.response?.data?.message || "Terjadi kesalahan saat memproses pesanan.";
    Swal.fire("Error!", message, "error");
  } finally {
    loading.value.processing = false;
  }
};

const formatCurrency = (value: number) => {
  if (isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

const getMenuCardClass = (menu: Menu) => {
  if (menu.stock <= 0) return 'bg-light-danger';
  if (menu.stock <= 5) return 'bg-light-warning';
  return 'bg-light-primary';
};

// --- LIFECYCLE HOOK ---
onMounted(() => {
  fetchMenus();
  fetchOccupiedRooms(); // Panggil fungsi yang baru
});
</script>
