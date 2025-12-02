<template>
  <div class="landing-page-wrapper">
    <nav 
      class="navbar navbar-expand-lg fixed-top py-3 transition-all"
      :class="{ 'navbar-scrolled': isScrolled, 'navbar-dark': !isScrolled, 'navbar-light': isScrolled }"
    >
      <div class="container">
        <a class="navbar-brand fw-bolder fs-3 d-flex align-items-center gap-2" href="#">
          <i class="fas fa-hotel text-theme-main"></i>
          <span>McFlyon<span class="text-theme-main">Hotel</span></span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
          <ul class="navbar-nav align-items-center gap-3 fw-medium">

            
           <li class="nav-item ms-lg-4 d-flex gap-3 align-items-center">
  
  <router-link 
    to="/auth/sign-in" 
    class="btn btn-nav-login px-4 py-2 rounded-pill fw-bold transition-all"
  >
    Masuk
  </router-link>

  <router-link 
    to="/auth/sign-up" 
    class="btn btn-nav-register px-4 py-2 rounded-pill fw-bold shadow-lg transition-all"
  >
    Daftar
  </router-link>

</li>
          </ul>
        </div>
      </div>
    </nav>

    <header id="home" class="hero-section d-flex align-items-center text-white text-center">
      <div class="hero-bg"></div>
      <div class="overlay-gradient"></div>
      <div class="container position-relative z-index-1">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill mb-3 animate-up backdrop-blur border border-white border-opacity-25">
              <i class="fas fa-crown text-warning me-1"></i> Hotel Bintang 5 Terbaik
            </div>
            <h1 class="display-2 fw-bolder mb-3 animate-up letter-spacing-tight">
              Golden Sunset <span class="text-gradient">Paradise</span>
            </h1>
            <p class="fs-5 mb-5 text-white-75 animate-up delay-1 px-md-5">
              Nikmati kehangatan layanan premium dan kenyamanan tanpa batas. Pengalaman menginap yang bersinar seperti emas.
            </p>
            <div class="d-flex justify-content-center gap-3 animate-up delay-2">
              <router-link to="/auth/sign-in" class="btn btn-theme btn-lg px-5 py-3 rounded-pill fw-bold shadow-lg hover-lift">
                Booking Sekarang
              </router-link>
              <button class="btn btn-outline-light btn-lg px-4 py-3 rounded-circle" @click="scrollToRooms">
                <i class="fas fa-arrow-down"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="container relative-overlap">
      <div class="row g-4 justify-content-center">
        <div class="col-md-4">
          <div class="feature-card p-4 rounded-4 bg-white shadow-lg text-center h-100 hover-scale">
            <div class="icon-circle bg-theme-light text-theme-main mb-3 mx-auto">
              <i class="fas fa-concierge-bell fs-3"></i>
            </div>
            <h5 class="fw-bold">Layanan 24 Jam</h5>
            <p class="text-muted small mb-0">Staff ramah kami siap membantu kebutuhan Anda dengan senyuman.</p>
          </div>
        </div>
        <div class="col-md-4">
      <div class="feature-card p-4 rounded-4 bg-white shadow-lg text-center h-100 hover-scale">
        <div class="icon-circle bg-primary-light text-primary mb-3 mx-auto">
          <i class="fas fa-tags fs-3"></i>
        </div>
        <h5 class="fw-bold">Harga Terbaik</h5>
        <p class="text-muted small mb-0">
          Booking langsung melalui website kami dan dapatkan harga termurah tanpa biaya tersembunyi.
        </p>
      </div>
    </div>
        <div class="col-md-4">
          <div class="feature-card p-4 rounded-4 bg-white shadow-lg text-center h-100 hover-scale">
            <div class="icon-circle bg-danger-light text-danger-orange mb-3 mx-auto">
              <i class="fas fa-map-marked-alt fs-3"></i>
            </div>
            <h5 class="fw-bold">Strategis</h5>
            <p class="text-muted small mb-0">Dekat dengan pusat bisnis dan keindahan wisata kota.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="rooms" class="py-section bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h6 class="text-theme-main fw-bold text-uppercase ls-2">Akomodasi</h6>
          <h2 class="fw-bold display-6 mb-3">Room Choices</h2>
          <div class="divider mx-auto bg-theme-main"></div>
        </div>

        <div class="row g-4">
          <div class="col-lg-4 col-md-6">
            <div class="room-card card h-100 border-0 rounded-4 overflow-hidden shadow-sm hover-shadow-lg">
              <div class="room-img-wrapper position-relative">
                <img src="https://i.pinimg.com/1200x/b2/2a/ec/b22aec02ecd50042053a51886ad30c24.jpg" class="card-img-top" alt="Standard Room">
                <div class="price-tag bg-dark text-white">
                  <span class="currency text-warning">Rp</span> 100k<span class="per-night">/malam</span>
                </div>
              </div>
              <div class="card-body p-4">
                <h4 class="card-title fw-bold mb-2">Standard Room</h4>
                <div class="d-flex gap-2 mb-3 text-muted small">
                  <span><i class="fas fa-user me-1 text-theme-main"></i> 2 Tamu</span>
                  <span><i class="fas fa-ruler-combined me-1 text-theme-main"></i> 24m²</span>
                </div>
                <p class="card-text text-muted mb-4 line-clamp-2">
                  Kamar nyaman dengan nuansa hangat. Pilihan cerdas untuk traveler.
                </p>
                <router-link to="/auth/sign-in" class="btn btn-outline-theme w-100 rounded-pill fw-medium stretched-link">
                  Lihat Detail
                </router-link>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6">
            <div class="room-card card h-100 border-0 rounded-4 overflow-hidden shadow-sm hover-shadow-lg">
              <div class="room-img-wrapper position-relative">
                <img src="https://vikivalue.com/wp-content/uploads/2025/02/Bedroom-Interior-Design-Company-in-JP-Nagar-1024x528.webp" class="card-img-top" alt="Deluxe Room">
                <div class="price-tag bg-theme">
                  <span class="currency">Rp</span> 500k<span class="per-night">/malam</span>
                </div>
              </div>
              <div class="card-body p-4">
                <h4 class="card-title fw-bold mb-2">Deluxe Room</h4>
                <div class="d-flex gap-2 mb-3 text-muted small">
                  <span><i class="fas fa-user me-1 text-theme-main"></i> 2-3 Tamu</span>
                  <span><i class="fas fa-ruler-combined me-1 text-theme-main"></i> 32m²</span>
                </div>
                <p class="card-text text-muted mb-4 line-clamp-2">
                  Ruang lebih luas dengan balkon pribadi dan pencahayaan alami yang hangat.
                </p>
                <router-link to="/auth/sign-in" class="btn btn-outline-theme w-100 rounded-pill fw-medium stretched-link">
                  Lihat Detail
                </router-link>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6">
            <div class="room-card card h-100 border-0 rounded-4 overflow-hidden shadow-sm hover-shadow-lg">
              <div class="room-img-wrapper position-relative">
                <div class="badge bg-warning text-dark position-absolute top-0 start-0 m-3 z-index-1 fw-bold shadow-sm">Popular Choice</div>
                <img src="https://encrypted-tbn3.gstatic.com/images?q=tbn:ANd9GcSpe3b9M-iAWuq8Epy8wfj_ZitrA8ojqfvYfhKX10c82afvx1Lr" class="card-img-top" alt="Executive Suite">
                <div class="price-tag bg-dark text-warning">
                  <span class="currency">Rp</span> 1jt<span class="per-night">/malam</span>
                </div>
              </div>
              <div class="card-body p-4">
                <h4 class="card-title fw-bold mb-2">Executive Suite</h4>
                <div class="d-flex gap-2 mb-3 text-muted small">
                  <span><i class="fas fa-users me-1 text-theme-main"></i> 4 Tamu</span>
                  <span><i class="fas fa-ruler-combined me-1 text-theme-main"></i> 56m²</span>
                </div>
                <p class="card-text text-muted mb-4 line-clamp-2">
                  Kemewahan puncak dengan ruang tamu terpisah dan bathub jacuzzi pribadi.
                </p>
                <router-link to="/auth/sign-in" class="btn btn-outline-theme w-100 rounded-pill fw-medium stretched-link">
                  Lihat Detail
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="cta-section py-5 text-white position-relative overflow-hidden">
      <div class="cta-bg"></div>
      <div class="container position-relative z-index-1 text-center py-4">
        <h2 class="fw-bold mb-3">Siap untuk Liburan Impian?</h2>
        <p class="mb-4 fs-5 text-white-75">Bergabunglah sekarang dan rasakan kehangatan layanan kami.</p>
        <router-link to="/auth/sign-up" class="btn btn-light text-theme-main btn-lg rounded-pill fw-bold px-5 shadow hover-lift">
          JOIN NOW
        </router-link>
      </div>
    </section>

    <footer class="bg-dark text-white pt-5 pb-3 border-top border-warning border-opacity-25">
      <div class="container">
        <div class="row g-4 mb-5">
          <div class="col-lg-4 col-md-6">
            <a class="navbar-brand fw-bold fs-3 text-white d-block mb-3" href="#">
              <i class="fas fa-hotel text-warning me-2"></i>McFlyon Hotel
            </a>
            <p class="text-secondary mb-4">
              Destinasi terbaik untuk kenyamanan dan kemewahan. Kami menghadirkan pengalaman menginap yang bersinar.
            </p>
            <div class="social-links d-flex gap-3">
              <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
              <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
              <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
            </div>
          </div>
          <div class="col-lg-2 col-md-6 offset-lg-1">
            <h5 class="fw-bold mb-3 text-warning">Perusahaan</h5>
            <ul class="list-unstyled text-secondary">
              <li class="mb-2"><a href="#" class="text-decoration-none text-secondary hover-theme">Tentang Kami</a></li>
              <li class="mb-2"><a href="#" class="text-decoration-none text-secondary hover-theme">Karir</a></li>
              <li class="mb-2"><a href="#" class="text-decoration-none text-secondary hover-theme">Kebijakan Privasi</a></li>
            </ul>
          </div>
          <div class="col-lg-2 col-md-6">
            <h5 class="fw-bold mb-3 text-warning">Layanan</h5>
            <ul class="list-unstyled text-secondary">
              <li class="mb-2"><a href="#" class="text-decoration-none text-secondary hover-theme">Restoran</a></li>
              <li class="mb-2"><a href="#" class="text-decoration-none text-secondary hover-theme">Wellness & Spa</a></li>
              <li class="mb-2"><a href="#" class="text-decoration-none text-secondary hover-theme">Conference</a></li>
            </ul>
          </div>
          <div class="col-lg-3 col-md-6">
            <h5 class="fw-bold mb-3 text-warning">Hubungi Kami</h5>
            <ul class="list-unstyled text-secondary">
              <li class="mb-3 d-flex"><i class="fas fa-map-marker-alt mt-1 me-3 text-warning"></i> Jl. Bungkal Gg. II No. 25B Kec/Kel. Sambikerep Kota Surabaya</li>
              <li class="mb-3 d-flex"><i class="fas fa-phone-alt mt-1 me-3 text-warning"></i> 085174323674</li>
              <li class="d-flex"><i class="fas fa-envelope mt-1 me-3 text-warning"></i> admin@mcflyon.co.id</li>
            </ul>
          </div>
        </div>
        <hr class="border-secondary opacity-25">
        <div class="text-center text-secondary small pt-3">
          &copy; 2026 McFlyon Hotel System. All rights reserved.
        </div>
      </div>
    </footer>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, onUnmounted } from "vue";

export default defineComponent({
  name: "LandingPage",
  setup() {
    const isScrolled = ref(false);

    const handleScroll = () => {
      isScrolled.value = window.scrollY > 50;
    };

    const scrollToRooms = () => {
      const el = document.getElementById("rooms");
      if (el) el.scrollIntoView({ behavior: "smooth" });
    };

    onMounted(() => {
      document.body.classList.remove("page-loading");
      document.body.removeAttribute("data-kt-app-layout");
      document.body.removeAttribute("data-kt-name");
      window.addEventListener("scroll", handleScroll);
    });

    onUnmounted(() => {
      window.removeEventListener("scroll", handleScroll);
    });

    return {
      isScrolled,
      scrollToRooms,
    };
  },
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

.landing-page-wrapper {
  font-family: 'Plus Jakarta Sans', sans-serif;
  overflow-x: hidden;
  color: #2c3e50;
  
  /* --- PALET WARNA ORANGE/KUNING --- */
  --color-primary: #FF6B35; /* Orange Cerah */
  --color-primary-dark: #e85d2a;
  --color-secondary: #FFD700; /* Emas/Kuning */
  --color-secondary-soft: #fff9db;
  --color-accent: #ff9f43;
}

/* --- Utilities Custom Theme --- */
.text-theme-main { color: var(--color-primary) !important; }
.bg-theme { background-color: var(--color-primary) !important; color: white; }
.bg-theme-light { background-color: rgba(255, 107, 53, 0.1) !important; }

.btn-theme {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: white;
  transition: all 0.3s ease;
}
.btn-theme:hover {
  background-color: var(--color-primary-dark);
  border-color: var(--color-primary-dark);
  transform: translateY(-2px);
}

.btn-outline-theme {
  color: var(--color-primary);
  border-color: var(--color-primary);
  background: transparent;
  transition: all 0.3s ease;
}
.btn-outline-theme:hover {
  background-color: var(--color-primary);
  color: white;
}

/* Custom Icons colors */
.bg-gold-light { background-color: rgba(255, 215, 0, 0.15); }
.text-gold-dark { color: #d4af37; }
.bg-danger-light { background-color: rgba(255, 82, 82, 0.1); }
.text-danger-orange { color: #ff5252; }

/* --- Navbar Glassmorphism --- */
.navbar { transition: all 0.4s ease; }
.navbar-scrolled {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
  padding-top: 1rem !important;
  padding-bottom: 1rem !important;
}
.navbar-scrolled .navbar-brand span { color: #2c3e50; }
.navbar-scrolled .nav-link { color: #555 !important; }
.navbar-scrolled .nav-link:hover { color: var(--color-primary) !important; }

/* --- Hero Section --- */
.hero-section {
  position: relative;
  height: 100vh;
  min-height: 700px;
}
.hero-bg {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  /* Ganti gambar dengan nuansa lebih hangat jika perlu */
  background: url('https://images.unsplash.com/photo-1560624052-449f5ddf0c31?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80') center/cover no-repeat; 
  background-attachment: fixed;
}
.overlay-gradient {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  /* Gradasi Hitam ke Orange Gelap */
  background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(68, 30, 0, 0.7) 100%);
}
.text-gradient {
  /* Gradasi Teks Orange ke Kuning */
  background: linear-gradient(to right, #FFD700, #FFA500);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
}
.letter-spacing-tight { letter-spacing: -1px; }

/* --- Cards --- */
.relative-overlap {
  position: relative;
  margin-top: -60px;
  z-index: 10;
}
.feature-card {
  transition: all 0.3s ease;
  border-top: 4px solid transparent;
}
.feature-card:hover {
  transform: translateY(-10px);
  border-color: var(--color-primary); /* Border Orange saat hover */
}
.icon-circle {
  width: 70px; height: 70px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%;
}

/* --- Room Cards --- */
.room-card { transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
.room-card:hover { transform: translateY(-5px); }
.hover-shadow-lg:hover { box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important; }
.room-img-wrapper { height: 240px; overflow: hidden; }
.room-img-wrapper img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform 0.5s ease;
}
.room-card:hover .room-img-wrapper img { transform: scale(1.1); }

.price-tag {
  position: absolute; bottom: 0; right: 0;
  padding: 10px 20px;
  border-top-left-radius: 1rem;
  font-weight: 700;
  font-size: 1.1rem;
}
.per-night { font-size: 0.8rem; font-weight: 400; opacity: 0.8; }
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* --- CTA Section --- */
.cta-section { background-color: var(--color-primary); }
.cta-bg {
  position: absolute; top: 0; left: 0; width: 100%; height: 100%;
  background: url('https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1500&q=80') center/cover;
  opacity: 0.15; mix-blend-mode: multiply;
}

/* --- Footer --- */
.social-btn {
  width: 40px; height: 40px;
  background: rgba(255,255,255,0.1);
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%; color: white;
  transition: all 0.3s;
  text-decoration: none;
}
.social-btn:hover { background: var(--color-primary); transform: translateY(-3px); color: white; }
.hover-white:hover { color: white !important; }
.hover-theme:hover { color: var(--color-primary) !important; }

/* --- CUSTOM NAVBAR BUTTONS --- */

/* 1. TOMBOL DAFTAR (Primary) */
.btn-nav-register {
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  color: white !important;
  border: none;
  box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3); /* Bayangan Orange Halus */
  position: relative;
  overflow: hidden;
  z-index: 1;
}

/* Efek Hover untuk Daftar */
.btn-nav-register:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(255, 107, 53, 0.5);
  background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);
}

/* 2. TOMBOL MASUK (Secondary) */
.btn-nav-login {
  /* Kondisi Awal (Di Hero Section - Gelap) */
  background-color: rgba(255, 255, 255, 0.1); /* Sedikit transparan */
  color: white !important;
  border: 1px solid rgba(255, 255, 255, 0.4);
  backdrop-filter: blur(4px); /* Efek kaca */
}

.btn-nav-login:hover {
  background-color: white;
  color: var(--color-primary) !important;
  border-color: white;
}

/* --- LOGIKA SAAT NAVBAR DI-SCROLL (Background Putih) --- */

/* Saat scroll, tombol Masuk berubah jadi warna tema agar terbaca */
.navbar-scrolled .btn-nav-login {
  background-color: transparent;
  color: var(--color-primary) !important; /* Teks jadi Orange */
  border: 1px solid var(--color-primary);
}

.navbar-scrolled .btn-nav-login:hover {
  background-color: var(--color-primary);
  color: white !important;
}

/* Tombol Daftar tetap Solid tapi shadow menyesuaikan */
.navbar-scrolled .btn-nav-register {
  box-shadow: 0 4px 15px rgba(255, 107, 53, 0.25);
}


/* --- Animations --- */
.py-section { padding-top: 5rem; padding-bottom: 5rem; }
.divider { width: 50px; height: 4px; border-radius: 3px; }
.hover-lift { transition: transform 0.3s; }
.hover-lift:hover { transform: translateY(-3px); }

.animate-up {
  animation: fadeInUp 0.8s ease-out forwards;
  opacity: 0; transform: translateY(30px);
}
.delay-1 { animation-delay: 0.2s; }
.delay-2 { animation-delay: 0.4s; }

@keyframes fadeInUp {
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 991px) {
  .relative-overlap { margin-top: 2rem; }
  .navbar-collapse {
    background: white; padding: 1rem;
    border-radius: 1rem; margin-top: 1rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  }
  .navbar-nav .nav-link { color: #333 !important; }
}
</style>