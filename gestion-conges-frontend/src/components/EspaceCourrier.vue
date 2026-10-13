<script setup>
import { ref, onMounted } from "vue";
import apiClient from "../services/api";

const dossiersCourrier = ref([]);
const loading = ref(true);
const exporting = ref(false);
const processingId = ref(null);
const successMessage = ref("");
const errorMessage = ref("");
const formAmpliation = ref({});
const modalDecision = ref(null);

const modalConsulter = ref(false);
const dossierConsulte = ref(null);
const loadingConsulter = ref(false);

const fetchDossiersCourrier = async () => {
  loading.value = true;
  errorMessage.value = "";
  try {
    const res = await apiClient.get("/courrier/dossiers");
    dossiersCourrier.value = res.data.data || [];

    dossiersCourrier.value.forEach((d) => {
      if (!formAmpliation.value[d.id_traitement]) {
        formAmpliation.value[d.id_traitement] = {
          reference_ampliation: "",
          date_verification_planning: new Date().toISOString().split("T")[0],
        };
      }
    });
  } catch (err) {
    console.error("Erreur chargement des dossiers courrier", err);
    errorMessage.value =
      err.response?.data?.message || "Impossible de récupérer les dossiers.";
  } finally {
    loading.value = false;
  }
};

const consulterDossier = async (id_traitement) => {
  loadingConsulter.value = true;
  modalConsulter.value = true;
  dossierConsulte.value = null;
  try {
    const res = await apiClient.get(`/courrier/dossiers/${id_traitement}`);
    dossierConsulte.value = res.data.data;
  } catch (err) {
    console.error("Erreur lors de la récupération du dossier", err);
    alert("Impossible de charger les détails du dossier.");
    modalConsulter.value = false;
  } finally {
    loadingConsulter.value = false;
  }
};

const traiterDossierCourrier = async (id_traitement) => {
  const formData = formAmpliation.value[id_traitement];
  if (!formData || !formData.reference_ampliation) {
    alert("Veuillez renseigner la référence d'ampliation.");
    return;
  }

  processingId.value = id_traitement;
  successMessage.value = "";
  errorMessage.value = "";

  try {
    const res = await apiClient.post(
      `/courrier/dossiers/${id_traitement}/traiter`,
      formData,
    );
    successMessage.value = res.data.message;
    modalDecision.value = res.data.data;
    await fetchDossiersCourrier();
  } catch (err) {
    console.error("Erreur lors de la clôture", err);
    errorMessage.value =
      err.response?.data?.message || "Erreur lors de la clôture du dossier.";
  } finally {
    processingId.value = null;
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

const exporterCsvCourrier = async () => {
  exporting.value = true;
  try {
    const response = await apiClient.get("/courrier/export/csv", {
      responseType: "blob",
    });
    const blob = new Blob([response.data], { type: "text/csv;charset=utf-8;" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `dossiers_courrier_${new Date().toISOString().slice(0, 10)}.csv`,
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

const imprimerDecision = () => {
  window.print();
};

onMounted(fetchDossiersCourrier);
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
          <span class="material-symbols-outlined text-slate-700">mail</span>
          Espace Service Courrier - Clôture & Ampliation
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          Générez les amplications officielles, mettez à jour les soldes et
          archivez les dossiers.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="exporterCsvCourrier"
          :disabled="exporting"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium px-3.5 py-2 rounded-xl border border-slate-200 transition shadow-sm flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
        >
          <span class="material-symbols-outlined text-sm">download</span>
          {{ exporting ? "Exportation..." : "Exporter (CSV)" }}
        </button>
        <button
          @click="fetchDossiersCourrier"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium px-3.5 py-2 rounded-xl border border-slate-200 transition shadow-sm flex items-center gap-1.5 cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm">refresh</span>
          Actualiser
        </button>
      </div>
    </div>

    <!-- Messages -->
    <div
      v-if="successMessage"
      class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center justify-between"
    >
      <span>{{ successMessage }}</span>
      <button
        @click="successMessage = ''"
        class="text-emerald-600 font-bold cursor-pointer"
      >
        &times;
      </button>
    </div>
    <div
      v-if="errorMessage"
      class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm flex items-center justify-between"
    >
      <span>{{ errorMessage }}</span>
      <button
        @click="errorMessage = ''"
        class="text-rose-600 font-bold cursor-pointer"
      >
        &times;
      </button>
    </div>

    <div v-if="loading" class="text-center py-8 text-slate-500 text-sm">
      Chargement des dossiers en attente...
    </div>

    <!-- Tableau -->
    <div v-else class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm text-slate-700">
        <thead>
          <tr
            class="border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider bg-slate-50/50"
          >
            <th class="py-3 px-4 font-semibold">Agent (IM)</th>
            <th class="py-3 px-4 font-semibold">Période & Documents</th>
            <th class="py-3 px-4 font-semibold">Durée</th>
            <th class="py-3 px-4 font-semibold">Date Vérif.</th>
            <th class="py-3 px-4 font-semibold">Réf. Ampliation</th>
            <th class="py-3 px-4 font-semibold text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <template v-if="dossiersCourrier.length > 0">
            <tr
              v-for="dossier in dossiersCourrier"
              :key="dossier.id_traitement"
              class="hover:bg-slate-50/85 transition"
            >
              <td class="py-3.5 px-4 font-semibold text-slate-900">
                {{ dossier.demande?.im ?? "N/A" }}
                <div class="text-xs font-normal text-slate-500">
                  {{ dossier.demande?.personnel?.nom ?? "" }}
                  {{ dossier.demande?.personnel?.prenom ?? "" }}
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-medium text-slate-900">
                  {{ dossier.demande?.type_periode ?? "N/A" }}
                </div>
                <div class="text-xs text-slate-500">
                  Du {{ dossier.demande?.date_debut }} au
                  {{ dossier.demande?.date_fin }}
                </div>
              </td>
              <td class="py-3.5 px-4 font-bold text-slate-900">
                {{ dossier.demande?.nombre_jours_demandes ?? 0 }} j
              </td>
              <td class="py-3.5 px-4">
                <input
                  type="date"
                  v-if="formAmpliation[dossier.id_traitement]"
                  v-model="
                    formAmpliation[dossier.id_traitement]
                      .date_verification_planning
                  "
                  class="bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:border-blue-500 focus:outline-none shadow-sm"
                />
              </td>
              <td class="py-3.5 px-4">
                <input
                  type="text"
                  v-if="formAmpliation[dossier.id_traitement]"
                  v-model="
                    formAmpliation[dossier.id_traitement].reference_ampliation
                  "
                  placeholder="Ex: N° 123/RHM"
                  class="bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs text-slate-700 w-full focus:border-blue-500 focus:outline-none shadow-sm"
                />
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    @click="consulterDossier(dossier.id_traitement)"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl text-xs font-medium transition shadow-sm inline-flex items-center gap-1 border border-slate-200 cursor-pointer"
                  >
                    <span class="material-symbols-outlined text-sm"
                      >visibility</span
                    >
                    Consulter
                  </button>
                  <button
                    @click="traiterDossierCourrier(dossier.id_traitement)"
                    :disabled="processingId === dossier.id_traitement"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-3 py-1.5 rounded-xl text-xs font-medium transition shadow-sm inline-flex items-center gap-1 cursor-pointer"
                  >
                    <span class="material-symbols-outlined text-sm"
                      >folder_managed</span
                    >
                    {{
                      processingId === dossier.id_traitement
                        ? "Traitement..."
                        : "Clôturer & Amplier"
                    }}
                  </button>
                </div>
              </td>
            </tr>
          </template>
          <tr v-else>
            <td colspan="6" class="py-10 text-center text-slate-400 italic">
              Aucun dossier en attente au service courrier.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODALE DE CONSULTATION AVEC PIÈCES JOINTES -->
    <div
      v-if="modalConsulter"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-white text-slate-900 rounded-2xl p-6 sm:p-8 max-w-xl w-full space-y-4 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto"
      >
        <div class="flex justify-between items-center border-b pb-3">
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-700"
              >description</span
            >
            Détails du Dossier & Pièces Justificatives
          </h4>
          <button
            @click="modalConsulter = false"
            class="text-slate-400 hover:text-slate-700 font-bold cursor-pointer"
          >
            &times;
          </button>
        </div>

        <div
          v-if="loadingConsulter"
          class="text-center py-6 text-slate-500 text-xs"
        >
          Chargement des détails...
        </div>

        <div
          v-else-if="dossierConsulte"
          class="space-y-3 text-xs text-slate-700"
        >
          <div
            class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-2"
          >
            <p>
              <strong>Agent (IM) :</strong> {{ dossierConsulte.demande?.im }} -
              {{ dossierConsulte.demande?.personnel?.nom }}
              {{ dossierConsulte.demande?.personnel?.prenom }}
            </p>
            <p>
              <strong>Type de Période :</strong>
              {{ dossierConsulte.demande?.type_periode }}
            </p>
            <p>
              <strong>Période :</strong> Du
              {{ dossierConsulte.demande?.date_debut }} au
              {{ dossierConsulte.demande?.date_fin }} (<strong
                >{{
                  dossierConsulte.demande?.nombre_jours_demandes
                }}
                jours</strong
              >)
            </p>
            <p>
              <strong>Motif :</strong>
              {{ dossierConsulte.demande?.motif || "Aucun motif" }}
            </p>
          </div>

          <!-- Pièces jointes rattachées -->
          <div class="space-y-2 pt-2">
            <p class="font-semibold text-slate-900">Documents rattachés :</p>
            <div class="flex flex-wrap gap-2">
              <template
                v-for="(path, idx) in getFichiersListe(
                  dossierConsulte.demande?.fichier_decision,
                )"
                :key="'c-dec-' + idx"
              >
                <button
                  @click="telechargerFichier(path)"
                  class="bg-emerald-50 border border-emerald-300 hover:bg-emerald-100 text-emerald-800 text-[11px] font-medium px-2.5 py-1.5 rounded flex items-center gap-1 cursor-pointer"
                >
                  <span class="material-symbols-outlined text-xs">gavel</span>
                  Décision Officielle
                </button>
              </template>
              <template
                v-for="(path, idx) in getFichiersListe(
                  dossierConsulte.demande?.fichier_demande,
                )"
                :key="'c-dem-' + idx"
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
              <template
                v-for="(path, idx) in getFichiersListe(
                  dossierConsulte.demande?.fichier_planning,
                )"
                :key="'c-plan-' + idx"
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

        <div class="flex justify-end pt-3 border-t border-slate-200">
          <button
            @click="modalConsulter = false"
            class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- MODALE DE CONFIRMATION DE CLÔTURE -->
    <div
      v-if="modalDecision"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-white text-slate-900 rounded-2xl p-6 sm:p-8 max-w-md w-full space-y-4 shadow-2xl border border-slate-200"
      >
        <h4
          class="text-sm font-bold border-b border-slate-200 pb-3 text-center uppercase text-slate-900"
        >
          Région Haute Matsiatra<br />
          <span class="text-xs font-normal text-slate-500"
            >Service Courrier - Clôture Effectuée</span
          >
        </h4>
        <div
          class="text-xs space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-200"
        >
          <p class="flex justify-between">
            <span class="text-slate-500">Réf. Ampliation :</span>
            <span class="font-bold text-slate-900">{{
              modalDecision.reference_ampliation
            }}</span>
          </p>
          <p class="flex justify-between">
            <span class="text-slate-500">Date de Clôture :</span>
            <span class="font-bold text-slate-900">{{
              modalDecision.date_saisie_decision
            }}</span>
          </p>
          <div
            class="text-emerald-800 font-medium bg-emerald-50 border border-emerald-200 p-2.5 rounded-lg flex items-center gap-2 mt-2"
          >
            <span class="material-symbols-outlined text-emerald-600 text-sm"
              >check_circle</span
            >
            <span>Dossier clôturé et solde mis à jour avec succès.</span>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
          <button
            @click="imprimerDecision"
            class="bg-white hover:bg-slate-100 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-medium flex items-center gap-1 border border-slate-300 cursor-pointer"
          >
            <span class="material-symbols-outlined text-sm">print</span>
            Imprimer
          </button>
          <button
            @click="modalDecision = null"
            class="bg-slate-800 hover:bg-slate-700 text-white px-4 py-2 rounded-xl text-xs font-medium transition cursor-pointer"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
