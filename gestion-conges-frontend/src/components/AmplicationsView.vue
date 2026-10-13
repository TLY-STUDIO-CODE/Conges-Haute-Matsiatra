<script setup>
import { ref, onMounted } from "vue";
import apiClient from "../services/api";

const amplicationsList = ref([]);
const loading = ref(true);
const error = ref("");

const fetchAmplications = async () => {
  loading.value = true;
  error.value = "";
  try {
    const response = await apiClient.get("/archives/amplications/liste");
    amplicationsList.value = response.data.data || [];
  } catch (err) {
    console.error("Erreur chargement amplications", err);
    error.value = "Impossible de récupérer la liste des amplications générées.";
  } finally {
    loading.value = false;
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
    alert("Erreur lors du téléchargement du fichier.");
  }
};

onMounted(() => {
  fetchAmplications();
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
          <span class="material-symbols-outlined text-indigo-600"
            >description</span
          >
          Registre des Amplications Officielles Générées
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          Consultez, vérifiez et téléchargez l'ensemble des fichiers
          d'ampliation générés pour les agents.
        </p>
      </div>
      <button
        @click="fetchAmplications"
        class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium px-3.5 py-2 rounded-xl border border-slate-200 transition shadow-sm flex items-center gap-1.5 cursor-pointer"
      >
        <span class="material-symbols-outlined text-sm">refresh</span>
        Actualiser
      </button>
    </div>

    <!-- Erreur / Chargement -->
    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm"
    >
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-8 text-slate-500 text-sm">
      Chargement des amplications en cours...
    </div>

    <!-- Tableau des Amplications -->
    <div v-if="!loading" class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm text-slate-700">
        <thead>
          <tr
            class="border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider bg-slate-50/50"
          >
            <th class="py-3 px-4 font-semibold">Agent (IM)</th>
            <th class="py-3 px-4 font-semibold">Référence Ampliation</th>
            <th class="py-3 px-4 font-semibold">Période de Congé</th>
            <th class="py-3 px-4 font-semibold">Date de Clôture / Saisie</th>
            <th class="py-3 px-4 font-semibold text-center">
              Fichiers & Téléchargement
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="item in amplicationsList"
            :key="item.id_traitement"
            class="hover:bg-slate-50/80 transition"
          >
            <td class="py-3.5 px-4 font-semibold text-slate-900">
              <div>{{ item.demande?.im || "N/A" }}</div>
              <div class="text-xs font-normal text-slate-500">
                {{ item.demande?.personnel?.nom }}
                {{ item.demande?.personnel?.prenom }}
              </div>
            </td>
            <td class="py-3.5 px-4">
              <span
                class="px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-semibold"
              >
                {{ item.reference_ampliation || "Non définie" }}
              </span>
            </td>
            <td class="py-3.5 px-4 text-xs text-slate-600">
              Du {{ item.demande?.date_debut }} au {{ item.demande?.date_fin }}
              <div class="font-bold text-slate-900 mt-0.5">
                {{ item.demande?.nombre_jours_demandes }} jour(s)
              </div>
            </td>
            <td class="py-3.5 px-4 text-xs text-slate-600">
              {{ item.date_saisie_decision || item.updated_at.slice(0, 10) }}
            </td>
            <td class="py-3.5 px-4 text-center">
              <div class="flex items-center justify-center gap-2 flex-wrap">
                <template
                  v-for="(path, idx) in getFichiersListe(
                    item.fichier_ampliation,
                  )"
                  :key="'file-amp-' + idx"
                >
                  <button
                    @click="telechargerFichier(path)"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-medium transition shadow-sm inline-flex items-center gap-1.5 cursor-pointer"
                  >
                    <span class="material-symbols-outlined text-sm"
                      >download</span
                    >
                    Télécharger PDF
                  </button>
                </template>
              </div>
            </td>
          </tr>
          <tr v-if="amplicationsList.length === 0">
            <td colspan="5" class="py-10 text-center text-slate-400 italic">
              Aucune ampliation officielle générée pour le moment.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
