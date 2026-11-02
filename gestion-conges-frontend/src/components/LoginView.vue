<script setup>
import { ref } from "vue";
import apiClient from "../services/api";
import logoRegion from "../assets/logo.png";

const isRegistering = ref(false);

// Champs Formulaire Connexion
const email = ref("");
const password = ref("");

// Champs Formulaire Inscription
const regName = ref("");
const regEmail = ref("");
const regPassword = ref("");
const regRole = ref("admin");
const regGrade = ref("Chef_Service");

const error = ref("");
const success = ref("");
const loading = ref(false);

const emit = defineEmits(["connexion-reussie"]);

const handleLogin = async () => {
  loading.value = true;
  error.value = "";
  success.value = "";
  try {
    const response = await apiClient.post("/login", {
      email: email.value,
      password: password.value,
    });

    if (response.data && response.data.success) {
      localStorage.setItem("auth_token", response.data.access_token);
      localStorage.setItem("user_role", response.data.user.role);
      localStorage.setItem("user_grade", response.data.user.grade || "");
      emit("connexion-reussie", response.data.user);
    }
  } catch (err) {
    console.error("Erreur de connexion", err);
    error.value =
      err.response?.data?.message || "Email ou mot de passe invalide.";
  } finally {
    loading.value = false;
  }
};

const handleRegister = async () => {
  loading.value = true;
  error.value = "";
  success.value = "";
  try {
    const response = await apiClient.post("/register", {
      name: regName.value,
      email: regEmail.value,
      password: regPassword.value,
      role: regRole.value,
      grade: regGrade.value,
    });

    if (response.data && response.data.success) {
      success.value = "Compte créé avec succès ! Vous pouvez vous connecter.";
      isRegistering.value = false;
      regName.value = "";
      regEmail.value = "";
      regPassword.value = "";
    }
  } catch (err) {
    console.error("Erreur d'enregistrement", err);
    error.value =
      err.response?.data?.message || "Erreur lors de la création du compte.";
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-slate-100 p-4 font-sans selection:bg-slate-300 selection:text-slate-900"
  >
    <div
      class="bg-white border border-slate-200/80 rounded-3xl shadow-xl p-8 max-w-md w-full space-y-6 transition-all duration-300"
    >
      <!-- En-tête institutionnel avec le logo officiel importé -->
      <div class="text-center space-y-2">
        <div
          class="mx-auto w-16 h-16 rounded-2xl bg-slate-50 border border-slate-200 p-2 shadow-sm flex items-center justify-center overflow-hidden transition-transform hover:scale-105"
        >
          <img
            :src="logoRegion"
            alt="Logo Région Haute Matsiatra"
            class="w-full h-full object-contain"
          />
        </div>
        <div>
          <h2 class="text-xl font-bold tracking-tight text-slate-900">
            {{ isRegistering ? "Création de Compte" : "Portail de Connexion" }}
          </h2>
          <p
            class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mt-0.5"
          >
            Région Haute Matsiatra — Gestion des Congés
          </p>
        </div>
      </div>

      <!-- Messages d'alerte -->
      <div
        v-if="error"
        class="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs flex items-center gap-2 shadow-2xs"
      >
        <span class="material-symbols-outlined text-base">error</span>
        <span>{{ error }}</span>
      </div>

      <div
        v-if="success"
        class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs flex items-center gap-2 shadow-2xs"
      >
        <span class="material-symbols-outlined text-base">check_circle</span>
        <span>{{ success }}</span>
      </div>

      <!-- FORMULAIRE DE CONNEXION -->
      <form
        v-if="!isRegistering"
        @submit.prevent="handleLogin"
        class="space-y-4 text-xs"
      >
        <div class="space-y-1">
          <label class="block font-semibold text-slate-700"
            >Adresse Email</label
          >
          <div class="relative">
            <span
              class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
            >
              <span class="material-symbols-outlined text-base">mail</span>
            </span>
            <input
              type="email"
              v-model="email"
              required
              class="w-full pl-10 pr-3.5 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 bg-slate-50/50 transition"
              placeholder="admin@matsiatra.mg"
            />
          </div>
        </div>

        <div class="space-y-1">
          <label class="block font-semibold text-slate-700">Mot de passe</label>
          <div class="relative">
            <span
              class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
            >
              <span class="material-symbols-outlined text-base">lock</span>
            </span>
            <input
              type="password"
              v-model="password"
              required
              class="w-full pl-10 pr-3.5 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 bg-slate-50/50 transition"
              placeholder="••••••••"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-2xl transition shadow-md hover:shadow-lg cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 mt-2"
        >
          <span
            v-if="loading"
            class="material-symbols-outlined animate-spin text-base"
            >progress_activity</span
          >
          <span>{{ loading ? "Connexion en cours..." : "Se connecter" }}</span>
        </button>

        <div class="text-center pt-3">
          <button
            type="button"
            @click="
              isRegistering = true;
              error = '';
              success = '';
            "
            class="text-blue-600 hover:text-blue-800 font-semibold cursor-pointer transition"
          >
            Créer un nouveau compte utilisateur / valideur →
          </button>
        </div>
      </form>

      <!-- FORMULAIRE DE CRÉATION DE COMPTE -->
      <form v-else @submit.prevent="handleRegister" class="space-y-4 text-xs">
        <div class="space-y-1">
          <label class="block font-semibold text-slate-700">Nom complet</label>
          <div class="relative">
            <span
              class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
            >
              <span class="material-symbols-outlined text-base">badge</span>
            </span>
            <input
              type="text"
              v-model="regName"
              required
              class="w-full pl-10 pr-3.5 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 bg-slate-50/50 transition"
              placeholder="Ex: RAKAKABE"
            />
          </div>
        </div>

        <div class="space-y-1">
          <label class="block font-semibold text-slate-700"
            >Adresse Email</label
          >
          <div class="relative">
            <span
              class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
            >
              <span class="material-symbols-outlined text-base">mail</span>
            </span>
            <input
              type="email"
              v-model="regEmail"
              required
              class="w-full pl-10 pr-3.5 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 bg-slate-50/50 transition"
              placeholder="agent@matsiatra.mg"
            />
          </div>
        </div>

        <div class="space-y-1">
          <label class="block font-semibold text-slate-700">Mot de passe</label>
          <div class="relative">
            <span
              class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400"
            >
              <span class="material-symbols-outlined text-base">lock</span>
            </span>
            <input
              type="password"
              v-model="regPassword"
              required
              class="w-full pl-10 pr-3.5 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-900 bg-slate-50/50 transition"
              placeholder="••••••••"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label class="block font-semibold text-slate-700"
              >Rôle Principal</label
            >
            <select
              v-model="regRole"
              class="w-full px-3 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
              <option value="user">RH (User)</option>
              <option value="admin">Validateur (Admin)</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="block font-semibold text-slate-700"
              >Grade Hiérarchique</label
            >
            <select
              v-model="regGrade"
              class="w-full px-3 py-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-slate-900"
            >
              <option value="Chef_Service">Chef de Service</option>
              <option value="DAG">DAG</option>
              <option value="SG">SG</option>
              <option value="Chef_Region">Chef de Région</option>
              <option value="RH">Ressources Humaines</option>
            </select>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-2xl transition shadow-md hover:shadow-lg cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 mt-2"
        >
          <span
            v-if="loading"
            class="material-symbols-outlined animate-spin text-base"
            >progress_activity</span
          >
          <span>{{
            loading ? "Création en cours..." : "Enregistrer le compte"
          }}</span>
        </button>

        <div class="text-center pt-3">
          <button
            type="button"
            @click="
              isRegistering = false;
              error = '';
              success = '';
            "
            class="text-slate-600 hover:text-slate-900 font-semibold cursor-pointer transition"
          >
            ← Retour à la connexion
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
