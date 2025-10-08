<template>
  <div>
    <h1 class="mb-3">Pesan Makanan & Minuman</h1>
    <p class="fs-6 text-muted mb-8">
      Nikmati hidangan lezat yang diantar langsung ke kamar Anda.
    </p>

    <div class="row g-6 g-xl-9">
      <div class="col-lg-8">
        <div v-if="isLoading" class="d-flex justify-content-center py-20">
          <div class="spinner-border text-primary" style="width: 3rem; height: 3rem"></div>
        </div>

        <div v-else-if="menus.length === 0" class="card shadow-sm">
            <div class="card-body d-flex flex-column flex-center text-center p-20">
                <i class="ki-duotone ki-coffee fs-5x text-muted mb-5">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>
                </i>
                <h3 class="fs-3 text-gray-800">Menu Belum Tersedia</h3>
                <p class="fs-6 text-muted">Saat ini belum ada menu yang dapat dipesan.</p>
            </div>
        </div>

        <div v-else>
          <TransitionGroup name="fade" tag="div" class="row g-5 g-xl-8">
            <div v-for="menu in menus" :key="menu.id" class="col-md-6 col-xl-4">
              <div class="card card-flush h-100 shadow-sm border border-2 border-gray-300">
                <div class="card-body d-flex flex-column p-0">
                  <div class="position-relative">
                    <img :src="menu.image_url || '/media/illustrations/blank.svg'" class="card-img-top rounded-top" alt="Menu Image" style="height: 180px; object-fit: cover"/>
                    <div class="position-absolute top-0 end-0 m-4">
                        <span v-if="menu.stock > 0" class="badge badge-light-success fs-7">
                          Stok: {{ menu.stock }}
                        </span>
                        <span v-else class="badge badge-light-danger fs-7">Habis</span>
                    </div>
                  </div>
                  <div class="p-5 d-flex flex-column flex-grow-1">
                    <h5 class="card-title text-gray-800 fw-bold mb-2">{{ menu.name }}</h5>
                    <div class="fs-4 fw-bolder text-primary mt-auto pt-4">{{ formatCurrency(menu.price) }}</div>
                  </div>
                  <div class="px-5 pb-5">
                    <button class="btn btn-light-primary w-100" @click="addToCart(menu)" :disabled="menu.stock === 0">
                      <i class="ki-duotone ki-plus fs-3"></i>
                      <span>Tambah ke Keranjang</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card sticky-top shadow-sm card-flush border border-2 border-gray-300" style="top: 100px">
          <div class="card-header pt-5">
            <h3 class="card-title">
              <i class="ki-duotone ki-basket-ok fs-2 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
              Keranjang Pesanan
            </h3>
          </div>
          <div class="card-body pt-4">
            <div v-if="activeRoom" class="mb-5 p-4 bg-light-primary rounded">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-muted">Tamu:</span>
                    <span class="fw-bold fs-6">{{ guestName }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="fw-semibold text-muted">Kamar:</span>
                    <span class="badge badge-primary fs-6">{{ activeRoom.room_number }}</span>
                </div>
            </div>

            <Transition name="list" mode="out-in">
              <div v-if="cart.length === 0" class="text-center text-muted py-10">
                <i class="ki-duotone ki-basket fs-5x"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                <p class="mt-3 fs-6">Pilih menu untuk memulai pesanan.</p>
              </div>

              <div v-else>
                <div class="scroll-y" style="max-height: 250px;">
                    <TransitionGroup name="list" tag="div" class="pe-4">
                        <div v-for="item in cart" :key="item.id" class="d-flex align-items-center mb-5">
                            <div class="symbol symbol-50px me-4">
                                <img :src="item.image_url || '/media/illustrations/blank.svg'" alt="" class="rounded-3"/>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-gray-800">{{ item.name }}</div>
                                <div class="fs-7 text-muted">{{ formatCurrency(item.price) }}</div>
                            </div>
                            <div class="d-flex align-items-center ms-3">
                                <button class="btn btn-icon btn-sm btn-light-danger" @click="updateQuantity(item.id, -1)">-</button>
                                <span class="mx-3 fw-bold">{{ item.quantity }}</span>
                                <button class="btn btn-icon btn-sm btn-light-primary" @click="updateQuantity(item.id, 1)">+</button>
                            </div>
                        </div>
                    </TransitionGroup>
                </div>
                <hr class="my-5 border-dashed"/>

                <div class="d-flex justify-content-between fs-6">
                  <span class="fw-semibold text-muted">Subtotal</span>
                  <span class="fw-semibold">{{ formatCurrency(cartTotal) }}</span>
                </div>
                <div class="d-flex justify-content-between fs-6 mt-2">
                  <span class="fw-semibold text-muted">Pajak & Layanan (10%)</span>
                  <span class="fw-semibold">{{ formatCurrency(taxAmount) }}</span>
                </div>
                <hr class="my-4 border-dashed"/>
                <div class="d-flex justify-content-between align-items-center fs-5 fw-bolder">
                  <span>Total</span>
                  <span class="text-primary fs-4">{{ formatCurrency(grandTotal) }}</span>
                </div>

                <button class="btn btn-primary w-100 mt-5" @click="processOrder" :disabled="isSubmitting || cart.length === 0">
                  <span v-if="!isSubmitting">Lanjutkan ke Pembayaran</span>
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
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
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
import { useRouter } from "vue-router";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

// --- INTERFACES ---
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

// --- STATE ---
const menus = ref<Menu[]>([]);
const isLoading = ref(true);
const cart = ref<CartItem[]>([]);
const activeRoom = ref<Room | null>(null);
const guestName = ref<string>('');
const isSubmitting = ref(false);
const router = useRouter();

// --- API FUNCTIONS ---
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
  if (cart.value.length === 0 || !activeRoom.value) {
    Swal.fire({ text: "Keranjang Anda kosong atau Anda belum check-in.", icon: "warning" });
    return;
  }
  isSubmitting.value = true;

  const payload = {
    room_id: activeRoom.value.id,
    items: cart.value.map(item => ({
      menu_id: item.id,
      quantity: item.quantity,
    })),
  };

  try {
    const response = await ApiService.post("/guest/orders", payload);
    const newOrder = response.data.order || response.data;

    cart.value = [];

    await Swal.fire({
        text: "Pesanan berhasil dibuat! Anda akan diarahkan ke halaman pembayaran.",
        icon: "success",
        timer: 2000,
        showConfirmButton: false
    });

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

// --- CART FUNCTIONS ---
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

// --- HELPER FUNCTIONS ---
const formatCurrency = (value: number) => {
  if (!value) return "Rp 0";
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
