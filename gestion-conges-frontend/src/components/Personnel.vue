<script setup>
import { ref, onMounted } from "vue";
import apiClient from "../services/api";

const personnels = ref([]);
const loading = ref(true);
const error = ref("");
const showAddModal = ref(false);
const selectedPersonnel = ref(null);

const submitting = ref(false);
const formError = ref("");
const formSuccess = ref("");

const form = ref({
  im: "",
  nom: "",
  prenom: "",
  date_naissance: "",
  categorie: "",
  grade: "",
  date_contrat: "",
});

const fetchPersonnels = async () => {
  loading.value = true;
  error.value = "";
  try {
    const res = await apiClient.get("/personnels");
    const resData = res.data;
    personnels.value = Array.isArray(resData) ? resData : resData.data || [];
  } catch (err) {
    console.error("Erreur chargement personnels", err);
    error.value =
      "Impossible de charger la liste des agents depuis le serveur.";
  } finally {
    loading.value = false;
  }
};

const exporterCsv = async () => {
  try {
    const response = await apiClient.get("/personnels/export/csv", {
      responseType: "blob",
    });
    const blob = new Blob([response.data], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = window.URL.createObjectURL(blob);
    link.setAttribute(
      "download",
      `liste_personnel_${new Date().toISOString().slice(0, 10)}.csv`,
    );
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(link.href);
  } catch (err) {
    console.error("Erreur lors de l'export CSV", err);
  }
};

const enregistrerPersonnel = async () => {
  submitting.value = true;
  formError.value = "";
  formSuccess.value = "";

  try {
    const res = await apiClient.post("/personnels", form.value);
    formSuccess.value = res.data.message || "Agent enregistré avec succès !";

    setTimeout(() => {
      showAddModal.value = false;
      formSuccess.value = "";
      form.value = {
        im: "",
        nom: "",
        prenom: "",
        date_naissance: "",
        categorie: "",
        grade: "",
        date_contrat: "",
      };
      fetchPersonnels();
    }, 1500);
  } catch (err) {
    console.error("Erreur enregistrement agent", err);
    if (err.response?.data?.errors) {
      const firstKey = Object.keys(err.response.data.errors)[0];
      formError.value = err.response.data.errors[firstKey][0];
    } else {
      formError.value =
        err.response?.data?.message ||
        "Erreur lors de l'enregistrement de l'agent.";
    }
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchPersonnels();
});
</script>

<template>
  <div
    class="bg-white border border-slate-200 rounded-2xl shadow-xl p-6 md:p-8 space-y-6 mt-8 text-slate-800"
  >
    <!-- EN-TÊTE -->
    <div
      class="border-b border-slate-200 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-slate-700">badge</span>
          Registre & Gestion du Personnel - Région Haute Matsiatra
        </h3>
        <p class="text-sm text-slate-500 mt-1">
          Gestion automatisée des agents et calcul dynamique des soldes de
          congés.
        </p>
      </div>
      <div class="flex items-center flex-wrap gap-2 w-full md:w-auto">
        <button
          @click="showAddModal = true"
          class="bg-[#1A222C] hover:bg-black text-white text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-1.5 font-medium shadow-sm cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm text-slate-300"
            >person_add</span
          >
          Nouvel Agent
        </button>
        <button
          @click="exporterCsv"
          class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 font-medium shadow-sm cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm text-slate-500"
            >download</span
          >
          Exporter (CSV)
        </button>
        <button
          @click="fetchPersonnels"
          class="bg-white hover:bg-slate-50 text-slate-700 text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 transition flex items-center gap-1.5 cursor-pointer shadow-sm"
        >
          <span class="material-symbols-outlined text-sm text-slate-500"
            >refresh</span
          >
          Actualiser
        </button>
      </div>
    </div>

    <!-- ÉTATS DE CHARGEMENT -->
    <div
      v-if="loading"
      class="text-center py-8 text-slate-500 text-sm flex items-center justify-center gap-2"
    >
      <span class="material-symbols-outlined animate-spin text-slate-700"
        >progress_activity</span
      >
      Chargement des agents en cours...
    </div>
    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-sm flex items-center gap-2"
    >
      <span class="material-symbols-outlined">error</span>
      {{ error }}
    </div>

    <!-- TABLEAU -->
    <div v-if="!loading && !error" class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-sm text-slate-700">
        <thead>
          <tr
            class="border-b border-slate-200 text-slate-500 uppercase text-xs tracking-wider"
          >
            <th class="py-3 px-4">IM</th>
            <th class="py-3 px-4">Nom & Prénom</th>
            <th class="py-3 px-4">Date Contrat</th>
            <th class="py-3 px-4">Total Droits Acquis</th>
            <th class="py-3 px-4">Solde Restant Actuel</th>
            <th class="py-3 px-4 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="p in personnels"
            :key="p.im"
            class="hover:bg-slate-50/80 transition"
          >
            <td class="py-3.5 px-4 font-mono font-medium text-slate-900">
              {{ p.im }}
            </td>
            <td class="py-3.5 px-4 text-slate-800 font-medium">
              {{ p.nom }} {{ p.prenom || "" }}
            </td>
            <td class="py-3.5 px-4 text-slate-600">
              {{ p.date_contrat || "N/A" }}
            </td>
            <td class="py-3.5 px-4 text-slate-700 font-semibold">
              {{ p.total_droits_acquis ?? "30" }} jours
            </td>
            <td class="py-3.5 px-4 font-bold text-emerald-600">
              {{ p.solde_conge_actuel }} jour(s)
            </td>
            <td class="py-3.5 px-4 text-center">
              <button
                @click="selectedPersonnel = p"
                class="bg-white hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-lg text-xs border border-slate-300 transition inline-flex items-center gap-1 cursor-pointer shadow-sm"
              >
                <span class="material-symbols-outlined text-xs text-slate-500"
                  >visibility</span
                >
                Consulter
              </button>
            </td>
          </tr>
          <tr v-if="personnels.length === 0">
            <td colspan="6" class="py-8 text-center text-slate-400">
              Aucun agent enregistré pour le moment.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODALE D'AJOUT -->
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
              >person_add</span
            >
            Enregistrement d'un Nouvel Agent
          </h4>
          <button
            @click="showAddModal = false"
            class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer"
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

        <form @submit.prevent="enregistrerPersonnel" class="space-y-4 text-xs">
          <div>
            <label class="block font-medium text-slate-700 mb-1"
              >Immatriculation (IM) <span class="text-rose-500">*</span></label
            >
            <input
              type="text"
              v-model="form.im"
              required
              placeholder="Ex: 123456"
              class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Nom <span class="text-rose-500">*</span></label
              >
              <input
                type="text"
                v-model="form.nom"
                required
                placeholder="Nom de l'agent"
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Prénom</label
              >
              <input
                type="text"
                v-model="form.prenom"
                placeholder="Prénom de l'agent"
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Date de naissance</label
              >
              <input
                type="date"
                v-model="form.date_naissance"
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Date de contrat <span class="text-rose-500">*</span></label
              >
              <input
                type="date"
                v-model="form.date_contrat"
                required
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Catégorie</label
              >
              <input
                type="text"
                v-model="form.categorie"
                placeholder="Ex: A, B, C..."
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-700 mb-1">Grade</label>
              <input
                type="text"
                v-model="form.grade"
                placeholder="Ex: Administrateur"
                class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-slate-800 text-xs focus:ring-2 focus:ring-slate-500 outline-none transition"
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
              :disabled="submitting"
              class="bg-[#1A222C] hover:bg-black disabled:opacity-50 text-white px-4 py-2 rounded-xl font-semibold shadow cursor-pointer transition flex items-center gap-1.5"
            >
              <span
                v-if="submitting"
                class="material-symbols-outlined animate-spin text-sm"
                >progress_activity</span
              >
              {{ submitting ? "Enregistrement..." : "Enregistrer l'Agent" }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODALE DE VISUALISATION -->
    <div
      v-if="selectedPersonnel"
      class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 backdrop-blur-xs"
    >
      <div
        class="bg-white border border-slate-200 text-slate-800 rounded-2xl p-6 max-w-md w-full space-y-4 shadow-2xl"
      >
        <div
          class="flex justify-between items-center border-b border-slate-200 pb-2"
        >
          <h4 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-700">info</span>
            Détails de l'Agent (IM : {{ selectedPersonnel.im }})
          </h4>
          <button
            @click="selectedPersonnel = null"
            class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer"
          >
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>

        <div
          class="text-xs space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-200"
        >
          <p>
            <strong>Nom complet :</strong>
            <span class="text-slate-900 font-semibold"
              >{{ selectedPersonnel.nom }}
              {{ selectedPersonnel.prenom || "" }}</span
            >
          </p>
          <p>
            <strong>Date de naissance :</strong>
            <span class="text-slate-600">{{
              selectedPersonnel.date_naissance || "N/A"
            }}</span>
          </p>
          <p>
            <strong>Date de contrat :</strong>
            <span class="text-slate-600">{{
              selectedPersonnel.date_contrat || "N/A"
            }}</span>
          </p>
          <p>
            <strong>Catégorie :</strong>
            <span class="text-slate-600">{{
              selectedPersonnel.categorie || "N/A"
            }}</span>
          </p>
          <p>
            <strong>Grade :</strong>
            <span class="text-slate-600">{{
              selectedPersonnel.grade || "N/A"
            }}</span>
          </p>
          <p>
            <strong>Total Droits Acquis :</strong>
            <span class="text-slate-900 font-semibold"
              >{{ selectedPersonnel.total_droits_acquis }} jours</span
            >
          </p>
          <p>
            <strong>Solde Restant Actuel :</strong>
            <span class="text-emerald-600 font-semibold"
              >{{ selectedPersonnel.solde_conge_actuel }} jour(s)</span
            >
          </p>
        </div>
        y6

        <div class="flex justify-end pt-4 border-t border-slate-200">
          <button
            @click="selectedPersonnel = null"
            class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 px-4 py-2 rounded-xl text-xs font-medium cursor-pointer transition flex items-center gap-1 shadow-sm"
          >
            <span class="material-symbols-outlined text-sm">check</span>
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
