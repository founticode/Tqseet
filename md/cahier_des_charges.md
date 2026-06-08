# CAHIER DES CHARGES : TQSEET

## 1. Contexte et Présentation du Projet
**Nom du Projet :** TQSEET  
**Secteur :** FinTech / E-commerce (BNPL - Buy Now, Pay Later)  
**Description :** TQSEET est une plateforme innovante de paiement fractionné destinée au marché marocain. Elle permet aux clients d'acheter des produits en ligne et de payer en 4 tranches (versements) sans frais ni intérêts, tout en offrant aux marchands locaux une solution clé en main pour augmenter leurs ventes avec des paiements garantis.

---

## 2. Public Cible (Les Acteurs)
1. **Les Clients (Consommateurs) :** Acheteurs finaux cherchant de la flexibilité financière et du pouvoir d'achat.
2. **Les Marchands (Boutiques/Vendeurs) :** Magasins souhaitant proposer le paiement en plusieurs fois pour augmenter leur taux de conversion et leur panier moyen.
3. **Les Administrateurs (Opérateurs TQSEET) :** Équipe interne chargée de la vérification KYC, de la gestion des risques et de la comptabilité.

---

## 3. Spécifications Fonctionnelles

### 3.1. Espace Client (User)
- **Inscription & Sécurité :** Authentification sécurisée via OTP (2FA).
- **Vérification d'Identité (KYC) :** Téléchargement de la Carte d'Identité Nationale (CIN) et justificatifs de revenus.
- **Scoring & Limite de Crédit :** Attribution automatique d'une ligne de crédit basée sur le salaire (Limite = Salaire mensuel * 1.5).
- **Achat & Paiement :** Achat direct avec division automatique du montant en 4 mensualités égales.
- **Gestion Financière :** Tableau de bord pour suivre l'historique des achats, l'échéancier des paiements, le solde restant et la carte bancaire.

### 3.2. Espace Marchand (Merchant)
- **Onboarding :** Création de compte professionnel avec les informations commerciales (Nom du magasin, Logo, RIB).
- **Gestion du Catalogue :** Ajout, modification et suppression de produits.
- **Tableau de Bord des Ventes :** Suivi en temps réel du volume de ventes (GMV) et des commandes.
- **Gestion des Règlements :** Suivi des reversements (Settlements) effectués par TQSEET vers le compte bancaire du marchand.
- **Liens de Paiement (Payment Links) :** Génération de factures dynamiques (liens de paiement) envoyées directement aux clients via WhatsApp ou Email.

### 3.3. Espace Administrateur (Control Tower)
- **Monitoring Global :** Suivi des métriques clés (Revenu net, GMV total, nombre d'utilisateurs actifs).
- **Centre de Vérification (Hub KYC) :** Validation ou rejet des profils clients (identités, salaires) et des dossiers d'ouverture de magasins marchands.
- **Payment Monitor :** Vue globale (Installment Wall) détaillée de l'ensemble des plans de financement en cours.
- **Comptabilité & Commissions :** Suivi des commissions prélevées sur chaque transaction et gestion des décaissements marchands.

---

## 4. Règles Métier & Logique Financière
- **Plan de Financement :** La transaction est toujours divisée en 4 versements (Initial, +30 jours, +60 jours, +90 jours).
- **Calcul de Commission :** La plateforme se rémunère en prélevant une commission fixe (en pourcentage) sur la vente côté marchand. Le client final paie exactement le prix affiché (0% d'intérêts).
- **Garde Fou (Credit Guard) :** Le système bloque l'achat lors du passage en caisse si le montant total dépasse le "Crédit Disponible" du client.
- **Paiement Séquentiel :** Le système verrouille les paiements futurs ; le client ne peut payer la mensualité N+1 que si la mensualité N est réglée.

---

## 5. Spécifications Techniques
- **Architecture :** Backend modulaire en PHP Natif (Architecture MVC allégée).
- **Base de Données :** MySQL (Structure relationnelle stricte avec clés étrangères).
- **Sécurité :** Requêtes SQL préparées, hachage `password_hash()`, protection des sessions, et ségrégation stricte des rôles (Role-Based Access Control).
- **Interface Utilisateur (UI/UX) :** HTML5, CSS3 Natif, design Responsive (Mobile-First), typographie Google Fonts premium (Outfit, Inter).

---

## 6. Évolutions Prévues (Roadmap)
- Intégration d'un moteur de traduction Multilingue (Français & Arabe avec support d'affichage RTL).
- Mise en place d'un système complet de gestion des retours et remboursements (Refunds & Debt Forgiveness).
