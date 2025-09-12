<template>
  <div
    class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold py-4 fs-6 w-275px"
    data-kt-menu="true"
  >
    <div v-if="user" class="menu-item px-3">
      <div class="menu-content d-flex align-items-center px-3">
        <div class="symbol symbol-50px me-5">
          <img
            alt="Logo"
            :src="user.photo || getAssetPath('media/avatars/300-3.jpg')"
          />
        </div>
        <div class="d-flex flex-column">
          <div class="fw-bold d-flex align-items-center fs-5">
            {{ user.name }}
            <span
              v-if="user.role"
              class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2"
            >
              {{ user.role.name }}
            </span>
          </div>
          <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">
            {{ user.email }}
          </a>
        </div>
        </div>
    </div>
    <div class="separator my-2"></div>
    <div class="menu-item px-5 my-1">
      <router-link to="/admin/profile" class="menu-link px-5">
        Pengaturan Akun
      </router-link>
    </div>
    <div class="menu-item px-5">
      <a @click="signOut()" class="menu-link px-5"> Keluar </a>
    </div>
    </div>
  </template>

<script setup lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { useAuthStore } from "@/stores/auth";
import Swal from "sweetalert2";

const store = useAuthStore();

// Langsung ambil user dari store, tidak perlu 'computed'
const user = store.user;

const signOut = () => {
  Swal.fire({
    icon: "warning",
    text: "Apakah Anda yakin ingin keluar?",
    showCancelButton: true,
    confirmButtonText: "Ya, Keluar",
    cancelButtonText: "Batal",
    reverseButtons: true,
    buttonsStyling: false,
    customClass: {
      confirmButton: "btn fw-semibold btn-light-primary",
      cancelButton: "btn fw-semibold btn-light-danger",
    },
  }).then((result) => {
    if (result.isConfirmed) {
      store.logout();
    }
  });
};
</script>