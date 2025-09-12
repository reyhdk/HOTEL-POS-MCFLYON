<template>
  <div class="d-flex flex-column flex-root" id="kt_app_root">
    <div
      class="d-flex flex-column flex-center flex-column-fluid"
      style="background-image: url(/media/auth/bg1.jpg)"
    >
      <div class="d-flex flex-column flex-center flex-column-fluid p-10">
        <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-800px p-10">
          <router-view></router-view>
        </div>
        </div>
      </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import LayoutService from "@/core/services/LayoutService";
import { useBodyStore } from "@/stores/body";

const store = useBodyStore();

onMounted(() => {
  LayoutService.emptyElementClassesAndAttributes(document.body);
  store.addBodyClassname("app-blank");
});
</script>