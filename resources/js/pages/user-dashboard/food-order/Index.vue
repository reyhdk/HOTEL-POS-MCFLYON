<template>
  <div>
    <h1 class="mb-5">Pesan Makanan & Minuman</h1>

    <div class="row">
      <div class="col-lg-8">
        <div v-if="isLoading" class="d-flex justify-content-center py-10">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else-if="menus.length > 0">
          <TransitionGroup name="fade" tag="div" class="row g-5">
            <div v-for="menu in menus" :key="menu.id" class="col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm card-flush">
                <img :src="menu.image_url || '/media/illustrations/blank.svg'" class="card-img-top" alt="Menu Image" style="height: 200px; object-fit: cover"/>
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title">{{ menu.name }}</h5>
                  <p class="card-text text-muted">Stok: {{ menu.stock }}</p>
                  <h6 class="text-primary fs-3 fw-bold mt-auto">{{ formatCurrency(menu.price) }}</h6>
                  <button class="btn btn-primary mt-3" @click="addToCart(menu)" :disabled="menu.stock === 0">
                    <i class="ki-duotone ki-plus-square fs-3 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span v-if="menu.stock > 0">Tambah</span>
                    <span v-else>Stok Habis</span>
                  </button>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>

        <div v-else class="alert alert-secondary">
          Saat ini belum ada menu yang tersedia.
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card sticky-top shadow-sm" style="top: 100px">
          <div class="card-header">
            <h3 class="card-title">
              <i class="ki-duotone ki-basket-ok fs-2 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
              Detail Pesanan
            </h3>
          </div>
          <div class="card-body">
            <div v-if="activeRoom" class="mb-4 p-3 bg-light-primary rounded">
              <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-muted">Pesanan untuk:</span>
                <span class="fw-bold fs-6">{{ guestName }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="fw-semibold text-muted">Diantar ke Kamar:</span>
                <span class="badge badge-primary fs-6">{{ activeRoom.room_number }}</span>
              </div>
            </div>

            <Transition name="fade" mode="out-in">
              <div v-if="cart.length === 0" class="text-center text-muted py-5">
                <i class="ki-duotone ki-basket fs-4x"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                <p class="mt-3">Keranjang Anda masih kosong.</p>
              </div>
              <div v-else>
                <TransitionGroup name="fade" tag="div">
                  <div v-for="item in cart" :key="item.id" class="d-flex align-items-center mb-4">
                    <div class="flex-grow-1">
                      <p class="fw-bold mb-0">{{ item.name }}</p>
                      <small class="text-muted">{{ item.quantity }} x {{ formatCurrency(item.price) }}</small>
                    </div>
                    <div class="d-flex align-items-center ms-3">
                      <button class="btn btn-icon btn-sm btn-light-danger" @click="updateQuantity(item.id, -1)">-</button>
                      <span class="mx-4 fw-bold">{{ item.quantity }}</span>
                      <button class="btn btn-icon btn-sm btn-light-primary" @click="updateQuantity(item.id, 1)">+</button>
                    </div>
                    <button class="btn btn-icon btn-sm btn-light-danger ms-3" @click="removeFromCart(item.id)">
                        <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                    </button>
                  </div>
                </TransitionGroup>

                <hr class="my-5"/>
                <div class="d-flex justify-content-between fs-5">
                  <span class="fw-semibold text-muted">Subtotal:</span>
                  <span class="fw-semibold">{{ formatCurrency(cartTotal) }}</span>
                </div>
                <div class="d-flex justify-content-between fs-5 mt-2">
                  <span class="fw-semibold text-muted">Pajak & Layanan (10%):</span>
                  <span class="fw-semibold">{{ formatCurrency(taxAmount) }}</span>
                </div>
                <hr class="my-4 border-dashed"/>
                <div class="d-flex justify-content-between fs-3 fw-bolder">
                  <span>Total Tagihan:</span>
                  <span class="text-primary">{{ formatCurrency(grandTotal) }}</span>
                </div>

                <button class="btn btn-success w-100 mt-5 fs-5" @click="processOrder" :disabled="isSubmitting || cart.length === 0">
                  <span v-if="!isSubmitting">
                    <i class="ki-duotone ki-shield-tick fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                    Proses Pesanan Sekarang
                  </span>
                  <span v-else>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Memproses...
                  </span>
                </button>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router"; // [DIBENARKAN] Import useRouter
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

// --- INTERFACES (Tipe Data) ---
interface Menu {
  id: number;
  name: string;
  price: number;
  stock: number;
  image_url: string | null;
}
interface Room {
  id: number;
  room_number: string;
}
interface CartItem extends Menu {
  quantity: number;
}

// --- STATE MANAGEMENT ---
const menus = ref<Menu[]>([]);
const isLoading = ref(true);
const cart = ref<CartItem[]>([]);
const activeRoom = ref<Room | null>(null);
const guestName = ref<string>('');
const isSubmitting = ref(false);
const router = useRouter(); // [DIBENARKAN] Inisialisasi router

// --- FUNGSI-FUNGSI API ---
const fetchMenus = async () => {
  try {
    const response = await ApiService.get("/guest/menu");
    menus.value = response.data;
  } catch (error) {
    Swal.fire({ text: "Gagal memuat daftar menu.", icon: "error" });
  }
};

const fetchGuestProfile = async () => {
  try {
    const response = await ApiService.get("/guest/profile");
    activeRoom.value = response.data.active_room;
    guestName.value = response.data.guest_details.name;
  } catch (error) {
    console.error("Gagal mengambil profil tamu, mungkin belum check-in.", error);
  }
};

const processOrder = async () => {
  if (cart.value.length === 0) {
    Swal.fire({ text: "Keranjang Anda kosong.", icon: "warning" });
    return;
  }

  isSubmitting.value = true;
  const payload = {
    items: cart.value.map(item => ({
      menu_id: item.id,
      quantity: item.quantity,
    })),
  };

  try {
    const response = await ApiService.post("/guest/orders", payload);
    const newOrder = response.data.order;
    cart.value = [];
    router.push({ name: 'user-payment', params: { orderId: newOrder.id } });
  } catch (error: any) {
    const errorMessage = error.response?.data?.message || "Terjadi kesalahan saat memproses pesanan.";
    Swal.fire({
      text: errorMessage,
      icon: "error",
      buttonsStyling: false,
      confirmButtonText: "Coba Lagi",
      customClass: { confirmButton: "btn btn-danger" },
    });
  } finally {
    isSubmitting.value = false;
  }
};

// --- FUNGSI-FUNGSI KERANJANG ---
const addToCart = (menu: Menu) => {
  const itemInCart = cart.value.find((item) => item.id === menu.id);
  if (itemInCart) {
    if (itemInCart.quantity < menu.stock) {
      itemInCart.quantity++;
    } else {
      Swal.fire({ text: `Stok untuk ${menu.name} tidak mencukupi.`, icon: "warning" });
    }
  } else {
    if (menu.stock > 0) {
      cart.value.push({ ...menu, quantity: 1 });
    }
  }
};

const updateQuantity = (menuId: number, amount: number) => {
  const itemInCart = cart.value.find((item) => item.id === menuId);
  if (!itemInCart) return;
  const newQuantity = itemInCart.quantity + amount;
  if (newQuantity <= 0) {
    cart.value = cart.value.filter(item => item.id !== menuId);
    return;
  }
  if (amount > 0 && newQuantity > itemInCart.stock) {
    Swal.fire({ text: `Stok untuk ${itemInCart.name} tidak mencukupi.`, icon: "warning" });
    return;
  }
  itemInCart.quantity = newQuantity;
};

const removeFromCart = (menuId: number) => {
  cart.value = cart.value.filter(item => item.id !== menuId);
};

// --- FUNGSI BANTUAN ---
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

// --- COMPUTED PROPERTIES ---
const cartTotal = computed(() => {
  return cart.value.reduce((total, item) => total + item.price * item.quantity, 0);
});
const taxAmount = computed(() => {
  return cartTotal.value * 0.10;
});
const grandTotal = computed(() => {
  return cartTotal.value + taxAmount.value;
});

// --- LIFECYCLE HOOK ---
onMounted(() => {
  isLoading.value = true;
  Promise.all([fetchMenus(), fetchGuestProfile()]).finally(() => {
    isLoading.value = false;
  });
});
</script>