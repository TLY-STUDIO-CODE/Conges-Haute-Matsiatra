<script setup>
import { ref, onMounted } from "vue";
import apiClient from "../services/api";

const demandes = ref([]);
const personnelList = ref([]);
const loading = ref(true);
const error = ref("");
const selectedDemande = ref(null);
const showAddModal = ref(false);

// Visionneuse grand format / modal de document
const previewDocument = ref(null);

const form = ref({
  im: "",
  type_periode:
    new Date().getMonth() > 5 ? "Complet_Juin_Dec" : "Fractionne_Jan_Juin",
  date_debut: "",
  date_fin: "",
  nombre_jours_demandes: 1,
  motif: "",
  fichier_demande: [],
  fichier_planning: [],
});

// États pour la vérification du planning
const verifLoading = ref(false);
const verifMessage = ref("");
const verifSuccess = ref(false);
const planningDetails = ref(null);

// États pour la vérification de la décision officielle
const decisionVerifLoading = ref(false);
const decisionVerifMessage = ref("");
const decisionVerifSuccess = ref(false);
const decisionDetails = ref(null);

const submitting = ref(false);
const formError = ref("");
const formSuccess = ref("");

// Vérifie si la période fractionnée (Janvier-Juin) est passée
const isFractionneDisabled = () => {
  const moisActuel = new Date().getMonth();
  return moisActuel > 5;
};

const fetchDemandes = async () => {
  loading.value = true;
  error.value = "";
  try {
    const response = await apiClient.get("/demandes-conges");
    const resData = response.data;
    demandes.value = Array.isArray(resData) ? resData : resData.data || [];
  } catch (err) {
    console.error("Erreur lors de la récupération des demandes", err);
    error.value =
      "Impossible de charger l'historique des demandes depuis le serveur.";
  } finally {
    loading.value = false;
  }
};

const fetchPersonnel = async () => {
  try {
    const response = await apiClient.get("/personnels");
    const resData = response.data;
    personnelList.value = Array.isArray(resData) ? resData : resData.data || [];
  } catch (err) {
    console.error("Erreur lors de la récupération du personnel", err);
  }
};

const verifierPlanningAgent = async () => {
  if (!form.value.im) {
    verifMessage.value = "Veuillez d'abord sélectionner un IM Agent.";
    verifSuccess.value = false;
    return;
  }

  verifLoading.value = true;
  verifMessage.value = "";
  planningDetails.value = null;

  const anneeDemande = form.value.date_debut
    ? new Date(form.value.date_debut).getFullYear()
    : new Date().getFullYear();

  try {
    const response = await apiClient.get(
      `/demandes-conges/verifier-decision/${form.value.im}?annee=${anneeDemande}`,
    );
    verifSuccess.value = true;
    verifMessage.value = "Planning annuel validé pour l'agent.";
    planningDetails.value = response.data.planning || response.data;
  } catch (err) {
    verifSuccess.value = false;
    verifMessage.value =
      err.response?.data?.message ||
      "Agent non autorisé (Aucun planning valide trouvé ou quota atteint).";
  } finally {
    verifLoading.value = false;
  }
};

const verifierDecisionAgent = async () => {
  if (!form.value.im) {
    decisionVerifMessage.value = "Veuillez d'abord sélectionner un IM Agent.";
    decisionVerifSuccess.value = false;
    return;
  }

  decisionVerifLoading.value = true;
  decisionVerifMessage.value = "";
  decisionDetails.value = null;

  const anneeDemande = form.value.date_debut
    ? new Date(form.value.date_debut).getFullYear()
    : new Date().getFullYear();

  try {
    const response = await apiClient.get(
      `/demandes-conges/verifier-decision/${form.value.im}?annee=${anneeDemande}`,
    );
    decisionVerifSuccess.value = true;
    decisionVerifMessage.value =
      response.data.message || "Décision de congé conforme et active trouvée.";
    decisionDetails.value = response.data.decision || response.data;
  } catch (err) {
    decisionVerifSuccess.value = false;
    decisionVerifMessage.value =
      err.response?.data?.message ||
      "Aucune décision officielle active ou solde insuffisant pour cet agent.";
  } finally {
    decisionVerifLoading.value = false;
  }
};

const handleMultiFileUpload = (event, type) => {
  const files = Array.from(event.target.files);
  if (type === "demande") {
    form.value.fichier_demande = files;
  } else if (type === "planning") {
    form.value.fichier_planning = files;
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

  form.value.fichier_demande.forEach((file) => {
    formData.append("fichier_demande[]", file);
  });
  form.value.fichier_planning.forEach((file) => {
    formData.append("fichier_planning[]", file);
  });

  try {
    await apiClient.post("/demandes-conges", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    formSuccess.value = "Demande enregistrée et transmise avec succès !";
    setTimeout(() => {
      showAddModal.value = false;
      formSuccess.value = "";
      verifMessage.value = "";
      planningDetails.value = null;
      decisionVerifMessage.value = "";
      decisionDetails.value = null;
      verifSuccess.value = false;
      decisionVerifSuccess.value = false;
      form.value = {
        im: "",
        type_periode:
          new Date().getMonth() > 5
            ? "Complet_Juin_Dec"
            : "Fractionne_Jan_Juin",
        date_debut: "",
        date_fin: "",
        nombre_jours_demandes: 1,
        motif: "",
        fichier_demande: [],
        fichier_planning: [],
      };
      fetchDemandes();
    }, 1500);
  } catch (err) {
    console.error("Erreur soumission demande", err);
    if (err.response?.data?.errors) {
      const firstKey = Object.keys(err.response.data.errors)[0];
      formError.value = err.response.data.errors[firstKey][0];
    } else {
      formError.value =
        err.response?.data?.message ||
        "Erreur lors de l'enregistrement de la demande.";
    }
  } finally {
    submitting.value = false;
  }
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

const getFileExtension = (path) => {
  if (!path) return "";
  return path.split(".").pop().toUpperCase();
};

const isImageFile = (path) => {
  if (!path) return false;
  const ext = path.split(".").pop().toLowerCase();
  return ["jpg", "jpeg", "png", "webp", "gif"].includes(ext);
};

// Gestion dynamique des badges de statut selon la validation hiérarchique
const getBadgeClass = (statut) => {
  const currentStatut = (statut || "").toLowerCase();
  if (currentStatut.includes("valide") || currentStatut.includes("approuve")) {
    return "bg-emerald-100 text-emerald-800 border-emerald-300";
  } else if (
    currentStatut.includes("refus") ||
    currentStatut.includes("rejet")
  ) {
    return "bg-rose-100 text-rose-800 border-rose-300";
  } else {
    return "bg-amber-100 text-amber-800 border-amber-300";
  }
};

const getStatutLabel = (demande) => {
  // S'appuie en priorité sur l'attribut calculé par le back-end s'il existe
  if (demande.statut_global_affiche) return demande.statut_global_affiche;

  // Analyse de secours basée sur les visas
  const visas = demande.visas || [];
  const refuseVisa = visas.find((v) => v.decision_visa === "Refuse");
  if (refuseVisa) {
    return `Refusé par ${refuseVisa.etape.replace("_", " ")}`;
  }

  const tousValides =
    visas.length > 0 && visas.every((v) => v.decision_visa === "Valide");
  if (
    tousValides ||
    demande.statut_global === "valide_par_autorite" ||
    demande.statut_global === "valide"
  ) {
    return "Validé";
  }

  return demande.statut_global || demande.statut || "En attente";
};

const exporterCSV = async () => {
  try {
    const response = await apiClient.get("/demandes-conges/export/csv", {
      responseType: "blob",
    });

    const blob = new Blob([response.data], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = window.URL.createObjectURL(blob);
    link.setAttribute(
      "download",
      `registre_demandes_conges_${new Date().toISOString().slice(0, 10)}.csv`,
    );
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(blob.href);
  } catch (error) {
    console.error("Erreur lors de l'export CSV :", error);
  }
};

onMounted(() => {
  fetchDemandes();
  fetchPersonnel();
});
</script>

<template>
  <div
    class="bg-slate-50 border border-slate-200 rounded-xl shadow-md p-6 space-y-6 mt-6 text-slate-800"
  >
    <!-- En-Tête du Module -->
    <div
      class="border-b border-slate-200 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
    >
      <div>
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
          <span class="material-symbols-outlined text-slate-700"
            >assignment</span
          >
          Registre & Suivi des Demandes de Congé - Région Haute Matsiatra
        </h3>
        <p class="text-xs text-slate-500 mt-1">
          Plateforme administrative unifiée pour le suivi hiérarchique, les
          pièces justificatives et la gestion du personnel.
        </p>
      </div>
      <div class="flex items-center flex-wrap gap-2 w-full md:w-auto">
        <button
          @click="showAddModal = true"
          class="bg-slate-800 hover:bg-slate-700 text-white text-xs px-3.5 py-2 rounded-lg transition flex items-center gap-1.5 font-medium shadow-sm cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm">add_circle</span>
          Nouvelle Demande
        </button>
        <button
          @click="exporterCSV"
          class="bg-white hover:bg-slate-100 text-slate-700 text-xs px-3.5 py-2 rounded-lg border border-slate-300 transition flex items-center gap-1.5 font-medium shadow-sm cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm text-slate-600"
            >download</span
          >
          Exporter (CSV)
        </button>
        <button
          @click="fetchDemandes"
          class="bg-white hover:bg-slate-100 text-slate-700 text-xs px-3.5 py-2 rounded-lg border border-slate-300 transition flex items-center gap-1.5 cursor-pointer"
        >
          <span class="material-symbols-outlined text-sm text-slate-600"
            >refresh</span
          >
          Actualiser
        </button>
      </div>
    </div>

    <!-- États de chargement et erreurs -->
    <div
      v-if="loading"
      class="text-center py-6 text-slate-500 text-xs flex items-center justify-center gap-2"
    >
      <span class="material-symbols-outlined animate-spin text-sm"
        >progress_activity</span
      >
      Chargement du registre en cours...
    </div>
    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-xs"
    >
      {{ error }}
    </div>

    <!-- Tableau de Suivi -->
    <div
      v-if="!loading && !error"
      class="overflow-x-auto bg-white rounded-lg border border-slate-200 shadow-sm"
    >
      <table class="w-full text-left border-collapse text-xs text-slate-700">
        <thead
          class="bg-slate-100 border-b border-slate-200 text-slate-600 uppercase tracking-wider text-[11px]"
        >
          <tr>
            <th class="py-3 px-3">Agent (IM)</th>
            <th class="py-3 px-3">Type & Période</th>
            <th class="py-3 px-3">Dates</th>
            <th class="py-3 px-3">Durée</th>
            <th class="py-3 px-3">Pièces Justificatives & Décisions</th>
            <th class="py-3 px-3">Statut Hiérarchique</th>
            <th class="py-3 px-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="demande in demandes"
            :key="demande.id_demande || demande.id"
            class="hover:bg-slate-50 transition"
          >
            <td class="py-3.5 px-3 font-semibold text-slate-900">
              {{ demande.im }}
              <div class="text-[10px] text-slate-500 font-normal">
                {{ demande.personnel?.nom || "" }}
                {{ demande.personnel?.prenom || "" }}
              </div>
            </td>
            <td class="py-3.5 px-3 text-slate-700">
              {{ demande.type_periode }}
            </td>
            <td class="py-3.5 px-3 text-slate-600 whitespace-nowrap">
              Du {{ demande.date_debut }}<br />au {{ demande.date_fin }}
            </td>
            <td class="py-3.5 px-3 font-medium text-slate-900">
              {{ demande.nombre_jours_demandes }} jour(s)
            </td>

            <!-- Vignettes Multi-fichiers incluant la Décision Officielle -->
            <td class="py-3.5 px-3">
              <div class="flex items-center gap-2 flex-wrap">
                <!-- Fichiers Demande -->
                <div
                  v-if="
                    demande.fichier_demande &&
                    demande.fichier_demande.length > 0
                  "
                  class="flex items-center gap-1"
                >
                  <div
                    v-for="(file, idx) in demande.fichier_demande"
                    :key="'d-' + idx"
                  >
                    <div
                      @click="selectedDemande = demande"
                      class="w-8 h-10 bg-slate-100 border border-slate-300 rounded overflow-hidden shadow-sm hover:border-slate-500 transition cursor-pointer flex flex-col items-center justify-center"
                      title="Consulter la demande"
                    >
                      <span
                        class="material-symbols-outlined text-slate-600 text-sm"
                        >description</span
                      >
                      <span class="text-[7px] font-bold text-slate-700">{{
                        getFileExtension(file)
                      }}</span>
                    </div>
                  </div>
                </div>

                <!-- Fichiers Planning -->
                <div
                  v-if="
                    demande.fichier_planning &&
                    demande.fichier_planning.length > 0
                  "
                  class="flex items-center gap-1"
                >
                  <div
                    v-for="(file, idx) in demande.fichier_planning"
                    :key="'p-' + idx"
                  >
                    <div
                      @click="selectedDemande = demande"
                      class="w-8 h-10 bg-slate-100 border border-slate-300 rounded overflow-hidden shadow-sm hover:border-slate-500 transition cursor-pointer flex flex-col items-center justify-center"
                      title="Consulter le planning"
                    >
                      <span
                        class="material-symbols-outlined text-slate-600 text-sm"
                        >calendar_month</span
                      >
                      <span class="text-[7px] font-bold text-slate-700">{{
                        getFileExtension(file)
                      }}</span>
                    </div>
                  </div>
                </div>

                <!-- Décision Officielle rattachée -->
                <div
                  v-if="
                    demande.fichier_decision &&
                    demande.fichier_decision.length > 0
                  "
                  class="flex items-center gap-1"
                >
                  <div
                    v-for="(file, idx) in demande.fichier_decision"
                    :key="'dec-' + idx"
                  >
                    <div
                      @click="selectedDemande = demande"
                      class="w-8 h-10 bg-emerald-50 border border-emerald-300 rounded overflow-hidden shadow-sm hover:border-emerald-600 transition cursor-pointer flex flex-col items-center justify-center"
                      title="Décision officielle de congé"
                    >
                      <span
                        class="material-symbols-outlined text-emerald-700 text-sm"
                        >gavel</span
                      >
                      <span class="text-[7px] font-bold text-emerald-800"
                        >DEC</span
                      >
                    </div>
                  </div>
                </div>

                <span
                  v-if="
                    (!demande.fichier_demande ||
                      demande.fichier_demande.length === 0) &&
                    (!demande.fichier_planning ||
                      demande.fichier_planning.length === 0) &&
                    (!demande.fichier_decision ||
                      demande.fichier_decision.length === 0)
                  "
                  class="text-slate-400 italic text-[11px]"
                >
                  Aucun fichier
                </span>
              </div>
            </td>

            <td class="py-3.5 px-3">
              <span
                :class="getBadgeClass(getStatutLabel(demande))"
                class="px-2.5 py-1 rounded text-[11px] font-medium border inline-block"
              >
                {{ getStatutLabel(demande) }}
              </span>
            </td>
            <td class="py-3.5 px-3 text-center">
              <button
                @click="selectedDemande = demande"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg text-xs border border-slate-300 transition shadow-sm cursor-pointer flex items-center justify-center gap-1 mx-auto"
              >
                <span class="material-symbols-outlined text-xs"
                  >visibility</span
                >
                Consulter
              </button>
            </td>
          </tr>
          <tr v-if="demandes.length === 0">
            <td colspan="7" class="py-8 text-center text-slate-400 italic">
              Aucune demande de congé enregistrée dans le système.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODALE D'ENREGISTREMENT -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto"
    >
      <div
        class="bg-white border border-slate-300 text-slate-800 rounded-xl p-6 max-w-lg w-full space-y-4 shadow-2xl my-8"
      >
        <div
          class="flex justify-between items-center border-b border-slate-200 pb-3"
        >
          <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-700"
              >post_add</span
            >
            Soumission d'une Nouvelle Demande de Congé
          </h4>
          <button
            @click="showAddModal = false"
            class="text-slate-400 hover:text-slate-700 text-xs font-bold px-2 py-1 cursor-pointer"
          >
            <span class="material-symbols-outlined text-base">close</span>
          </button>
        </div>

        <div
          v-if="formSuccess"
          class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs font-medium"
        >
          {{ formSuccess }}
        </div>
        <div
          v-if="formError"
          class="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg text-xs font-medium"
        >
          {{ formError }}
        </div>

        <form @submit.prevent="submitDemande" class="space-y-3.5 text-xs">
          <!-- Sélection de l'Agent et Contrôles Préalables -->
          <div>
            <label class="block font-medium text-slate-700 mb-1"
              >Immatriculation (IM) Agent</label
            >
            <div class="flex flex-col gap-2">
              <select
                v-model="form.im"
                required
                class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-900 text-xs focus:ring-2 focus:ring-slate-400 outline-none"
              >
                <option value="" disabled>-- Sélectionner un agent --</option>
                <option
                  v-for="perso in personnelList"
                  :key="perso.id || perso.im"
                  :value="perso.im"
                >
                  {{ perso.im }} - {{ perso.nom }} {{ perso.prenom || "" }}
                </option>
              </select>

              <!-- Boutons de vérification (Planning + Décision Officielle) -->
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  @click="verifierPlanningAgent"
                  :disabled="verifLoading"
                  class="bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 font-medium px-3 py-2 rounded-lg transition text-xs cursor-pointer flex items-center justify-center gap-1 shadow-sm"
                >
                  <span class="material-symbols-outlined text-xs"
                    >verified</span
                  >
                  {{ verifLoading ? "Vérif..." : "Vérifier Planning" }}
                </button>

                <button
                  type="button"
                  @click="verifierDecisionAgent"
                  :disabled="decisionVerifLoading"
                  class="bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 font-medium px-3 py-2 rounded-lg transition text-xs cursor-pointer flex items-center justify-center gap-1 shadow-sm"
                >
                  <span class="material-symbols-outlined text-xs">gavel</span>
                  {{ decisionVerifLoading ? "Vérif..." : "Vérifier Décision" }}
                </button>
              </div>
            </div>
          </div>

          <!-- Affichage du Résultat de Vérification Planning -->
          <div
            v-if="verifMessage"
            class="p-2.5 border rounded-lg text-xs space-y-1 bg-slate-50 border-slate-200 text-slate-700"
          >
            <p class="font-semibold text-slate-900">
              📅 Planning : {{ verifMessage }}
            </p>
            <div v-if="planningDetails" class="text-[11px] text-slate-600">
              <p>Année : {{ planningDetails.annee || "Validée" }}</p>
            </div>
          </div>

          <!-- Affichage du Résultat de Vérification Décision -->
          <div
            v-if="decisionVerifMessage"
            class="p-2.5 border rounded-lg text-xs space-y-1 bg-slate-50 border-slate-200 text-slate-700"
          >
            <p class="font-semibold text-slate-900">
              ⚖️ Décision : {{ decisionVerifMessage }}
            </p>
            <div v-if="decisionDetails" class="text-[11px] text-slate-600">
              <p>
                N° Décision :
                <span class="font-bold text-slate-900">{{
                  decisionDetails.reference_decision || "N/A"
                }}</span>
                | Solde Restant :
                <span class="font-bold text-emerald-700"
                  >{{ decisionDetails.solde_restant || 30 }} j</span
                >
              </p>
            </div>
          </div>

          <div>
            <label class="block font-medium text-slate-700 mb-1"
              >Type de Période</label
            >
            <select
              v-model="form.type_periode"
              class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-900 text-xs outline-none"
            >
              <option
                value="Fractionne_Jan_Juin"
                :disabled="isFractionneDisabled()"
              >
                Fractionné (Janvier - Juin)
                {{ isFractionneDisabled() ? "(Période expirée)" : "" }}
              </option>
              <option value="Complet_Juin_Dec">
                Complet (Juin - Décembre)
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Date de Début</label
              >
              <input
                type="date"
                v-model="form.date_debut"
                required
                class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-900 text-xs outline-none"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Date de Fin</label
              >
              <input
                type="date"
                v-model="form.date_fin"
                required
                class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-900 text-xs outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block font-medium text-slate-700 mb-1"
              >Nombre de Jours Demandés</label
            >
            <input
              type="number"
              v-model="form.nombre_jours_demandes"
              required
              min="1"
              max="30"
              class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-900 text-xs outline-none"
            />
          </div>

          <div>
            <label class="block font-medium text-slate-700 mb-1"
              >Motif de la Demande</label
            >
            <textarea
              v-model="form.motif"
              rows="2"
              placeholder="Préciser le motif..."
              class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2 text-slate-900 text-xs outline-none"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3 pt-1">
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Pièce(s) Jointe(s) (Demande - Multi)</label
              >
              <input
                type="file"
                multiple
                @change="(e) => handleMultiFileUpload(e, 'demande')"
                class="w-full text-slate-500 text-[11px] file:mr-2 file:py-1 file:px-2.5 file:rounded file:border file:border-slate-300 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
              />
            </div>
            <div>
              <label class="block font-medium text-slate-700 mb-1"
                >Pièce(s) Jointe(s) (Planning - Multi)</label
              >
              <input
                type="file"
                multiple
                @change="(e) => handleMultiFileUpload(e, 'planning')"
                class="w-full text-slate-500 text-[11px] file:mr-2 file:py-1 file:px-2.5 file:rounded file:border file:border-slate-300 file:text-xs file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
            <button
              type="button"
              @click="showAddModal = false"
              class="bg-white hover:bg-slate-100 text-slate-700 px-3.5 py-2 rounded-lg font-medium text-xs border border-slate-300 cursor-pointer"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="submitting || !verifSuccess || !decisionVerifSuccess"
              class="bg-slate-800 hover:bg-slate-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg font-semibold shadow-sm text-xs cursor-pointer flex items-center gap-1"
              title="Nécessite la validation du planning et de la décision officielle"
            >
              <span class="material-symbols-outlined text-xs">send</span>
              {{ submitting ? "Enregistrement..." : "Soumettre la Demande" }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODALE DE CONSULTATION DÉTAILLÉE -->
    <div
      v-if="selectedDemande"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50"
    >
      <div
        class="bg-white border border-slate-300 text-slate-800 rounded-xl p-6 max-w-xl w-full space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto"
      >
        <h4
          class="text-sm font-bold border-b border-slate-200 pb-2 text-slate-900 flex items-center justify-between"
        >
          <span class="flex items-center gap-1.5"
            ><span class="material-symbols-outlined text-slate-700">info</span>
            Consultation Détaillée de la Demande</span
          >
          <span class="text-xs font-normal text-slate-500"
            >ID : {{ selectedDemande.id_demande || selectedDemande.id }}</span
          >
        </h4>
        <div class="text-xs space-y-2.5 text-slate-700">
          <p><strong>IM Agent :</strong> {{ selectedDemande.im }}</p>
          <p>
            <strong>Type de Période :</strong>
            {{ selectedDemande.type_periode }}
          </p>
          <p>
            <strong>Période demandée :</strong> Du
            {{ selectedDemande.date_debut }} au {{ selectedDemande.date_fin }}
          </p>
          <p>
            <strong>Durée :</strong>
            {{ selectedDemande.nombre_jours_demandes }} jour(s)
          </p>
          <p>
            <strong>Motif :</strong>
            {{ selectedDemande.motif || "Aucun motif renseigné" }}
          </p>
          <p>
            <strong>Statut Global :</strong>
            <span class="font-semibold">{{
              getStatutLabel(selectedDemande)
            }}</span>
          </p>

          <!-- Section Fichiers Multiples & Décision Officielle -->
          <div class="pt-3 border-t border-slate-200 space-y-3">
            <p class="font-semibold text-slate-900 flex items-center gap-1">
              <span class="material-symbols-outlined text-sm">folder_open</span>
              Pièces Justificatives & Décision Officielle Associées :
            </p>

            <div class="space-y-3">
              <!-- Décision Officielle -->
              <div
                v-if="
                  selectedDemande.fichier_decision &&
                  selectedDemande.fichier_decision.length > 0
                "
              >
                <p class="text-[11px] font-semibold text-emerald-800 mb-1">
                  Décision Officielle (Rattachée automatiquement) :
                </p>
                <div class="flex items-center gap-3 flex-wrap">
                  <div
                    v-for="(file, idx) in selectedDemande.fichier_decision"
                    :key="'dec-f-' + idx"
                    class="flex flex-col items-center gap-1 bg-emerald-50 p-2 rounded-lg border border-emerald-200"
                  >
                    <div
                      @click="telechargerFichier(file)"
                      class="w-14 h-16 bg-white border border-emerald-300 rounded overflow-hidden shadow-sm hover:border-emerald-600 transition cursor-pointer flex flex-col items-center justify-center"
                      title="Télécharger la décision"
                    >
                      <span
                        class="material-symbols-outlined text-emerald-700 text-xl"
                        >gavel</span
                      >
                      <span class="text-[9px] font-bold text-emerald-800"
                        >PDF</span
                      >
                    </div>
                    <button
                      @click="telechargerFichier(file)"
                      class="bg-emerald-700 hover:bg-emerald-800 text-white border border-emerald-600 px-2 py-1 rounded text-[10px] font-medium transition shadow-sm flex items-center gap-1 cursor-pointer"
                    >
                      <span class="material-symbols-outlined text-[10px]"
                        >download</span
                      >
                      Télécharger
                    </button>
                  </div>
                </div>
              </div>

              <!-- Fichiers Demande -->
              <div
                v-if="
                  selectedDemande.fichier_demande &&
                  selectedDemande.fichier_demande.length > 0
                "
              >
                <p class="text-[11px] font-semibold text-slate-600 mb-1">
                  Fichiers Demande :
                </p>
                <div class="flex items-center gap-3 flex-wrap">
                  <div
                    v-for="(file, idx) in selectedDemande.fichier_demande"
                    :key="'df-' + idx"
                    class="flex flex-col items-center gap-1 bg-slate-50 p-2 rounded-lg border border-slate-200"
                  >
                    <div
                      @click="
                        isImageFile(file)
                          ? (previewDocument = `http://127.0.0.1:8000/storage/${file}`)
                          : telechargerFichier(file)
                      "
                      class="w-14 h-16 bg-white border border-slate-300 rounded overflow-hidden shadow-sm hover:border-slate-500 transition cursor-pointer flex flex-col items-center justify-center"
                    >
                      <template v-if="isImageFile(file)">
                        <img
                          :src="`http://127.0.0.1:8000/storage/${file}`"
                          class="w-full h-full object-cover"
                        />
                      </template>
                      <template v-else>
                        <span
                          class="material-symbols-outlined text-slate-700 text-xl"
                          >description</span
                        >
                        <span class="text-[9px] font-bold text-slate-800">{{
                          getFileExtension(file)
                        }}</span>
                      </template>
                    </div>
                    <button
                      @click="telechargerFichier(file)"
                      class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 px-2 py-1 rounded text-[10px] font-medium transition shadow-sm flex items-center gap-1 cursor-pointer"
                    >
                      <span class="material-symbols-outlined text-[10px]"
                        >download</span
                      >
                      Télécharger
                    </button>
                  </div>
                </div>
              </div>

              <!-- Fichiers Planning -->
              <div
                v-if="
                  selectedDemande.fichier_planning &&
                  selectedDemande.fichier_planning.length > 0
                "
              >
                <p class="text-[11px] font-semibold text-slate-600 mb-1">
                  Fichiers Planning :
                </p>
                <div class="flex items-center gap-3 flex-wrap">
                  <div
                    v-for="(file, idx) in selectedDemande.fichier_planning"
                    :key="'pf-' + idx"
                    class="flex flex-col items-center gap-1 bg-slate-50 p-2 rounded-lg border border-slate-200"
                  >
                    <div
                      @click="
                        isImageFile(file)
                          ? (previewDocument = `http://127.0.0.1:8000/storage/${file}`)
                          : telechargerFichier(file)
                      "
                      class="w-14 h-16 bg-white border border-slate-300 rounded overflow-hidden shadow-sm hover:border-slate-500 transition cursor-pointer flex flex-col items-center justify-center"
                    >
                      <template v-if="isImageFile(file)">
                        <img
                          :src="`http://127.0.0.1:8000/storage/${file}`"
                          class="w-full h-full object-cover"
                        />
                      </template>
                      <template v-else>
                        <span
                          class="material-symbols-outlined text-slate-700 text-xl"
                          >calendar_month</span
                        >
                        <span class="text-[9px] font-bold text-slate-800">{{
                          getFileExtension(file)
                        }}</span>
                      </template>
                    </div>
                    <button
                      @click="telechargerFichier(file)"
                      class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 px-2 py-1 rounded text-[10px] font-medium transition shadow-sm flex items-center gap-1 cursor-pointer"
                    >
                      <span class="material-symbols-outlined text-[10px]"
                        >download</span
                      >
                      Télécharger
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
          <button
            @click="selectedDemande = null"
            class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 px-4 py-2 rounded-lg text-xs font-medium cursor-pointer"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>

    <!-- LIGHTBOX / VISIONNEUSE D'IMAGE EN GRAND FORMAT -->
    <div
      v-if="previewDocument"
      class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 z-50"
    >
      <div class="relative max-w-4xl max-h-full flex flex-col items-center">
        <button
          @click="previewDocument = null"
          class="absolute -top-10 right-0 text-white bg-slate-800 hover:bg-slate-700 border border-slate-600 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm transition shadow-lg cursor-pointer"
        >
          ✕
        </button>
        <img
          :src="previewDocument"
          alt="Agrandissement document"
          class="max-w-full max-h-[85vh] object-contain rounded-lg border border-slate-300 shadow-2xl bg-white"
        />
      </div>
    </div>
  </div>
</template>
