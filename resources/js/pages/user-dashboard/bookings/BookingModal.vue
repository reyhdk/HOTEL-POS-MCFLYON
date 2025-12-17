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

            <div class="d-flex flex-column mb-8 fv-row bg-light p-5 rounded">
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
              
              <div v-if="!isLoggedIn && !isLoadingUser" class="mb-5 alert alert-primary d-flex align-items-center p-3">
                 <i class="ki-duotone ki-information fs-2hx text-primary me-3"><span class="path1"></span><span class="path2"></span></i>
                 <div class="d-flex flex-column">
                    <span>Sudah punya akun? <strong>Login</strong> agar data terisi otomatis.</span>
                 </div>
              </div>

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

              <div class="d-flex align-items-center mb-8 bg-light-warning rounded p-4 border border-warning border-dashed">
                <i class="ki-duotone ki-shield-tick fs-2x me-4 text-warning">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <div class="flex-grow-1">
                      <span class="fw-bold text-gray-800 fs-6 d-block">Booking Incognito (Privasi)</span>
                      <span class="text-gray-600 fw-semibold fs-7">
                          Nama Anda akan disamarkan di layar resepsionis & sistem publik hotel.
                      </span>
                </div>
                <div class="form-check form-check-custom form-check-solid form-switch">
                      <input 
                          class="form-check-input w-45px h-30px" 
                          type="checkbox" 
                          id="is_incognito_online"
                          v-model="formData.is_incognito" 
                      />
                </div>
              </div>
            </form>
          </div>

          <div v-else class="text-center py-10">
            <span class="spinner-border text-primary"></span>
            <p class="mt-4">Memuat detail pesanan...</p>
          </div>
        </div>
        <div class="modal-footer flex-center">
            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
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
import { ref, computed, watch, onMounted } from 'vue';
import type { PropType } from 'vue';
import { Modal } from 'bootstrap';
import axios from '@/libs/axios'; 
import { toast } from 'vue3-toastify';

// Declare Snap global variable (Midtrans)
declare const snap: any;

const props = defineProps({
  room: { type: Object as PropType<any>, default: null },
  bookingDates: { type: Object as PropType<{ check_in_date: string; check_out_date: string; }>, required: true }
});

const emit = defineEmits(['booking-success']);

const modalRef = ref<HTMLElement | null>(null);
const isLoading = ref(false);
const isLoggedIn = ref(false);
const isLoadingUser = ref(false);

const formData = ref({
  guest_name: '',
  guest_email: '',
  guest_phone: '',
  is_incognito: false
});

// --- Computed Properties ---
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

// --- Helpers ---
const formatDate = (dateString: string) => {
  if (!dateString) return '...';
  const options: Intl.DateTimeFormatOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

const formatCurrency = (value: number) => {
  if (!value || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

// --- Fetch User Data (Auto-fill) ---
const fetchCurrentUser = async () => {
    try {
        isLoadingUser.value = true;
        // Asumsi endpoint standar Laravel Sanctum/Breeze: /api/user
        // Jika gagal (401), berarti guest belum login.
        const { data } = await axios.get('/user');
        
        if (data) {
            isLoggedIn.value = true;
            formData.value.guest_name = data.name || '';
            formData.value.guest_email = data.email || '';
            formData.value.guest_phone = data.phone_number || ''; // Pastikan field ini ada di User model
        }
    } catch (e) {
        isLoggedIn.value = false;
        // Silent error: User mungkin memang belum login
    } finally {
        isLoadingUser.value = false;
    }
};

// --- Submit Booking ---
const submitBooking = async () => {
  // 1. Validasi Frontend
  if (!formData.value.guest_name.trim() || !formData.value.guest_email.trim() || !formData.value.guest_phone.trim()) {
    toast.warn("Harap isi data diri Anda dengan lengkap.");
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
      guest_phone: formData.value.guest_phone,
      check_in_date: props.bookingDates.check_in_date,
      check_out_date: props.bookingDates.check_out_date,
      is_incognito: formData.value.is_incognito, 
    };

    // 2. Kirim ke Backend
    // Backend akan cek validitas tanggal (Anti-Bentrok)
    const response = await axios.post('/public/bookings', payload);
    const snapToken = response.data.snap_token;

    if (!snapToken) {
        throw new Error('Gagal mendapatkan token pembayaran.');
    }

    // 3. Tampilkan Popup Midtrans
    snap.pay(snapToken, {
        onSuccess: function(result: any){
            toast.success('Pembayaran berhasil! Kode Booking telah dikirim ke email Anda.');
            emit('booking-success');
            Modal.getInstance(modalRef.value as HTMLElement)?.hide();
        },
        onPending: function(result: any){
            toast.info('Menunggu pembayaran. Silakan selesaikan pembayaran Anda.');
            emit('booking-success');
            Modal.getInstance(modalRef.value as HTMLElement)?.hide();
        },
        onError: function(result: any){
            toast.error('Pembayaran Gagal atau Dibatalkan.');
        },
        onClose: function(){
            toast.warn('Anda menutup jendela pembayaran sebelum selesai.');
        }
    });

  } catch (error: any) {
    // 4. Handle Error Spesifik
    if (error.response?.status === 409) {
        // Ini adalah error dari logic "Anti-Bentrok" kita di Backend
        toast.error('Maaf, kamar ini baru saja dibooking orang lain untuk tanggal tersebut. Silakan pilih kamar lain.');
        Modal.getInstance(modalRef.value as HTMLElement)?.hide();
        emit('booking-success'); // Refresh list kamar agar kamar ini hilang
    } else {
        const message = error.response?.data?.message || 'Terjadi kesalahan saat memproses pesanan.';
        toast.error(message);
    }
  } finally {
    isLoading.value = false;
  }
};

// --- Lifecycle & Watchers ---
onMounted(() => {
    fetchCurrentUser();
});

watch(() => props.room, () => {
  // Jika room berubah (misal buka tutup modal), kita reset form,
  // tapi jika user sudah login, kita isi lagi datanya.
  if (!isLoggedIn.value) {
      formData.value.guest_name = '';
      formData.value.guest_email = '';
      formData.value.guest_phone = '';
  } else {
      fetchCurrentUser(); // Refresh data user just in case
  }
});
</script>