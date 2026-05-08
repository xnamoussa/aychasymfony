# Smart Personalization System (Local AI) - Documentation PIDEV

Ce module ajoute une couche d'intelligence artificielle locale à votre projet Symfony sans dépendances externes (Privacy-First).

## 🚀 Fonctionnalités IA

### 1. Smart Recommendation Engine
- **Analyse Sémantique** : Le système analyse les titres et descriptions des événements pour en extraire des catégories et des tags.
- **Similarité Profil** : Compare les tags des événements avec les préférences historiques de l'utilisateur (Progressive Learning).
- **Scoring Hybride** : Mélange le score de similarité (70%) avec le score de popularité (30%) pour proposer les meilleurs événements.

### 2. Popularité Dynamique (Dynamic Popularity)
Le système calcule en temps réel un score basé sur :
- Le nombre de vues (interactif).
- Le nombre de participants réels.
- La récence de l'événement.
- **Labels générés** : `Trending`, `High popularity`, `Medium popularity`, `Low popularity`.

### 3. Apprentissage Progressif (Progressive Learning)
Chaque interaction (vue d'un événement) met à jour les préférences de l'utilisateur stockées en session (pour respecter les contraintes de base de données non-destructives). Plus l'utilisateur consulte un type d'événement, plus les recommandations s'affinent.

## 🛠️ Intégration Technique

- **Service** : `src/Service/SmartAIService.php`
- **Controllers** : Injection dans `EvenementController` et `ParticipationController`.
- **Frontend** : Intégration Twig avec badges dynamiques et section de recommandations premium.

## 📊 Données d'Exemple

| Événement | Mots-clés détectés | Catégorie IA | Popularité |
|-----------|--------------------|--------------|------------|
| Tech Conference | ai, cloud, dev | technology | Trending |
| Jazz Night | concert, music | music | High |
| Marathon | sport, course | sport | Medium |

## 🎓 Soutenance PIDEV (Arguments Professionnels)

> "Pour ce projet, j'ai implémenté un **Smart Personalization System** basé sur une architecture de services Symfony. Plutôt que de dépendre d'API cloud coûteuses et intrusives pour la vie privée, j'ai développé un moteur d'analyse sémantique local capable de classer les événements et de prédire les intérêts des utilisateurs en temps réel. Ce système utilise des algorithmes de pondération pour la popularité et le calcul de similarité Jaccard-like, offrant ainsi une expérience utilisateur premium, fluide et sécurisée, tout en démontrant une maîtrise avancée de la logique métier complexe et des services Symfony."

## ✅ Résultat Attendu
1. Sur la page `/evenement`, une section **Smart Recommendations** apparaît en haut.
2. Chaque carte d'événement affiche un badge **IA Insights** (ex: 🔥 Trending) et des tags générés (ex: #tech).
3. Sur la page `/participations`, le bouton **NOUVELLE INSCRIPTION** est désormais visible pour tous les utilisateurs.
4. Le système apprend de vos clics : cliquez sur un événement de type "Tech", rechargez la page, et voyez les recommandations s'adapter.
