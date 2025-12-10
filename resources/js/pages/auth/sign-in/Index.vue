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
                {{ setting?.app || 'McFlyon Hotel' }}
             </h1>
        </div>

        <div class="text-center" style="max-width: 500px;">
            <p class="text-white fs-4 opacity-75 fw-light">
              "Experience luxury and comfort in every stay. Manage your hotel operations with ease."
            </p>
        </div>

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
            <h1 class="text-dark fw-bolder mb-3 fs-1">Selamat Datang</h1>
            <div class="text-gray-400 fw-semibold fs-5">
              Please log in to your account
            </div>
          </div>

          <div class="anim-up-2">
            <WithEmail />
          </div>

          <div class="text-center text-gray-500 fw-semibold fs-5 mt-10 anim-up-3">
            Belum memiliki akun?
            <router-link to="/auth/sign-up" class="link-theme fw-bold ms-1">
              Daftar Sekarang
            </router-link>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import WithEmail from "./tabs/WithEmail.vue";
import { useSetting } from "@/services";

// Mengambil data setting
const { data: setting } = useSetting();

// Computed Property untuk Background
// Ini mengecek: Apakah ada bg_auth dari database?
// Jika YA: Pakai itu.
// Jika TIDAK: Pakai gambar default Unsplash.
const backgroundStyle = computed(() => {
  const data = setting.value;
  
  if (data && data.bg_auth) {
    return {
      backgroundImage: `url('${data.bg_auth}')`
    };
  }

  // Fallback Image (Default)
  return {
    backgroundImage: "url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=1470&auto=format&fit=crop')"
  };
});
</script>

<style scoped>
/* --- ANIMATION KEYFRAMES --- */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes fadeInUp {
  from { 
    opacity: 0;
    transform: translateY(30px);
  }
  to { 
    opacity: 1;
    transform: translateY(0);
  }
}

.anim-fade-in {
  animation: fadeIn 1.2s ease-out forwards;
}

.anim-up-1 {
  opacity: 0;
  animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.1s forwards;
}
.anim-up-2 {
  opacity: 0;
  animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.3s forwards;
}
.anim-up-3 {
  opacity: 0;
  animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.5s forwards;
}

/* --- EXISTING STYLES --- */
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

/* Menjaga style global button/form agar tetap konsisten */
:deep(.btn-orange) { background-color: #FF6B35 !important; border-color: #FF6B35 !important; color: white !important; outline: none !important; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important; }
:deep(.btn-orange:hover) { background-color: #e85d2a !important; border-color: #e85d2a !important; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255, 107, 53, 0.25) !important; }
:deep(.btn-orange:active), :deep(.btn-orange:focus) { background-color: #d14d1d !important; border-color: #d14d1d !important; box-shadow: 0 0 0 0.25rem rgba(255, 107, 53, 0.3) !important; }
:deep(.form-control) { border-radius: 0.75rem; padding-top: 0.8rem; padding-bottom: 0.8rem; }
:deep(.form-control:focus) { border-color: #FF6B35 !important; background-color: #fff9f5 !important; box-shadow: 0 0 0 0.25rem rgba(255, 107, 53, 0.15) !important; color: #3f4254; }
</style>