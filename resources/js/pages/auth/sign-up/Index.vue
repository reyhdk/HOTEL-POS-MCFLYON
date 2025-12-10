<template>
  <div class="d-flex flex-column flex-lg-row flex-column-fluid h-100vh overflow-hidden">
    
    <div 
      class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-1 position-relative h-100 anim-fade-in"
      :style="backgroundStyle"
    >
      <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100 h-100 bg-gradient-overlay">
        
        <div class="mb-10 text-center">
             <img v-if="setting?.logo" :src="setting.logo" class="h-70px mb-4" alt="Logo"/>
             <i v-else class="fas fa-hotel fa-4x text-white mb-4"></i>

             <h1 class="text-white display-4 fw-bolder text-shadow">
                Join {{ setting?.app || 'McFlyon Hotel' }}
             </h1>
        </div>

        <p class="text-white fs-4 opacity-75 text-center fw-light" style="max-width: 500px;">
          "Create an account to unlock exclusive features and manage your bookings effortlessly."
        </p>

        <div class="mt-auto text-white opacity-50 fs-7">
          &copy; {{ new Date().getFullYear() }} {{ setting?.app || 'McFlyon System' }}. By @reyhdk_
        </div>
      </div>
    </div>

    <div class="d-flex flex-column flex-lg-row-auto w-lg-500px w-xl-600px position-relative order-2 order-lg-2 bg-body h-100 overflow-auto">
      
      <div class="position-absolute top-0 end-0 m-5 z-index-2 anim-up-3">
         <router-link to="/" class="btn btn-light-theme btn-sm fw-bold px-4 rounded-pill">
            <i class="fas fa-times me-2"></i> Tutup
         </router-link>
      </div>

      <div class="d-flex flex-center flex-column flex-column-fluid p-10 p-lg-10">
        <div class="w-100 w-md-400px mx-auto">
          
          <div class="mb-10 anim-up-1">
            <h1 class="text-dark fw-bolder mb-3 fs-1">Buat Akun Baru</h1>
            <div class="text-gray-400 fw-semibold fs-5">
              Sudah punya akun?
              <router-link to="/auth/sign-in" class="link-theme fw-bold ms-1">
                Login
              </router-link>
            </div>
          </div>

          <div class="anim-up-2">
            <VForm class="form w-100" @submit="onSubmitRegister" :validation-schema="registrationSchema">
                
                <div class="fv-row mb-7">
                  <label class="form-label fw-bolder text-dark fs-6">Nama Lengkap</label>
                   <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-4 text-gray-500"><i class="fas fa-user fs-5"></i></span>
                    <Field class="form-control form-control-lg form-control-solid ps-12" type="text" name="name" autocomplete="off" placeholder="Nama Lengkap" />
                   </div>
                   <div class="fv-plugins-message-container mt-1"><ErrorMessage name="name" class="text-danger fs-7 fw-semibold" /></div>
                </div>

                <div class="fv-row mb-7">
                  <label class="form-label fw-bolder text-dark fs-6">Email</label>
                   <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-4 text-gray-500"><i class="fas fa-envelope fs-5"></i></span>
                    <Field class="form-control form-control-lg form-control-solid ps-12" type="email" name="email" autocomplete="off" placeholder="Email" />
                   </div>
                   <div class="fv-plugins-message-container mt-1"><ErrorMessage name="email" class="text-danger fs-7 fw-semibold" /></div>
                </div>

                <div class="fv-row mb-7">
                  <label class="form-label fw-bolder text-dark fs-6">Password</label>
                  <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-4 text-gray-500"><i class="fas fa-lock fs-5"></i></span>
                    <Field class="form-control form-control-lg form-control-solid ps-12 pe-12" :type="showPassword ? 'text' : 'password'" name="password" autocomplete="off" placeholder="Min. 8" />
                    <span class="btn btn-sm btn-icon position-absolute top-50 end-0 translate-middle-y me-2" @click="showPassword = !showPassword" style="cursor: pointer; z-index: 5;"><i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'" class="bi fs-3 text-gray-500"></i></span>
                  </div>
                  <div class="fv-plugins-message-container mt-1"><ErrorMessage name="password" class="text-danger fs-7 fw-semibold" /></div>
                </div>

                <div class="fv-row mb-10">
                  <label class="form-label fw-bolder text-dark fs-6">Konfirmasi Password</label>
                  <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-4 text-gray-500"><i class="fas fa-check-circle fs-5"></i></span>
                    <Field class="form-control form-control-lg form-control-solid ps-12 pe-12" :type="showConfirmPassword ? 'text' : 'password'" name="password_confirmation" autocomplete="off" placeholder="********" />
                    <span class="btn btn-sm btn-icon position-absolute top-50 end-0 translate-middle-y me-2" @click="showConfirmPassword = !showConfirmPassword" style="cursor: pointer; z-index: 5;"><i :class="showConfirmPassword ? 'bi-eye-slash' : 'bi-eye'" class="bi fs-3 text-gray-500"></i></span>
                  </div>
                  <div class="fv-plugins-message-container mt-1"><ErrorMessage name="password_confirmation" class="text-danger fs-7 fw-semibold" /></div>
                </div>

                <div class="text-center">
                  <button ref="submitButton" type="submit" class="btn btn-lg btn-orange w-100 mb-5">
                    <span class="indicator-label fw-bold fs-5">Daftar Sekarang</span>
                    <span class="indicator-progress">Memproses... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                  </button>
                </div>
            </VForm>
          </div>

          <div class="anim-up-3">
             <div class="text-center text-muted text-uppercase fw-bold mb-5">and</div>
             <div class="text-gray-400 fs-7 text-center mb-5">
               By registering, you agree to<a href="#" class="link-theme fw-bold"><a> Our </a>Terms and Conditions</a> 
             </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue"; // Tambahkan computed
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import * as Yup from "yup";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";
import { useSetting } from "@/services";

const submitButton = ref<HTMLButtonElement | null>(null);
const store = useAuthStore();
const router = useRouter();

// Menggunakan useSetting yang sama dengan SignIn
const { data: setting } = useSetting();

// Computed Property untuk Background Dinamis
const backgroundStyle = computed(() => {
  const data = setting.value;
  
  if (data && data.bg_auth) {
    return {
      backgroundImage: `url('${data.bg_auth}')`
    };
  }

  // Fallback ke Default Image
  return {
    backgroundImage: "url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=1470&auto=format&fit=crop')"
  };
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const registrationSchema = Yup.object().shape({
  name: Yup.string().required("Nama wajib diisi").label("Nama Lengkap"),
  email: Yup.string().min(4).required("Email wajib diisi").email("Format email salah").label("Email"),
  password: Yup.string().required("Password wajib diisi").min(8, "Minimal 8 karakter").label("Password"),
  password_confirmation: Yup.string().required("Konfirmasi password wajib diisi").oneOf([Yup.ref("password")], "Password tidak sama").label("Konfirmasi password"),
});

const onSubmitRegister = async (values: any) => {
  if (submitButton.value) {
    submitButton.value.disabled = true;
    submitButton.value.setAttribute("data-kt-indicator", "on");
  }
  try {
    await store.register(values);
    Swal.fire({
      text: "Registrasi berhasil! Anda akan dialihkan.",
      icon: "success",
      buttonsStyling: false,
      confirmButtonText: "Ok, Lanjutkan!",
      customClass: { confirmButton: "btn btn-orange text-white" }, 
    }).then(() => { router.push({ name: "user-dashboard" }); });
  } catch (error) {
    const errorData = store.errors as any;
    let errorMessage = "Terjadi kesalahan. Silakan coba lagi.";
    if (typeof errorData === 'object' && errorData !== null) {
      const firstErrorKey = Object.keys(errorData)[0];
      if (firstErrorKey && Array.isArray(errorData[firstErrorKey])) { errorMessage = errorData[firstErrorKey][0]; }
    }
    Swal.fire({ text: errorMessage, icon: "error", buttonsStyling: false, confirmButtonText: "Coba Lagi", customClass: { confirmButton: "btn btn-danger" }, });
  } finally {
    if (submitButton.value) { submitButton.value.disabled = false; submitButton.value.removeAttribute("data-kt-indicator"); }
  }
};
</script>

<style scoped>
/* --- ANIMATION KEYFRAMES --- */
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.anim-fade-in { animation: fadeIn 1.2s ease-out forwards; }
.anim-up-1 { opacity: 0; animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.1s forwards; }
.anim-up-2 { opacity: 0; animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.3s forwards; }
.anim-up-3 { opacity: 0; animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.5s forwards; }

/* --- STYLE EXISTING --- */
.h-100vh { height: 100vh !important; }
.overflow-hidden { overflow: hidden !important; }
.overflow-auto::-webkit-scrollbar { width: 6px; }
.overflow-auto::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.2); border-radius: 4px; }
.z-index-2 { z-index: 2; }
.bg-gradient-overlay { background: linear-gradient(to bottom, rgba(0, 0, 0, 0.4) 0%, rgba(255, 107, 53, 0.7) 100%); backdrop-filter: blur(2px); }
.text-shadow { text-shadow: 0 4px 15px rgba(0,0,0,0.5); }
.text-theme { color: #FF6B35 !important; }
.btn-light-theme { background-color: white; border: 1px solid #f1f1f4; color: #7e8299; transition: all 0.3s ease; }
.btn-light-theme:hover { background-color: #fff5f0; color: #FF6B35; border-color: #FF6B35; }
.link-theme { color: #FF6B35 !important; text-decoration: none; transition: all 0.2s ease; }
.link-theme:hover { color: #d14d1d !important; text-decoration: underline; }
.text-gray-500 { color: #a1a5b7 !important; }

/* Style Form & Button Konsisten dengan Login */
:deep(.btn-orange) { background-color: #FF6B35 !important; border: 1px solid #FF6B35 !important; color: white !important; outline: none !important; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important; }
:deep(.btn-orange:hover) { background-color: #e85d2a !important; border-color: #e85d2a !important; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255, 107, 53, 0.25) !important; color: white !important; }
:deep(.btn-orange:active), :deep(.btn-orange:focus) { background-color: #d14d1d !important; border-color: #d14d1d !important; box-shadow: 0 0 0 0.25rem rgba(255, 107, 53, 0.3) !important; color: white !important; }
:deep(.form-control-solid) { background-color: #f5f8fa; border-color: #f5f8fa; color: #5e6278; border-radius: 0.75rem; padding-top: 0.8rem; padding-bottom: 0.8rem; transition: all 0.2s ease; }
:deep(.form-control-solid:focus) { border-color: #FF6B35 !important; background-color: #fff9f5 !important; box-shadow: 0 0 0 0.25rem rgba(255, 107, 53, 0.15) !important; color: #3f4254; }

.ps-12 { padding-left: 3rem !important; }
.pe-12 { padding-right: 3rem !important; }
</style>