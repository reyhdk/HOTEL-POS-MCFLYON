<script setup lang="ts">
import { h, ref, watch } from "vue";
import { useDelete } from "@/libs/hooks";
import Form from "./Form.vue";
import { createColumnHelper } from "@tanstack/vue-table";
import type { Role } from "@/types"; // [DIBENARKAN] Menggunakan tipe Role

// [DIBENARKAN] Mendefinisikan kolom sesuai tipe Role
const columnHelper = createColumnHelper<Role>();
const paginateRef = ref<any>(null);
const selectedId = ref<number | null>(null); // [DIBENARKAN] Tipe diubah menjadi number | null
const isFormVisible = ref<boolean>(false);

const { delete: deleteRole } = useDelete({
    onSuccess: () => paginateRef.value.refetch(),
});

const columns = [
    columnHelper.accessor("id", { // Menggunakan 'id' untuk nomor urut
        header: "#",
        cell: info => info.row.index + 1,
    }),
    columnHelper.accessor("name", {
        header: "Nama",
    }),
    columnHelper.accessor("full_name", {
        header: "Nama Lengkap",
    }),
    columnHelper.accessor("id", {
        header: "Aksi",
        cell: (info) =>
            h("div", { class: "d-flex gap-2" }, [
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-icon btn-light-primary",
                        title: "Edit",
                        onClick: () => {
                            selectedId.value = info.getValue();
                            isFormVisible.value = true;
                        },
                    },
                    h("i", { class: "ki-duotone ki-pencil fs-2" })
                ),
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-icon btn-light-danger",
                        title: "Hapus",
                        onClick: () =>
                            deleteRole(`/master/roles/${info.getValue()}`),
                    },
                    h("i", { class: "ki-duotone ki-trash fs-2" })
                ),
            ]),
    }),
];

const refreshTable = () => {
  if (paginateRef.value) {
    paginateRef.value.refetch();
  }
};

const openAddForm = () => {
    selectedId.value = null; // Pastikan ID kosong saat menambah
    isFormVisible.value = true;
};

// Mengawasi perubahan visibilitas form
    (isFormVisible, (isVisible) => {
    if (!isVisible) {
        selectedId.value = null; // Reset ID saat form ditutup
    }
    window.scrollTo(0, 0);
});
</script>

<template>
    <Form
        v-if="isFormVisible"
        :selected="selectedId"
        @close="isFormVisible = false"
        @refresh="refreshTable"
    />

    <div class="card shadow-sm">
        <div class="card-header align-items-center">
            <h2 class="mb-0">Manajemen Role</h2>
            <button
                type="button"
                class="btn btn-sm btn-primary ms-auto"
                v-if="!isFormVisible"
                @click="openAddForm"
            >
                <i class="ki-duotone ki-plus fs-2"></i>
                Tambah Role
            </button>
        </div>
        <div class="card-body">
            <PaginateDataTable
                ref="paginateRef"
                id="table-role"
                url="/master/roles"
                :columns="columns"
            ></PaginateDataTable>
        </div>
    </div>
</template>
