<template>
  <div class="modal fade" id="kt_modal_booking" ref="modalRef" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <div class="modal-content">
        <div class="modal-header pb-0 border-0 justify-content-end">
          <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1"></i>
          </div>
        </div>

        <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
          <div v-if="room && bookingDates">
            <div class="text-center mb-13">
              <h1 class="mb-3">Konfirmasi Pesanan Anda</h1>
              <div class="text-muted fw-semibold fs-5">
                Pastikan detail pesanan Anda sudah benar sebelum melanjutkan.
              </div>
            </div>

            <div class="d-flex flex-column mb-8 fv-row">
              <div class="d-flex flex-stack d-flex-wrap">
                <div class="flex-grow-1">
                  <span class="text-muted fw-semibold">Kamar</span>
                  <div class="fw-bold fs-5">{{ room.room_number }} - {{ room.type }}</div>
                </div>
                <div class="text-end">
                  <span class="text-muted fw-semibold">Harga per Malam</span>
                  <div class="fw-bold fs-5">{{ formatCurrency(room.price_per_night) }}</div>
                </div>
              </div>

              <div class="separator separator-dashed my-5"></div>

              <div class="d-flex flex-stack d-flex-wrap">
                <div class="flex-grow-1">
                  <span class="text-muted fw-semibold">Check-in</span>
                  <div class="fw-bold fs-6">{{ formatDate(bookingDates.check_in_date) }}</div>
                </div>
                <div class="text-end">
                  <span class="text-muted fw-semibold">Check-out</span>
                  <div class="fw-bold fs-6">{{ formatDate(bookingDates.check_out_date) }}</div>
                </div>
              </div>

              <div class="separator separator-dashed my-5"></div>
              
              <div class="d-flex flex-stack">
                <span class="fw-semibold">Durasi Menginap</span>
                <span class="fw-semibold">{{ durationInNights }} Malam</span>
              </div>
              <div class="d-flex flex-stack mt-2">
                <span class="fw-semibold">Total Biaya</span>
                <span class="fw-bold fs-3 text-primary">{{ formatCurrency(totalCost) }}</span>
              </div>
            </div>

            <hr class="my-10">

            <form @submit.prevent="submitBooking">
              <h4 class="mb-5">Data Diri Anda</h4>
              <div class="fv-row mb-7">
                <label class="form-label required fs-6 fw-semibold">Nama Lengkap</label>
                <input v-model="formData.guest_name" type="text" class="form-control" required placeholder="Masukkan nama lengkap Anda"/>
              </div>
              <div class="fv-row mb-7">
                <label class="form-label required fs-6 fw-semibold">Alamat Email</label>
                <input v-model="formData.guest_email" type="email" class="form-control" required placeholder="contoh@email.com"/>
              </div>
              <div class="fv-row mb-7">
                <label class="form-label required fs-6 fw-semibold">Nomor Telepon</label>
                <input v-model="formData.guest_phone" type="tel" class="form-control" required placeholder="Contoh: 08123456789"/>
              </div>
            </form>
          </div>
          
          <div v-else class="text-center py-10">
            <span class="spinner-border text-primary"></span>
            <p class="mt-4">Memuat detail pesanan...</p>
          </div>
        </div>
        <div class="modal-footer flex-center">
            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
            <button @click="submitBooking" type="button" class="btn btn-lg btn-primary" :disabled="isLoading">
              <span v-if="!isLoading" class="indicator-label">
                Konfirmasi & Pesan
              </span>
              <span v-if="isLoading" class="indicator-progress">
                Memproses... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
              </span>
            </button>
          </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { PropType } from 'vue';
import { Modal } from 'bootstrap';
import axios from '@/libs/axios';
import { toast } from 'vue3-toastify';

const props = defineProps({
  room: { type: Object as PropType<any>, default: null },
  bookingDates: { type: Object as PropType<{ check_in_date: string; check_out_date: string; }>, required: true }
});

const emit = defineEmits(['booking-success']);

const modalRef = ref<HTMLElement | null>(null);
const isLoading = ref(false);

// [DITAMBAHKAN] Properti guest_phone di formData
const formData = ref({
  guest_name: '',
  guest_email: '',
  guest_phone: '',
});

const durationInNights = computed(() => {
  if (!props.bookingDates?.check_in_date || !props.bookingDates?.check_out_date) return 0;
  const start = new Date(props.bookingDates.check_in_date);
  const end = new Date(props.bookingDates.check_out_date);
  if (isNaN(start.getTime()) || isNaN(end.getTime()) || start >= end) return 0;
  const diffTime = Math.abs(end.getTime() - start.getTime());
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const totalCost = computed(() => {
  if (!props.room || !durationInNights.value) return 0;
  return props.room.price_per_night * durationInNights.value;
});

const formatDate = (dateString: string) => {
  if (!dateString) return '...';
  const options: Intl.DateTimeFormatOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

const formatCurrency = (value: number) => {
  if (!value || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

const submitBooking = async () => {
  // [DIUBAH] Validasi sekarang mencakup nomor telepon
  if (!formData.value.guest_name.trim() || !formData.value.guest_email.trim() || !formData.value.guest_phone.trim()) {
    toast.warn("Harap isi semua kolom yang diperlukan.");
    return;
  }
  if (!props.room) {
    toast.error("Terjadi kesalahan, kamar tidak terpilih.");
    return;
  }
  
  isLoading.value = true;
  try {
    const payload = {
      room_id: props.room.id,
      guest_name: formData.value.guest_name,
      guest_email: formData.value.guest_email,
      guest_phone: formData.value.guest_phone, // [DITAMBAHKAN] Kirim data telepon
      check_in_date: props.bookingDates.check_in_date,
      check_out_date: props.bookingDates.check_out_date,
    };
    
    const response = await axios.post('/public/bookings', payload);
    
    toast.success(response.data.message || 'Booking Anda telah berhasil dikonfirmasi!');
    emit('booking-success');
    
    Modal.getInstance(modalRef.value as HTMLElement)?.hide();

  } catch (error: any) {
    const message = error.response?.data?.message || 'Terjadi kesalahan saat membuat pesanan.';
    toast.error(message);
  } finally {
    isLoading.value = false;
  }
};

// Reset form saat data baru masuk
watch(() => props.room, () => {
  formData.value.guest_name = '';
  formData.value.guest_email = '';
  formData.value.guest_phone = ''; 
});
</script>