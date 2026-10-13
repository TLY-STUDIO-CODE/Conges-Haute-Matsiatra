<script setup>
import { ref, onMounted, computed } from "vue";
import apiClient from "../services/api";

const decisions = ref([]);
const personnels = ref([]);
const loading = ref(true);
const error = ref("");
const showAddModal = ref(false);
const selectedDecision = ref(null);

const submitting = ref(false);
const formError = ref("");
const formSuccess = ref("");

const form = ref({
  reference_decision: "",
  im: "",
  imputation_budgetaire: "",
  grade_emploi: "",
  bureau_service: "",
});

// Calcul dynamique de l'aperçu de la période et des jours basés sur l'agent sélectionné
const selectedAgentDetails = computed(() => {
  if (!form.value.im) return null;
  const agent = personnels.value.find((p) => p.im === form.value.im);
  if (!agent || !agent.date_contrat) return null;

  const anneeContrat = new Date(agent.date_contrat).getFullYear();
  const anneeCourante = new Date().getFullYear();

  if (anneeContrat >= anneeCourante) {
    return {
      ineligible: true,
      message: `Cet agent a été recruté en ${anneeContrat}. Aucune décision n'est requise avant l'année prochaine.`,
    };
  }

  const anneeDebut = Math.max(anneeContrat + 1, anneeCourante - 2);
  const anneeFin = anneeCourante;
  const nombreAnnees = anneeFin - anneeDebut + 1;
  const nombreJoursTotal = nombreAnnees * 30;

  return {
    ineligible: false,
    anneeDebut,
    anneeFin,
    nombreJoursTotal,
    anneeContrat,
  };
});

// Charger la liste des décisions et des agents
const fetchData = async () => {
  loading.value = true;
  error.value = "";
  try {
    const [resDecisions, resPersonnels] = await Promise.all([
      apiClient.get("/decisions"),
      apiClient.get("/personnels"),
    ]);

    const decData = resDecisions.data;
    decisions.value = Array.isArray(decData) ? decData : decData.data || [];

    const persData = resPersonnels.data;
    personnels.value = Array.isArray(persData) ? persData : persData.data || [];
  } catch (err) {
    console.error("Erreur chargement données décisions", err);
    error.value =
      "Impossible de charger les décisions ou la liste des agents depuis le serveur.";
  } finally {
    loading.value = false;
  }
};

// Remplissage automatique des informations de l'agent sélectionné
const onPersonnelChange = (e) => {
  const selectedIm = e.target.value;
  const agent = personnels.value.find((p) => p.im === selectedIm);
  if (agent) {
    form.value.grade_emploi = agent.grade_emploi || agent.grade || "";
    form.value.bureau_service = agent.bureau_service || agent.direction || "";
  }
};

const enregistrerDecision = async () => {
  submitting.value = true;
  formError.value = "";
  formSuccess.value = "";

  try {
    const res = await apiClient.post("/decisions", form.value);

    formSuccess.value =
      res.data.message ||
      "Décision générée et enregistrée avec succès comme pièce jointe !";

    setTimeout(() => {
      showAddModal.value = false;
      formSuccess.value = "";
      form.value = {
        reference_decision: "",
        im: "",
        imputation_budgetaire: "",
        grade_emploi: "",
        bureau_service: "",
      };
      fetchData();
    }, 1200);
  } catch (err) {
    console.error("Erreur génération décision", err);
    if (err.response?.data?.errors) {
      const firstKey = Object.keys(err.response.data.errors)[0];
      formError.value = err.response.data.errors[firstKey][0];
    } else {
      formError.value =
        err.response?.data?.message ||
        "Erreur lors de la génération de la décision.";
    }
  } finally {
    submitting.value = false;
  }
};

// Fonction pour le téléchargement direct ou l'affichage sécurisé
const getFileUrl = (chemin) => {
  if (!chemin) return "#";
  const cleanPath = chemin.replace(/^storage\//, "");
  return `http://localhost:8000/storage/${cleanPath}`;
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div
    class="bg-white border border-slate-200 rounded-2xl shadow-xl p-6 md:p-8 space-y-6 mt-8 text-slate-800 transition-all duration-300"
  >
    <!-- EN-TÊTE DU BLOC -->
    <div
      class="border-b border-slate-200 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-slate-700">gavel</span>
          Registre des Décisions de Congés - Région Haute Matsiatra
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          Génération automatique des arrêtés et décisions officielles calculés
          selon l'ancienneté du contrat.
        </p>
      </div>
      <div class="flex items-center flex-wrap gap-2 w-full md:w-auto">
        <button
          @click="showAddModal = true"
          class="bg-[#1A222C] hover:bg-black text-white text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 font-medium shadow-sm cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm text-slate-300"
            >description</span
          >
          Générer Décision
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

    <!-- ÉTATS DE CHARGEMENT ET ERREURS -->
    <div
      v-if="loading"
      class="text-center py-12 text-slate-500 text-sm flex flex-col items-center justify-center gap-2"
    >
      <span
        class="material-symbols-outlined animate-spin text-3xl text-slate-700"
        >progress_activity</span
      >
      <span>Chargement des décisions en cours...</span>
    </div>

    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm flex items-center gap-2 shadow-xs"
    >
      <span class="material-symbols-outlined text-rose-500">error</span>
      <span>{{ error }}</span>
    </div>

    <!-- TABLEAU DE LA LISTE DES DÉCISIONS -->
    <div
      v-if="!loading && !error"
      class="overflow-x-auto rounded-xl border border-slate-100 shadow-xs"
    >
      <table class="w-full text-left border-collapse text-sm text-slate-700">
        <thead>
          <tr
            class="bg-slate-50/70 border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider"
          >
            <th class="py-3.5 px-4 font-semibold">N° Référence</th>
            <th class="py-3.5 px-4 font-semibold">Agent (IM & Nom)</th>
            <th class="py-3.5 px-4 font-semibold">Période Couverte</th>
            <th class="py-3.5 px-4 font-semibold">Jours Attribués</th>
            <th class="py-3.5 px-4 font-semibold">Imputation</th>
            <th class="py-3.5 px-4 font-semibold">Pièce Jointe (PDF)</th>
            <th class="py-3.5 px-4 font-semibold text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="d in decisions"
            :key="d.id_decision"
            class="hover:bg-slate-50/80 transition-colors"
          >
            <td class="py-3.5 px-4 font-mono font-semibold text-slate-900">
              {{ d.reference_decision }}
            </td>
            <td class="py-3.5 px-4 text-slate-800 font-medium">
              <span
                class="font-mono text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200 mr-1.5"
              >
                [{{ d.im }}]
              </span>
              {{
                d.personnel
                  ? d.personnel.nom + " " + (d.personnel.prenom || "")
                  : "Agent non lié"
              }}
            </td>
            <td class="py-3.5 px-4 text-slate-600 font-medium">
              {{ d.annee_debut }} - {{ d.annee_fin }}
            </td>
            <td class="py-3.5 px-4 font-semibold text-emerald-600">
              {{ d.nombre_jours }} jour(s)
            </td>
            <td class="py-3.5 px-4 text-slate-600 text-xs font-mono">
              {{ d.imputation_budgetaire || "N/A" }}
            </td>
            <td class="py-3.5 px-4">
              <template v-if="d.fichier_decision">
                <a
                  :href="
                    getFileUrl(
                      Array.isArray(d.fichier_decision)
                        ? d.fichier_decision[0]
                        : d.fichier_decision,
                    )
                  "
                  target="_blank"
                  download
                  class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-lg border border-emerald-200 transition"
                  title="Télécharger la décision PDF"
                >
                  <span class="material-symbols-outlined text-sm"
                    >picture_as_pdf</span
                  >
                  <span>PDF Généré</span>
                </a>
              </template>
              <span v-else class="text-xs text-slate-400 italic"
                >Aucun fichier</span
              >
            </td>
            <td class="py-3.5 px-4 text-center">
              <div class="inline-flex items-center gap-1.5">
                <button
                  @click="selectedDecision = d"
                  class="bg-white hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-lg text-xs border border-slate-300 transition inline-flex items-center gap-1 cursor-pointer shadow-xs font-medium"
                  title="Consulter les détails"
                >
                  <span class="material-symbols-outlined text-xs text-slate-500"
                    >visibility</span
                  >
                  Consulter
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="decisions.length === 0">
            <td colspan="7" class="py-12 text-center text-slate-400">
              <span
                class="material-symbols-outlined text-3xl mb-1 text-slate-300 block"
                >folder_open</span
              >
              Aucune décision officielle enregistrée pour le moment.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODALE DE CONSULTATION D'UNE DÉCISION -->
    <div
      v-if="selectedDecision"
      class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto backdrop-blur-xs"
    >
      <div
        class="bg-white border border-slate-200 text-slate-800 rounded-2xl p-6 max-w-lg w-full space-y-4 shadow-2xl my-8 animate-in fade-in zoom-in duration-200"
      >
        <div
          class="flex justify-between items-center border-b border-slate-200 pb-3"
        >
          <h4
            class="text-base font-bold text-slate-900 flex items-center gap-2"
          >
            <span class="material-symbols-outlined text-slate-700"
              >visibility</span
            >
            Détails de la Décision Officielle
          </h4>
          <button
            @click="selectedDecision = null"
            class="text-slate-400 hover:text-slate-700 text-sm font-bold p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer"
          >
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div
            class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2.5"
          >
            <p>
              <b>N° Référence :</b>
              <span class="font-mono font-semibold text-slate-900">{{
                selectedDecision.reference_decision
              }}</span>
            </p>
            <p>
              <b>Agent (IM) :</b>
              <span class="font-mono text-slate-600"
                >[{{ selectedDecision.im }}]</span
              >
              {{ selectedDecision.personnel?.nom }}
              {{ selectedDecision.personnel?.prenom }}
            </p>
            <p>
              <b>Période Couverte :</b> Années
              {{ selectedDecision.annee_debut }} -
              {{ selectedDecision.annee_fin }}
            </p>
            <p>
              <b>Jours Attribués :</b>
              <span class="text-emerald-600 font-semibold"
                >{{ selectedDecision.nombre_jours }} jours</span
              >
            </p>
            <p>
              <b>Imputation Budgétaire :</b>
              <span class="font-mono">{{
                selectedDecision.imputation_budgetaire || "N/A"
              }}</span>
            </p>
            <p>
              <b>Grade / Emploi :</b>
              {{ selectedDecision.grade_emploi || "N/A" }}
            </p>
            <p>
              <b>Bureau / Service :</b>
              {{ selectedDecision.bureau_service || "Région Haute Matsiatra" }}
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
          <button
            @click="selectedDecision = null"
            class="bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-xl font-medium cursor-pointer transition border border-slate-300 shadow-xs"
          >
            Fermer
          </button>
          <a
            v-if="selectedDecision.fichier_decision"
            :href="
              getFileUrl(
                Array.isArray(selectedDecision.fichier_decision)
                  ? selectedDecision.fichier_decision[0]
                  : selectedDecision.fichier_decision,
              )
            "
            target="_blank"
            download
            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl font-semibold shadow-xs cursor-pointer transition inline-flex items-center gap-1.5 text-xs"
          >
            <span class="material-symbols-outlined text-sm">download</span>
            Télécharger le PDF
          </a>
        </div>
      </div>
    </div>

    <!-- MODALE DE GÉNÉRATION D'UNE DÉCISION -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 overflow-y-auto backdrop-blur-xs"
    >
      <div
        class="bg-white border border-slate-200 text-slate-800 rounded-2xl p-6 max-w-2xl w-full space-y-4 shadow-2xl my-8 animate-in fade-in zoom-in duration-200"
      >
        <div
          class="flex justify-between items-center border-b border-slate-200 pb-3"
        >
          <h4
            class="text-base font-bold text-slate-900 flex items-center gap-2"
          >
            <span class="material-symbols-outlined text-slate-700"
              >description</span
            >
            Générer une Décision Officielle de Congé
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
          class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs flex items-center gap-2 shadow-xs"
        >
          <span class="material-symbols-outlined text-sm">check_circle</span>
          <span>{{ formSuccess }}</span>
        </div>
        <div
          v-if="formError"
          class="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs flex items-center gap-2 shadow-xs"
        >
          <span class="material-symbols-outlined text-sm">error</span>
          <span>{{ formError }}</span>
        </div>

        <form @submit.prevent="enregistrerDecision" class="space-y-4 text-xs">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-medium text-slate-700 mb-1">
                Référence de la Décision <span class="text-rose-500">*</span>
              </label>
              <input
                type="text"
                v-model="form.reference_decision"
                required
                placeholder="Ex: N° 2026-045/REG/HM"
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-700 mb-1">
                Personnel Sélectionné (IM & Nom)
                <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.im"
                @change="onPersonnelChange"
                required
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition cursor-pointer"
              >
                <option value="" disabled>-- Sélectionner un agent --</option>
                <option v-for="p in personnels" :key="p.im" :value="p.im">
                  [{{ p.im }}] {{ p.nom }} {{ p.prenom || "" }} (Contrat:
                  {{ p.date_contrat }})
                </option>
              </select>
            </div>
          </div>

          <!-- APERÇU AUTOMATIQUE CALCULÉ DEPUIS LE CONTRAT DE L'AGENT -->
          <div
            v-if="selectedAgentDetails"
            class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5"
          >
            <template v-if="selectedAgentDetails.ineligible">
              <p class="text-rose-600 font-semibold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">warning</span>
                {{ selectedAgentDetails.message }}
              </p>
            </template>
            <template v-else>
              <p class="font-semibold text-slate-700 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm text-emerald-600"
                  >check_circle</span
                >
                Calcul automatique basé sur l'ancienneté du contrat :
              </p>
              <p class="text-slate-600">
                • Période couverte :
                <b class="text-slate-900"
                  >{{ selectedAgentDetails.anneeDebut }} -
                  {{ selectedAgentDetails.anneeFin }}</b
                >
              </p>
              <p class="text-slate-600">
                • Total cumulé attribué :
                <b class="text-emerald-600"
                  >{{ selectedAgentDetails.nombreJoursTotal }} jours</b
                >
                (30 jours par an)
              </p>
            </template>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Imputation Budgétaire</label
              >
              <input
                type="text"
                v-model="form.imputation_budgetaire"
                placeholder="Ex: 60 601 6011"
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition font-mono"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Grade ou Emploi</label
              >
              <input
                type="text"
                v-model="form.grade_emploi"
                placeholder="Ex: Encadreurs"
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
              />
            </div>
          </div>

          <div>
            <label class="block font-medium text-slate-700 mb-1"
              >Bureau ou Service</label
            >
            <input
              type="text"
              v-model="form.bureau_service"
              placeholder="Ex: Région Haute Matsiatra"
              class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
            />
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
            <button
              type="button"
              @click="showAddModal = false"
              class="bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-xl font-medium cursor-pointer transition border border-slate-300 shadow-xs"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="
                submitting ||
                (selectedAgentDetails && selectedAgentDetails.ineligible)
              "
              class="bg-[#1A222C] hover:bg-black disabled:opacity-50 text-white px-4 py-2 rounded-xl font-semibold shadow-xs cursor-pointer transition flex items-center gap-1.5"
            >
              <span
                v-if="submitting"
                class="material-symbols-outlined animate-spin text-sm"
                >progress_activity</span
              >
              {{
                submitting ? "Génération..." : "Valider et Joindre au Dossier"
              }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
