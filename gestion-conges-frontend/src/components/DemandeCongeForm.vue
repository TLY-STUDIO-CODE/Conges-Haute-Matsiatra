<script setup>
import { ref, onMounted } from "vue";
import apiClient from "../services/api";

const demandes = ref([]);
const loading = ref(true);
const error = ref("");
const selectedDemande = ref(null); // Pour la modale de visualisation
const showAddModal = ref(false); // Pour afficher/masquer la modale d'ajout

// Champs du formulaire d'ajout
const form = ref({
  im: "",
  type_periode: "Congé Annuel",
  date_debut: "",
  date_fin: "",
  nombre_jours_demandes: 1,
  motif: "",
  fichier_demande: null,
  fichier_planning: null,
});

const submitting = ref(false);
const formError = ref("");
const formSuccess = ref("");

const fetchDemandes = async () => {
  loading.value = true;
  error.value = "";
  try {
    const response = await apiClient.get("/demandes-conges");
    demandes.value = response.data.data || response.data;
  } catch (err) {
    console.error("Erreur lors de la récupération des demandes", err);
    error.value =
      "Impossible de charger l'historique des demandes depuis le serveur.";
  } finally {
    loading.value = false;
  }
};

const handleFileUpload = (event, type) => {
  const file = event.target.files[0];
  if (type === "demande") {
    form.value.fichier_demande = file;
  } else if (type === "planning") {
    form.value.fichier_planning = file;
  }
};

const submitDemande = async () => {
  submitting.value = true;
  formError.value = "";
  formSuccess.value = "";

  const formData = new FormData();
  formData.append("im", form.value.im);
  formData.append("type_periode", form.value.type_periode);
  formData.append("date_debut", form.value.date_debut);
  formData.append("date_fin", form.value.date_fin);
  formData.append("nombre_jours_demandes", form.value.nombre_jours_demandes);
  formData.append("motif", form.value.motif || "");

  if (form.value.fichier_demande) {
    formData.append("fichier_demande", form.value.fichier_demande);
  }
  if (form.value.fichier_planning) {
    formData.append("fichier_planning", form.value.fichier_planning);
  }

  try {
    await apiClient.post("/demandes-conges", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
    formSuccess.value = "Demande enregistrée avec succès !";
    setTimeout(() => {
      showAddModal.value = false;
      formSuccess.value = "";
      // Réinitialiser le formulaire
      form.value = {
        im: "",
        type_periode: "Congé Annuel",
        date_debut: "",
        date_fin: "",
        nombre_jours_demandes: 1,
        motif: "",
        fichier_demande: null,
        fichier_planning: null,
      };
      fetchDemandes(); // Actualiser la liste
    }, 1500);
  } catch (err) {
    console.error("Erreur soumission demande", err);
    formError.value =
      err.response?.data?.message ||
      "Erreur lors de l'enregistrement de la demande.";
  } finally {
    submitting.value = false;
  }
};

const exporterCSV = () => {
  let csvContent =
    "data:text/csv;charset=utf-8,ID;Type Periode;Date Debut;Date Fin;Jours;Statut Global\n";
  demandes.value.forEach((d) => {
    csvContent += `${d.id_demande || d.id};${d.type_periode};${d.date_debut};${d.date_fin};${d.nombre_jours_demandes};${d.statut_global || d.statut}\n`;
  });
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", "mes_demandes_conges.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const getBadgeClass = (statut) => {
  const currentStatut = (statut || "").toLowerCase();
  switch (currentStatut) {
    case "valide":
    case "approuve":
      return "bg-emerald-950/80 text-emerald-300 border-emerald-500/40";
    case "refuse":
    case "rejete":
      return "bg-rose-950/80 text-rose-300 border-rose-500/40";
    default:
      return "bg-amber-950/80 text-amber-300 border-amber-500/40";
  }
};

onMounted(() => {
  fetchDemandes();
});
</script>

<template>
  <div
    class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-8 space-y-6 mt-8"
  >
    <!-- En-tête avec les actions -->
    <div
      class="border-b border-slate-800 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h3 class="text-xl font-bold text-white flex items-center gap-2">
          📊 Suivi des Demandes de Congé
        </h3>
        <p class="text-sm text-slate-400 mt-1">
          Consultez l'état d'avancement de vos demandes en cours et passées.
        </p>
      </div>
      <!-- Modification ici : Ajout de flex-wrap pour garantir l'affichage de tous les boutons -->
      <div class="flex items-center flex-wrap gap-2 w-full md:w-auto">
        <button
          @click="showAddModal = true"
          class="bg-blue-600 hover:bg-blue-500 text-white text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 font-semibold shadow-lg shadow-blue-600/30"
        >
          ➕ Ajouter Demande
        </button>
        <button
          @click="exporterCSV"
          class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5"
        >
          📥 Exporter CSV
        </button>
        <button
          @click="fetchDemandes"
          class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-3.5 py-2 rounded-xl border border-slate-700 transition flex items-center gap-1.5"
        >
          🔄 Actualiser
        </button>
      </div>
    </div>

    <!-- Chargement & Erreurs -->
    <div v-if="loading" class="text-center py-6 text-slate-400 text-sm">
      Chargement des données en cours...
    </div>
    <div
      v-if="error"
      class="p-4 bg-rose-950/80 border border-rose-500/50 text-rose-200 rounded-xl text-sm"
    >
      {{ error }}
    </div>

    <!-- Tableau principal de suivi -->
    <div v-if="!loading && !error" class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm text-slate-300">
        <thead>
          <tr
            class="border-b border-slate-800 text-slate-400 uppercase text-xs tracking-wider"
          >
            <th class="py-3 px-4">Période & Fichiers</th>
            <th class="py-3 px-4">Du</th>
            <th class="py-3 px-4">Au</th>
            <th class="py-3 px-4">Jours</th>
            <th class="py-3 px-4">Statut / Workflow</th>
            <th class="py-3 px-4 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr
            v-for="demande in demandes"
            :key="demande.id_demande || demande.id"
            class="hover:bg-slate-800/40 transition"
          >
            <td class="py-3.5 px-4 font-medium text-white">
              <div>{{ demande.type_periode }}</div>
              <div
                class="flex gap-2 mt-1"
                v-if="demande.fichier_demande || demande.fichier_planning"
              >
                <a
                  v-if="demande.fichier_demande"
                  :href="`http://localhost:8000/storage/${demande.fichier_demande}`"
                  target="_blank"
                  class="text-xs text-blue-400 underline hover:text-blue-300"
                  >📄 Demande</a
                >
                <a
                  v-if="demande.fichier_planning"
                  :href="`http://localhost:8000/storage/${demande.fichier_planning}`"
                  target="_blank"
                  class="text-xs text-emerald-400 underline hover:text-emerald-300"
                  >📊 Planning</a
                >
              </div>
            </td>
            <td class="py-3.5 px-4">{{ demande.date_debut }}</td>
            <td class="py-3.5 px-4">{{ demande.date_fin }}</td>
            <td class="py-3.5 px-4 font-semibold text-white">
              {{ demande.nombre_jours_demandes }} j
            </td>
            <td class="py-3.5 px-4">
              <span
                :class="getBadgeClass(demande.statut || demande.statut_global)"
                class="px-3 py-1 rounded-full text-xs font-medium border inline-block"
              >
                {{ demande.statut || demande.statut_global || "En attente" }}
              </span>
            </td>
            <td class="py-3.5 px-4 text-center">
              <button
                @click="selectedDemande = demande"
                class="bg-slate-800 hover:bg-slate-700 text-blue-400 px-3 py-1.5 rounded-lg text-xs border border-slate-700 transition"
              >
                🔍 Voir Détails
              </button>
            </td>
          </tr>
          <tr v-if="demandes.length === 0">
            <td colspan="6" class="py-6 text-center text-slate-500">
              Aucune demande de congé enregistrée pour le moment.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODALE D'AJOUT D'UNE DEMANDE -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50 overflow-y-auto"
    >
      <div
        class="bg-slate-900 border border-slate-800 text-slate-100 rounded-2xl p-6 max-w-lg w-full space-y-4 shadow-2xl my-8"
      >
        <div
          class="flex justify-between items-center border-b border-slate-800 pb-3"
        >
          <h4 class="text-lg font-bold text-white flex items-center gap-2">
            📝 Nouvelle Demande de Congé
          </h4>
          <button
            @click="showAddModal = false"
            class="text-slate-400 hover:text-white text-sm font-bold px-2 py-1"
          >
            ✕
          </button>
        </div>

        <div
          v-if="formSuccess"
          class="p-3 bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 rounded-xl text-xs"
        >
          {{ formSuccess }}
        </div>
        <div
          v-if="formError"
          class="p-3 bg-rose-950/80 border border-rose-500/50 text-rose-200 rounded-xl text-xs"
        >
          {{ formError }}
        </div>

        <form @submit.prevent="submitDemande" class="space-y-4 text-xs">
          <div>
            <label class="block font-medium text-slate-300 mb-1"
              >Immatriculation (IM) Agent</label
            >
            <input
              type="text"
              v-model="form.im"
              required
              placeholder="Ex: 123456"
              class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white"
            />
          </div>

          <div>
            <label class="block font-medium text-slate-300 mb-1"
              >Type de Période / Congé</label
            >
            <select
              v-model="form.type_periode"
              class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white"
            >
              <option value="Congé Annuel">Congé Annuel</option>
              <option value="Permission Exceptionnelle">
                Permission Exceptionnelle
              </option>
              <option value="Congé de Maladie">Congé de Maladie</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-slate-300 mb-1"
                >Date de début</label
              >
              <input
                type="date"
                v-model="form.date_debut"
                required
                class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-300 mb-1"
                >Date de fin</label
              >
              <input
                type="date"
                v-model="form.date_fin"
                required
                class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white"
              />
            </div>
          </div>

          <div>
            <label class="block font-medium text-slate-300 mb-1"
              >Nombre de jours demandés</label
            >
            <input
              type="number"
              v-model="form.nombre_jours_demandes"
              required
              min="1"
              max="60"
              class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white"
            />
          </div>

          <div>
            <label class="block font-medium text-slate-300 mb-1">Motif</label>
            <textarea
              v-model="form.motif"
              rows="2"
              placeholder="Motif de la demande..."
              class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-slate-300 mb-1"
                >Fichier Demande (Scan/PDF)</label
              >
              <input
                type="file"
                @change="(e) => handleFileUpload(e, 'demande')"
                class="w-full text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-blue-400 hover:file:bg-slate-700"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-300 mb-1"
                >Fichier Planning</label
              >
              <input
                type="file"
                @change="(e) => handleFileUpload(e, 'planning')"
                class="w-full text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-emerald-400 hover:file:bg-slate-700"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showAddModal = false"
              class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2 rounded-xl font-medium"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white px-4 py-2 rounded-xl font-semibold shadow"
            >
              {{ submitting ? "Enregistrement..." : "Soumettre la Demande" }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODALE DE VISUALISATION DÉTAILLÉE -->
    <div
      v-if="selectedDemande"
      class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-slate-900 border border-slate-800 text-slate-100 rounded-2xl p-6 max-w-md w-full space-y-4 shadow-2xl"
      >
        <h4 class="text-lg font-bold border-b border-slate-800 pb-2">
          Détails de la Demande
        </h4>
        <div class="text-xs space-y-2">
          <p><strong>IM Agent :</strong> {{ selectedDemande.im }}</p>
          <p><strong>Type :</strong> {{ selectedDemande.type_periode }}</p>
          <p>
            <strong>Période :</strong> Du {{ selectedDemande.date_debut }} au
            {{ selectedDemande.date_fin }}
          </p>
          <p>
            <strong>Nombre de jours :</strong>
            {{ selectedDemande.nombre_jours_demandes }} jours
          </p>
          <p>
            <strong>Motif :</strong>
            {{ selectedDemande.motif || "Aucun motif renseigné" }}
          </p>
          <p>
            <strong>Statut Global :</strong>
            {{ selectedDemande.statut_global || selectedDemande.statut }}
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-4 border-t border-slate-800">
          <button
            @click="selectedDemande = null"
            class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-xs font-medium"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
