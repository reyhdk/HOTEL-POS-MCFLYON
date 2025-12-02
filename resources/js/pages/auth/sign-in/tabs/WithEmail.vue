<template>
  <VForm class="form w-100" @submit="onSubmitLogin" :validation-schema="loginSchema">
    
    <div class="fv-row mb-8">
      <label class="form-label fs-6 fw-bold text-dark">Email</label>
      <div class="position-relative">
        <span class="position-absolute top-50 start-0 translate-middle-y ms-4 text-gray-400">
           <i class="fas fa-envelope fs-5"></i>
        </span>
        <Field tabindex="1" class="form-control form-control-lg form-control-solid ps-12" type="text" name="email" placeholder="Email" autocomplete="off" />
      </div>
      <div class="fv-plugins-message-container mt-1"><ErrorMessage name="email" class="text-danger fs-7 fw-semibold" /></div>
    </div>

    <div class="fv-row mb-10">
      <div class="d-flex flex-stack mb-2">
        <label class="form-label fw-bold fs-6 mb-0 text-dark">Password</label>
        <router-link to="/auth/password-reset" class="link-theme fs-7 fw-bold" tabindex="-1">Lupa Password?</router-link>
      </div>
      <div class="position-relative mb-3">
        <span class="position-absolute top-50 start-0 translate-middle-y ms-4 text-gray-400">
           <i class="fas fa-lock fs-5"></i>
        </span>
        <Field tabindex="2" class="form-control form-control-lg form-control-solid ps-12 pe-10" :type="passwordFieldType" name="password" placeholder="••••••••" autocomplete="off" />
        <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-1" @click="togglePasswordVisibility" style="z-index: 5;">
          <i :class="passwordFieldType === 'password' ? 'bi-eye-slash' : 'bi-eye'" class="bi fs-3 text-gray-500"></i>
        </span>
      </div>
      <div class="fv-plugins-message-container mt-1"><ErrorMessage name="password" class="text-danger fs-7 fw-semibold" /></div>
    </div>

    <div class="text-center">
      <button tabindex="3" type="submit" ref="submitButton" class="btn btn-lg btn-orange w-100 mb-5 shadow-sm">
        <span class="indicator-label fs-5">Masuk</span>
        <span class="indicator-progress">Mohon tunggu... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
      </button>
    </div>
  </VForm>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import { useAuthStore } from "@/stores/auth";
import * as Yup from "yup";
import Swal from "sweetalert2";

const store = useAuthStore();
const submitButton = ref<HTMLButtonElement | null>(null);
const passwordFieldType = ref("password");

const loginSchema = Yup.object().shape({
  email: Yup.string().email("Format email tidak valid").required("Email wajib diisi").label("Email"),
  password: Yup.string().min(8, "Password minimal 8 karakter").required("Password wajib diisi").label("Password"),
});

const togglePasswordVisibility = () => {
  passwordFieldType.value = passwordFieldType.value === "password" ? "text" : "password";
};

const onSubmitLogin = async (values: any) => {
  if (submitButton.value) {
    submitButton.value.disabled = true;
    submitButton.value.setAttribute("data-kt-indicator", "on");
  }

  try {
    // 1. Login (Pastikan handleRedirect dimatikan di auth.ts)
    await store.login(values);

    // 2. Tampilkan Notifikasi "Premium Side-Accent"
    await Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Login Berhasil',
      text: 'Selamat datang kembali di McFlyon Hotel.',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
      
      // KONFIGURASI STYLE PREMIUM
      background: '#ffffff',
      iconColor: '#FF6B35', // Warna Icon Orange
      customClass: {
        popup: 'premium-toast',         // Class Custom (Lihat CSS di bawah)
        title: 'premium-toast-title',
        htmlContainer: 'premium-toast-message',
        timerProgressBar: 'premium-progress'
      },
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });

    // 3. Panggil Redirect Manual
    setTimeout(() => {
        store.handleRedirect();
    }, 500);

  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Gagal Masuk",
      text: store.errors ? Object.values(store.errors)[0] as string : "Email atau password salah.",
      buttonsStyling: false,
      confirmButtonText: "Coba Lagi",
      customClass: { 
        popup: 'rounded-4 shadow-lg border-0',
        confirmButton: "btn btn-danger rounded-pill fw-bold px-4" 
      },
    });
  } finally {
    if (submitButton.value) {
      submitButton.value.disabled = false;
      submitButton.value.removeAttribute("data-kt-indicator");
    }
  }
};
</script>

<style>
/* --- PREMIUM TOAST STYLING (Luxury Hotel Theme) --- */

/* 1. Kotak Utama Alert */
.premium-toast {
  border-radius: 12px !important;            /* Sudut membulat */
  border: none !important;                   /* Hapus border bawaan */
  border-left: 6px solid #FF6B35 !important; /* AKSEN UTAMA: Garis Orange di Kiri */
  background: #ffffff !important;            /* Putih bersih */
  box-shadow: 0 15px 40px -10px rgba(0, 0, 0, 0.15) !important; /* Bayangan Mewah */
  padding: 1.25rem 1rem !important;          /* Spasi lega */
  display: flex !important;
  align-items: center !important;
  margin-top: 1rem !important;
  margin-right: 1.5rem !important;
}

/* 2. Judul (Login Berhasil) */
.premium-toast-title {
  color: #2c3e50 !important;
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 700 !important;
  font-size: 1.1rem !important;
  margin-bottom: 0.2rem !important;
}

/* 3. Pesan (Selamat datang...) */
.premium-toast-message {
  color: #7f8c8d !important;
  font-size: 0.9rem !important;
  font-weight: 500 !important;
}

/* 4. Progress Bar */
.premium-progress {
  background-color: rgba(255, 107, 53, 0.1) !important; /* Track tipis */
}
.swal2-timer-progress-bar {
  background-color: #FF6B35 !important; /* Bar berjalan orange */
}

/* 5. Icon SweetAlert */
.premium-toast .swal2-icon {
  border-color: #FF6B35 !important; 
  color: #FF6B35 !important;
  transform: scale(0.8);
}

/* Style Input & Link (Bawaan) */
.link-theme { color: #FF6B35; text-decoration: none; }
.link-theme:hover { color: #d14d1d; text-decoration: underline; }
.ps-12 { padding-left: 3rem !important; }
.form-control-solid { background-color: #f5f8fa; border-color: #f5f8fa; color: #5e6278; transition: all 0.2s ease; }
.form-control-solid:focus { background-color: #fff9f5 !important; border-color: #FF6B35 !important; box-shadow: 0 0 0 0.25rem rgba(255, 107, 53, 0.15) !important; }
</style>