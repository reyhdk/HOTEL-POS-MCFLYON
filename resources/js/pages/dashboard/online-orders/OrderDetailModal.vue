<template>
  <div class="modal fade" tabindex="-1" ref="modalRef">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" v-if="order">
        <div class="modal-header">
          <h3 class="modal-title">Detail Pesanan #{{ order.id }}</h3>
          <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex justify-content-between mb-5">
            <div>
              <p class="fs-6 text-muted mb-1">Pesanan Untuk:</p>
              <h4 class="fs-4 fw-bold">{{ order.user?.name }}</h4>
              <span class="badge badge-light-primary">Kamar {{ order.room?.room_number }}</span>
            </div>
            <div class="text-end">
              <p class="fs-6 text-muted mb-1">Status Pesanan:</p>
              <span :class="getStatusBadge(order.status)" class="text-capitalize fs-5 fw-bold">{{ order.status }}</span>
              <p class="fs-7 text-muted mt-2">{{ formatDateTime(order.created_at) }}</p>
            </div>
          </div>

          <div class="separator separator-dashed my-5"></div>

          <div>
            <h5 class="mb-4">Item Dipesan</h5>
            <div class="d-flex flex-column gap-4">
              <div v-for="item in order.items" :key="item.id" class="d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                  <img :src="item.menu.image_url || '/media/illustrations/blank.svg'" alt="item image" class="rounded" />
                </div>
                <div class="flex-grow-1">
                  <span class="fw-bold text-dark">{{ item.menu.name }}</span>
                  <small class="d-block text-muted">{{ item.quantity }} x {{ formatCurrency(item.price) }}</small>
                </div>
                <div class="fw-bold text-dark text-end" style="width: 120px;">
                  {{ formatCurrency(item.price * item.quantity) }}
                </div>
              </div>
            </div>
          </div>

          <div class="separator separator-dashed my-5"></div>

          <div class="d-flex flex-column align-items-end gap-2">
            <div class="d-flex justify-content-between w-250px">
              <span class="fw-semibold text-muted">Subtotal:</span>
              <span class="fw-semibold text-dark">{{ formatCurrency(order.total_price) }}</span>
            </div>
            <div class="d-flex justify-content-between w-250px">
              <span class="fw-semibold text-muted">Pajak (10%):</span>
              <span class="fw-semibold text-dark">{{ formatCurrency(order.total_price * 0.1) }}</span>
            </div>
            <div class="d-flex justify-content-between w-250px mt-3">
              <span class="fs-4 fw-bolder">Total:</span>
              <span class="fs-4 fw-bolder text-primary">{{ formatCurrency(order.total_price * 1.1) }}</span>
            </div>
          </div>

          <div v-if="order.status !== 'completed' && order.status !== 'cancelled'" class="text-center mt-8">
            <h5 class="mb-4">Aksi Pesanan</h5>
            <div v-if="isSubmitting" class="spinner-border text-primary">
              <span class="visually-hidden">Loading...</span>
            </div>
            <div v-else class="d-flex gap-2 justify-content-center">
              <button v-if="order.status === 'paid' || order.status === 'pending'" @click="changeStatus('processing')" class="btn btn-info">
                <i class="ki-duotone ki-blender fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                Siapkan Pesanan
              </button>
              <button v-if="order.status === 'processing'" @click="changeStatus('delivering')" class="btn btn-primary">
                <i class="ki-duotone ki-delivery fs-3"><span class="path1"></span><span class="path2"></span></i>
                Antar ke Kamar
              </button>
              <button v-if="order.status === 'delivering'" @click="changeStatus('completed')" class="btn btn-success">
                <i class="ki-duotone ki-shield-tick fs-3"><span class="path1"></span><span class="path2"></span></i>
                Selesaikan Pesanan
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { Modal } from "bootstrap";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

const props = defineProps<{
  order: any | null;
}>();

// [DITAMBAHKAN] Emit untuk berkomunikasi dengan komponen induk
const emit = defineEmits(['orderUpdated', 'closeModal']);

const modalRef = ref<HTMLElement | null>(null);
let modalInstance: Modal | null = null;
const isSubmitting = ref(false); // [DITAMBAHKAN] State untuk loading aksi

onMounted(() => {
  if (modalRef.value) {
    modalInstance = new Modal(modalRef.value);
  }
});

// [DIPERBARUI] Fungsi untuk menutup modal dan memberitahu parent
const closeModal = () => {
    modalInstance?.hide();
    emit('closeModal'); // Kirim sinyal bahwa modal ditutup
}

watch(() => props.order, (newOrder) => {
  if (newOrder) {
    modalInstance?.show();
  } else {
    modalInstance?.hide();
  }
});

// [DITAMBAHKAN] Fungsi untuk mengubah status pesanan
const changeStatus = async (newStatus: string) => {
    if (!props.order) return;

    isSubmitting.value = true;
    try {
        await ApiService.patch(`/online-orders/${props.order.id}/status`, { status: newStatus });

        Swal.fire({
            text: `Status pesanan berhasil diubah menjadi '${newStatus}'.`,
            icon: "success",
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        emit('orderUpdated'); // Kirim event ke parent untuk refresh data tabel
        closeModal();       // Tutup modal

    } catch (error) {
        console.error("DETAIL ERRORNYA ADALAH:", error);
        Swal.fire({ text: "Gagal mengubah status.", icon: "error" });
    } finally {
        isSubmitting.value = false;
    }
};

// Fungsi bantuan (dengan tipe data yang benar)
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

const formatDateTime = (dateString: string) => {
  const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

const getStatusBadge = (status: string) => {
  if (status === 'pending' || status === 'paid') return 'badge badge-light-warning';
  if (status === 'processing' || status === 'delivering') return 'badge badge-light-info';
  if (status === 'completed') return 'badge badge-light-success';
  if (status === 'cancelled') return 'badge badge-light-danger';
  return 'badge badge-light-dark';
};
</script>
