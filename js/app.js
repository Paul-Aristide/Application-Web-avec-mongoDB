const API = (() => {
  const path = window.location.pathname.replace(/\/[^/]*$/, '') || '';
  return path ? path + '/api' : 'api';
})();

async function api(path, options = {}) {
  const url = path.startsWith('http') ? path : `${API}/${path}`;
  const r = await fetch(url, {
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
  });
  const data = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error(data.error || r.statusText);
  return data;
}

const { createApp } = Vue;

const defaultSummary = { enseignants: 0, etudiants: 0, cours: 0, inscriptions: 0 };
function normalizeStats(res) {
  if (!res || typeof res !== 'object') {
    return {
      source: null,
      summary: { ...defaultSummary },
      cours_by_enseignant: [],
      cours_by_departement: [],
      etudiants_by_filiere: [],
      cours_by_niveau: [],
      enseignants_by_departement: [],
      inscriptions_by_cours: [],
      inscriptions_by_etudiant: [],
      recent_cours: [],
      recent_inscriptions: [],
    };
  }
  return {
    source: res.source || null,
    summary: { ...defaultSummary, ...(res.summary || {}) },
    cours_by_enseignant: res.cours_by_enseignant || [],
    cours_by_departement: res.cours_by_departement || [],
    etudiants_by_filiere: res.etudiants_by_filiere || [],
    cours_by_niveau: res.cours_by_niveau || [],
    enseignants_by_departement: res.enseignants_by_departement || [],
    inscriptions_by_cours: res.inscriptions_by_cours || [],
    inscriptions_by_etudiant: res.inscriptions_by_etudiant || [],
    recent_cours: res.recent_cours || [],
    recent_inscriptions: res.recent_inscriptions || [],
  };
}

createApp({
  data() {
    return {
      view: 'dashboard',
      live: true,
      enseignants: [],
      etudiants: [],
      cours: [],
      inscriptions: [],
      stats: normalizeStats(null),
      enseignantForm: {
        NUM_ENS: '', NOM: '', PRENOM: '', EMAIL: '', DEPARTEMENT: '', GRADE: '', SPECIALITE: '',
      },
      etudiantForm: {
        NUM_CARTE: '', NOM: '', PRENOM: '', EMAIL: '', TELEPHONE: '', FILIERE: '', ANNEE_ENTREE: null, DATE_NAISSANCE: '',
      },
      coursForm: {
        ID_ENS: '', INTITULE: '', DESCRIPTION_: '', NBRE_CREDITS: 0, SEMESTRE: 1, NIVEAU: '', DEPARTEMENT: '', PREREQUIS: '',
      },
      inscriptionForm: { etudiantKey: '', coursKey: '' },
      editingEnseignantId: null,
      editingEtudiantId: null,
      editingCoursId: null,
      chartCoursByEnseignant: null,
      chartCoursByDepartement: null,
      chartEtudiantsByFiliere: null,
      chartCoursByNiveau: null,
      chartEnseignantsByDepartement: null,
      chartInscriptionsByCours: null,
      chartInscriptionsByEtudiant: null,
      statsInterval: null,
    };
  },
  mounted() {
    this.loadAll();
    this.startLiveStats();
  },
  beforeUnmount() {
    this.stopLiveStats();
  },
  watch: {
    view(v) {
      if (v === 'dashboard') {
        this.startLiveStats();
        this.$nextTick(() => this.updateCharts());
      } else {
        this.stopLiveStats();
      }
    },
    stats: {
      deep: true,
      handler() {
        this.$nextTick(() => this.updateCharts());
      },
    },
  },
  methods: {
    async loadAll() {
      try {
        const [ensRes, etuRes, coursRes, inscRes, statsRes] = await Promise.all([
          api('enseignants.php'),
          api('etudiants.php'),
          api('cours.php'),
          api('inscriptions.php'),
          api('stats.php'),
        ]);
        this.enseignants = ensRes.data || [];
        this.etudiants = etuRes.data || [];
        this.cours = coursRes.data || [];
        this.inscriptions = inscRes.data || [];
        this.stats = normalizeStats(statsRes);
      } catch (e) {
        console.error(e);
        this.stats = normalizeStats(null);
      }
    },
    startLiveStats() {
      this.stopLiveStats();
      this.statsInterval = setInterval(async () => {
        try {
          const res = await api('stats.php');
          this.stats = normalizeStats(res);
          this.live = true;
        } catch (_) {
          this.live = false;
        }
      }, 5000);
    },
    stopLiveStats() {
      if (this.statsInterval) {
        clearInterval(this.statsInterval);
        this.statsInterval = null;
      }
    },
    enseignantNom(idEns) {
      if (idEns == null) return '—';
      const e = this.enseignants.find(x => x.ID_ENS == idEns);
      return e ? `${e.NOM} ${e.PRENOM}` : `ID ${idEns}`;
    },
    etudiantKey(e) {
      return `${e.ID_ETUDIANTS}|${e.NUM_CARTE}`;
    },
    coursKey(c) {
      return `${c.ID_COURS}|${c.CODE_COURS}`;
    },
    etudiantNom(idEtu, numCarte) {
      if (idEtu == null && !numCarte) return '—';
      const e = this.etudiants.find(x => x.ID_ETUDIANTS == idEtu && x.NUM_CARTE === numCarte);
      return e ? `${e.NOM || ''} ${e.PRENOM || ''}`.trim() || e.NUM_CARTE : `${idEtu} / ${numCarte}`;
    },
    coursIntitule(idCours, codeCours) {
      if (idCours == null && codeCours == null) return '—';
      const c = this.cours.find(x => x.ID_COURS == idCours && x.CODE_COURS == codeCours);
      return c ? c.INTITULE : `${idCours}/${codeCours}`;
    },
    updateCharts() {
      if (this.view !== 'dashboard') return;
      this.$nextTick(() => {
        this.renderCoursByEnseignant();
        this.renderCoursByDepartement();
        this.renderEtudiantsByFiliere();
        this.renderCoursByNiveau();
        this.renderEnseignantsByDepartement();
        this.renderInscriptionsByCours();
        this.renderInscriptionsByEtudiant();
      });
    },
    chartColors(n) {
      const palette = ['#a78bfa', '#22c55e', '#eab308', '#06b6d4', '#f43f5e', '#8b5cf6', '#14b8a6', '#f97316'];
      return Array.from({ length: n }, (_, i) => palette[i % palette.length]);
    },
    renderCoursByEnseignant() {
      const el = this.$refs.chartCoursByEnseignant;
      if (!el) return;
      const data = this.stats.cours_by_enseignant || [];
      const labels = data.map(r => this.enseignantNom(r._id) || `ID ${r._id}`);
      const values = data.map(r => r.count);
      if (this.chartCoursByEnseignant) this.chartCoursByEnseignant.destroy();
      this.chartCoursByEnseignant = new Chart(el, {
        type: 'bar',
        data: {
          labels,
          datasets: [{ label: 'Nombre de cours', data: values, backgroundColor: this.chartColors(values.length) }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#71717a' } },
            x: { grid: { display: false }, ticks: { color: '#71717a', maxRotation: 45 } },
          },
        },
      });
    },
    renderCoursByDepartement() {
      const el = this.$refs.chartCoursByDepartement;
      if (!el) return;
      const data = this.stats.cours_by_departement || [];
      const labels = data.map(r => r._id || '?');
      const values = data.map(r => r.count);
      if (this.chartCoursByDepartement) this.chartCoursByDepartement.destroy();
      this.chartCoursByDepartement = new Chart(el, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{ data: values, backgroundColor: this.chartColors(values.length), borderWidth: 0 }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { position: 'right', labels: { color: '#e4e4e7', padding: 12 } } },
        },
      });
    },
    renderEtudiantsByFiliere() {
      const el = this.$refs.chartEtudiantsByFiliere;
      if (!el) return;
      const data = this.stats.etudiants_by_filiere || [];
      const labels = data.map(r => (r._id && r._id.length > 25 ? r._id.slice(0, 25) + '…' : r._id) || '?');
      const values = data.map(r => r.count);
      if (this.chartEtudiantsByFiliere) this.chartEtudiantsByFiliere.destroy();
      this.chartEtudiantsByFiliere = new Chart(el, {
        type: 'bar',
        data: {
          labels,
          datasets: [{ label: 'Étudiants', data: values, backgroundColor: 'rgba(34, 197, 94, 0.7)' }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          indexAxis: 'y',
          plugins: { legend: { display: false } },
          scales: {
            x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#71717a' } },
            y: { grid: { display: false }, ticks: { color: '#71717a' } },
          },
        },
      });
    },
    renderCoursByNiveau() {
      const el = this.$refs.chartCoursByNiveau;
      if (!el) return;
      const data = this.stats.cours_by_niveau || [];
      const labels = data.map(r => r._id || '?');
      const values = data.map(r => r.count);
      if (this.chartCoursByNiveau) this.chartCoursByNiveau.destroy();
      this.chartCoursByNiveau = new Chart(el, {
        type: 'pie',
        data: {
          labels,
          datasets: [{ data: values, backgroundColor: this.chartColors(values.length), borderWidth: 0 }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { position: 'right', labels: { color: '#e4e4e7', padding: 12 } } },
        },
      });
    },
    renderEnseignantsByDepartement() {
      const el = this.$refs.chartEnseignantsByDepartement;
      if (!el) return;
      const data = this.stats.enseignants_by_departement || [];
      const labels = data.map(r => r._id || '?');
      const values = data.map(r => r.count);
      if (this.chartEnseignantsByDepartement) this.chartEnseignantsByDepartement.destroy();
      this.chartEnseignantsByDepartement = new Chart(el, {
        type: 'bar',
        data: {
          labels,
          datasets: [{ label: 'Enseignants', data: values, backgroundColor: 'rgba(167, 139, 250, 0.7)' }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#71717a' } },
            x: { grid: { display: false }, ticks: { color: '#71717a', maxRotation: 45 } },
          },
        },
      });
    },
    renderInscriptionsByCours() {
      const el = this.$refs.chartInscriptionsByCours;
      if (!el) return;
      const data = this.stats.inscriptions_by_cours || [];
      const labels = data.map(r => this.coursIntitule(r.ID_COURS, r.CODE_COURS));
      const values = data.map(r => r.count);
      if (this.chartInscriptionsByCours) this.chartInscriptionsByCours.destroy();
      this.chartInscriptionsByCours = new Chart(el, {
        type: 'bar',
        data: {
          labels,
          datasets: [{ label: 'Inscriptions', data: values, backgroundColor: 'rgba(6, 182, 212, 0.7)' }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#71717a' } },
            x: { grid: { display: false }, ticks: { color: '#71717a', maxRotation: 45 } },
          },
        },
      });
    },
    renderInscriptionsByEtudiant() {
      const el = this.$refs.chartInscriptionsByEtudiant;
      if (!el) return;
      const data = this.stats.inscriptions_by_etudiant || [];
      const labels = data.map(r => this.etudiantNom(r.ID_ETUDIANTS, r.NUM_CARTE));
      const values = data.map(r => r.count);
      if (this.chartInscriptionsByEtudiant) this.chartInscriptionsByEtudiant.destroy();
      this.chartInscriptionsByEtudiant = new Chart(el, {
        type: 'bar',
        data: {
          labels,
          datasets: [{ label: 'Inscriptions', data: values, backgroundColor: 'rgba(244, 63, 94, 0.7)' }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          indexAxis: 'y',
          plugins: { legend: { display: false } },
          scales: {
            x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.06)' }, ticks: { color: '#71717a' } },
            y: { grid: { display: false }, ticks: { color: '#71717a' } },
          },
        },
      });
    },
    // Enseignants CRUD
    async saveEnseignant() {
      try {
        const body = { ...this.enseignantForm };
        if (this.editingEnseignantId) {
          await api(`enseignants.php?id=${this.editingEnseignantId}`, { method: 'PUT', body });
        } else {
          await api('enseignants.php', { method: 'POST', body });
        }
        this.enseignantForm = { NUM_ENS: '', NOM: '', PRENOM: '', EMAIL: '', DEPARTEMENT: '', GRADE: '', SPECIALITE: '' };
        this.editingEnseignantId = null;
        await this.loadAll();
      } catch (e) {
        alert(e.message || 'Erreur');
      }
    },
    editEnseignant(e) {
      this.enseignantForm = {
        NUM_ENS: e.NUM_ENS || '',
        NOM: e.NOM || '',
        PRENOM: e.PRENOM || '',
        EMAIL: e.EMAIL || '',
        DEPARTEMENT: e.DEPARTEMENT || '',
        GRADE: e.GRADE || '',
        SPECIALITE: e.SPECIALITE || '',
      };
      this.editingEnseignantId = e._id;
    },
    cancelEditEnseignant() {
      this.enseignantForm = { NUM_ENS: '', NOM: '', PRENOM: '', EMAIL: '', DEPARTEMENT: '', GRADE: '', SPECIALITE: '' };
      this.editingEnseignantId = null;
    },
    async deleteEnseignant(id) {
      if (!confirm('Supprimer cet enseignant ?')) return;
      try {
        await api(`enseignants.php?id=${id}`, { method: 'DELETE' });
        await this.loadAll();
      } catch (e) {
        alert(e.message || 'Erreur');
      }
    },
    // Étudiants CRUD
    async saveEtudiant() {
      try {
        const body = { ...this.etudiantForm };
        if (body.ANNEE_ENTREE === '' || body.ANNEE_ENTREE == null) body.ANNEE_ENTREE = null;
        if (this.editingEtudiantId) {
          await api(`etudiants.php?id=${this.editingEtudiantId}`, { method: 'PUT', body });
        } else {
          await api('etudiants.php', { method: 'POST', body });
        }
        this.etudiantForm = { NUM_CARTE: '', NOM: '', PRENOM: '', EMAIL: '', TELEPHONE: '', FILIERE: '', ANNEE_ENTREE: null, DATE_NAISSANCE: '' };
        this.editingEtudiantId = null;
        await this.loadAll();
      } catch (e) {
        alert(e.message || 'Erreur');
      }
    },
    editEtudiant(e) {
      this.etudiantForm = {
        NUM_CARTE: e.NUM_CARTE || '',
        NOM: e.NOM || '',
        PRENOM: e.PRENOM || '',
        EMAIL: e.EMAIL || '',
        TELEPHONE: e.TELEPHONE || '',
        FILIERE: e.FILIERE || '',
        ANNEE_ENTREE: e.ANNEE_ENTREE ?? null,
        DATE_NAISSANCE: (e.DATE_NAISSANCE || '').toString().slice(0, 10),
      };
      this.editingEtudiantId = e._id;
    },
    cancelEditEtudiant() {
      this.etudiantForm = { NUM_CARTE: '', NOM: '', PRENOM: '', EMAIL: '', TELEPHONE: '', FILIERE: '', ANNEE_ENTREE: null, DATE_NAISSANCE: '' };
      this.editingEtudiantId = null;
    },
    async deleteEtudiant(id) {
      if (!confirm('Supprimer cet étudiant ?')) return;
      try {
        await api(`etudiants.php?id=${id}`, { method: 'DELETE' });
        await this.loadAll();
      } catch (e) {
        alert(e.message || 'Erreur');
      }
    },
    // Cours CRUD
    async saveCours() {
      try {
        const body = {
          ID_ENS: Number(this.coursForm.ID_ENS),
          INTITULE: this.coursForm.INTITULE,
          DESCRIPTION_: this.coursForm.DESCRIPTION_ || null,
          NBRE_CREDITS: Number(this.coursForm.NBRE_CREDITS),
          SEMESTRE: Number(this.coursForm.SEMESTRE),
          NIVEAU: this.coursForm.NIVEAU,
          DEPARTEMENT: this.coursForm.DEPARTEMENT,
          PREREQUIS: this.coursForm.PREREQUIS || null,
        };
        if (this.editingCoursId) {
          await api(`cours.php?id=${this.editingCoursId}`, { method: 'PUT', body });
        } else {
          await api('cours.php', { method: 'POST', body });
        }
        this.coursForm = { ID_ENS: '', INTITULE: '', DESCRIPTION_: '', NBRE_CREDITS: 0, SEMESTRE: 1, NIVEAU: '', DEPARTEMENT: '', PREREQUIS: '' };
        this.editingCoursId = null;
        await this.loadAll();
      } catch (e) {
        alert(e.message || 'Erreur');
      }
    },
    editCours(c) {
      this.coursForm = {
        ID_ENS: c.ID_ENS,
        INTITULE: c.INTITULE || '',
        DESCRIPTION_: c.DESCRIPTION_ || '',
        NBRE_CREDITS: c.NBRE_CREDITS ?? 0,
        SEMESTRE: c.SEMESTRE ?? 1,
        NIVEAU: c.NIVEAU || '',
        DEPARTEMENT: c.DEPARTEMENT || '',
        PREREQUIS: c.PREREQUIS || '',
      };
      this.editingCoursId = c._id;
    },
    cancelEditCours() {
      this.coursForm = { ID_ENS: '', INTITULE: '', DESCRIPTION_: '', NBRE_CREDITS: 0, SEMESTRE: 1, NIVEAU: '', DEPARTEMENT: '', PREREQUIS: '' };
      this.editingCoursId = null;
    },
    async deleteCours(id) {
      if (!confirm('Supprimer ce cours ?')) return;
      try {
        await api(`cours.php?id=${id}`, { method: 'DELETE' });
        await this.loadAll();
      } catch (e) {
        alert(e.message || 'Erreur');
      }
    },
    // S_INSCRIRE (Inscriptions)
    async saveInscription() {
      const [idEtu, numCarte] = (this.inscriptionForm.etudiantKey || '').split('|');
      const [idCours, codeCours] = (this.inscriptionForm.coursKey || '').split('|');
      if (!idEtu || !numCarte || !idCours || !codeCours) {
        alert('Veuillez choisir un étudiant et un cours.');
        return;
      }
      try {
        await api('inscriptions.php', {
          method: 'POST',
          body: { ID_ETUDIANTS: Number(idEtu), NUM_CARTE: numCarte, ID_COURS: Number(idCours), CODE_COURS: Number(codeCours) },
        });
        this.inscriptionForm = { etudiantKey: '', coursKey: '' };
        await this.loadAll();
      } catch (e) {
        alert(e.message || 'Erreur');
      }
    },
    async deleteInscription(id) {
      if (!confirm('Supprimer cette inscription ?')) return;
      try {
        await api(`inscriptions.php?id=${id}`, { method: 'DELETE' });
        await this.loadAll();
      } catch (e) {
        alert(e.message || 'Erreur');
      }
    },
  },
}).mount('#app');
