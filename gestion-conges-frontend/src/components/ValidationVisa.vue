<script setup>
import { ref, onMounted } from "vue";
import apiClient from "../services/api";

const visasEnAttente = ref([]);
const loading = ref(true);
const exporting = ref(false);
const processingId = ref(null);
const error = ref("");
const successMessage = ref("");
const modalConsultation = ref(null);

const fetchVisas = async () => {
  loading.value = true;
  error.value = "";
  try {
    const response = await apiClient.get("/visas");
    const toutesLesDemandes = response.data.data || response.data;

    // Filtre pour ne garder que les visas en attente de validation
    visasEnAttente.value = toutesLesDemandes.filter((visa) => {
      return visa.decision_visa === "En_Attente";
    });
  } catch (err) {
    console.error("Erreur chargement des visas", err);
    error.value = "Impossible de récupérer les demandes à valider.";
  } finally {
    loading.value = false;
  }
};

const traiterVisa = async (visa, decision) => {
  const idVisa = visa.id_visa || visa.id;
  const decisionFormatee = decision === "valide" ? "Valide" : "Refuse";

  processingId.value = idVisa;
  successMessage.value = "";
  error.value = "";

  try {
    const response = await apiClient.post(`/visas/${idVisa}/traiter`, {
      decision_visa: decisionFormatee,
      commentaire:
        decisionFormatee === "Refuse"
          ? "Refusé par la hiérarchie"
          : "Validé via le portail hiérarchique",
    });

    successMessage.value =
      response.data.message || `Visa traité avec succès (${decisionFormatee}).`;
    await fetchVisas();
  } catch (err) {
    console.error("Erreur lors du traitement du visa", err);
    error.value =
      err.response?.data?.message || "Erreur lors de la validation de l'étape.";
  } finally {
    processingId.value = null;
  }
};

const consulterDemande = (visa) => {
  modalConsultation.value = visa.demande;
};

const formatEtapeLabel = (etape) => {
  switch (etape) {
    case "Chef_Service":
    case "Chef_Hierarchique":
      return "Chef Service";
    case "DAG":
      return "DAG";
    case "SG":
      return "SG";
    case "Chef_Region":
      return "Chef de Région";
    default:
      return etape || "En attente";
  }
};

const getFichiersListe = (fichiers) => {
  if (!fichiers) return [];
  if (Array.isArray(fichiers)) return fichiers;
  try {
    const parsed = JSON.parse(fichiers);
    if (Array.isArray(parsed)) return parsed;
  } catch (e) {}
  return [fichiers];
};

const telechargerFichier = async (filePath) => {
  if (!filePath) return;
  try {
    const cleanPath = filePath.replace(/^storage\//, "");
    const response = await apiClient.get(`/storage/${cleanPath}`, {
      responseType: "blob",
    });

    const blob = new Blob([response.data]);
    const blobUrl = window.URL.createObjectURL(blob);
    const fileName = filePath.split("/").pop() || "document_haute_matsiatra";

    const link = document.createElement("a");
    link.href = blobUrl;
    link.setAttribute("download", fileName);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(blobUrl);
  } catch (err) {
    console.error("Erreur téléchargement blob", err);
  }
};

const exporterCsv = async () => {
  exporting.value = true;
  try {
    const response = await apiClient.get("/visas/export/csv", {
      responseType: "blob",
    });
    const blob = new Blob([response.data], { type: "text/csv;charset=utf-8;" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `visas_validation_${new Date().toISOString().slice(0, 10)}.csv`,
    );
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (err) {
    console.error("Erreur export CSV", err);
  } finally {
    exporting.value = false;
  }
};

onMounted(() => {
  fetchVisas();
});
</script>

<template>
  <div
    class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 space-y-6 mt-8 text-slate-800"
  >
    <!-- En-tête -->
    <div
      class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
    >
      <div>
        <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-slate-700">shield</span>
          Espace de Validation Hiérarchique
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          Validez ou refusez les demandes de congés transmises par les agents.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="exporterCsv"
          :disabled="exporting"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium px-3.5 py-2 rounded-xl border border-slate-200 transition shadow-sm flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
        >
          <span class="material-symbols-outlined text-sm">download</span>
          {{ exporting ? "Exportation..." : "Exporter (CSV)" }}
        </button>
        <button
          @click="fetchVisas"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium px-3.5 py-2 rounded-xl border border-slate-200 transition shadow-sm flex items-center gap-1.5 cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm">refresh</span>
          Actualiser
        </button>
      </div>
    </div>

    <div
      v-if="successMessage"
      class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center justify-between"
    >
      <span>{{ successMessage }}</span>
      <button
        @click="successMessage = ''"
        class="text-emerald-600 hover:text-emerald-800 font-bold cursor-pointer"
      >
        &times;
      </button>
    </div>

    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm flex items-center justify-between"
    >
      <span>{{ error }}</span>
      <button
        @click="error = ''"
        class="text-rose-600 hover:text-rose-800 font-bold cursor-pointer"
      >
        &times;
      </button>
    </div>

    <div v-if="loading" class="text-center py-8 text-slate-500 text-sm">
      Chargement des dossiers en attente...
    </div>

    <!-- Tableau -->
    <div v-if="!loading" class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm text-slate-700">
        <thead>
          <tr
            class="border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider bg-slate-50/50"
          >
            <th class="py-3 px-4 font-semibold">Agent (IM)</th>
            <th class="py-3 px-4 font-semibold">Étape Actuelle</th>
            <th class="py-3 px-4 font-semibold">
              Pièces Justificatives & Décision Officielle
            </th>
            <th class="py-3 px-4 font-semibold">Dates</th>
            <th class="py-3 px-4 font-semibold">Durée</th>
            <th class="py-3 px-4 font-semibold text-center">
              Actions & Décisions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="visa in visasEnAttente"
            :key="visa.id_visa || visa.id"
            class="hover:bg-slate-50/80 transition"
          >
            <td class="py-3.5 px-4 font-semibold text-slate-900">
              <div>{{ visa.demande?.im || "Agent N/A" }}</div>
              <div class="text-xs font-normal text-slate-500">
                {{ visa.demande?.personnel?.nom }}
                {{ visa.demande?.personnel?.prenom }}
              </div>
            </td>
            <td class="py-3.5 px-4">
              <span
                class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold"
              >
                {{ formatEtapeLabel(visa.etape) }}
              </span>
            </td>

            <!-- Colonne Pièces Justificatives (Demande, Planning & Décision) -->
            <td class="py-3.5 px-4">
              <div class="flex items-center gap-2 flex-wrap">
                <!-- Fichiers Demande -->
                <template
                  v-for="(path, idx) in getFichiersListe(
                    visa.demande?.fichier_demande,
                  )"
                  :key="'dem-' + idx"
                >
                  <div
                    @click="telechargerFichier(path)"
                    class="w-10 h-12 bg-white border border-slate-300 rounded shadow-xs hover:border-slate-500 transition cursor-pointer flex flex-col items-center justify-center p-0.5"
                    title="Télécharger la demande"
                  >
                    <span
                      class="material-symbols-outlined text-slate-600 text-base"
                      >description</span
                    >
                    <span class="text-[7px] font-bold text-slate-700">DOC</span>
                  </div>
                </template>

                <!-- Fichiers Planning -->
                <template
                  v-for="(path, idx) in getFichiersListe(
                    visa.demande?.fichier_planning,
                  )"
                  :key="'plan-' + idx"
                >
                  <div
                    @click="telechargerFichier(path)"
                    class="w-10 h-12 bg-white border border-slate-300 rounded shadow-xs hover:border-slate-500 transition cursor-pointer flex flex-col items-center justify-center p-0.5"
                    title="Télécharger le planning"
                  >
                    <span
                      class="material-symbols-outlined text-slate-600 text-base"
                      >calendar_month</span
                    >
                    <span class="text-[7px] font-bold text-slate-700"
                      >PLAN</span
                    >
                  </div>
                </template>

                <!-- Décision Officielle rattachée -->
                <template
                  v-for="(path, idx) in getFichiersListe(
                    visa.demande?.fichier_decision,
                  )"
                  :key="'dec-' + idx"
                >
                  <div
                    @click="telechargerFichier(path)"
                    class="w-10 h-12 bg-emerald-50 border border-emerald-300 rounded shadow-xs hover:border-emerald-600 transition cursor-pointer flex flex-col items-center justify-center p-0.5"
                    title="Télécharger la décision officielle"
                  >
                    <span
                      class="material-symbols-outlined text-emerald-700 text-base"
                      >gavel</span
                    >
                    <span class="text-[7px] font-bold text-emerald-800"
                      >DEC</span
                    >
                  </div>
                </template>
              </div>
            </td>

            <td class="py-3.5 px-4 text-xs text-slate-600">
              Du {{ visa.demande?.date_debut }}<br />au
              {{ visa.demande?.date_fin }}
            </td>
            <td class="py-3.5 px-4 font-bold text-slate-900">
              {{ visa.demande?.nombre_jours_demandes }} jour(s)
            </td>
            <td class="py-3.5 px-4 text-center">
              <div class="flex items-center justify-center gap-1.5 flex-wrap">
                <button
                  @click="consulterDemande(visa)"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl text-xs font-medium transition shadow-sm inline-flex items-center gap-1 border border-slate-200 cursor-pointer"
                >
                  <span class="material-symbols-outlined text-sm"
                    >visibility</span
                  >
                  Consulter
                </button>
                <button
                  @click="traiterVisa(visa, 'valide')"
                  :disabled="processingId === (visa.id_visa || visa.id)"
                  class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-3 py-1.5 rounded-xl text-xs font-medium transition shadow-sm inline-flex items-center gap-1 cursor-pointer"
                >
                  <span class="material-symbols-outlined text-sm"
                    >check_circle</span
                  >
                  Valider
                </button>
                <button
                  @click="traiterVisa(visa, 'refuse')"
                  :disabled="processingId === (visa.id_visa || visa.id)"
                  class="bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white px-3 py-1.5 rounded-xl text-xs font-medium transition shadow-sm inline-flex items-center gap-1 cursor-pointer"
                >
                  <span class="material-symbols-outlined text-sm">cancel</span>
                  Refuser
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="visasEnAttente.length === 0">
            <td colspan="6" class="py-10 text-center text-slate-400 italic">
              Aucun dossier en attente de validation hiérarchique.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODALE DE CONSULTATION DÉTAILLÉE -->
    <div
      v-if="modalConsultation"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-white text-slate-900 rounded-2xl p-6 sm:p-8 max-w-lg w-full space-y-4 shadow-2xl border border-slate-200"
      >
        <h4
          class="text-sm font-bold border-b border-slate-200 pb-3 text-slate-900 flex items-center justify-between"
        >
          <span class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-slate-700">info</span>
            Détails de la Demande - Agent IM : {{ modalConsultation.im }}
          </span>
        </h4>

        <div
          class="text-xs space-y-2.5 bg-slate-50 p-4 rounded-xl border border-slate-200 text-slate-700"
        >
          <p>
            <strong>Nom & Prénom :</strong>
            {{ modalConsultation.personnel?.nom }}
            {{ modalConsultation.personnel?.prenom }}
          </p>
          <p>
            <strong>Type de Période :</strong>
            {{ modalConsultation.type_periode }}
          </p>
          <p>
            <strong>Période :</strong> Du {{ modalConsultation.date_debut }} au
            {{ modalConsultation.date_fin }} (<strong
              >{{ modalConsultation.nombre_jours_demandes }} jours</strong
            >)
          </p>
          <p>
            <strong>Motif :</strong>
            {{ modalConsultation.motif || "Aucun motif renseigné" }}
          </p>
          <p>
            <strong>Statut Global :</strong>
            {{ modalConsultation.statut_global }}
          </p>

          <!-- Section Pièces Justificatives & Décision -->
          <div class="pt-3 border-t border-slate-200 space-y-2">
            <p class="font-semibold text-slate-900">Documents rattachés :</p>
            <div class="flex flex-wrap gap-2 pt-1">
              <!-- Décision -->
              <template
                v-for="(path, idx) in getFichiersListe(
                  modalConsultation.fichier_decision,
                )"
                :key="'m-dec-' + idx"
              >
                <button
                  @click="telechargerFichier(path)"
                  class="bg-emerald-50 border border-emerald-300 hover:bg-emerald-100 text-emerald-800 text-[11px] font-medium px-2.5 py-1.5 rounded flex items-center gap-1 cursor-pointer"
                >
                  <span class="material-symbols-outlined text-xs">gavel</span>
                  Décision Officielle
                </button>
              </template>
              <!-- Demande -->
              <template
                v-for="(path, idx) in getFichiersListe(
                  modalConsultation.fichier_demande,
                )"
                :key="'m-dem-' + idx"
              >
                <button
                  @click="telechargerFichier(path)"
                  class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-[11px] font-medium px-2.5 py-1.5 rounded flex items-center gap-1 cursor-pointer"
                >
                  <span class="material-symbols-outlined text-xs"
                    >description</span
                  >
                  Fichier Demande
                </button>
              </template>
              <!-- Planning -->
              <template
                v-for="(path, idx) in getFichiersListe(
                  modalConsultation.fichier_planning,
                )"
                :key="'m-plan-' + idx"
              >
                <button
                  @click="telechargerFichier(path)"
                  class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-[11px] font-medium px-2.5 py-1.5 rounded flex items-center gap-1 cursor-pointer"
                >
                  <span class="material-symbols-outlined text-xs"
                    >calendar_month</span
                  >
                  Fichier Planning
                </button>
              </template>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button
            @click="modalConsultation = null"
            class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-xl text-xs font-medium transition shadow-sm cursor-pointer"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
