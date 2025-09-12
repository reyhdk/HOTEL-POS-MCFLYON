<template>
  <div class="d-flex flex-column flex-root" id="kt_app_root">
    <div class="page d-flex flex-row flex-column-fluid">
      <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
        <div id="kt_header" class="header align-items-stretch">
          <div class="container-fluid d-flex align-items-stretch justify-content-between">
            <div class="d-flex align-items-center">
              <h3>Selamat Datang, {{ store.user?.name }}</h3>
            </div>
            <div class="d-flex align-items-center">
              <a @click="signOut" class="btn btn-sm btn-light-primary">Keluar</a>
            </div>
          </div>
        </div>
        <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
          <div class="container-xxl" id="kt_content_container">
            <router-view />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";

const router = useRouter();
const store = useAuthStore();
// PERBAIKAN DI SINI: Hapus baris "const { user } = store;"

const signOut = () => {
  store.logout();
  Swal.fire({
      text: "Anda telah berhasil keluar.",
      icon: "success",
      buttonsStyling: false,
      confirmButtonText: "Ok, mengerti!",
      customClass: { confirmButton: "btn btn-primary" },
  }).then(() => {
      router.push({ name: "sign-in" });
  });
};
</script>