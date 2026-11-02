<script setup>
import { ref, onMounted, computed } from "vue";
import apiClient from "./services/api";
import LoginView from "./components/LoginView.vue";
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

// État d'authentification
const isAuthenticated = ref(false);
const userRole = ref("");
const userGrade = ref("");
const currentUser = ref(null);

// État pour la modale de déconnexion
const showLogoutModal = ref(false);

// Vérification de la session au chargement
onMounted(async () => {
  const token = localStorage.getItem("auth_token");
  if (token) {
    try {
      const response = await apiClient.get("/me");
      if (response.data && response.data.success) {
        isAuthenticated.value = true;
        currentUser.value = response.data.user;
        userRole.value =
          response.data.user.role || localStorage.getItem("user_role");
        userGrade.value =
          response.data.user.grade || localStorage.getItem("user_grade") || "";
      }
    } catch (err) {
      handleLogoutLocal();
    }
  }
});

const handleConnexionReussie = (user) => {
  isAuthenticated.value = true;
  currentUser.value = user;
  userRole.value = user.role;
  userGrade.value = user.grade || "";

  localStorage.setItem("user_role", user.role);
  localStorage.setItem("user_grade", user.grade || "");

  // Redirection par défaut vers le tableau de bord
  currentTab.value = "dashboard";
};

// Ouvre la modale de confirmation de déconnexion
const confirmerDeconnexion = () => {
  showLogoutModal.value = true;
};

// Exécute la déconnexion effective
const handleLogout = async () => {
  try {
    await apiClient.post("/logout");
  } catch (e) {}
  showLogoutModal.value = false;
  handleLogoutLocal();
};

const handleLogoutLocal = () => {
  localStorage.removeItem("auth_token");
  localStorage.removeItem("user_role");
  localStorage.removeItem("user_grade");
  isAuthenticated.value = false;
  currentUser.value = null;
  userRole.value = "";
  userGrade.value = "";
};

// État pour gérer l'onglet actif principal
const currentTab = ref("dashboard");

// État pour gérer les sous-onglets de l'Espace Agent & RH
const agentSubTab = ref("personnel");

// Sécurité visuelle par rôle :
// - RH ('user') : Accès complet
// - Admin ('admin') : Tableau de bord, Agent (Personnel + Décisions uniquement), Validation hiérarchique, Amplications
const canAccessCourrierAndArchives = computed(() => userRole.value === "user");

const changerOnglet = (tab) => {
  if (
    (tab === "courrier" || tab === "archives") &&
    !canAccessCourrierAndArchives.value
  ) {
    alert("Accès restreint : Réservé au profil RH (User).");
    currentTab.value = "dashboard";
    return;
  }
  currentTab.value = tab;
};

// Libellé propre du grade pour l'affichage
const formatGradeLabel = (grade) => {
  switch (grade) {
    case "Chef_Service":
      return "Chef de Service";
    case "DAG":
      return "DAG";
    case "SG":
      return "SG";
    case "Chef_Region":
      return "Chef de Région";
    case "RH":
      return "Ressources Humaines";
    default:
      return grade || "Agent";
  }
};
</script>

<template>
  <!-- Affichage de la page de connexion / inscription si non authentifié -->
  <LoginView
    v-if="!isAuthenticated"
    @connexion-reussie="handleConnexionReussie"
  />

  <!-- Application principale si authentifié -->
  <div
    v-else
    class="min-h-screen bg-slate-100 text-slate-800 flex flex-col font-sans selection:bg-slate-300 selection:text-slate-900 relative"
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
            <!-- Affichage amélioré du compte utilisateur et de son grade -->
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-[11px] text-slate-500 font-medium">
                République de Madagascar — Compte :
              </span>
              <span
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-slate-100 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 shadow-2xs"
              >
                <span class="material-symbols-outlined text-xs text-slate-600"
                  >account_circle</span
                >
                {{ currentUser?.name || "Utilisateur" }}
                <span
                  class="ml-1 px-1.5 py-0.2 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[10px] font-extrabold uppercase tracking-wide"
                >
                  {{ formatGradeLabel(userGrade) }}
                </span>
              </span>
            </div>
          </div>
        </div>

        <!-- Navigation Principale -->
        <div class="flex items-center flex-wrap gap-3">
          <nav class="flex items-center flex-wrap gap-2">
            <button
              @click="changerOnglet('dashboard')"
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
              @click="changerOnglet('agent')"
              :class="[
                'px-3.5 py-2 rounded-xl text-xs font-semibold transition-all duration-200 border flex items-center gap-2 shadow-sm cursor-pointer',
                currentTab === 'agent'
                  ? 'bg-slate-900 text-white border-slate-900 shadow'
                  : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
              ]"
            >
              <svg
                class="w-4 h-4"
                :class="
                  currentTab === 'agent' ? 'text-white' : 'text-slate-500'
                "
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
              @click="changerOnglet('validateur')"
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

            <!-- Visible uniquement pour le rôle 'user' (RH) -->
            <button
              v-if="canAccessCourrierAndArchives"
              @click="changerOnglet('courrier')"
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

            <!-- Visible uniquement pour le rôle 'user' (RH) -->
            <button
              v-if="canAccessCourrierAndArchives"
              @click="changerOnglet('archives')"
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
              @click="changerOnglet('amplications')"
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
                  currentTab === 'amplications'
                    ? 'text-white'
                    : 'text-slate-500'
                "
                >description</span
              >
              Amplications
            </button>
          </nav>

          <!-- Bouton déclenchant la modale de déconnexion -->
          <button
            @click="confirmerDeconnexion"
            class="bg-rose-50 hover:bg-rose-100 text-rose-700 px-3.5 py-2 rounded-xl text-xs font-semibold border border-rose-200 transition shadow-sm flex items-center gap-1.5 cursor-pointer"
            title="Se déconnecter"
          >
            <span class="material-symbols-outlined text-sm">logout</span>
            Déconnexion
          </button>
        </div>
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
            v-if="userRole === 'user'"
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
            v-if="userRole === 'user'"
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
            <PlanningForm v-if="userRole === 'user'" />
            <div
              v-else
              class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs"
            >
              Accès restreint : Le module Planning Annuel est géré par les
              Ressources Humaines.
            </div>
          </div>

          <div v-if="agentSubTab === 'mes-demandes'">
            <MesDemandes v-if="userRole === 'user'" />
            <div
              v-else
              class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs"
            >
              Accès restreint : Le suivi global des demandes est géré par les
              Ressources Humaines.
            </div>
          </div>
        </div>
      </div>

      <div v-if="currentTab === 'validateur'" class="space-y-4 w-full">
        <ValidationVisa />
      </div>

      <div v-if="currentTab === 'courrier'" class="space-y-4 w-full">
        <EspaceCourrier />
      </div>

      <div v-if="currentTab === 'archives'" class="space-y-4 w-full">
        <ArchivageView
          @naviguer-vers-amplications="currentTab = 'amplications'"
        />
      </div>

      <div v-if="currentTab === 'amplications'" class="space-y-4 w-full">
        <AmplicationsView />
      </div>
    </main>

    <!-- MODALE DE CONFIRMATION DE DÉCONNEXION -->
    <div
      v-if="showLogoutModal"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fade-in"
    >
      <div
        class="bg-white text-slate-900 rounded-3xl p-6 sm:p-8 max-w-sm w-full space-y-5 shadow-2xl border border-slate-200 text-center"
      >
        <div
          class="mx-auto w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm"
        >
          <span class="material-symbols-outlined text-2xl">logout</span>
        </div>

        <div class="space-y-1.5">
          <h3 class="text-base font-bold text-slate-900">
            Confirmer la déconnexion
          </h3>
          <p class="text-xs text-slate-500 leading-relaxed">
            Êtes-vous sûr de vouloir fermer votre session active sur la
            plateforme de la Région Haute Matsiatra ?
          </p>
        </div>

        <div class="grid grid-cols-2 gap-2.5 pt-2">
          <button
            @click="showLogoutModal = false"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 px-4 rounded-xl text-xs transition cursor-pointer"
          >
            Annuler
          </button>
          <button
            @click="handleLogout"
            class="bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 px-4 rounded-xl text-xs transition shadow-md cursor-pointer flex items-center justify-center gap-1.5"
          >
            <span>Se déconnecter</span>
          </button>
        </div>
      </div>
    </div>

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
          Plateforme Administrative Intégrée — Rôle :
          {{ userRole.toUpperCase() }} | Grade :
          {{ formatGradeLabel(userGrade) }}
        </p>
      </div>
    </footer>
  </div>
</template>
