🎓 Unilearn — Plateforme d'Apprentissage Intelligente (Projet PI Java & Web)

<!-- CI workflow test trigger -->

UniLearn est un projet universitaire réalisé dans le cadre du module Projet Intégré – Développement Web Java.

Notre objectif est de concevoir une plateforme e-learning moderne, inspirée de systèmes comme Blackboard, Moodle et edX — mais pensée pour répondre aux besoins réels des étudiants : personnalisation de l’apprentissage, recommandations intelligentes, quiz, et même une place de marché de services étudiants.

Le projet est développé par une équipe de 5 étudiants ingénieurs, chacun responsable d’un module principal, tout en partageant la même base de données et la même architecture globale.

🚀 Vision du Projet

UniLearn a pour ambition de :

Aider les étudiants à apprendre via des cours, leçons et quiz.

Adapter les parcours pédagogiques grâce à des recommandations personnalisées (vidéo, texte, etc.).

Proposer des tableaux de bord pour étudiants et enseignants.

Délivrer des certifications après validation des cours.

Intégrer une assistance basée sur l’IA pour des fonctionnalités avancées.

Mettre en place une marketplace étudiante où les apprenants peuvent proposer des services (rédaction de CV, tutorat, micro-services) et être rémunérés.

La plateforme est multi-plateforme :

🖥️ Application Java

🌐 Application Web

Les deux versions utilisent la même base de données centrale et communiquent via des API bien définies.

🧠 Organisation de l’Équipe

Afin d’équilibrer la charge de travail et de garder une architecture propre, le système est découpé en cinq modules principaux :

👤 1) Authentification & Utilisateurs

Entités : User, Role
Fonctionnalités :

Gestion des utilisateurs (CRUD)

Gestion des rôles (CRUD)

Authentification sécurisée

Tokens JWT

Contrôle d’accès

Focus : sécurité, autorisation, gestion des identités.

📚 2) Gestion des Cours & Recommandation

Entités : Course, Lesson, Recommendation
Fonctionnalités :

CRUD des cours

CRUD des leçons

CRUD des recommandations

Personnalisation des parcours

Gestion des prérequis

Focus : pédagogie, relations entre cours, suggestions intelligentes.

📝 3) Quiz & Évaluation

Entités : Quiz, Question
Fonctionnalités :

CRUD des quiz

CRUD des questions

Système de scoring

Historique des tentatives

Suivi des performances

Focus : logique d’évaluation et mesure des acquis.

📅 4) Réservation & Séances en Ligne

Entités : Booking, Session, Teacher
Fonctionnalités :

CRUD des réservations

CRUD des sessions

Planification

Organisation des séances en ligne

Gestion des disponibilités

Focus : planning, réservation, enseignement en temps réel.

💼 5) Marketplace & Freelance Étudiant

Entités : Product, Order, Job, Student
Fonctionnalités :

CRUD des produits

CRUD des commandes

CRUD des missions

Marketplace de services étudiants

Paiements entre utilisateurs

Focus : place de marché, freelancing, transactions.

🏗️ Stack Technique

Backend : Java / Spring Boot

Frontend Web : React / Vue / Symfony (selon le sprint)

Desktop : JavaFX

Base de données : MySQL / PostgreSQL (partagée par tous les modules)

Architecture : MVC + DAO + couches Service

Sécurité : JWT & rôles

Méthodologie : Agile Scrum

Déploiement : architecture distribuée (serveur applicatif + serveur BD)

🔄 Méthode de Développement

Le projet suit Scrum avec plusieurs sprints :

Sprint 0 : analyse, UML, architecture, backlog.

Sprint 1 : développement Java.

Sprint 2 : développement Web & déploiement.

Phase finale : intégration, démonstration et soutenance.

Chaque sprint comprend :

Planification

Estimation des tâches

Implémentation

Tests

Revue & présentation

🎯 Ce que le Projet Met en Valeur

Ce projet ne se limite pas au code.

Il démontre :

Travail en équipe

Discipline en génie logiciel

Modélisation de bases de données

Conception d’API

Sécurité

Déploiement

Réflexion UI/UX

Logique métier

Compétences en présentation

👥 Esprit d’Équipe

UniLearn est construit de manière collaborative.
Chaque membre possède son module, mais l’intégration globale reste une priorité absolue.

« Développer séparément. Penser globalement. Intégrer parfaitement. »
