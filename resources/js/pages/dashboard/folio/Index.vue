<template>
  <div class="card shadow-sm">
    <div class="card-header border-0 pt-6">
      <h3 class="card-title">Folio Kamar (Tamu Sedang Menginap)</h3>
    </div>
    <div class="card-body pt-0">
      <div v-if="loading" class="text-center text-muted py-10">
        <span class="spinner-border text-primary"></span>
        <p class="mt-4">Memuat data folio...</p>
      </div>
      <div v-else-if="folios.length === 0" class="text-center text-muted py-10">
        Tidak ada tamu yang sedang check-in.
      </div>

      <div v-else class="accordion" id="kt_accordion_folios">
        <div v-for="(folio, index) in folios" :key="folio.id" class="accordion-item">
          <h2 class="accordion-header" :id="`header_folio_${folio.id}`">
            <button
              class="accordion-button fs-4 fw-semibold"
              :class="{ 'collapsed': index !== 0 }"
              type="button"
              data-bs-toggle="collapse"
              :data-bs-target="`#body_folio_${folio.id}`"
              :aria-expanded="index === 0 ? 'true' : 'false'">
              Kamar {{ folio.room_number }} - <span class="text-primary mx-2">{{ getGuestName(folio) }}</span>
              <span class="ms-auto badge badge-light-primary">Total Tagihan: {{ formatCurrency(calculateTotalBill(folio.orders)) }}</span>
            </button>
          </h2>
          <div :id="`body_folio_${folio.id}`"
               class="accordion-collapse collapse"
               :class="{ 'show': index === 0 }"
               data-bs-parent="#kt_accordion_folios">
            <div class="accordion-body">
              <div v-if="!folio.orders || !Array.isArray(folio.orders) || folio.orders.length === 0" class="text-muted text-center py-5">
                Belum ada tagihan untuk tamu ini.
              </div>
              <div v-else>
                <h5 class="mb-4">Rincian Tagihan:</h5>
                <div v-for="order in folio.orders" :key="order.id" class="mb-5 p-5 border border-dashed rounded">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold">Pesanan #{{ order.id }}</span>
                    <span class="text-muted fs-7">{{ new Date(order.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) }}</span>
                  </div>
                  <ul class="list-group list-group-flush">
                    <li v-for="item in order.items" :key="item.id" class="list-group-item d-flex justify-content-between align-items-center ps-0">
                      <span>{{ item.menu.name }} <span class="text-muted">({{ item.quantity }}x)</span></span>
                      <span>{{ formatCurrency(item.price * item.quantity) }}</span>
                    </li>
                  </ul>
                </div>
              </div>
              <div class="mt-5 text-end">
                <button @click="processFolioCheckout(folio)" class="btn btn-sm btn-success" :disabled="isCheckoutLoading">
                  <span v-if="isCheckoutLoading" class="spinner-border spinner-border-sm"></span>
                  <span v-else><i class="ki-duotone ki-dollar fs-2"></i> Bayar Semua & Check-out</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import ApiService from "@/core/services/ApiService"; // Diubah menggunakan ApiService agar konsisten
import Swal from "sweetalert2";

// ... (Interface Anda sudah benar) ...
interface Guest { id: number; name: string; }
interface CheckIn { id: number; guest: Guest; is_active: boolean; }
interface MenuItem { id: number; name: string; }
interface OrderItem { id: number; price: number; quantity: number; menu: MenuItem; }
interface Order { id: number; total_price: number; items: OrderItem[]; created_at: string; }
interface FolioData {
  id: number;
  room_number: string;
  checkIns: CheckIn[];
  orders: Order[] | null; // Izinkan orders menjadi null
}

const folios = ref<FolioData[]>([]);
const loading = ref(true);
const isCheckoutLoading = ref(false);

const getFolios = async () => {
  try {
    loading.value = true;
    const { data } = await ApiService.get("/folios");
    folios.value = data;
  } catch (error) {
    console.error("Gagal memuat data folio:", error);
    Swal.fire("Error!", "Gagal memuat data folio dari server.", "error");
  } finally {
    loading.value = false;
  }
};

const getGuestName = (folio: FolioData) => {
    const activeCheckIn = folio.checkIns?.find(ci => ci.is_active);
    return activeCheckIn?.guest?.name || 'Tamu';
};

// [FUNGSI UTAMA YANG DIPERBAIKI]
const calculateTotalBill = (orders: Order[] | null): number => {
  // Cek dulu apakah 'orders' adalah sebuah Array.
  // Jika bukan (misalnya null atau object), maka totalnya adalah 0.
  if (!Array.isArray(orders)) {
    return 0;
  }
  // Jika ini adalah array (meskipun kosong), .reduce() akan aman untuk dijalankan.
  return orders.reduce((total, order) => total + Number(order.total_price), 0);
};

const formatCurrency = (value: number): string => {
  if (isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

const processFolioCheckout = (folio: FolioData) => {
    const totalBill = calculateTotalBill(folio.orders);
    const guestName = getGuestName(folio);

    const confirmationConfig: any = {
        title: `Konfirmasi Check-out`,
        html: `Lanjutkan proses check-out untuk <strong>${guestName}</strong> dari kamar <strong>${folio.room_number}</strong>?`,
        icon: "info",
        showCancelButton: true,
        buttonsStyling: false,
        confirmButtonText: "Ya, Check-out!",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn fw-bold btn-primary", cancelButton: "btn fw-bold btn-active-light-primary" },
    };

    if (totalBill > 0) {
        confirmationConfig.title = `Konfirmasi Pembayaran & Check-out`;
        confirmationConfig.html = `Anda akan memproses pembayaran sebesar <strong>${formatCurrency(totalBill)}</strong> dan check-out untuk <strong>${guestName}</strong> dari kamar <strong>${folio.room_number}</strong>. Lanjutkan?`;
        confirmationConfig.icon = "question";
        confirmationConfig.customClass.confirmButton = "btn fw-bold btn-success";
    }

    Swal.fire(confirmationConfig).then((result) => {
        if (result.isConfirmed) {
            executeCheckout(folio);
        }
    });
};

const executeCheckout = async (folio: FolioData) => {
    isCheckoutLoading.value = true;
    try {
        // [PERBAIKAN DI SINI] Tambahkan objek kosong {} sebagai argumen kedua
        await ApiService.post(`/folios/${folio.id}/checkout`, {});

        Swal.fire("Berhasil!", "Pembayaran sukses dan tamu telah check-out.", "success");
        getFolios(); // Refresh daftar folio
    } catch (error: any) {
        const message = error.response?.data?.message || "Gagal memproses folio.";
        Swal.fire("Error!", message, "error");
        console.error(error);
    } finally {
        isCheckoutLoading.value = false;
    }
};

onMounted(() => {
  getFolios();
});
</script>
