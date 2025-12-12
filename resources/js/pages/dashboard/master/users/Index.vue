<template>
  <div class="d-flex flex-column gap-5">
    
    <!-- HEADER CARD - Premium Orange Design -->
    <div class="row g-5 g-xl-8 mb-5 animate__animated animate__fadeInDown">
      <div class="col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-60px me-5">
                <span class="symbol-label bg-light-orange">
                  <i class="ki-duotone ki-profile-user fs-2x text-orange">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2hx fw-bold text-gray-900 lh-1 ls-n2">{{ pagination.total }}</span>
                <span class="text-gray-500 pt-1 fw-semibold fs-5">Total Pengguna Terdaftar</span>
              </div>
            </div>

            <div>
              <button @click="handleAdd" class="btn btn-orange">
                <i class="ki-duotone ki-plus fs-2"></i>
                Tambah User
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN TABLE CARD -->
    <div class="card card-flush animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
      
      <!-- Card Header with Filters -->
      <div class="card-header align-items-center py-5 gap-2 gap-md-5 border-0">
        <div class="card-title">
          <div class="d-flex align-items-center position-relative my-1">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
              <span class="path1"></span>
              <span class="path2"></span>
            </i>
            <input 
              type="text" 
              v-model="search" 
              @input="handleSearch"
              class="form-control form-control-solid w-250px ps-12" 
              placeholder="Cari nama, email..." 
            />
          </div>
        </div>

        <div class="card-toolbar flex-row-fluid justify-content-end gap-2">
          <!-- Filter Role -->
          <el-select 
            v-model="filterRole" 
            placeholder="Filter Role" 
            class="w-150px metronic-select-orange"
            size="large"
            @change="handleFilterChange"
            clearable
          >
            <template #prefix>
              <i class="ki-duotone ki-filter fs-3 text-gray-500">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
            </template>
            <el-option label="Semua Role" value="" />
            <el-option 
              v-for="role in rolesList" 
              :key="role.id" 
              :label="role.name.toUpperCase()" 
              :value="role.name" 
            />
          </el-select>

          <!-- Reset Button -->
          <button 
            v-if="search || filterRole"
            @click="resetFilters"
            class="btn btn-icon btn-light btn-active-light-danger"
            data-bs-toggle="tooltip"
            title="Reset Filter"
          >
            <i class="ki-duotone ki-cross fs-2">
              <span class="path1"></span>
              <span class="path2"></span>
            </i>
          </button>
        </div>
      </div>

      <!-- Card Body -->
      <div class="card-body pt-0">
        
        <!-- Loading State -->
        <div v-if="loading" class="table-responsive">
          <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
              <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                <th class="min-w-300px">User</th>
                <th class="min-w-150px">Role</th>
                <th class="min-w-150px">Kontak</th>
                <th class="text-end min-w-100px">Aksi</th>
              </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
              <tr v-for="i in 5" :key="i">
                <td>
                  <div class="d-flex align-items-center">
                    <el-skeleton-item variant="circle" style="width: 50px; height: 50px" />
                    <div class="ms-5">
                      <el-skeleton-item variant="text" style="width: 150px" class="mb-2" />
                      <el-skeleton-item variant="text" style="width: 200px" />
                    </div>
                  </div>
                </td>
                <td><el-skeleton-item variant="text" style="width: 100px" /></td>
                <td><el-skeleton-item variant="text" style="width: 120px" /></td>
                <td><el-skeleton-item variant="text" style="width: 80px" /></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Data Table -->
        <div v-else class="table-responsive">
          <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_users_table">
            <thead>
              <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                <th class="min-w-300px">User</th>
                <th class="min-w-150px">Role</th>
                <th class="min-w-150px">Kontak</th>
                <th class="text-end min-w-100px">Aksi</th>
              </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
              <!-- Empty State -->
              <tr v-if="tableData.length === 0">
                <td colspan="4" class="text-center py-20">
                  <div class="d-flex flex-column align-items-center">
                    <div class="symbol symbol-100px symbol-circle mb-7">
                      <span class="symbol-label bg-light-orange">
                        <i class="ki-duotone ki-abstract-26 fs-3x text-orange">
                          <span class="path1"></span>
                          <span class="path2"></span>
                        </i>
                      </span>
                    </div>
                    <h3 class="fw-bold text-gray-800 mb-3">Tidak Ada Data</h3>
                    <span class="text-gray-500 fs-6">Coba ubah kata kunci pencarian atau filter Anda.</span>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <tr v-for="row in tableData" :key="row.id" class="hover-table-row">
                <td>
                  <div class="d-flex align-items-center">
                    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                      <div class="symbol-label">
                        <img 
                          v-if="row.photo" 
                          :src="row.photo" 
                          class="w-100" 
                        />
                        <span v-else class="symbol-label fs-3 fw-bold text-orange bg-light-orange">
                          {{ getInitials(row.name) }}
                        </span>
                      </div>
                    </div>
                    <div class="d-flex flex-column">
                      <a href="#" class="text-gray-800 text-hover-orange fw-bold mb-1">
                        {{ row.name }}
                      </a>
                      <span class="text-gray-500 fw-semibold d-block fs-7">
                        {{ row.email }}
                      </span>
                    </div>
                  </div>
                </td>

                <td>
                  <div class="d-flex flex-wrap gap-2">
                    <span 
                      v-for="role in row.roles" 
                      :key="role.id" 
                      class="badge badge-light-orange fw-bold"
                    >
                      {{ role.name.toUpperCase() }}
                    </span>
                  </div>
                </td>

                <td>
                  <span v-if="row.phone" class="text-gray-700 fw-semibold">
                    {{ row.phone }}
                  </span>
                  <span v-else class="text-muted fst-italic fs-7">Tidak tersedia</span>
                </td>

                <td class="text-end">
                  <button 
                    @click="handleEdit(row)" 
                    class="btn btn-icon btn-bg-light btn-active-color-orange btn-sm me-1"
                    data-bs-toggle="tooltip" 
                    title="Edit"
                  >
                    <i class="ki-duotone ki-pencil fs-3">
                      <span class="path1"></span>
                      <span class="path2"></span>
                    </i>
                  </button>
                  <button 
                    @click="handleDelete(row.id)" 
                    class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm"
                    data-bs-toggle="tooltip" 
                    title="Delete"
                  >
                    <i class="ki-duotone ki-trash fs-3">
                      <span class="path1"></span>
                      <span class="path2"></span>
                      <span class="path3"></span>
                      <span class="path4"></span>
                      <span class="path5"></span>
                    </i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > 0" class="d-flex flex-stack flex-wrap pt-10">
          <div class="fs-6 fw-semibold text-gray-700">
            Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }} hingga 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
            dari {{ pagination.total }} data
          </div>

          <el-pagination
            background
            layout="prev, pager, next"
            :total="pagination.total"
            :page-size="pagination.per_page"
            :current-page="pagination.current_page"
            @current-change="handlePageChange"
            class="pagination-orange"
          />
        </div>
      </div>
    </div>

    <!-- Form Modal Component -->
    <Form ref="formRef" @saved="fetchData" />

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import ApiService from "@/core/services/ApiService";
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import Form from './Form.vue';

// --- TYPES ---
interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    photo?: string;
    roles: Role[];
}

// --- STATE ---
const loading = ref(false);
const tableData = ref<User[]>([]);
const search = ref('');
const filterRole = ref('');
const rolesList = ref<Role[]>([]);
const formRef = ref<any>(null);

const pagination = reactive({
    current_page: 1,
    per_page: 10,
    total: 0
});

// Helper: Initials
const getInitials = (name: string) => {
    if (!name) return "?";
    return name.split(' ').map(n => n.charAt(0)).join('').substring(0, 2).toUpperCase();
};

// --- API METHODS ---

const fetchRolesList = async () => {
    try {
        const response = await ApiService.get("master/all-roles");
        rolesList.value = response.data;
    } catch (error) {
        console.error("Gagal load list roles", error);
    }
};

const fetchData = async (page = 1) => {
    loading.value = true;
    try {
        const response = await ApiService.query("master/users", {
            page: page,
            per_page: pagination.per_page,
            search: search.value,
            role: filterRole.value
        });
        
        tableData.value = response.data.data;
        pagination.current_page = response.data.current_page;
        pagination.per_page = response.data.per_page;
        pagination.total = response.data.total;

    } catch (error) {
        console.error("Error fetching users:", error);
    } finally {
        setTimeout(() => {
            loading.value = false;
        }, 300);
    }
};

// --- HANDLERS ---

const handleSearch = debounce(() => {
    fetchData(1);
}, 500);

const handleFilterChange = () => {
    fetchData(1);
};

const resetFilters = () => {
    search.value = '';
    filterRole.value = '';
    fetchData(1);
};

const handlePageChange = (newPage: number) => {
    fetchData(newPage);
};

const handleAdd = () => {
    if (formRef.value) {
        formRef.value.open();
    }
};

const handleEdit = (row: User) => {
    if (formRef.value) {
        formRef.value.open(row);
    }
};

const handleDelete = (id: number) => {
    Swal.fire({
        title: "Yakin ingin menghapus?",
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-danger",
            cancelButton: "btn btn-light"
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await ApiService.delete(`master/users/${id}`);
                Swal.fire({
                    text: "User berhasil dihapus.",
                    icon: "success",
                    buttonsStyling: false,
                    confirmButtonText: "Ok",
                    customClass: { confirmButton: "btn btn-orange" }
                });
                fetchData(pagination.current_page);
            } catch (error: any) {
                Swal.fire({
                    title: "Gagal!",
                    text: error.response?.data?.message || "Gagal menghapus data.",
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok",
                    customClass: { confirmButton: "btn btn-orange" }
                });
            }
        }
    });
};

// Lifecycle
onMounted(() => {
    fetchData();
    fetchRolesList();
});
</script>

<style scoped>
/* ========================
   ORANGE THEME COLORS
   ======================== */
.text-orange { 
  color: #F68B1E !important; 
}

.bg-orange { 
  background-color: #F68B1E !important; 
}

.bg-light-orange { 
  background-color: rgba(246, 139, 30, 0.1) !important; 
}

.border-orange { 
  border-color: #F68B1E !important; 
}

.btn-orange {
  background-color: #F68B1E;
  border-color: #F68B1E;
  color: #fff;
}

.btn-orange:hover {
  background-color: #e57b0e;
  border-color: #e57b0e;
  color: #fff;
}

.btn-active-color-orange:hover,
.btn-active-color-orange:focus,
.btn-active-color-orange:active {
  color: #F68B1E !important;
}

.text-hover-orange:hover {
  color: #F68B1E !important;
}

/* Badge Orange */
.badge-light-orange {
  background-color: rgba(246, 139, 30, 0.1);
  color: #F68B1E;
}

/* ========================
   TABLE ENHANCEMENTS
   ======================== */
.hover-table-row {
  transition: all 0.3s ease;
}

.hover-table-row:hover {
  background-color: rgba(246, 139, 30, 0.03);
}

[data-bs-theme="dark"] .hover-table-row:hover {
  background-color: rgba(246, 139, 30, 0.08);
}

/* Card flush styling */
.card-flush {
  box-shadow: 0px 0px 20px 0px rgba(76, 87, 125, 0.02);
}

.card-flush > .card-header {
  border-bottom: 0;
}

/* Smooth animations */
.animate__animated {
  animation-duration: 0.6s;
}

/* ========================
   EL-SELECT ORANGE THEME
   ======================== */
.metronic-select-orange :deep(.el-input__wrapper) {
  background-color: var(--bs-body-bg);
  border-color: var(--bs-gray-300);
}

.metronic-select-orange :deep(.el-input__wrapper:hover) {
  border-color: #F68B1E;
}

.metronic-select-orange :deep(.el-input__wrapper.is-focus) {
  border-color: #F68B1E;
  box-shadow: 0 0 0 0.25rem rgba(246, 139, 30, 0.15);
}

.metronic-select-orange :deep(.el-select__caret) {
  color: var(--bs-gray-500);
}

[data-bs-theme="dark"] .metronic-select-orange :deep(.el-input__wrapper) {
  background-color: #1e1e2d;
  border-color: #323248;
}

/* ========================
   EL-PAGINATION ORANGE
   ======================== */
.pagination-orange :deep(.el-pager li:hover) {
  color: #F68B1E;
}

.pagination-orange :deep(.el-pager li.is-active) {
  background-color: #F68B1E;
  color: #fff;
}

.pagination-orange :deep(.btn-prev:hover),
.pagination-orange :deep(.btn-next:hover) {
  color: #F68B1E;
}

[data-bs-theme="dark"] .pagination-orange :deep(.el-pagination) {
  --el-pagination-bg-color: #1e1e2d;
  --el-pagination-button-color: #92929f;
}

/* ========================
   DARK MODE OVERRIDES
   ======================== */
[data-bs-theme="dark"] .bg-light-orange {
  background-color: rgba(246, 139, 30, 0.15) !important;
}

[data-bs-theme="dark"] .badge-light-orange {
  background-color: rgba(246, 139, 30, 0.15);
}
</style>