<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import apiClient from "../services/api";

const stats = ref({
  nombre_personnels: 0,
  nombre_decisions: 0,
  nombre_personnels_planning: 0,
  nombre_demandes_soumises: 0,
  nombre_demandes_validees: 0,
  nombre_demandes_refusees: 0,
  nombre_demandes_en_attente: 0,
  nombre_dossiers_clotures: 0,
});

const loading = ref(true);
const error = ref("");
const currentDateFormatted = ref("");
let timer = null;

// Fonction pour mettre à jour l'horloge en temps réel (Heure locale Madagascar / UTC+3)
const updateClock = () => {
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    timeZone: "Indian/Antananarivo",
  };
  currentDateFormatted.value = new Intl.DateTimeFormat("fr-FR", options).format(
    new Date(),
  );
};

// Récupération des données statistiques depuis l'API Laravel
const fetchStats = async () => {
  loading.value = true;
  error.value = "";
  try {
    const res = await apiClient.get("/dashboard/stats");
    if (res.data && res.data.success) {
      stats.value = res.data.data;
    }
  } catch (err) {
    console.error("Erreur chargement du tableau de bord", err);
    error.value =
      "Impossible de récupérer les statistiques du tableau de bord.";
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchStats();
  updateClock();
  timer = setInterval(updateClock, 1000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>

<template>
  <div class="space-y-6 text-slate-800">
    <!-- En-tête du Dashboard avec Horloge en Temps Réel -->
    <div
      class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-blue-600">dashboard</span>
          Tableau de Bord — Région Haute Matsiatra
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          Vue d'ensemble synthétique et indicateurs clés de la gestion des
          congés et du personnel.
        </p>
      </div>

      <!-- Bloc Date et Heure Actuelles -->
      <div
        class="bg-slate-50 border border-slate-200 px-4 py-2.5 rounded-xl flex items-center gap-2.5 shadow-xs"
      >
        <span class="material-symbols-outlined text-slate-500 text-lg"
          >schedule</span
        >
        <div>
          <div
            class="text-[10px] uppercase tracking-wider text-slate-400 font-bold"
          >
            Date et heure actuelles
          </div>
          <div class="text-xs font-bold text-slate-800 capitalize">
            {{ currentDateFormatted }}
          </div>
        </div>
        <button
          @click="fetchStats"
          class="ml-2 p-1 text-slate-400 hover:text-slate-700 transition rounded-lg hover:bg-slate-200 cursor-pointer"
          title="Actualiser les données"
        >
          <span class="material-symbols-outlined text-sm">refresh</span>
        </button>
      </div>
    </div>

    <!-- Message d'erreur ou de chargement -->
    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm flex items-center gap-2"
    >
      <span class="material-symbols-outlined">error</span>
      {{ error }}
    </div>

    <div
      v-if="loading"
      class="text-center py-12 text-slate-500 text-sm flex items-center justify-center gap-2"
    >
      <span class="material-symbols-outlined animate-spin text-slate-700"
        >progress_activity</span
      >
      Chargement des indicateurs clés...
    </div>

    <!-- Grille des Statistiques / Cartes -->
    <div
      v-if="!loading"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
    >
      <!-- Carte 1 : Nombre de Personnes -->
      <div
        class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex items-center justify-between"
      >
        <div class="space-y-1">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Total Personnel
          </p>
          <h4 class="text-2xl font-black text-slate-900">
            {{ stats.nombre_personnels }}
          </h4>
          <p class="text-[11px] text-slate-500">Agents enregistrés</p>
        </div>
        <div
          class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center border border-blue-100"
        >
          <span class="material-symbols-outlined text-2xl">group</span>
        </div>
      </div>

      <!-- Carte 2 : Décisions Générées -->
      <div
        class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex items-center justify-between"
      >
        <div class="space-y-1">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Décisions Officielles
          </p>
          <h4 class="text-2xl font-black text-slate-900">
            {{ stats.nombre_decisions }}
          </h4>
          <p class="text-[11px] text-slate-500">Arrêtés générés</p>
        </div>
        <div
          class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center border border-indigo-100"
        >
          <span class="material-symbols-outlined text-2xl">gavel</span>
        </div>
      </div>

      <!-- Carte 3 : Plannings de Congé -->
      <div
        class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex items-center justify-between"
      >
        <div class="space-y-1">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Plannings Établis
          </p>
          <h4 class="text-2xl font-black text-slate-900">
            {{ stats.nombre_personnels_planning }}
          </h4>
          <p class="text-[11px] text-slate-500">Agents avec planning</p>
        </div>
        <div
          class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center border border-amber-100"
        >
          <span class="material-symbols-outlined text-2xl">calendar_month</span>
        </div>
      </div>

      <!-- Carte 4 : Dossiers Clôturés -->
      <div
        class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex items-center justify-between"
      >
        <div class="space-y-1">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Dossiers Clôturés
          </p>
          <h4 class="text-2xl font-black text-emerald-600">
            {{ stats.nombre_dossiers_clotures }}
          </h4>
          <p class="text-[11px] text-slate-500">Traités et archivés</p>
        </div>
        <div
          class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center border border-emerald-100"
        >
          <span class="material-symbols-outlined text-2xl">inventory_2</span>
        </div>
      </div>

      <!-- Carte 5 : Demandes Soumises -->
      <div
        class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex items-center justify-between"
      >
        <div class="space-y-1">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Demandes Soumises
          </p>
          <h4 class="text-2xl font-black text-slate-900">
            {{ stats.nombre_demandes_soumises }}
          </h4>
          <p class="text-[11px] text-slate-500">Total des requêtes</p>
        </div>
        <div
          class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center border border-purple-100"
        >
          <span class="material-symbols-outlined text-2xl">assignment</span>
        </div>
      </div>

      <!-- Carte 6 : Demandes Validées -->
      <div
        class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex items-center justify-between"
      >
        <div class="space-y-1">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Demandes Validées
          </p>
          <h4 class="text-2xl font-black text-blue-600">
            {{ stats.nombre_demandes_validees }}
          </h4>
          <p class="text-[11px] text-slate-500">Approuvées par l'autorité</p>
        </div>
        <div
          class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center border border-blue-100"
        >
          <span class="material-symbols-outlined text-2xl">task_alt</span>
        </div>
      </div>

      <!-- Carte 7 : Demandes Refusées -->
      <div
        class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex items-center justify-between"
      >
        <div class="space-y-1">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Demandes Refusées
          </p>
          <h4 class="text-2xl font-black text-rose-600">
            {{ stats.nombre_demandes_refusees }}
          </h4>
          <p class="text-[11px] text-slate-500">Rejetées par la hiérarchie</p>
        </div>
        <div
          class="w-12 h-12 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center border border-rose-100"
        >
          <span class="material-symbols-outlined text-2xl">cancel</span>
        </div>
      </div>

      <!-- Carte 8 : Demandes en Attente (Placée juste après les refusées) -->
      <div
        class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex items-center justify-between"
      >
        <div class="space-y-1">
          <p
            class="text-xs font-semibold text-slate-400 uppercase tracking-wider"
          >
            Demandes en Attente
          </p>
          <h4 class="text-2xl font-black text-amber-600">
            {{ stats.nombre_demandes_en_attente }}
          </h4>
          <p class="text-[11px] text-slate-500">En cours de validation</p>
        </div>
        <div
          class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center border border-amber-100"
        >
          <span class="material-symbols-outlined text-2xl">hourglass_top</span>
        </div>
      </div>
    </div>
  </div>
</template>
