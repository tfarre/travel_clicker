# 🎮 Travel Clicker - Game Design Document (MVP)

## Concept

**Travel Clicker** est un **idle game** inspiré de Cookie Clicker, où le joueur développe une **marketplace de voyages**.

L'objectif : attirer des visiteurs, générer des ventes réparties entre différentes **verticales voyage**, et réinvestir les commissions pour faire croître son business.

---

## 🎯 MVP Scope

### User Flow

```
┌─────────────────────────────────────────────────────────────┐
│  1. Landing Page                                            │
│     └── Bouton "Jouer" (pas de login pour le MVP)           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Game Page (Single Page)                                 │
│     └── Tout le jeu sur une seule page                      │
│         ├── Zone de clic (génère des visiteurs)             │
│         ├── Dashboard (stats, argent, répartition marché)   │
│         └── Shop (Marketing & Verticales Voyage)            │
└─────────────────────────────────────────────────────────────┘
```

### Core Features (MVP)

| Feature | Description | Priorité |
|---------|-------------|----------|
| **Click Button** | Génère 1 visiteur par clic | P0 |
| **Passive Visitors** | Achat d'options marketing pour visiteurs automatiques | P0 |
| **Sales Funnel** | Visiteurs → Acheteurs (conversion) → Répartition par verticale | P0 |
| **Commission System** | Gain = commission × Σ(ventes par verticale) | P0 |
| **Verticales Voyage** | Débloquer/améliorer des catégories de voyage (prix + marge) | P0 |
| **Persistence** | Sauvegarde locale (localStorage) | P1 |

---

## 💰 Game Loop

### Boucle Principale - "Entonnoir de Vente"

```
     ┌────────────────────────────────────────────────────────┐
     │                                                        │
     ▼                                                        │
┌─────────┐    ┌───────────┐    ┌───────────┐    ┌─────────┐ │
│  CLIC   │───▶│ VISITEURS │───▶│ ACHETEURS │───▶│ VENTES  │─┘
│(manuel) │    │           │    │(conversion)│    │(par vert.)
└─────────┘    └───────────┘    └───────────┘    └─────────┘
     ▲              ▲                                  │
     │              │                                  │
     │         ┌────┴────┐                            │
     │         │MARKETING│◀───────────────────────────┤
     │         │(passif) │      Investissement        │
     │         └─────────┘                            │
     │                                                │
     │         ┌───────────────────┐                  │
     └─────────│ VERTICALES VOYAGE │◀─────────────────┘
               │(répartition marché)│   Investissement
               └───────────────────┘
```

### Système de Verticales

Les **verticales** représentent les catégories de voyage vendues. Chaque verticale a :
- **Attractivité** : Part de marché relative (poids dans la distribution des acheteurs)
- **Prix de base** : Valeur de vente initiale
- **Facteur de marge** : Multiplicateur de prix par niveau
- **Coût de déblocage** : Prix pour activer la verticale

```
┌─────────────────────────────────────────────────────────────┐
│  RÉPARTITION DES ACHETEURS (Market Share)                   │
│                                                             │
│  Total Attractivité = Σ(attractivité des verticales actives)│
│                                                             │
│  Part de marché = attractivité_verticale / total_attractivité│
│                                                             │
│  Exemple avec 2 verticales actives :                        │
│  ├── Week-end France (attr: 100) → 100/130 = 77%           │
│  └── City Break Europe (attr: 30) → 30/130 = 23%           │
└─────────────────────────────────────────────────────────────┘
```

### Formules de Base

| Métrique | Formule | Valeur Initiale |
|----------|---------|-----------------|
| **Visiteurs par clic** | `visitorsPerClick` | 1 |
| **Visiteurs passifs** | `Σ(marketing.production)` | 0/sec |
| **Seuil de vente** | Tous les `X` visiteurs | 100 visiteurs |
| **Taux de conversion** | `conversionRate` | 10% (0.10) |
| **Part de marché** | `attractivité / Σ(attractivités)` | Variable |
| **Prix verticale** | `basePrice × marginFactor^(level-1)` | Variable |
| **Commission** | `baseCommissionRate` | 10% (0.10) |
| **Gain par lot** | `commission × Σ(ventes × prix)` | Variable |

### Exemple de Calcul

```
Lot de 100 visiteurs → Conversion 10% → 10 acheteurs

Verticales actives :
├── Week-end France (attr: 100, prix: 150€, niveau 2)
│   └── Part: 77% → 7.7 ventes × 150€ = 1,155€
└── City Break Europe (attr: 30, prix: 300€, niveau 1)
    └── Part: 23% → 2.3 ventes × 300€ = 690€

Total ventes : 1,845€
Commission (10%) : 184.50€ → Gain joueur
```

---

## 🌍 Verticales Voyage

### Catalogue des Verticales

| ID | Nom | Prix Base | Attractivité | Facteur Marge | Coût Déblocage | Segment |
|----|-----|-----------|--------------|---------------|----------------|---------|
| `weekend_france` | Week-end France | 150€ | 100 | 1.08 | Gratuit | Mass Market |
| `citybreak_europe` | City Break Europe | 300€ | 80 | 1.10 | 500€ | Mass Market |
| `sejour_balnéaire` | Séjour Balnéaire | 800€ | 60 | 1.12 | 2,000€ | Mid Market |
| `circuit_culturel` | Circuit Culturel | 1,500€ | 40 | 1.15 | 5,000€ | Mid Market |
| `aventure_trek` | Aventure & Trek | 2,500€ | 25 | 1.18 | 10,000€ | Niche |
| `safari_afrique` | Safari Afrique | 4,000€ | 15 | 1.20 | 25,000€ | Premium |
| `croisiere_luxe` | Croisière de Luxe | 6,000€ | 10 | 1.22 | 50,000€ | Premium |
| `resort_maldives` | Resort Maldives | 8,000€ | 6 | 1.25 | 100,000€ | Luxe |
| `expedition_polaire` | Expédition Polaire | 12,000€ | 3 | 1.28 | 250,000€ | Ultra Luxe |
| `tour_monde` | Tour du Monde | 20,000€ | 1 | 1.30 | 500,000€ | Ultra Premium |

### Mécaniques des Verticales

**Déblocage** : Payer le coût initial pour activer la verticale (niveau 1)

**Amélioration** : Chaque niveau augmente le prix de vente
```
Prix au niveau N = basePrice × marginGrowthFactor^(N-1)

Exemple Safari niveau 3 :
4,000€ × 1.20² = 4,000€ × 1.44 = 5,760€
```

**Coût d'amélioration** : Croît exponentiellement
```
Coût niveau N = unlockCost × verticalUpgradeGrowthRate^(N-1)

Exemple Safari niveau 3 :
25,000€ × 1.25² = 25,000€ × 1.5625 = 39,062€
```

**Stratégie** : 
- Mass market = Volume élevé, marges faibles
- Premium/Luxe = Volume faible, marges élevées
- Débloquer dilue les parts de marché mais augmente le revenu potentiel

---

## 🛒 Services & Upgrades

### Marketing (Visiteurs Passifs)

Génère des visiteurs automatiquement.

| ID | Nom | Coût Base | Production | Description |
|----|-----|-----------|------------|-------------|
| `flyers` | Flyers | 10€ | 0.1/sec | Distribution locale |
| `seo_basic` | SEO Basic | 50€ | 0.5/sec | Référencement naturel |
| `google_ads` | Google Ads | 200€ | 2/sec | Publicité payante |
| `influencer` | Influenceur | 1,000€ | 10/sec | Marketing d'influence |

> **Scaling** : Coût augmente de 15% à chaque achat (`cost = baseCost × 1.15^owned`)

---

## 📊 Dashboard (MVP)

### Métriques Affichées

| Métrique | Description |
|----------|-------------|
| 💰 **Argent** | Solde actuel (en €, centimes stockés) |
| 👥 **Visiteurs totaux** | Compteur cumulé depuis le début |
| 📈 **Visiteurs/sec** | Production passive actuelle |
| 🛒 **Ventes totales** | Nombre de ventes déclenchées |
| 💵 **Commission/lot** | Revenu estimé par lot de visiteurs |
| 🌍 **Verticales actives** | X/10 verticales débloquées |
| 📊 **Répartition marché** | Barres de progression par verticale |

### Layout Proposé (MVP)

```
┌─────────────────────────────────────────────────────────────┐
│  🌍 Travel Clicker                            💰 1,234.56€  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│    ┌─────────────────────────────────────────────────┐      │
│    │                                                 │      │
│    │            [ 🖱️ CLIQUEZ ICI ]                  │      │
│    │                                                 │      │
│    │         👥 12,456 visiteurs totaux              │      │
│    │         📈 2.5 visiteurs/sec                    │      │
│    │         🛒 124 ventes (prochain lot: 67/100)    │      │
│    │                                                 │      │
│    │   📊 Répartition du marché                      │      │
│    │   🏖️ Week-end France ████████░░ 77%            │      │
│    │   🏙️ City Break      ██░░░░░░░░ 23%            │      │
│    │                                                 │      │
│    └─────────────────────────────────────────────────┘      │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  MARKETING                    │  VERTICALES VOYAGE          │
│  ─────────────────────────    │  ─────────────────────────  │
│  📢 Flyers (x3)      15€      │  🏖️ Week-end Niv.2   625€   │
│  🔍 SEO Basic (x1)   58€      │  🏙️ City Break Niv.1 500€   │
│  📱 Google Ads (x0)  200€     │  🔒 Balnéaire       2000€   │
│  ⭐ Influenceur (x0) 1000€    │  🔒 Safari         25000€   │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 Technical Mapping

### Config YAML → Game Mechanics

```yaml
# config/game/formulas.yaml
formulas:
  cost_growth_rate: 1.15              # Coefficient multiplicateur des prix marketing
  visitors_per_click: 1               # Visiteurs gagnés par clic
  sale_trigger_threshold: 100         # Visiteurs nécessaires pour 1 lot
  conversion_rate: 0.10               # 10% conversion visiteurs → acheteurs
  base_commission_rate: 0.10          # 10% de commission
  vertical_upgrade_growth_rate: 1.25  # Croissance coût amélioration verticales
  tick_interval_ms: 100               # Intervalle de calcul (ms)
```

```yaml
# config/game/buildings.yaml
buildings:
  marketing:
    - id: flyers
      name: "Flyers"
      base_cost: 1000              # 10€ en centimes
      production: 0.1              # visiteurs/sec
      icon: "📢"
    # ...
```

```yaml
# config/game/verticals.yaml
verticals:
  - id: weekend_france
    name: "Week-end France"
    description: "Escapades courtes en France"
    icon: "🏖️"
    base_price: 15000              # 150€ en centimes
    attractivity: 100              # Poids pour la répartition
    margin_growth_factor: 1.08     # ×1.08 par niveau
    unlock_cost: 0                 # Gratuit (débloqué au départ)
    
  - id: safari_afrique
    name: "Safari Afrique"
    description: "Big Five et savane"
    icon: "🦁"
    base_price: 400000             # 4,000€ en centimes
    attractivity: 15
    margin_growth_factor: 1.20
    unlock_cost: 2500000           # 25,000€
```

### State Structure

```typescript
interface GameState {
  money: number;                   // En centimes (int)
  totalVisitors: number;           // Compteur cumulé
  visitorsTowardsSale: number;     // Compteur vers prochain lot
  totalSales: number;              // Ventes totales (float, car fractionnel)
  totalRevenue: number;            // Revenu total avant commission
  
  buildings: {
    [buildingId: string]: {
      owned: number;               // Quantité possédée
    }
  };
  
  verticals: {
    [verticalId: string]: {
      level: number;               // 0 = verrouillé, 1+ = actif
    }
  };
  
  // Derivé automatiquement
  totalAttractivity: number;       // Σ(attractivités des verticales actives)
  marketDistribution: Array<{      // Répartition pour affichage
    id: string;
    name: string;
    marketShare: number;           // 0-100%
    currentPrice: number;
  }>;
}
```

---

## 🚀 Roadmap

### Phase 1 - MVP ✅
- [x] Click to earn visitors
- [x] Passive visitor generation (Marketing)
- [x] Sales funnel with conversion
- [x] Verticales voyage system (market share distribution)
- [x] Basic UI with Svelte 5
- [x] Config-driven (YAML → PHP → JSON → Svelte)

### Phase 2 - Enhancements
- [ ] Product upgrades (conversion, commission bonuses)
- [ ] Achievements system
- [ ] Offline earnings calculation
- [ ] Sound effects & animations

### Phase 3 - Persistence & Social
- [ ] User authentication
- [ ] Server-side save (PostgreSQL)
- [ ] Leaderboards (Mercure real-time)

### Phase 4 - Polish
- [ ] Prestige/Reset system
- [ ] Events saisonniers
- [ ] Nouvelles verticales (Espace, Sous-marin, etc.)
