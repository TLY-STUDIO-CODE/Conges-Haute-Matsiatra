<script setup>
import { ref, onMounted } from "vue";
import apiClient from "../services/api";

const emit = defineEmits(["naviguer-vers-amplications"]);

const dossiersArchives = ref([]);
const loading = ref(true);
const exporting = ref(false);
const error = ref("");
const modalConsultation = ref(null);
const generatingAmpliation = ref(false);

const fetchArchives = async () => {
  loading.value = true;
  error.value = "";
  try {
    const response = await apiClient.get("/archives");
    dossiersArchives.value = response.data.data || [];
  } catch (err) {
    console.error("Erreur chargement archives", err);
    error.value = "Impossible de récupérer les dossiers archivés.";
  } finally {
    loading.value = false;
  }
};

const consulterDossier = (dossier) => {
  modalConsultation.value = dossier;
};

const genererAmpliation = async (dossier) => {
  generatingAmpliation.value = true;
  try {
    const res = await apiClient.post(
      `/archives/${dossier.id_traitement}/ampliation`,
    );
    if (res.data && res.data.success) {
      modalConsultation.value = null;
      // Redirection automatique vers l'onglet des amplications
      emit("naviguer-vers-amplications");
    }
  } catch (err) {
    console.error("Erreur génération ampliation", err);
  } finally {
    generatingAmpliation.value = false;
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

    const blob = new Blob([response.data], { type: "application/pdf" });
    const blobUrl = window.URL.createObjectURL(blob);
    const fileName =
      filePath.split("/").pop() || "ampliation_haute_matsiatra.pdf";

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
    const response = await apiClient.get("/archives/export/csv", {
      responseType: "blob",
    });
    const blob = new Blob([response.data], { type: "text/csv;charset=utf-8;" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `dossiers_archives_${new Date().toISOString().slice(0, 10)}.csv`,
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
  fetchArchives();
});
</script>

<template>
  <div
    class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 space-y-6 mt-8 text-slate-800"
  >
    <div
      class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
    >
      <div>
        <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-emerald-600"
            >inventory_2</span
          >
          Suivi et Archivage des Dossiers Clôturés
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          Consultez l'historique des congés traités, les amplications et les
          soldes de congés mis à jour.
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
          @click="fetchArchives"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium px-3.5 py-2 rounded-xl border border-slate-200 transition shadow-sm flex items-center gap-1.5 cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm">refresh</span>
          Actualiser
        </button>
      </div>
    </div>

    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm"
    >
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-8 text-slate-500 text-sm">
      Chargement des dossiers archivés...
    </div>

    <div v-if="!loading" class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm text-slate-700">
        <thead>
          <tr
            class="border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider bg-slate-50/50"
          >
            <th class="py-3 px-4 font-semibold">Agent (IM)</th>
            <th class="py-3 px-4 font-semibold">Réf. Ampliation</th>
            <th class="py-3 px-4 font-semibold">Période & Durée</th>
            <th class="py-3 px-4 font-semibold">Solde Restant</th>
            <th class="py-3 px-4 font-semibold text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="dossier in dossiersArchives"
            :key="dossier.id_traitement"
            class="hover:bg-slate-50/80 transition"
          >
            <td class="py-3.5 px-4 font-semibold text-slate-900">
              <div>{{ dossier.demande?.im || "N/A" }}</div>
              <div class="text-xs font-normal text-slate-500">
                {{ dossier.demande?.personnel?.nom }}
                {{ dossier.demande?.personnel?.prenom }}
              </div>
            </td>
            <td class="py-3.5 px-4">
              <span
                class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold"
              >
                {{ dossier.reference_ampliation || "Non définie" }}
              </span>
            </td>
            <td class="py-3.5 px-4 text-xs text-slate-600">
              Du {{ dossier.demande?.date_debut }} au
              {{ dossier.demande?.date_fin }}
              <div class="font-bold text-slate-900 mt-0.5">
                {{ dossier.demande?.nombre_jours_demandes }} jour(s)
              </div>
            </td>
            <td class="py-3.5 px-4 font-bold text-blue-600">
              {{ dossier.solde_restant_calcule ?? "N/A" }} jours
            </td>
            <td class="py-3.5 px-4 text-center space-x-2">
              <button
                @click="genererAmpliation(dossier)"
                :disabled="generatingAmpliation"
                class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-xl text-xs font-medium transition shadow-sm inline-flex items-center gap-1 border border-indigo-200 cursor-pointer disabled:opacity-50"
              >
                <span class="material-symbols-outlined text-sm">print</span>
                Générer Ampliation
              </button>
              <button
                @click="consulterDossier(dossier)"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl text-xs font-medium transition shadow-sm inline-flex items-center gap-1 border border-slate-200 cursor-pointer"
              >
                <span class="material-symbols-outlined text-sm"
                  >visibility</span
                >
                Consulter
              </button>
            </td>
          </tr>
          <tr v-if="dossiersArchives.length === 0">
            <td colspan="5" class="py-10 text-center text-slate-400 italic">
              Aucun dossier archivé pour le moment.
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
        class="bg-white text-slate-900 rounded-2xl p-6 sm:p-8 max-w-lg w-full space-y-4 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto"
      >
        <h4
          class="text-sm font-bold border-b border-slate-200 pb-3 text-slate-900 flex items-center gap-2"
        >
          <span class="material-symbols-outlined text-emerald-600"
            >folder_supervised</span
          >
          Dossier Archivé & Clôturé - IM : {{ modalConsultation.demande?.im }}
        </h4>

        <div
          class="text-xs space-y-2.5 bg-slate-50 p-4 rounded-xl border border-slate-200 text-slate-700"
        >
          <p>
            <strong>Nom & Prénom :</strong>
            {{ modalConsultation.demande?.personnel?.nom }}
            {{ modalConsultation.demande?.personnel?.prenom }}
          </p>
          <p>
            <strong>Référence Ampliation :</strong>
            {{ modalConsultation.reference_ampliation }}
          </p>
          <p>
            <strong>Date de Clôture / Saisie :</strong>
            {{ modalConsultation.date_saisie_decision }}
          </p>
          <p>
            <strong>Solde Global Restant :</strong>
            <span class="font-bold text-blue-600">
              {{ modalConsultation.solde_restant_calcule ?? "N/A" }} jours
            </span>
          </p>

          <div class="pt-3 border-t border-slate-200 space-y-2">
            <div class="flex flex-wrap gap-2 pt-1">
              <button
                @click="genererAmpliation(modalConsultation)"
                class="bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-medium px-3.5 py-2 rounded-lg flex items-center gap-1.5 cursor-pointer shadow-xs"
              >
                <span class="material-symbols-outlined text-xs">autorenew</span>
                Générer et afficher l'Ampliation
              </button>
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
