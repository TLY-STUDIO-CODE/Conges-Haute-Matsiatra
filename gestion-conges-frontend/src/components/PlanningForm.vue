<script setup>
import { ref, onMounted, computed } from "vue";
import apiClient from "../services/api";

const plannings = ref([]);
const personnels = ref([]);
const loading = ref(true);
const error = ref("");
const showAddModal = ref(false);
const selectedPlanning = ref(null);

const submitting = ref(false);
const formError = ref("");
const formSuccess = ref("");

const anneesAutoriseesAgent = ref([]);
const verificationMessage = ref("");
const agentEligible = ref(false);

const form = ref({
  im: "",
  annee: "",
  lignes: [
    { mois: "Janvier", nombre_jours_prevus: 0 },
    { mois: "Février", nombre_jours_prevus: 0 },
    { mois: "Mars", nombre_jours_prevus: 0 },
    { mois: "Avril", nombre_jours_prevus: 0 },
    { mois: "Mai", nombre_jours_prevus: 0 },
    { mois: "Juin", nombre_jours_prevus: 0 },
    { mois: "Juillet", nombre_jours_prevus: 0 },
    { mois: "Août", nombre_jours_prevus: 0 },
    { mois: "Septembre", nombre_jours_prevus: 0 },
    { mois: "Octobre", nombre_jours_prevus: 0 },
    { mois: "Novembre", nombre_jours_prevus: 0 },
    { mois: "Décembre", nombre_jours_prevus: 0 },
  ],
});

const moisMapping = {
  Janvier: 0,
  Février: 1,
  Mars: 2,
  Avril: 3,
  Mai: 4,
  Juin: 5,
  Juillet: 6,
  Août: 7,
  Septembre: 8,
  Octobre: 9,
  Novembre: 10,
  Décembre: 11,
};

const isMoisDisabled = (nomMois) => {
  if (!agentEligible.value || !form.value.annee) return true;

  const anneeActuelle = new Date().getFullYear();
  const moisActuel = new Date().getMonth();
  const anneePlanning = Number(form.value.annee);

  if (anneePlanning < anneeActuelle) return true;
  if (anneePlanning > anneeActuelle) return false;

  const indexMois = moisMapping[nomMois];
  return indexMois < moisActuel;
};

const fetchData = async () => {
  loading.value = true;
  error.value = "";
  try {
    const [resPlannings, resPersonnels] = await Promise.all([
      apiClient.get("/plannings"),
      apiClient.get("/personnels"),
    ]);

    plannings.value = Array.isArray(resPlannings.data)
      ? resPlannings.data
      : resPlannings.data.data || [];
    personnels.value = Array.isArray(resPersonnels.data)
      ? resPersonnels.data
      : resPersonnels.data.data || [];
  } catch (err) {
    console.error("Erreur chargement données", err);
    error.value = "Impossible de charger les plannings depuis le serveur.";
  } finally {
    loading.value = false;
  }
};

const onAgentChange = async () => {
  form.value.annee = "";
  anneesAutoriseesAgent.value = [];
  verificationMessage.value = "";
  agentEligible.value = false;

  if (!form.value.im) return;

  try {
    const res = await apiClient.get(
      `/plannings/verifier-decision/${form.value.im}`,
    );
    if (res.data.success) {
      anneesAutoriseesAgent.value = res.data.annees_autorisees;
      agentEligible.value = true;
      verificationMessage.value = `Décision validée en base. Années ouvertes pour le planning : ${res.data.annees_autorisees.join(", ")}`;

      if (anneesAutoriseesAgent.value.length > 0) {
        form.value.annee = anneesAutoriseesAgent.value[0];
      }
    }
  } catch (err) {
    agentEligible.value = false;
    verificationMessage.value =
      err.response?.data?.message ||
      "Aucune décision active trouvée en base pour cet agent.";
  }
};

const totalJoursForm = computed(() => {
  return form.value.lignes.reduce(
    (acc, curr) => acc + (Number(curr.nombre_jours_prevus) || 0),
    0,
  );
});

const soumettrePlanning = async () => {
  if (!agentEligible.value) {
    formError.value =
      "Impossible de soumettre : L'agent n'a pas de décision active valide.";
    return;
  }

  if (totalJoursForm.value > 30) {
    formError.value = `Le total des jours prévus (${totalJoursForm.value}j) dépasse la limite annuelle de 30 jours.`;
    return;
  }

  submitting.value = true;
  formError.value = "";
  formSuccess.value = "";

  try {
    const payload = {
      im: form.value.im,
      annee: Number(form.value.annee),
      lignes: form.value.lignes.map((l) => ({
        mois: l.mois,
        nombre_jours_prevus: Number(l.nombre_jours_prevus) || 0,
      })),
    };

    const res = await apiClient.post("/plannings", payload);
    formSuccess.value = res.data.message || "Planning enregistré avec succès !";

    setTimeout(() => {
      showAddModal.value = false;
      formSuccess.value = "";
      form.value.im = "";
      form.value.annee = "";
      agentEligible.value = false;
      verificationMessage.value = "";
      form.value.lignes.forEach((l) => (l.nombre_jours_prevus = 0));
      fetchData();
    }, 1500);
  } catch (err) {
    console.error("Erreur soumission planning", err);
    formError.value =
      err.response?.data?.message ||
      "Erreur lors de l'enregistrement du planning.";
  } finally {
    submitting.value = false;
  }
};

const exporterCSV = async () => {
  try {
    const response = await apiClient.get("/plannings/export/csv", {
      responseType: "blob",
    });
    const blob = new Blob([response.data], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = window.URL.createObjectURL(blob);
    link.setAttribute(
      "download",
      `registre_plannings_annuels_${new Date().toISOString().slice(0, 10)}.csv`,
    );
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (err) {
    console.error("Erreur export CSV :", err);
  }
};

const getTotalJours = (planning) => {
  if (!planning.lignes) return 0;
  return planning.lignes.reduce(
    (acc, curr) => acc + (Number(curr.nombre_jours_prevus) || 0),
    0,
  );
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div
    class="bg-white border border-slate-200 rounded-2xl shadow-xl p-6 md:p-8 space-y-6 mt-8 text-slate-800"
  >
    <!-- EN-TÊTE DU BLOC -->
    <div
      class="border-b border-slate-200 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-slate-700"
            >calendar_month</span
          >
          Registre & Suivi des Plannings Annuels - Région Haute Matsiatra
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          Gestion des plannings synchronisée avec les décisions, les contrats et
          les périodes réglementaires.
        </p>
      </div>
      <div class="flex items-center flex-wrap gap-2 w-full md:w-auto">
        <button
          @click="showAddModal = true"
          class="bg-[#1A222C] hover:bg-black text-white text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 font-medium shadow-sm cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm text-slate-300"
            >add_circle</span
          >
          Nouveau Planning
        </button>
        <button
          @click="exporterCSV"
          class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 font-medium shadow-sm cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm text-slate-500"
            >download</span
          >
          Exporter (CSV)
        </button>
        <button
          @click="fetchData"
          class="bg-white hover:bg-slate-50 text-slate-700 text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 transition flex items-center gap-1.5 cursor-pointer shadow-sm"
        >
          <span class="material-symbols-outlined text-sm text-slate-500"
            >refresh</span
          >
          Actualiser
        </button>
      </div>
    </div>

    <!-- TABLEAU DE LA LISTE DES PLANNINGS -->
    <div v-if="!loading && !error" class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm text-slate-700">
        <thead>
          <tr
            class="border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider"
          >
            <th class="py-3 px-4">Agent (IM)</th>
            <th class="py-3 px-4">Nom & Prénom</th>
            <th class="py-3 px-4">Année</th>
            <th class="py-3 px-4">Date Soumission</th>
            <th class="py-3 px-4">Total Jours Prévus</th>
            <th class="py-3 px-4 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="planning in plannings"
            :key="planning.id_planning"
            class="hover:bg-slate-50/80 transition"
          >
            <td class="py-3.5 px-4 font-mono font-medium text-slate-900">
              {{ planning.im }}
            </td>
            <td class="py-3.5 px-4 text-slate-800">
              {{
                planning.personnel
                  ? `${planning.personnel.nom} ${planning.personnel.prenom || ""}`
                  : "N/A"
              }}
            </td>
            <td class="py-3.5 px-4 font-medium text-slate-700">
              {{ planning.annee }}
            </td>
            <td class="py-3.5 px-4 text-slate-500">
              {{ planning.date_soumission || "N/A" }}
            </td>
            <td class="py-3.5 px-4 font-semibold text-emerald-600">
              {{ getTotalJours(planning) }} jour(s)
            </td>
            <td class="py-3.5 px-4 text-center">
              <button
                @click="selectedPlanning = planning"
                class="bg-white hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-lg text-xs border border-slate-300 transition inline-flex items-center gap-1 cursor-pointer shadow-sm"
              >
                <span class="material-symbols-outlined text-xs text-slate-500"
                  >visibility</span
                >
                Consulter
              </button>
            </td>
          </tr>
          <tr v-if="plannings.length === 0">
            <td colspan="6" class="py-8 text-center text-slate-400">
              Aucun planning annuel enregistré pour le moment.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODALE DE CONSULTATION DÉTAILLÉE D'UN PLANNING -->
    <div
      v-if="selectedPlanning"
      class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto backdrop-blur-xs"
    >
      <div
        class="bg-white border border-slate-200 text-slate-800 rounded-2xl p-6 max-w-lg w-full space-y-4 shadow-2xl my-8"
      >
        <div
          class="flex justify-between items-center border-b border-slate-200 pb-3"
        >
          <h4 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-700"
              >visibility</span
            >
            Détails du Planning Annuel
          </h4>
          <button
            @click="selectedPlanning = null"
            class="text-slate-400 hover:text-slate-700 text-sm font-bold p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer"
          >
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div
          class="text-xs space-y-2 text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-200"
        >
          <p><strong>Agent (IM) :</strong> {{ selectedPlanning.im }}</p>
          <p>
            <strong>Nom & Prénom :</strong>
            {{
              selectedPlanning.personnel
                ? `${selectedPlanning.personnel.nom} ${selectedPlanning.personnel.prenom || ""}`
                : "N/A"
            }}
          </p>
          <p>
            <strong>Année du Planning :</strong> {{ selectedPlanning.annee }}
          </p>
          <p>
            <strong>Date de soumission :</strong>
            {{ selectedPlanning.date_soumission }}
          </p>
          <p>
            <strong>Total des jours prévus :</strong>
            <span class="font-semibold text-emerald-600"
              >{{ getTotalJours(selectedPlanning) }} jour(s)</span
            >
          </p>
        </div>

        <div>
          <h5 class="font-semibold text-xs text-slate-800 mb-2">
            Répartition mensuelle :
          </h5>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <div
              v-for="ligne in selectedPlanning.lignes"
              :key="ligne.id_ligne_plan"
              class="bg-slate-50 border border-slate-200 p-2.5 rounded-lg flex justify-between items-center text-xs"
            >
              <span class="font-medium text-slate-600">{{ ligne.mois }}</span>
              <span
                class="font-bold text-slate-900 bg-white px-2 py-1 rounded border border-slate-200"
              >
                {{ ligne.nombre_jours_prevus }} j
              </span>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-200">
          <button
            type="button"
            @click="selectedPlanning = null"
            class="bg-[#1A222C] hover:bg-black text-white px-4 py-2 rounded-xl font-semibold text-xs cursor-pointer transition shadow"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- MODALE D'AJOUT DE PLANNING -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto backdrop-blur-xs"
    >
      <div
        class="bg-white border border-slate-200 text-slate-800 rounded-2xl p-6 max-w-2xl w-full space-y-4 shadow-2xl my-8"
      >
        <div
          class="flex justify-between items-center border-b border-slate-200 pb-3"
        >
          <h4 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-700"
              >event_note</span
            >
            Soumission du Planning Annuel Prévisionnel
          </h4>
          <button
            @click="showAddModal = false"
            class="text-slate-400 hover:text-slate-700 text-sm font-bold p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer"
          >
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div
          v-if="formSuccess"
          class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs flex items-center gap-2"
        >
          <span class="material-symbols-outlined text-sm">check_circle</span>
          {{ formSuccess }}
        </div>
        <div
          v-if="formError"
          class="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs flex items-center gap-2"
        >
          <span class="material-symbols-outlined text-sm">error</span>
          {{ formError }}
        </div>

        <form @submit.prevent="soumettrePlanning" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Agent (Immatriculation)</label
              >
              <select
                v-model="form.im"
                @change="onAgentChange"
                required
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition cursor-pointer"
              >
                <option disabled value="">Sélectionnez un agent</option>
                <option v-for="p in personnels" :key="p.im" :value="p.im">
                  [{{ p.im }}] {{ p.nom }} {{ p.prenom || "" }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Année couverte par la décision</label
              >
              <select
                v-model="form.annee"
                :disabled="!agentEligible || anneesAutoriseesAgent.length === 0"
                required
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition disabled:bg-slate-100 disabled:cursor-not-allowed cursor-pointer"
              >
                <option disabled value="">
                  -- Choisir une année autorisée --
                </option>
                <option
                  v-for="annee in anneesAutoriseesAgent"
                  :key="annee"
                  :value="annee"
                >
                  Année {{ annee }} (Valide)
                </option>
              </select>
            </div>
          </div>

          <div
            v-if="verificationMessage"
            class="p-3 rounded-xl border text-xs"
            :class="
              agentEligible
                ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                : 'bg-rose-50 border-rose-200 text-rose-800'
            "
          >
            <p class="font-semibold flex items-center gap-1">
              <span class="material-symbols-outlined text-sm">{{
                agentEligible ? "verified" : "error"
              }}</span>
              {{ verificationMessage }}
            </p>
          </div>

          <div class="flex justify-between items-center pt-2">
            <span class="font-semibold text-slate-700">
              Répartition mensuelle (Total :
              <b
                :class="
                  totalJoursForm > 30 ? 'text-rose-600' : 'text-emerald-600'
                "
                >{{ totalJoursForm }} / 30 jours</b
              >)
            </span>
          </div>

          <!-- GRILLE DES MOIS AVEC BLOCAGE AUTOMATIQUE DES MOIS PASSÉS -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div
              v-for="(ligne, index) in form.lignes"
              :key="index"
              class="border p-3 rounded-xl transition"
              :class="
                isMoisDisabled(ligne.mois)
                  ? 'bg-slate-200/70 border-slate-300 opacity-60'
                  : 'bg-slate-50 border-slate-200'
              "
            >
              <label
                class="block text-xs font-semibold mb-1 flex items-center justify-between"
                :class="
                  isMoisDisabled(ligne.mois)
                    ? 'text-slate-500'
                    : 'text-slate-700'
                "
              >
                <span>{{ ligne.mois }}</span>
                <span
                  v-if="isMoisDisabled(ligne.mois)"
                  class="text-[10px] bg-slate-300 text-slate-700 px-1.5 py-0.5 rounded font-mono"
                  >Clos</span
                >
              </label>
              <input
                type="number"
                min="0"
                max="31"
                v-model="ligne.nombre_jours_prevus"
                :disabled="!agentEligible || isMoisDisabled(ligne.mois)"
                class="w-full bg-white border border-slate-300 rounded-lg p-2 text-slate-800 text-xs focus:ring-1 focus:ring-slate-500 outline-none transition disabled:bg-slate-200 disabled:cursor-not-allowed"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
            <button
              type="button"
              @click="showAddModal = false"
              class="bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-xl font-medium cursor-pointer transition border border-slate-300 shadow-sm"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="
                submitting ||
                !agentEligible ||
                totalJoursForm > 30 ||
                !form.annee
              "
              class="bg-[#1A222C] hover:bg-black disabled:opacity-50 text-white px-4 py-2 rounded-xl font-semibold shadow cursor-pointer transition flex items-center gap-1.5"
            >
              <span
                v-if="submitting"
                class="material-symbols-outlined animate-spin text-sm"
                >progress_activity</span
              >
              {{ submitting ? "Enregistrement..." : "Enregistrer le Planning" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
