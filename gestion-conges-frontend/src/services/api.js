// src/services/api.js
import axios from "axios";

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://127.0.0.1:8000/api",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Intercepteur pour injecter automatiquement le token Sanctum dans chaque requête
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("auth_token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer proprement les erreurs globales (ex: 401 Non autorisé)
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    // Si le serveur renvoie 401 (Unauthorized), le token a expiré ou est invalide
    if (error.response && error.response.status === 401) {
      // Nettoyage des données de session locales
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user_role");

      // Redirection optionnelle vers l'état de connexion si l'utilisateur n'y est pas déjà
      if (!window.location.pathname.includes('/login')) {
        window.location.reload();
      }
    }
    return Promise.reject(error);
  }
);

export default apiClient;