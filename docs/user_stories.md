# Documentation User Stories - Module Authentification

## User Stories

### 1. Inscription d'un utilisateur

**Story :** "En tant qu'utilisateur non connecté, je veux m'inscrire pour pouvoir accéder aux fonctionnalités réservées aux membres."

**Implémentation :**
- L'utilisateur accède au formulaire d'inscription via l'interface Vue.js
- Il saisit : pseudo (3-50 caractères), email valide, mot de passe (8+ caractères avec complexité)
- Le système valide côté client avec VeeValidate puis côté serveur
- Vérification de l'unicité du pseudo et de l'email en base de données MySQL
- Le mot de passe est hashé avec Symfony PasswordHasher (bcrypt/argon2i)
- Création de l'utilisateur avec `user_roles` = ["ROLE_USER"] et `user_is_verified` = false
- Génération d'un token de vérification stocké temporairement (Redis, 24h)
- Envoi d'un email de confirmation via Symfony Mailer
- Retour d'une réponse de succès sans données sensibles

**Routes API :**
- POST /api/auth/register

**Priorité :** Haute

---

### 2. Connexion d'un utilisateur

**Story :** "En tant qu'utilisateur inscrit, je veux me connecter pour pouvoir accéder à mon compte et utiliser l'application."

**Implémentation :**
- L'utilisateur saisit email/pseudo et mot de passe
- Validation des champs côté client et serveur
- Recherche de l'utilisateur par email ou pseudo
- Vérification du mot de passe avec PasswordHasher
- Vérification que le compte est vérifié (`user_is_verified` = true)
- Génération d'un JWT token avec payload contenant user_id, roles, exp
- Mise à jour de `user_last_login` avec la date/heure actuelle
- Retour du token JWT et des informations utilisateur (sans mot de passe)
- Stockage du token côté client (Pinia store) pour les requêtes suivantes

**Routes API :**
- POST /api/auth/login

**Priorité :** Haute

---

### 3. Vérification d'email après inscription

**Story :** "En tant qu'utilisateur nouvellement inscrit, je veux vérifier mon email pour pouvoir activer mon compte et me connecter."

**Implémentation :**
- L'utilisateur clique sur le lien de vérification reçu par email
- Extraction du token de vérification depuis l'URL
- Recherche du token en cache Redis
- Si token valide : récupération de l'user_id associé
- Mise à jour de `user_is_verified` = true en base de données
- Suppression du token de vérification du cache
- Redirection vers la page de connexion avec message de succès
- Si token invalide/expiré : affichage d'un message d'erreur avec possibilité de renvoyer un email

**Routes API :**
- GET /api/auth/verify-email/{token}

**Priorité :** Haute

---

### 4. Renvoi d'email de vérification

**Story :** "En tant qu'utilisateur non vérifié, je veux pouvoir demander un nouveau lien de vérification si le premier a expiré."

**Implémentation :**
- L'utilisateur saisit son email sur une page dédiée
- Recherche de l'utilisateur par email
- Vérification que le compte n'est pas déjà vérifié
- Limitation du nombre de renvois (max 3 par heure par IP/email)
- Génération d'un nouveau token de vérification (invalidation de l'ancien)
- Stockage du nouveau token en Redis avec expiration 24h
- Envoi du nouvel email de vérification
- Retour d'un message de succès générique (même si email inexistant, pour la sécurité)

**Routes API :**
- POST /api/auth/resend-verification

**Priorité :** Moyenne

---

### 5. Déconnexion d'un utilisateur

**Story :** "En tant qu'utilisateur connecté, je veux me déconnecter pour pouvoir sécuriser mon compte."

**Implémentation :**
- L'utilisateur clique sur le bouton de déconnexion
- Côté client : suppression du token JWT du store Pinia et du localStorage
- Optionnel côté serveur : blacklist du token JWT en Redis jusqu'à expiration
- Redirection vers la page d'accueil ou de connexion
- Nettoyage de toutes les données utilisateur du state management

**Routes API :**
- POST /api/auth/logout (optionnel, peut être géré uniquement côté client)

**Priorité :** Moyenne

---

### 6. Récupération du profil utilisateur connecté

**Story :** "En tant qu'utilisateur connecté, je veux récupérer les informations de mon profil pour pouvoir les afficher dans l'interface."

**Implémentation :**
- Extraction du user_id depuis le JWT token dans l'en-tête Authorization
- Validation du token JWT (signature, expiration)
- Récupération des données utilisateur depuis la base de données
- Retour des informations publiques (pas de mot de passe, pas d'informations sensibles)
- Utilisation pour afficher le profil, vérifier les permissions, etc.

**Routes API :**
- GET /api/auth/me

**Priorité :** Haute

---

### 7. Actualisation du token JWT

**Story :** "En tant qu'utilisateur connecté, je veux pouvoir actualiser mon token d'authentification automatiquement pour maintenir ma session active."

**Implémentation :**
- Vérification de la validité du token JWT actuel
- Si le token expire dans moins de X minutes, génération d'un nouveau token
- Nouveau token avec même payload mais nouvelle date d'expiration
- Retour du nouveau token pour mise à jour côté client
- Gestion automatique côté client via intercepteurs Axios

**Routes API :**
- POST /api/auth/refresh

**Priorité :** Moyenne

---

### 8. Demande de réinitialisation de mot de passe

**Story :** "En tant qu'utilisateur ayant oublié son mot de passe, je veux pouvoir demander une réinitialisation pour pouvoir retrouver l'accès à mon compte."

**Implémentation :**
- L'utilisateur saisit son email sur la page "Mot de passe oublié"
- Recherche de l'utilisateur par email
- Génération d'un token de réinitialisation sécurisé (crypto random)
- Stockage du token en Redis avec expiration courte (1h)
- Envoi d'un email avec lien de réinitialisation
- Limitation des demandes (max 3 par heure par IP/email)
- Retour d'un message générique de succès (même si email inexistant)

**Routes API :**
- POST /api/auth/forgot-password

**Priorité :** Haute

---

### 9. Réinitialisation du mot de passe

**Story :** "En tant qu'utilisateur ayant demandé une réinitialisation, je veux pouvoir définir un nouveau mot de passe via le lien reçu par email."

**Implémentation :**
- L'utilisateur accède au lien de réinitialisation avec le token
- Affichage d'un formulaire de nouveau mot de passe
- Validation du token de réinitialisation en Redis
- Validation du nouveau mot de passe (complexité, longueur)
- Hashage du nouveau mot de passe
- Mise à jour du mot de passe en base de données
- Suppression du token de réinitialisation du cache
- Invalidation de tous les tokens JWT existants (optionnel)
- Message de succès avec redirection vers la connexion

**Routes API :**
- POST /api/auth/reset-password

**Priorité :** Haute

---

## Règles métier identifiées

### Validation des données
- **Pseudo :** 3-50 caractères, alphanumérique + tirets/underscores, unique
- **Email :** Format valide, unique, domaines non blacklistés
- **Mot de passe :** 8+ caractères, au moins 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial

### Sécurité
- Hash des mots de passe avec Symfony PasswordHasher (bcrypt/argon2i)
- Limitation du taux de requêtes (rate limiting)
- Tokens de vérification/réinitialisation à usage unique avec expiration
- JWT avec durée de vie limitée (1h recommandé)

### Notifications
- Email de vérification obligatoire après inscription
- Email de réinitialisation avec lien sécurisé
- Messages de succès génériques pour éviter l'énumération d'emails

### États utilisateur
- `user_is_verified` doit être true pour permettre la connexion
- Tracking de `user_last_login` pour statistiques/sécurité
- Support des rôles Symfony via `user_roles` JSON

---

## Améliorations suggérées

### Sécurité avancée
1. **Authentification à deux facteurs (2FA)** avec TOTP/SMS
2. **Détection d'activités suspectes** (connexions multiples, géolocalisation)
3. **Blacklist de mots de passe courants**
4. **Captcha** sur inscription/connexions multiples échouées

### Expérience utilisateur
1. **Connexion via OAuth2** (Google, Discord, GitHub)
2. **Mémorisation de session** ("Se souvenir de moi")
3. **Mode invité** pour tester l'application
4. **Progressive Web App** avec notifications push

### Administration
1. **Gestion administrative des utilisateurs**
2. **Logs d'audit des authentifications**
3. **Statistiques d'inscription/connexion**
4. **Gestion des utilisateurs suspendus/bannis**


# User Stories - Module Profile Utilisateur

## Story 1 : Consulter la liste des utilisateurs

**Format :** "En tant qu'utilisateur authentifié, je veux consulter la liste des utilisateurs pour pouvoir découvrir d'autres joueurs et maîtres de jeu."

**Implémentation :**
- L'utilisateur doit être authentifié pour accéder à cette fonctionnalité
- Le système récupère la liste paginée des utilisateurs vérifiés (user_is_verified = true)
- Les données sensibles (email, mot de passe) ne sont pas exposées
- Possibilité de filtrer par pseudo ou de recherche textuelle
- Pagination avec limite configurable (défaut : 20 utilisateurs par page)
- Tri possible par date d'inscription, dernière connexion ou pseudo
- Seuls les profils publics sont visibles (les utilisateurs peuvent masquer leur profil)

**Routes API déduites :**
- GET /api/users

**Priorité :** Moyenne

---

## Story 2 : Consulter son propre profil

**Format :** "En tant qu'utilisateur authentifié, je veux consulter mon profil pour pouvoir voir mes informations personnelles."

**Implémentation :**
- L'utilisateur doit être authentifié
- Le système récupère toutes les informations du profil de l'utilisateur connecté
- Toutes les données personnelles sont visibles (y compris email)
- Affichage des statistiques personnelles (nombre de parties, date d'inscription, etc.)
- Vérification que l'utilisateur demande bien ses propres informations

**Routes API déduites :**
- GET /api/users/me

**Priorité :** Haute

---

## Story 3 : Consulter le profil d'un autre utilisateur

**Format :** "En tant qu'utilisateur authentifié, je veux consulter le profil d'un autre utilisateur pour pouvoir connaître ses informations publiques."

**Implémentation :**
- L'utilisateur doit être authentifié
- Le système récupère les informations publiques de l'utilisateur demandé
- Vérification que l'utilisateur cible existe et est vérifié
- Les données sensibles (email, rôles) ne sont pas exposées
- Affichage des statistiques publiques (date d'inscription, dernière connexion si publique)
- Retour d'erreur 404 si l'utilisateur n'existe pas ou n'est pas vérifié

**Routes API déduites :**
- GET /api/users/{id}

**Priorité :** Moyenne

---

## Story 4 : Modifier son profil

**Format :** "En tant qu'utilisateur authentifié, je veux modifier mon profil pour pouvoir mettre à jour mes informations personnelles."

**Implémentation :**
- L'utilisateur doit être authentifié
- Validation des champs modifiables : pseudo, email, avatar, timezone, langue
- Vérification de l'unicité du nouveau pseudo et email si modifiés
- Le pseudo doit contenir entre 3 et 50 caractères, sans caractères spéciaux
- L'email doit être valide et unique
- Si l'email est modifié, user_is_verified repasse à false et un email de vérification est envoyé
- L'avatar doit être une URL valide ou un fichier uploadé
- Timezone doit être une timezone PHP valide
- Langue doit être supportée par l'application
- Mise à jour du champ user_updated_at automatiquement

**Routes API déduites :**
- PUT /api/users/me
- PATCH /api/users/me

**Priorité :** Haute

---

## Story 5 : Changer son mot de passe

**Format :** "En tant qu'utilisateur authentifié, je veux changer mon mot de passe pour pouvoir sécuriser mon compte."

**Implémentation :**
- L'utilisateur doit être authentifié
- Vérification du mot de passe actuel avant changement
- Le nouveau mot de passe doit respecter la politique de sécurité :
  - Minimum 8 caractères
  - Au moins 1 majuscule, 1 minuscule, 1 chiffre
- Hashage du nouveau mot de passe avec l'algorithme Symfony (bcrypt/argon2i)
- Invalidation de toutes les sessions actives sauf la courante
- Envoi d'un email de notification de changement de mot de passe
- Mise à jour du champ user_updated_at

**Routes API déduites :**
- PUT /api/users/me/password

**Priorité :** Haute

---

## Story 6 : Supprimer son compte

**Format :** "En tant qu'utilisateur authentifié, je veux supprimer mon compte pour pouvoir quitter définitivement la plateforme."

**Implémentation :**
- L'utilisateur doit être authentifié
- Demande de confirmation avec saisie du mot de passe
- Vérification que l'utilisateur n'est pas maître de jeu d'une partie active
- Si l'utilisateur est MJ, proposition de transférer les parties ou les archiver
- Suppression en cascade des données liées (sessions, participations aux parties)
- Anonymisation des données si suppression impossible (messages, historiques)
- Envoi d'un email de confirmation de suppression
- Déconnexion immédiate et invalidation de toutes les sessions

**Routes API déduites :**
- DELETE /api/users/me

**Priorité :** Moyenne

---

## Story 7 : Upload d'avatar

**Format :** "En tant qu'utilisateur authentifié, je veux uploader une image d'avatar pour pouvoir personnaliser mon profil."

**Implémentation :**
- L'utilisateur doit être authentifié
- Validation du fichier uploadé :
  - Formats acceptés : JPG, PNG, WebP
  - Taille maximum : 2MB
  - Dimensions minimum : 100x100px, maximum : 1000x1000px
- Redimensionnement automatique si nécessaire
- Génération de plusieurs tailles (thumbnail, medium, full)
- Stockage sécurisé avec nom de fichier unique
- Suppression de l'ancien avatar si existant
- Mise à jour du champ user_avatar avec l'URL du nouvel avatar
- Retour de l'URL publique de l'avatar

**Routes API déduites :**
- POST /api/users/me/avatar

**Priorité :** Basse

---

## Story 8 : Administration - Consulter tous les utilisateurs

**Format :** "En tant qu'administrateur, je veux consulter tous les utilisateurs pour pouvoir gérer la plateforme."

**Implémentation :**
- L'utilisateur doit avoir le rôle ROLE_ADMIN
- Accès à tous les utilisateurs (vérifiés et non vérifiés)
- Affichage de toutes les informations (y compris sensibles)
- Pagination avancée avec filtres multiples
- Recherche par pseudo, email, date d'inscription
- Tri par tous les champs disponibles
- Export possible des données (CSV)
- Statistiques globales (total utilisateurs, nouveaux inscriptions, etc.)

**Routes API déduites :**
- GET /api/admin/users

**Priorité :** Basse

---

## Story 9 : Administration - Modifier un utilisateur

**Format :** "En tant qu'administrateur, je veux modifier n'importe quel utilisateur pour pouvoir gérer les comptes problématiques."

**Implémentation :**
- L'utilisateur doit avoir le rôle ROLE_ADMIN
- Possibilité de modifier tous les champs utilisateur
- Gestion des rôles (ajout/suppression ROLE_ADMIN, ROLE_USER)
- Possibilité de forcer la vérification (user_is_verified)
- Possibilité de verrouiller un compte temporairement
- Traçabilité des modifications administrateur
- Envoi d'email de notification à l'utilisateur concerné
- Protection contre la suppression accidentelle du dernier admin

**Routes API déduites :**
- PUT /api/admin/users/{id}
- PATCH /api/admin/users/{id}

**Priorité :** Basse

---

## Story 10 : Administration - Supprimer un utilisateur

**Format :** "En tant qu'administrateur, je veux supprimer un utilisateur pour pouvoir gérer les comptes problématiques ou inactifs."

**Implémentation :**
- L'utilisateur doit avoir le rôle ROLE_ADMIN
- Vérification que l'utilisateur à supprimer n'est pas le dernier admin
- Gestion des parties où l'utilisateur est MJ (transfert ou archivage)
- Suppression en cascade ou anonymisation selon la politique RGPD
- Traçabilité de la suppression administrative
- Envoi d'email de notification à l'utilisateur (si possible)
- Confirmation requise pour éviter les suppressions accidentelles

**Routes API déduites :**
- DELETE /api/admin/users/{id}

**Priorité :** Basse


# Documentation Module Parties (Games)

## User Stories

### 1. Créer une partie

**Story :** "En tant qu'utilisateur authentifié, je veux créer une partie pour pouvoir organiser une session de JDR avec d'autres joueurs."

**Implémentation :**
- L'utilisateur doit être authentifié (JWT valide)
- Saisie des informations de la partie : nom (obligatoire, 3-250 caractères), description (optionnelle), nombre max de joueurs (2-20, défaut 6)
- Configuration de la visibilité : publique ou privée
- Si privée : possibilité de définir un mot de passe
- Paramètres optionnels : règles maison, options de partie (JSON)
- Validation côté serveur de toutes les données
- Création de la partie avec `game_status` = "preparation" et `game_master_id` = utilisateur actuel
- Création automatique d'une entrée dans `game_player` avec `player_role` = "player" et `player_status` = "active" pour le MJ
- Génération d'un identifiant unique pour la partie
- Retour des détails de la partie créée avec son ID

**Routes API déduites :**
- POST /api/games

**Priorité :** Haute

---

### 2. Consulter la liste des parties

**Story :** "En tant qu'utilisateur authentifié, je veux consulter la liste des parties disponibles pour pouvoir rejoindre une partie existante."

**Implémentation :**
- L'utilisateur doit être authentifié
- Récupération des parties selon les critères :
  - Parties publiques visibles par tous
  - Parties privées uniquement si l'utilisateur est déjà membre
  - Mes parties (où je suis MJ ou joueur)
- Filtres disponibles : statut, public/privé, places disponibles
- Pagination avec limite configurable (défaut : 20)
- Tri par date de création, dernière activité, nombre de joueurs
- Pour chaque partie : infos de base + nombre de joueurs actuels/max
- Indication si l'utilisateur est déjà membre de la partie

**Routes API déduites :**
- GET /api/games
- GET /api/games/my (mes parties)

**Priorité :** Haute

---

### 3. Consulter le détail d'une partie

**Story :** "En tant qu'utilisateur authentifié, je veux consulter le détail d'une partie pour pouvoir voir ses informations complètes et décider si je veux la rejoindre."

**Implémentation :**
- L'utilisateur doit être authentifié
- Vérification des droits d'accès :
  - Si partie publique : accessible à tous
  - Si partie privée : accessible uniquement aux membres
- Récupération des informations complètes de la partie
- Liste des joueurs avec leurs rôles (masquage partiel si non-membre)
- Statistiques : nombre de sessions, durée moyenne, etc.
- Cartes associées si l'utilisateur est membre
- Retour erreur 403 si accès non autorisé

**Routes API déduites :**
- GET /api/games/{id}

**Priorité :** Haute

---

### 4. Modifier une partie

**Story :** "En tant que maître de jeu, je veux modifier ma partie pour pouvoir ajuster ses paramètres selon les besoins."

**Implémentation :**
- L'utilisateur doit être authentifié et être le MJ de la partie
- Possibilité de modifier : nom, description, nombre max de joueurs, visibilité, mot de passe, paramètres
- Validation que le nouveau max de joueurs >= nombre actuel de joueurs
- Si passage de publique à privée : possibilité de définir un mot de passe
- Si changement de statut : vérifications spécifiques
  - "active" : au moins 2 joueurs actifs
  - "archived" : confirmation requise, partie non modifiable après
- Notification aux joueurs des changements importants (optionnel)
- Mise à jour de `game_updated_at`

**Routes API déduites :**
- PUT /api/games/{id}
- PATCH /api/games/{id}

**Priorité :** Haute

---

### 5. Supprimer une partie

**Story :** "En tant que maître de jeu, je veux supprimer ma partie pour pouvoir nettoyer les parties abandonnées ou erronées."

**Implémentation :**
- L'utilisateur doit être authentifié et être le MJ de la partie
- Vérification du statut de la partie :
  - Si "active" : demande de confirmation supplémentaire
  - Si "archived" : suppression simple
- Suppression en cascade de toutes les données liées :
  - Joueurs (game_player)
  - Cartes (game_map)
  - Autres données associées
- Notification aux joueurs de la suppression (optionnel)
- Log de l'action pour traçabilité
- Retour de confirmation de suppression

**Routes API déduites :**
- DELETE /api/games/{id}

**Priorité :** Haute

---

### 6. Rejoindre une partie publique

**Story :** "En tant qu'utilisateur authentifié, je veux rejoindre une partie publique pour pouvoir participer à une session de JDR."

**Implémentation :**
- L'utilisateur doit être authentifié
- Vérification que la partie existe et est publique
- Vérification du statut de la partie (doit être "preparation" ou "active")
- Vérification qu'il reste des places disponibles
- Vérification que l'utilisateur n'est pas déjà membre
- Si mot de passe requis : validation du mot de passe
- Création d'une entrée dans `game_player` avec :
  - `player_role` = "player"
  - `player_status` = "active"
  - `joined_at` = maintenant
- Notification au MJ de l'arrivée du nouveau joueur
- Retour des détails de la partie mise à jour

**Routes API déduites :**
- POST /api/games/{id}/join

**Priorité :** Haute

---

### 7. Inviter un joueur à une partie

**Story :** "En tant que maître de jeu, je veux inviter des joueurs à ma partie pour pouvoir constituer mon groupe de jeu."

**Implémentation :**
- L'utilisateur doit être authentifié et être le MJ ou co-MJ
- Recherche de l'utilisateur à inviter par ID ou pseudo
- Vérification que l'utilisateur existe et est vérifié
- Vérification qu'il n'est pas déjà membre ou invité
- Vérification qu'il reste des places disponibles
- Création d'une entrée dans `game_player` avec :
  - `player_role` = "player"
  - `player_status` = "invited"
- Envoi d'une notification/email à l'utilisateur invité
- Possibilité d'inviter plusieurs joueurs en une fois
- Retour de la liste des invitations créées

**Routes API déduites :**
- POST /api/games/{id}/invite

**Priorité :** Haute

---

### 8. Répondre à une invitation

**Story :** "En tant qu'utilisateur invité, je veux accepter ou refuser une invitation pour pouvoir gérer mes participations aux parties."

**Implémentation :**
- L'utilisateur doit être authentifié et avoir une invitation en attente
- Vérification de l'invitation (existe, statut "invited")
- Si acceptation :
  - Mise à jour `player_status` = "active"
  - Mise à jour `joined_at` = maintenant
  - Notification au MJ
- Si refus :
  - Suppression de l'entrée dans `game_player`
  - Notification au MJ du refus
- Retour du statut mis à jour ou confirmation de suppression

**Routes API déduites :**
- PUT /api/games/{id}/invitation (accepter/refuser)
- POST /api/games/{id}/accept-invitation
- POST /api/games/{id}/decline-invitation

**Priorité :** Haute

---

### 9. Quitter une partie

**Story :** "En tant que joueur, je veux quitter une partie pour pouvoir me désengager d'une partie qui ne me convient plus."

**Implémentation :**
- L'utilisateur doit être authentifié et être membre de la partie
- Vérification que l'utilisateur n'est pas le MJ principal
- Si l'utilisateur est co-MJ et seul avec le MJ : avertissement
- Suppression de l'entrée dans `game_player`
- Si la partie devient vide (seulement le MJ) : passage en statut "paused"
- Notification au MJ du départ du joueur
- Mise à jour des statistiques de la partie
- Retour de confirmation

**Routes API déduites :**
- DELETE /api/games/{id}/leave
- POST /api/games/{id}/leave

**Priorité :** Moyenne

---

### 10. Gérer les joueurs d'une partie

**Story :** "En tant que maître de jeu, je veux gérer les joueurs de ma partie pour pouvoir maintenir un bon environnement de jeu."

**Implémentation :**
- L'utilisateur doit être authentifié et être le MJ de la partie
- Actions possibles sur un joueur :
  - Promouvoir en co-MJ (`player_role` = "co_gm")
  - Rétrograder en joueur simple
  - Passer en spectateur (`player_role` = "spectator")
  - Bannir de la partie (`player_status` = "banned")
  - Retirer de la partie (suppression)
- Validation des changements de rôle
- Impossibilité de se bannir/retirer soi-même
- Notification au joueur concerné
- Log des actions pour traçabilité

**Routes API déduites :**
- PUT /api/games/{id}/players/{playerId}
- DELETE /api/games/{id}/players/{playerId}

**Priorité :** Moyenne

---

### 11. Transférer la maîtrise d'une partie

**Story :** "En tant que maître de jeu, je veux transférer la maîtrise de ma partie pour pouvoir passer le relais à un autre joueur."

**Implémentation :**
- L'utilisateur doit être authentifié et être le MJ actuel
- Sélection du nouveau MJ parmi les joueurs actifs
- Confirmation de l'action (irréversible)
- Mise à jour de `game_master_id` avec le nouveau MJ
- L'ancien MJ devient joueur normal ou quitte la partie (selon choix)
- Mise à jour des rôles dans `game_player`
- Notification à tous les joueurs du changement
- Retour des détails de la partie mise à jour

**Routes API déduites :**
- POST /api/games/{id}/transfer-ownership

**Priorité :** Basse

---

### 12. Changer le statut d'une partie

**Story :** "En tant que maître de jeu, je veux changer le statut de ma partie pour pouvoir indiquer son état actuel aux joueurs."

**Implémentation :**
- L'utilisateur doit être authentifié et être le MJ ou co-MJ
- Transitions de statut autorisées :
  - preparation → active (minimum 2 joueurs)
  - active → paused
  - paused → active
  - active/paused → archived (confirmation requise)
- Validation des pré-conditions pour chaque transition
- Mise à jour de `game_status`
- Notification aux joueurs du changement
- Si archivage : partie devient read-only
- Retour du nouveau statut

**Routes API déduites :**
- PATCH /api/games/{id}/status

**Priorité :** Moyenne

---

## Règles métier identifiées

### Gestion des parties
- **Nom de partie :** 3-250 caractères, obligatoire
- **Nombre de joueurs :** Entre 2 et 20, défaut 6
- **Statuts possibles :** preparation, active, paused, archived
- **Partie archivée :** Lecture seule, aucune modification possible

### Rôles et permissions
- **Maître de jeu (MJ) :** Tous les droits sur la partie
- **Co-MJ :** Peut inviter, gérer les joueurs (sauf le MJ)
- **Joueur :** Participe à la partie
- **Spectateur :** Observe sans participer
- **Invité :** En attente de réponse à l'invitation
- **Banni :** Ne peut plus rejoindre la partie

### Sécurité et accès
- **Parties publiques :** Visibles par tous, rejoignables librement
- **Parties privées :** Accès sur invitation ou avec mot de passe
- **Suppression :** Seul le MJ peut supprimer une partie
- **Transfert :** Le MJ peut transférer la maîtrise

### Notifications
- Invitation reçue
- Joueur rejoint/quitte la partie
- Changement de statut de la partie
- Promotion/rétrogradation de rôle

---

## Améliorations suggérées

### Fonctionnalités avancées
1. **Templates de parties** pour création rapide
2. **Système de tags** pour catégoriser les parties
3. **File d'attente** pour parties complètes
4. **Partie en mode "campagne"** avec sessions liées
5. **Intégration Discord** pour notifications

### Gestion sociale
1. **Système de réputation** pour joueurs et MJ
2. **Blacklist personnelle** d'utilisateurs
3. **Recommandations** de parties basées sur l'historique
4. **Groupes de joueurs** récurrents

### Outils de jeu
1. **Planification de sessions** avec calendrier
2. **Notes de partie** partagées
3. **Bibliothèque de ressources** par partie
4. **Système de votes** pour décisions de groupe


# Documentation - Module Actions de Partie

## LANCERS DE DÉS

### Story 1: Lancer de dés basique
**Format:** "En tant que joueur actif dans une partie, je veux pouvoir lancer des dés avec une expression simple (ex: 2d6+3) pour pouvoir résoudre des actions de jeu."

**Implémentation:**
- Le joueur saisit une expression de dés dans l'interface ou clique sur des dés prédéfinis
- Le système parse l'expression (validation regex: `/^(\d+d\d+([+-]\d+)?)+$/`)
- Génération des résultats aléatoires pour chaque dé
- Calcul du total avec les modificateurs
- Stockage en base de données avec structure JSON détaillée
- Broadcast WebSocket à tous les joueurs de la partie
- Affichage dans le chat avec animation des dés
- Restrictions: Utilisateur doit être joueur actif dans la partie

**Route API:** POST /api/games/{gameId}/dice/roll  
**Priorité:** Haute

---

### Story 2: Lancer de dés privé
**Format:** "En tant que MJ, je veux pouvoir effectuer des lancers de dés privés pour pouvoir gérer les actions secrètes des PNJ et monstres."

**Implémentation:**
- Le MJ active l'option "lancer privé" avant de lancer
- Seul le MJ voit le résultat dans son interface
- Le système stocke le lancer avec `roll_is_private = true`
- Aucun broadcast WebSocket aux joueurs
- Possibilité de révéler le lancer ultérieurement
- Validation: Seuls MJ et co-GM peuvent faire des lancers privés
- Log d'audit pour traçabilité

**Route API:** POST /api/games/{gameId}/dice/roll-private  
**Priorité:** Haute

---

### Story 3: Historique des lancers
**Format:** "En tant que joueur, je veux consulter l'historique des lancers de dés de la partie pour pouvoir vérifier les résultats précédents."

**Implémentation:**
- Récupération paginée des lancers depuis la base de données
- Filtrage par: joueur, personnage, type de lancer, période
- Exclusion des lancers privés sauf pour le MJ
- Cache Redis pour optimisation (TTL: 5 minutes)
- Tri par date décroissante par défaut
- Export possible en CSV pour le MJ

**Route API:** GET /api/games/{gameId}/dice/history  
**Priorité:** Moyenne

---

### Story 4: Lancers avec avantage/désavantage
**Format:** "En tant que joueur, je veux pouvoir effectuer des lancers avec avantage ou désavantage pour pouvoir appliquer les règles de D&D 5e."

**Implémentation:**
- Option avantage/désavantage dans l'interface de lancer
- Pour un d20: lance 2d20 et garde le plus haut (avantage) ou plus bas (désavantage)
- Affichage des deux résultats avec indication visuelle du résultat retenu
- Stockage des deux valeurs dans `roll_result` JSON
- Support des lancers multiples (ex: 2d20kh1 pour avantage)
- Intégration avec les règles de personnage

**Route API:** POST /api/games/{gameId}/dice/roll-advantage  
**Priorité:** Moyenne

---

## GESTION DES TOKENS SUR LA CARTE

### Story 5: Déplacer un token
**Format:** "En tant que joueur, je veux pouvoir déplacer mon token sur la carte pour pouvoir représenter les mouvements de mon personnage."

**Implémentation:**
- Drag & drop du token avec preview du déplacement
- Validation des permissions (joueur = propriétaire ou MJ)
- Vérification token non verrouillé
- Calcul de la distance parcourue selon le type de grille
- Mise à jour des coordonnées en base de données
- Broadcast WebSocket avec animation fluide
- Historique des positions pour annulation possible
- Snap-to-grid automatique
- Affichage du chemin parcouru pendant le déplacement

**Route API:** PATCH /api/games/{gameId}/maps/{mapId}/tokens/{tokenId}/position  
**Priorité:** Haute

---

### Story 6: Créer un token
**Format:** "En tant que MJ, je veux pouvoir créer de nouveaux tokens sur la carte pour pouvoir ajouter des PNJ, monstres ou objets durant la partie."

**Implémentation:**
- Interface de création avec sélection du type
- Upload d'image ou sélection depuis bibliothèque
- Attribution automatique d'un nom unique
- Placement initial aux coordonnées cliquées
- Définition de la taille et rotation
- Attribution optionnelle à un joueur
- Validation des droits MJ/co-GM
- Broadcast de l'apparition du token
- Présets pour tokens communs (gobelin, coffre, etc.)

**Route API:** POST /api/games/{gameId}/maps/{mapId}/tokens  
**Priorité:** Haute

---

### Story 7: Modifier les propriétés d'un token
**Format:** "En tant que MJ, je veux pouvoir modifier les propriétés visuelles des tokens pour pouvoir ajuster leur apparence et état durant la partie."

**Implémentation:**
- Interface de propriétés avec preview en temps réel
- Modification: taille, rotation, visibilité, verrouillage
- Changement de layer (background, objects, tokens, effects)
- Application d'effets visuels (auras, conditions)
- Sauvegarde des paramètres dans `token_settings`
- Validation des permissions MJ/co-GM
- Broadcast des modifications
- Templates de conditions prédéfinies (poisoned, stunned, etc.)

**Route API:** PATCH /api/games/{gameId}/maps/{mapId}/tokens/{tokenId}  
**Priorité:** Moyenne

---

### Story 8: Supprimer un token
**Format:** "En tant que MJ, je veux pouvoir supprimer des tokens de la carte pour pouvoir retirer les éléments qui ne sont plus nécessaires."

**Implémentation:**
- Confirmation avant suppression
- Soft delete avec archivage possible
- Libération des ressources associées
- Notification aux joueurs concernés
- Validation stricte des droits MJ
- Log d'audit de la suppression
- Option de suppression en masse

**Route API:** DELETE /api/games/{gameId}/maps/{mapId}/tokens/{tokenId}  
**Priorité:** Moyenne

---

### Story 9: Mesurer les distances
**Format:** "En tant que joueur, je veux pouvoir mesurer les distances sur la carte pour pouvoir planifier mes déplacements et attaques."

**Implémentation:**
- Outil de mesure activable par raccourci clavier
- Affichage en temps réel de la distance en cases/mètres
- Support des diagonales selon les règles choisies
- Mesure point à point ou chemin complet
- Partage optionnel de la mesure avec les autres joueurs
- Calcul automatique selon le type de grille (carré/hexagonal)

**Route API:** Non applicable (côté client uniquement)  
**Priorité:** Moyenne


## SYSTÈME DE CHAT

### Story 11: Envoyer un message public
**Format:** "En tant que joueur, je veux pouvoir envoyer des messages dans le chat de partie pour pouvoir communiquer avec les autres participants."

**Implémentation:**
- Saisie du message avec limite de 1000 caractères
- Détection automatique des commandes (/roll, /emote, etc.)
- Sanitization HTML pour sécurité
- Attribution au joueur ou personnage (mode IC/OOC)
- Stockage en base avec horodatage
- Broadcast WebSocket immédiat
- Support des emojis et formatage markdown basique
- Mention d'autres joueurs avec @pseudo
- Liens cliquables automatiques

**Route API:** POST /api/games/{gameId}/messages  
**Priorité:** Haute

---

### Story 12: Envoyer un whisper
**Format:** "En tant que joueur, je veux pouvoir envoyer des messages privés à un autre joueur pour pouvoir avoir des conversations secrètes."

**Implémentation:**
- Sélection du destinataire dans la liste des joueurs
- Préfixe visuel distinctif pour les whispers
- Stockage avec `message_target_user_id`
- WebSocket uniquement vers expéditeur et destinataire
- Notification visuelle pour le destinataire
- Le MJ peut voir tous les whispers (option configurable)
- Historique des whispers séparé
- Notification sonore optionnelle

**Route API:** POST /api/games/{gameId}/messages/whisper  
**Priorité:** Haute

---

### Story 13: Parler en tant que personnage
**Format:** "En tant que joueur, je veux pouvoir envoyer des messages en tant que mon personnage pour pouvoir faire du roleplay immersif."

**Implémentation:**
- Toggle IC (In Character) / OOC (Out Of Character)
- En mode IC, attribution au personnage actif
- Affichage du nom et avatar du personnage
- Coloration différente pour distinction visuelle
- Flag `message_is_ic = true` en base
- Option de voix/accent personnalisé (stocké dans settings)
- Bulles de dialogue au-dessus des tokens (optionnel)
- Templates de phrases types par personnage

**Route API:** POST /api/games/{gameId}/messages/ic  
**Priorité:** Moyenne

---

### Story 14: Actions et emotes
**Format:** "En tant que joueur, je veux pouvoir décrire des actions narratives pour pouvoir enrichir le roleplay."

**Implémentation:**
- Commande `/me` ou `/emote` ou bouton dédié
- Formatage italique automatique
- Préfixe avec le nom du personnage/joueur
- Type de message `emote` en base
- Possibilité d'inclure des jets de dés inline
- Support des actions longues (jusqu'à 500 caractères)
- Bibliothèque d'emotes prédéfinies
- Animation visuelle sur le token associé

**Route API:** POST /api/games/{gameId}/messages/emote  
**Priorité:** Moyenne

---

### Story 15: Historique des messages
**Format:** "En tant que joueur, je veux pouvoir consulter l'historique du chat pour pouvoir retrouver des informations importantes."

**Implémentation:**
- Chargement paginé (50 messages par page)
- Scroll infini ou bouton "charger plus"
- Recherche par mots-clés
- Filtrage par type de message
- Export possible en format texte pour le MJ
- Conservation 30 jours après fin de partie
- Marque-pages pour messages importants
- Jump to date/time

**Route API:** GET /api/games/{gameId}/messages  
**Priorité:** Basse

---

### Story 16: Commandes de chat
**Format:** "En tant que joueur, je veux pouvoir utiliser des commandes dans le chat pour pouvoir effectuer rapidement des actions courantes."

**Implémentation:**
- Parsing des commandes commençant par `/`
- Commandes supportées: /roll, /whisper, /me, /ooc, /ic, /help
- Auto-complétion des commandes et paramètres
- Validation des paramètres avant exécution
- Messages d'erreur clairs
- Historique des commandes avec flèche haut/bas
- Alias personnalisables par utilisateur

**Route API:** Traitement côté client puis appel API appropriée  
**Priorité:** Basse

---

## ACTIONS TRANSVERSALES

### Story 17: Annuler la dernière action
**Format:** "En tant que joueur, je veux pouvoir annuler ma dernière action pour pouvoir corriger une erreur."

**Implémentation:**
- Système d'historique des actions (5 dernières)
- Bouton undo avec raccourci clavier (Ctrl+Z)
- Validation de la fenêtre temporelle (30 secondes)
- Restauration de l'état précédent
- Notification aux autres joueurs
- Non applicable aux lancers de dés (intégrité du jeu)
- Redo possible (Ctrl+Y)
- Log des annulations

**Route API:** POST /api/games/{gameId}/actions/undo  
**Priorité:** Basse

---

### Story 18: Mode initiative/combat
**Format:** "En tant que MJ, je veux pouvoir activer un mode combat avec suivi d'initiative pour pouvoir gérer les combats de manière structurée."

**Implémentation:**
- Bouton d'activation du mode combat
- Fenêtre de gestion d'initiative
- Lancer automatique d'initiative pour tous les participants
- Ordre de passage visible par tous
- Indicateur du tour actuel
- Timer optionnel par tour
- Actions rapides (attaque, déplacement, fin de tour)
- Suivi des points de vie sur les tokens
- Log automatique des actions de combat

**Route API:** POST /api/games/{gameId}/combat/start  
**Priorité:** Basse

---

### Story 19: Templates d'effets de zone
**Format:** "En tant que joueur, je veux pouvoir placer des templates d'effets de zone sur la carte pour pouvoir visualiser les sorts et capacités."

**Implémentation:**
- Bibliothèque de templates (cercle, cône, ligne, carré)
- Placement et rotation sur la carte
- Tailles configurables selon les règles
- Transparence et couleurs personnalisables
- Détection automatique des tokens affectés
- Durée limitée avec compte à rebours
- Superposition de plusieurs effets
- Sauvegarde comme effet persistant ou temporaire

**Route API:** POST /api/games/{gameId}/maps/{mapId}/effects  
**Priorité:** Basse

---

### Story 20: Notes et annotations sur la carte
**Format:** "En tant que MJ, je veux pouvoir ajouter des notes et annotations sur la carte pour pouvoir préparer et enrichir mes scènes."

**Implémentation:**
- Outil d'annotation avec texte, formes, flèches
- Notes privées (MJ uniquement) ou publiques
- Épingles cliquables avec contenu riche
- Catégorisation des notes (lieu, PNJ, objet, piège)
- Révélation conditionnelle (perception, investigation)
- Liens vers fiches de personnages ou documents
- Export/import des annotations
- Layers d'annotations séparés

**Route API:** POST /api/games/{gameId}/maps/{mapId}/annotations  
**Priorité:** Basse

---

## RÈGLES MÉTIER SPÉCIFIQUES

### Règles de validation
- **Lancers de dés:** Expression limitée à 20 dés maximum, valeurs entre d2 et d100
- **Tokens:** Maximum 100 tokens par carte, taille entre 0.5 et 5.0
- **Messages:** Maximum 1000 caractères (500 pour emotes), anti-spam 1 message/seconde
- **Déplacements:** Validation selon les règles de mouvement du système de jeu
- **Permissions:** Strict respect de la hiérarchie MJ > co-GM > joueur > spectateur

### Règles de sécurité
- Sanitization de tous les inputs utilisateur
- Validation des permissions à chaque action
- Rate limiting sur toutes les routes (100 req/min par user)
- Logs d'audit pour actions sensibles
- Chiffrement des données sensibles en base

### Règles de performance
- Cache Redis pour données fréquemment consultées
- Pagination obligatoire pour les listes
- Compression WebSocket pour broadcasts
- Lazy loading des ressources lourdes (images)
- Batch updates pour modifications multiples

---

## SUGGESTIONS D'AMÉLIORATIONS

### Court terme
1. **Macros de dés:** Sauvegarder des lancers fréquents
2. **Sons d'ambiance:** Intégration audio pour immersion
3. **Conditions visuelles:** Icônes de statut sur les tokens
4. **Quick bars:** Barres d'actions rapides personnalisables

### Moyen terme
1. **Vision dynamique:** Calcul de ligne de vue en temps réel
2. **Météo dynamique:** Effets visuels et mécaniques
3. **Journal de quête:** Suivi collaboratif des objectifs
4. **Intégration Discord:** Bot pour notifications

### Long terme
1. **IA pour PNJ:** Génération de dialogues contextuels
2. **Génération procédurale:** Cartes et donjons aléatoires
3. **Mode spectateur:** Streaming de parties publiques
4. **Marketplace:** Partage de contenus créés par la communauté