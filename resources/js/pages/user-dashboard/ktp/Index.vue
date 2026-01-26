<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router'; // ✅ Import Vue Router
import axios from 'axios';
import Swal from 'sweetalert2';
import { 
  Camera, UploadCloud, CheckCircle, ShieldAlert, 
  Loader2, Info, UserCheck, XCircle, RefreshCw, AlertCircle, Lock, Calendar
} from 'lucide-vue-next';

// ✅ Initialize Router
const router = useRouter();

// --- STATE ---
const guestData = ref(null);
const selectedFile = ref(null);
const previewUrl = ref(null);
const isUploading = ref(false);
const isLoading = ref(true);
const hasBooking = ref(false);

const getImageUrl = (path) => {
  if (!path) return null;
  return path.startsWith('http') ? path : `/storage/${path}`;
};

// ✅ FETCH PROFIL + CEK BOOKING
const fetchProfile = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get('/user/profile-ktp');
    guestData.value = response.data;
    
    if (guestData.value?.bookings && guestData.value.bookings.length > 0) {
      hasBooking.value = true;
      
      if (guestData.value?.ktp_image) {
        previewUrl.value = getImageUrl(guestData.value.ktp_image);
      }
    } else {
      hasBooking.value = false;
    }
  } catch (error) {
    console.error("Gagal memuat profil:", error);
    
    if (error.response?.status === 404) {
      hasBooking.value = false;
      guestData.value = null;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Gagal Memuat Data',
        text: error.response?.data?.message || 'Tidak dapat memuat profil Anda.',
        confirmButtonText: 'Coba Lagi',
        buttonsStyling: false,
        customClass: {
          confirmButton: 'btn btn-danger fw-bold'
        }
      });
    }
  } finally {
    isLoading.value = false;
  }
};

const onFileChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.size > 4 * 1024 * 1024) {
    Swal.fire({
      icon: 'warning',
      title: 'File Terlalu Besar',
      text: 'Ukuran maksimal file adalah 4MB. Silakan kompres foto terlebih dahulu.',
      confirmButtonText: 'Mengerti',
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-warning fw-bold',
        popup: 'rounded-4'
      }
    });
    return;
  }

  selectedFile.value = file;
  previewUrl.value = URL.createObjectURL(file);
};

const submitKtp = async () => {
  if (!selectedFile.value) return;

  const result = await Swal.fire({
    title: 'Konfirmasi Upload',
    html: `
      <div class="text-start">
        <p class="text-gray-600 mb-3">Pastikan foto KTP Anda sudah:</p>
        <ul class="text-gray-700 fs-7">
          <li>✓ Terlihat jelas dan tidak buram</li>
          <li>✓ Seluruh bagian kartu masuk frame</li>
          <li>✓ Tidak ada pantulan cahaya yang menutupi teks</li>
        </ul>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '<i class="ki-duotone ki-check fs-2"></i> Ya, Upload Sekarang',
    cancelButtonText: 'Batal',
    buttonsStyling: false,
    customClass: {
      confirmButton: 'btn btn-primary fw-bold',
      cancelButton: 'btn btn-light fw-bold',
      popup: 'rounded-4'
    }
  });

  if (!result.isConfirmed) return;

  isUploading.value = true;
  const formData = new FormData();
  formData.append('ktp_image', selectedFile.value);

  try {
    await axios.post('/user/update-ktp', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    await Swal.fire({
      icon: 'success',
      title: 'Upload Berhasil!',
      html: `
        <div class="text-center">
          <div class="mb-3">
            <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
          <p class="text-gray-600 fs-6">Dokumen Anda sedang dalam proses verifikasi oleh tim admin.</p>
          <p class="text-muted fs-7 mb-0">Kami akan mengirimkan notifikasi email setelah verifikasi selesai.</p>
        </div>
      `,
      confirmButtonText: 'Mengerti',
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-success fw-bold',
        popup: 'rounded-4'
      }
    });
    
    selectedFile.value = null;
    fetchProfile();
  } catch (error) {
    console.error("Gagal upload:", error);
    Swal.fire({
      icon: 'error',
      title: 'Gagal Upload',
      text: error.response?.data?.message || 'Terjadi kesalahan saat mengunggah dokumen.',
      confirmButtonText: 'Coba Lagi',
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-danger fw-bold',
        popup: 'rounded-4'
      }
    });
  } finally {
    isUploading.value = false;
  }
};

const cancelSelection = () => {
  selectedFile.value = null;
  if (guestData.value?.ktp_image) {
    previewUrl.value = getImageUrl(guestData.value.ktp_image);
  } else {
    previewUrl.value = null;
  }
};

const triggerFileInput = () => {
  document.getElementById('ktp-file-input').click();
};

// ✅ Navigate ke halaman booking menggunakan Vue Router (SPA)
const goToBooking = () => {
  router.push('/user/booking');
};

// ✅ Navigate ke home menggunakan Vue Router (SPA)
const goToHome = () => {
  router.push('/');
};

onMounted(fetchProfile);
</script>

<template>
  <div class="card shadow-sm border-0">
    <!-- Header Card dengan Status Badge -->
    <div class="card-header d-flex justify-content-between align-items-center p-6 bg-white border-bottom">
      <h3 class="card-title fw-bold text-gray-800 m-0">Verifikasi Identitas</h3>
      <div v-if="!isLoading && hasBooking">
        <span v-if="guestData?.is_verified" class="badge badge-light-success px-4 py-2 fs-7">
          <CheckCircle class="w-4 h-4 me-1" /> Terverifikasi
        </span>
        <span v-else-if="guestData?.rejection_reason" class="badge badge-light-danger px-4 py-2 fs-7 animate__animated animate__pulse animate__infinite">
          <XCircle class="w-4 h-4 me-1" /> Perlu Perbaikan
        </span>
        <span v-else-if="guestData?.ktp_image" class="badge badge-light-warning px-4 py-2 fs-7">
          <RefreshCw class="w-4 h-4 me-1 animate-spin-slow" /> Menunggu Review
        </span>
        <span v-else class="badge badge-light-info px-4 py-2 fs-7">Belum Unggah KTP</span>
      </div>
    </div>

    <div class="card-body p-9">
      <!-- Loading State -->
      <div v-if="isLoading" class="text-center py-10">
        <Loader2 class="w-10 h-10 text-primary animate-spin mx-auto mb-3" />
        <p class="text-muted">Menyiapkan profil Anda...</p>
      </div>

      <!-- ✅ LOCKED STATE: Belum Pernah Booking -->
      <div v-else-if="!hasBooking" class="d-flex align-items-center justify-content-center min-h-500px">
        <div class="text-center max-w-600px mx-auto p-10">
          <div class="mb-8 animate__animated animate__bounceIn">
            <div class="symbol symbol-150px symbol-circle bg-light-warning mx-auto mb-6 position-relative">
              <Lock class="w-20 h-20 text-warning" />
              <div class="position-absolute bottom-0 end-0 bg-warning rounded-circle p-3 shadow-lg animate__animated animate__pulse animate__infinite">
                <Calendar class="w-8 h-8 text-white" />
              </div>
            </div>
          </div>

          <h2 class="fw-bold text-gray-800 mb-4">Halaman Terkunci</h2>
          <p class="text-gray-600 fs-5 mb-6 leading-relaxed">
            Fitur verifikasi identitas hanya tersedia setelah Anda melakukan pemesanan kamar. 
            Foto KTP yang Anda upload saat booking akan otomatis tersinkronisasi ke sini.
          </p>

          <div class="bg-light-primary rounded-4 p-6 mb-8 border-2 border-primary border-dashed animate__animated animate__fadeInUp">
            <div class="d-flex align-items-start text-start">
              <div class="symbol symbol-40px symbol-circle bg-primary me-4 flex-shrink-0">
                <Info class="w-5 h-5 text-white" />
              </div>
              <div>
                <h5 class="fw-bold text-primary mb-2">Mengapa Harus Booking Dulu?</h5>
                <p class="text-gray-700 fs-7 mb-0">
                  Sistem kami menggunakan data KTP dari pemesanan pertama Anda sebagai identitas utama. 
                  Ini memastikan keamanan dan kesesuaian data antara booking dan profil Anda.
                </p>
              </div>
            </div>
          </div>

          <div class="d-flex flex-column gap-3 align-items-center animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <!-- ✅ Gunakan @click dengan router.push untuk SPA -->
            <button 
              @click="goToBooking" 
              class="btn btn-primary btn-lg fw-bold px-10 shadow-sm hover-elevate-up"
            >
              <Calendar class="w-5 h-5 me-2" />
              Booking Kamar Sekarang
            </button>
            
            <!-- ✅ Link ke home juga pakai router -->
            <button 
              @click="goToHome"
              class="btn btn-link text-muted fs-7 text-hover-primary text-decoration-none"
            >
              Kembali ke Beranda
            </button>
          </div>
        </div>
      </div>

      <!-- ✅ UNLOCKED STATE: Sudah Pernah Booking -->
      <div v-else class="row g-10">
        <!-- Kolom Kiri: Form & Preview -->
        <div class="col-lg-7">
          
          <!-- ✅ BANNER PENOLAKAN DENGAN CTA JELAS -->
          <div v-if="guestData?.rejection_reason && !guestData?.is_verified" 
               class="alert alert-danger d-flex flex-column p-6 mb-7 border-2 border-danger border-dashed rounded-4 position-relative overflow-hidden animate__animated animate__headShake">
            
            <div class="position-absolute top-0 end-0 opacity-5">
              <AlertCircle style="width: 150px; height: 150px;" />
            </div>

            <div class="d-flex align-items-start mb-4 position-relative z-index-1">
              <div class="symbol symbol-50px symbol-circle me-4 bg-danger bg-opacity-10">
                <ShieldAlert class="w-6 h-6 text-danger" />
              </div>
              <div class="flex-grow-1">
                <h4 class="fw-bold text-danger mb-2">Dokumen KTP Ditolak</h4>
                <p class="text-gray-700 fs-6 mb-1 fw-semibold">Alasan Penolakan:</p>
                <div class="bg-light-danger rounded p-3 border border-danger border-opacity-25">
                  <p class="text-gray-800 mb-0 fs-6">"{{ guestData.rejection_reason }}"</p>
                </div>
              </div>
            </div>

            <button 
              @click="triggerFileInput" 
              class="btn btn-danger btn-lg fw-bold shadow-sm w-100 position-relative z-index-1 hover-elevate-up">
              <Camera class="w-5 h-5 me-2" />
              Klik di Sini untuk Upload KTP Baru
            </button>
            
            <p class="text-muted fs-8 text-center mt-3 mb-0 fst-italic">
              *Pastikan foto yang baru lebih jelas dan sesuai panduan
            </p>
          </div>

          <!-- ✅ LABEL DENGAN INSTRUKSI JELAS -->
          <div class="mb-4">
            <label class="fs-5 fw-bold text-gray-800 mb-2 d-flex align-items-center">
              <Camera class="w-5 h-5 me-2 text-primary" />
              Foto KTP (Identitas Asli)
            </label>
            <p class="text-muted fs-7 mb-0">
              <span v-if="!guestData?.ktp_image && !selectedFile">
                Klik area di bawah atau tombol untuk memilih foto dari galeri Anda
              </span>
              <span v-else-if="guestData?.is_verified">
                Dokumen Anda sudah terverifikasi dan tersimpan dengan aman
              </span>
              <span v-else>
                Klik pada gambar untuk mengganti dengan foto yang lebih baik
              </span>
            </p>
          </div>
          
          <div class="position-relative">
            <input 
              id="ktp-file-input"
              type="file" 
              @change="onFileChange"
              accept="image/*"
              class="d-none"
              :disabled="isUploading || guestData?.is_verified"
            />

            <div 
              @click="!guestData?.is_verified && triggerFileInput()" 
              class="border-3 border-dashed rounded-4 overflow-hidden min-h-300px d-flex align-items-center justify-content-center bg-light position-relative transition-all"
              :class="{
                'border-primary bg-light-primary cursor-pointer hover-border-solid': !guestData?.is_verified && !previewUrl,
                'border-success': guestData?.is_verified,
                'border-gray-300': previewUrl && !guestData?.is_verified,
                'cursor-pointer hover-shadow-lg': !guestData?.is_verified && previewUrl
              }"
            >
              
              <div v-if="previewUrl" class="w-100 h-100 position-relative">
                <img :src="previewUrl" class="w-100 h-300px object-fit-cover" alt="KTP Preview" />
                
                <div 
                  v-if="!guestData?.is_verified" 
                  class="position-absolute inset-0 bg-dark bg-opacity-50 d-flex flex-column align-items-center justify-content-center opacity-0 hover-opacity-100 transition-all cursor-pointer"
                >
                  <Camera class="w-12 h-12 text-white mb-3" />
                  <div class="btn btn-lg btn-light fw-bold shadow-lg px-8">
                    {{ selectedFile ? 'Ganti Foto Lagi' : 'Klik untuk Ganti Foto' }}
                  </div>
                </div>

                <div v-if="guestData?.is_verified" class="position-absolute top-0 end-0 m-4">
                  <span class="badge badge-success badge-lg px-4 py-2 shadow-lg">
                    <CheckCircle class="w-4 h-4 me-1" /> Terverifikasi
                  </span>
                </div>
              </div>

              <div v-else class="text-center p-10 w-100">
                <div class="mb-5">
                  <div class="symbol symbol-100px symbol-circle bg-light-primary mx-auto mb-4">
                    <UploadCloud class="w-12 h-12 text-primary" />
                  </div>
                </div>
                <h4 class="fw-bold text-gray-800 mb-3">Upload Foto KTP Anda</h4>
                <p class="text-gray-600 fs-6 mb-5 px-5">
                  Klik area ini atau tombol di bawah untuk memilih foto dari galeri
                </p>
                <button 
                  type="button"
                  @click.stop="triggerFileInput"
                  class="btn btn-primary btn-lg fw-bold shadow-sm px-8"
                >
                  <Camera class="w-5 h-5 me-2" />
                  Pilih Foto dari Galeri
                </button>
                <p class="text-muted fs-8 mt-4 mb-0">Format: JPG, PNG • Maksimal 4MB</p>
              </div>
            </div>
          </div>

          <div v-if="selectedFile" class="mt-6 animate__animated animate__fadeInUp">
            <div class="alert alert-info d-flex align-items-center p-4 mb-4 border-info border-dashed">
              <Info class="w-5 h-5 text-info me-3" />
              <span class="text-gray-700 fs-7">File baru dipilih. Klik <strong>"Simpan & Kirim"</strong> untuk memproses verifikasi.</span>
            </div>
            
            <div class="d-flex gap-3">
              <button 
                @click="submitKtp" 
                :disabled="isUploading" 
                class="btn btn-primary btn-lg flex-grow-1 fw-bold py-3 shadow-sm hover-elevate-up position-relative overflow-hidden"
              >
                <span v-if="isUploading" class="d-flex align-items-center justify-content-center">
                  <Loader2 class="w-5 h-5 animate-spin me-2" />
                  Mengunggah...
                </span>
                <span v-else class="d-flex align-items-center justify-content-center">
                  <CheckCircle class="w-5 h-5 me-2" />
                  Simpan & Kirim Verifikasi
                </span>
              </button>
              <button 
                @click="cancelSelection" 
                :disabled="isUploading" 
                class="btn btn-light-danger btn-lg fw-bold py-3 px-6 hover-elevate-up"
              >
                <XCircle class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div v-if="guestData?.is_verified && !selectedFile" class="mt-6 p-6 bg-light-success rounded-4 border-2 border-success border-dashed animate__animated animate__fadeIn">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px symbol-circle me-4 bg-success shadow-sm">
                <UserCheck class="w-6 h-6 text-white" />
              </div>
              <div class="flex-grow-1">
                <h5 class="fw-bold text-success mb-1">Identitas Tervalidasi</h5>
                <p class="text-gray-700 fs-7 mb-0">Dokumen Anda telah sesuai standar verifikasi. Akun Anda kini memiliki akses penuh ke semua layanan.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Informasi & Panduan -->
        <div class="col-lg-5">
          <div class="sticky-top custom-sticky" style="top: 20px;">
            <div class="p-8 bg-light-primary rounded-4 border-2 border-primary border-opacity-10 h-100">
              <div class="d-flex align-items-center mb-6">
                <div class="symbol symbol-40px symbol-circle bg-primary me-3">
                  <Info class="w-5 h-5 text-white" />
                </div>
                <h5 class="fw-bold text-primary m-0">Panduan Foto KTP</h5>
              </div>
              
              <p class="fs-7 text-gray-700 mb-6 leading-relaxed">
                Untuk mempercepat proses verifikasi, pastikan foto KTP Anda memenuhi kriteria berikut:
              </p>

              <div class="d-flex flex-column gap-4 mb-6">
                <div class="d-flex align-items-start bg-white rounded-3 p-4 shadow-sm">
                  <div class="symbol symbol-30px symbol-circle bg-light-success me-3 flex-shrink-0">
                    <CheckCircle class="w-4 h-4 text-success" />
                  </div>
                  <span class="text-gray-700 fs-7 fw-semibold">Gunakan KTP asli, bukan fotokopi atau foto dari layar HP</span>
                </div>
                <div class="d-flex align-items-start bg-white rounded-3 p-4 shadow-sm">
                  <div class="symbol symbol-30px symbol-circle bg-light-success me-3 flex-shrink-0">
                    <CheckCircle class="w-4 h-4 text-success" />
                  </div>
                  <span class="text-gray-700 fs-7 fw-semibold">Pastikan seluruh sudut kartu masuk ke dalam frame foto</span>
                </div>
                <div class="d-flex align-items-start bg-white rounded-3 p-4 shadow-sm">
                  <div class="symbol symbol-30px symbol-circle bg-light-success me-3 flex-shrink-0">
                    <CheckCircle class="w-4 h-4 text-success" />
                  </div>
                  <span class="text-gray-700 fs-7 fw-semibold">Nama dan NIK harus terbaca jelas tanpa pantulan cahaya</span>
                </div>
                <div class="d-flex align-items-start bg-white rounded-3 p-4 shadow-sm">
                  <div class="symbol symbol-30px symbol-circle bg-light-success me-3 flex-shrink-0">
                    <CheckCircle class="w-4 h-4 text-success" />
                  </div>
                  <span class="text-gray-700 fs-7 fw-semibold">Ambil foto di tempat dengan pencahayaan yang cukup</span>
                </div>
              </div>

              <div class="separator separator-dashed border-primary border-opacity-25 my-6"></div>

              <div class="bg-white rounded-3 p-4 border border-primary border-opacity-10">
                <p class="fs-8 text-muted mb-0 d-flex align-items-start">
                  <ShieldAlert class="w-4 h-4 text-primary me-2 flex-shrink-0 mt-1" />
                  <span class="fst-italic">
                    Keamanan data Anda adalah prioritas kami. Dokumen dienkripsi dan hanya digunakan untuk keperluan verifikasi identitas check-in hotel.
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.z-index-1 { z-index: 1; }
.inset-0 { top: 0; left: 0; right: 0; bottom: 0; }
.transition-all { transition: all 0.3s ease; }
.hover-opacity-100:hover { opacity: 1 !important; }
.hover-shadow-lg:hover { box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important; }
.hover-elevate-up:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12); }
.hover-border-solid:hover { border-style: solid !important; }

.min-h-300px { min-height: 300px; }
.min-h-500px { min-height: 500px; }
.max-w-600px { max-width: 600px; }
.object-fit-cover { object-fit: cover; }

.custom-sticky {
  position: sticky;
  z-index: 1 !important;
}

.animate-spin { animation: spin 1s linear infinite; }
.animate-spin-slow { animation: spin 2s linear infinite; }

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.leading-relaxed { line-height: 1.75; }
.cursor-pointer { cursor: pointer; }
.flex-shrink-0 { flex-shrink: 0; }

@media (max-width: 991.98px) {
  .custom-sticky {
    position: relative !important;
  }
}
</style>