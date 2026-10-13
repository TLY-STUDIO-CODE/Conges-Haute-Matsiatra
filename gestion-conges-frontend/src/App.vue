<script setup>
import { ref } from "vue";
import DashboardView from "./components/DashboardView.vue";
import PlanningForm from "./components/PlanningForm.vue";
import MesDemandes from "./components/MesDemandes.vue";
import Personnel from "./components/Personnel.vue";
import DecisionView from "./components/DecisionView.vue";
import ValidationVisa from "./components/ValidationVisa.vue";
import EspaceCourrier from "./components/EspaceCourrier.vue";
import ArchivageView from "./components/ArchivageView.vue";
import AmplicationsView from "./components/AmplicationsView.vue";
import logoRegion from "./assets/logo.png";

// État pour gérer l'onglet actif principal : 'dashboard', 'agent', 'validateur', 'courrier', 'archives' ou 'amplications'
const currentTab = ref("dashboard");

// État pour gérer les sous-onglets de l'Espace Agent & RH
const agentSubTab = ref("personnel");
</script>

<template>
  <div
    class="min-h-screen bg-slate-100 text-slate-800 flex flex-col font-sans selection:bg-slate-300 selection:text-slate-900"
  >
    <!-- Barre de navigation supérieure institutionnelle -->
    <header
      class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-30"
    >
      <div
        class="w-full px-6 py-3.5 flex flex-col md:flex-row justify-between items-center gap-4"
      >
        <!-- Bloc Identité / Armoiries Région Haute Matsiatra -->
        <div class="flex items-center space-x-3.5 group">
          <div
            class="bg-slate-50 border border-slate-200 p-1.5 rounded-xl shadow-sm flex items-center justify-center w-11 h-11 overflow-hidden transition-transform group-hover:scale-105"
          >
            <img
              :src="logoRegion"
              alt="Logo Région Haute Matsiatra"
              class="w-full h-full object-contain"
            />
          </div>
          <div>
            <h1
              class="text-xs font-bold tracking-wider text-slate-900 uppercase flex items-center gap-2"
            >
              Région Haute Matsiatra
              <span
                class="inline-block w-1.5 h-1.5 rounded-full bg-blue-600"
              ></span>
            </h1>
            <p class="text-[11px] text-slate-500 font-medium tracking-wide">
              République de Madagascar — Service Public et Administration
              Intégrée
            </p>
          </div>
        </div>

        <!-- Navigation Principale par Rôles -->
        <nav class="flex items-center flex-wrap gap-2">
          <button
            @click="currentTab = 'dashboard'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border flex items-center gap-2 shadow-sm cursor-pointer',
              currentTab === 'dashboard'
                ? 'bg-slate-900 text-white border-slate-900 shadow'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
            ]"
          >
            <span
              class="material-symbols-outlined text-sm"
              :class="
                currentTab === 'dashboard' ? 'text-white' : 'text-slate-500'
              "
              >dashboard</span
            >
            Tableau de Bord
          </button>

          <button
            @click="currentTab = 'agent'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border flex items-center gap-2 shadow-sm cursor-pointer',
              currentTab === 'agent'
                ? 'bg-slate-900 text-white border-slate-900 shadow'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
            ]"
          >
            <svg
              class="w-4 h-4"
              :class="currentTab === 'agent' ? 'text-white' : 'text-slate-500'"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
            Espace Agent & RH
          </button>

          <button
            @click="currentTab = 'validateur'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border flex items-center gap-2 shadow-sm cursor-pointer',
              currentTab === 'validateur'
                ? 'bg-slate-900 text-white border-slate-900 shadow'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
            ]"
          >
            <svg
              class="w-4 h-4"
              :class="
                currentTab === 'validateur' ? 'text-white' : 'text-slate-500'
              "
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
              />
            </svg>
            Validation Hiérarchique
          </button>

          <button
            @click="currentTab = 'courrier'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border flex items-center gap-2 shadow-sm cursor-pointer',
              currentTab === 'courrier'
                ? 'bg-slate-900 text-white border-slate-900 shadow'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
            ]"
          >
            <svg
              class="w-4 h-4"
              :class="
                currentTab === 'courrier' ? 'text-white' : 'text-slate-500'
              "
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
              />
            </svg>
            Service Courrier
          </button>

          <button
            @click="currentTab = 'archives'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border flex items-center gap-2 shadow-sm cursor-pointer',
              currentTab === 'archives'
                ? 'bg-slate-900 text-white border-slate-900 shadow'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
            ]"
          >
            <svg
              class="w-4 h-4"
              :class="
                currentTab === 'archives' ? 'text-white' : 'text-slate-500'
              "
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
              />
            </svg>
            Archives & Historique
          </button>

          <button
            @click="currentTab = 'amplications'"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border flex items-center gap-2 shadow-sm cursor-pointer',
              currentTab === 'amplications'
                ? 'bg-slate-900 text-white border-slate-900 shadow'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
            ]"
          >
            <span
              class="material-symbols-outlined text-sm"
              :class="
                currentTab === 'amplications' ? 'text-white' : 'text-slate-500'
              "
              >description</span
            >
            Amplications
          </button>
        </nav>
      </div>
    </header>

    <!-- Contenu Principal -->
    <main class="flex-grow w-full px-6 py-6">
      <div v-if="currentTab === 'dashboard'" class="space-y-4 w-full">
        <DashboardView />
      </div>

      <div v-if="currentTab === 'agent'" class="space-y-6">
        <div class="border-b border-slate-200 pb-4">
          <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
            Portail de Gestion des Ressources Humaines
          </h2>
          <p class="text-slate-500 text-xs mt-1 leading-relaxed">
            Module intégré de gestion administrative du personnel, des arrêtés
            de congés, des plannings annuels et du suivi des dossiers.
          </p>
        </div>

        <div
          class="flex items-center gap-2.5 border-b border-slate-200 pb-3 overflow-x-auto scrollbar-none"
        >
          <button
            @click="agentSubTab = 'personnel'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 border whitespace-nowrap flex items-center gap-2 shadow-sm cursor-pointer',
              agentSubTab === 'personnel'
                ? 'bg-white text-slate-900 border-slate-300 shadow ring-1 ring-slate-300'
                : 'bg-slate-50/60 text-slate-600 border-slate-200 hover:text-slate-900 hover:bg-slate-100',
            ]"
          >
            <span class="material-symbols-outlined text-sm text-slate-500"
              >badge</span
            >
            Fichier du Personnel
          </button>

          <button
            @click="agentSubTab = 'decisions'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 border whitespace-nowrap flex items-center gap-2 shadow-sm cursor-pointer',
              agentSubTab === 'decisions'
                ? 'bg-white text-slate-900 border-slate-300 shadow ring-1 ring-slate-300'
                : 'bg-slate-50/60 text-slate-600 border-slate-200 hover:text-slate-900 hover:bg-slate-100',
            ]"
          >
            <span class="material-symbols-outlined text-sm text-slate-500"
              >gavel</span
            >
            Décisions de Congés
          </button>

          <button
            @click="agentSubTab = 'planning'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 border whitespace-nowrap flex items-center gap-2 shadow-sm cursor-pointer',
              agentSubTab === 'planning'
                ? 'bg-white text-slate-900 border-slate-300 shadow ring-1 ring-slate-300'
                : 'bg-slate-50/60 text-slate-600 border-slate-200 hover:text-slate-900 hover:bg-slate-100',
            ]"
          >
            <span class="material-symbols-outlined text-sm text-slate-500"
              >calendar_month</span
            >
            Planning Annuel
          </button>

          <button
            @click="agentSubTab = 'mes-demandes'"
            :class="[
              'px-4 py-2.5 rounded-xl text-xs font-semibold transition-all duration-200 border whitespace-nowrap flex items-center gap-2 shadow-sm cursor-pointer',
              agentSubTab === 'mes-demandes'
                ? 'bg-white text-slate-900 border-slate-300 shadow ring-1 ring-slate-300'
                : 'bg-slate-50/60 text-slate-600 border-slate-200 hover:text-slate-900 hover:bg-slate-100',
            ]"
          >
            <span class="material-symbols-outlined text-sm text-slate-500"
              >assignment</span
            >
            Suivi des Demandes
          </button>
        </div>

        <div class="pt-2 w-full">
          <div v-if="agentSubTab === 'personnel'">
            <Personnel />
          </div>

          <div v-if="agentSubTab === 'decisions'">
            <DecisionView />
          </div>

          <div v-if="agentSubTab === 'planning'">
            <PlanningForm />
          </div>

          <div v-if="agentSubTab === 'mes-demandes'">
            <MesDemandes />
          </div>
        </div>
      </div>

      <div v-if="currentTab === 'validateur'" class="space-y-4 w-full">
        <ValidationVisa />
      </div>

      <div v-if="currentTab === 'courrier'" class="space-y-4 w-full">
        <EspaceCourrier />
      </div>

      <!-- ÉCOUTE DE L'ÉVÉNEMENT DE REDIRECTION VERS LES AMPLICATIONS -->
      <div v-if="currentTab === 'archives'" class="space-y-4 w-full">
        <ArchivageView
          @naviguer-vers-amplications="currentTab = 'amplications'"
        />
      </div>

      <div v-if="currentTab === 'amplications'" class="space-y-4 w-full">
        <AmplicationsView />
      </div>
    </main>

    <!-- Pied de page -->
    <footer
      class="bg-white border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-500 mt-auto shadow-sm"
    >
      <div
        class="w-full flex flex-col sm:flex-row justify-between items-center gap-2"
      >
        <p class="font-medium text-slate-600">
          &copy; 2026 Région Haute Matsiatra — Direction de l'Administration et
          de la Gestion (DAG)
        </p>
        <p class="text-[11px] text-slate-400 font-medium">
          Plateforme Administrative Intégrée — Tous droits réservés
        </p>
      </div>
    </footer>
  </div>
</template>
