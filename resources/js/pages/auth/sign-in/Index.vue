<template>
  <div class="w-100">
    <div class="text-center mb-10">
        <img
            :src="setting?.logo"
            :alt="setting?.app"
            class="w-200px mb-8"
        />
        <h1 class="mb-3">
            Masuk ke <span class="text-primary">{{ setting?.app }}</span>
        </h1>
    </div>

    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#with-email">Login</a>
      </li>
    </ul>

    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade show active" id="with-email" role="tabpanel">
        <WithEmail />
      </div>
    </div>

    <div class="border-bottom border-gray-300 w-100 mt-5 mb-10"></div>

    <div class="text-gray-400 fw-semobold fs-4 text-center">
      Belum memiliki akun?
      <router-link to="/auth/sign-up" class="link-primary fw-bold">
        Buat Akun Sekarang
      </router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import WithEmail from "./tabs/WithEmail.vue";
import { useSetting } from "@/services";

const { data: setting } = useSetting() || { data: {} };
</script>
