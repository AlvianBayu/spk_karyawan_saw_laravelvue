<template>
    <Head title="">
        <meta name="description" content="Masuk ke Sistem Informasi Kedisiplinan Pegawai">
    </Head>

    <div class="login-bg min-h-screen flex items-center justify-center p-6 font-sans">
        
        <div class="login-card w-full max-w-md overflow-hidden shadow-2xl rounded-3xl border border-white/10">
            
            <div class="p-6 md:p-8 bg-slate-900/80">
                
                <div class="text-center mb-10">
                    <h1 class="text-white text-3xl font-bold tracking-tight">SIKAP</h1>
                    <p class="text-gray-400 text-sm mt-2">
                        Sistem Informasi Kedisiplinan Absensi Pegawai
                    </p>
                </div>

                <transition name="fade">
                    <div
                        v-if="form.errors.email || form.errors.password"
                        class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-xl mb-6 text-xs flex items-center gap-3 animate-shake"
                        role="alert"
                        aria-live="assertive"
                    >
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        Identitas masuk tidak valid atau salah.
                    </div>
                </transition>

                <form @submit.prevent="submit" class="space-y-5" novalidate>
                    
                    <div>
                        <label for="email" class="sr-only">Alamat Email</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 group-focus-within:text-blue-400 transition-colors pointer-events-none" aria-hidden="true">
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                            </div>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                v-model="form.email"
                                required
                                :aria-invalid="!!form.errors.email"
                                :class="{'border-red-500/50 ring-2 ring-red-500/10': form.errors.email}"
                                class="w-full pl-12 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none text-white placeholder-gray-500 transition-all text-sm"
                                placeholder="Alamat Email"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="password" class="sr-only">Kata Sandi</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 group-focus-within:text-blue-400 transition-colors pointer-events-none" aria-hidden="true">
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                :aria-invalid="!!form.errors.password"
                                :class="{'border-red-500/50 ring-2 ring-red-500/10': form.errors.password}"
                                class="w-full pl-12 pr-12 py-3 bg-white/5 border border-white/10 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none text-white placeholder-gray-500 transition-all text-sm"
                                placeholder="Kata Sandi"
                            >
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                aria-label="Tampilkan kata sandi"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-blue-400 transition-colors"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3.5 rounded-xl flex items-center justify-center gap-3 transition active:scale-[0.98] shadow-lg shadow-blue-900/30 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="form.processing" class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span>{{ form.processing ? 'Menghubungkan...' : 'Masuk ' }}</span>
                    </button>

                </form>

                <div class="mt-8 pt-8 border-t border-white/5 text-center">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-full text-[10px] font-bold uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                        SAW Method
                    </div>
                    <p class="text-gray-600 text-[10px] mt-8 tracking-[0.2em] uppercase">
                        &copy; 2026 Prototype Team
                    </p>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

// Status tampilan password
const showPassword = ref(false)

// Inisialisasi form Inertia
const form = useForm({
    email: '',
    password: ''
})

// Fungsi submit login
const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<style scoped>
/* BACKGROUND TANPA FOTO - 100% CSS GRADIENT */
.login-bg {
    background-color: #020617; /* Base Dark slate */
    background-image: 
        radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.15), transparent 40%),
        radial-gradient(circle at 20% 80%, rgba(30, 58, 138, 0.15), transparent 40%),
        linear-gradient(to bottom right, #0f172a, #020617);
    background-attachment: fixed;
}

/* Glassmorphism Card effect */
.login-card {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px); /* Blur rendah agar performa tetap terjaga */
}

/* Animasi Error */
.animate-shake {
    animation: shake 0.4s cubic-bezier(.36, .07, .19, .97) both;
}

@keyframes shake {
    10%, 90% { transform: translate3d(-1px, 0, 0); }
    20%, 80% { transform: translate3d(2px, 0, 0); }
    30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
    40%, 60% { transform: translate3d(3px, 0, 0); }
}

/* Transisi Fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Accessibility Utility */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

/* Optimalisasi Motion */
@media (prefers-reduced-motion: reduce) {
    .animate-shake, .animate-spin, .animate-pulse {
        animation: none;
    }
}
</style>