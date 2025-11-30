# 🎮 Travel Clicker - Game Design Document (MVP)

## Concept

**Travel Clicker** est un **idle game** inspiré de Cookie Clicker, où le joueur développe une **marketplace de voyages**.

L'objectif : attirer des visiteurs, générer des ventes, et réinvestir les commissions pour faire croître son business.

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
│         ├── Dashboard (stats, argent, visiteurs)            │
│         └── Shop (achats Marketing & Partenaires)           │
└─────────────────────────────────────────────────────────────┘
```

### Core Features (MVP)

| Feature | Description | Priorité |
|---------|-------------|----------|
| **Click Button** | Génère 1 visiteur par clic | P0 |
| **Passive Visitors** | Achat d'options marketing pour visiteurs automatiques | P0 |
| **Sales Trigger** | Vente déclenchée tous les X visiteurs | P0 |
| **Commission System** | Gain = % commission × panier moyen | P0 |
| **Partner Sourcing** | Investir pour augmenter le panier moyen | P0 |
| **Persistence** | Sauvegarde locale (localStorage) | P1 |

---

## 💰 Game Loop

### Boucle Principale

```
     ┌────────────────────────────────────────────────────────┐
     │                                                        │
     ▼                                                        │
┌─────────┐    ┌───────────┐    ┌─────────┐    ┌──────────┐  │
│  CLIC   │───▶│ VISITEURS │───▶│  VENTE  │───▶│  ARGENT  │──┘
│(manuel) │    │           │    │(auto)   │    │(commission)
└─────────┘    └───────────┘    └─────────┘    └──────────┘
     ▲              ▲                               │
     │              │                               │
     │         ┌────┴────┐                         │
     │         │MARKETING│◀────────────────────────┤
     │         │(passif) │      Investissement     │
     │         └─────────┘                         │
     │                                             │
     │         ┌─────────┐                         │
     └─────────│PARTENAIRES│◀──────────────────────┘
               │(panier+)  │    Investissement
               └───────────┘
```

### Formules de Base

| Métrique | Formule | Valeur Initiale |
|----------|---------|-----------------|
| **Visiteurs par clic** | `1 + bonusClick` | 1 |
| **Visiteurs passifs** | `Σ(marketing.production)` | 0/sec |
| **Seuil de vente** | Tous les `X` visiteurs | 100 visiteurs |
| **Taux de conversion** | `baseConversion + bonusConversion` | 0.1% (0.001) |
| **Panier moyen** | `basePanier + Σ(partenaires.value)` | 50€ |
| **Commission** | `baseCommission` | 10% (0.10) |
| **Gain par vente** | `panierMoyen × commission` | 5€ |

### Exemple de Calcul

```
Visiteurs accumulés : 100 → Déclenche 1 vente
Panier moyen : 50€ (base) + 30€ (partenaires) = 80€
Commission : 10%
Gain : 80€ × 10% = 8€
```

---

## 🛒 Services & Upgrades

### 1. Marketing (Visiteurs Passifs)

Génère des visiteurs automatiquement.

| Nom | Coût Base | Production | Description |
|-----|-----------|------------|-------------|
| SEO Basic | 50€ | 0.5 visiteur/sec | Référencement naturel |
| Google Ads | 200€ | 2 visiteurs/sec | Publicité payante |

> **Scaling** : Coût augmente de 15% à chaque achat (`cost = baseCost × 1.15^owned`)

### 2. Partenaires (Panier Moyen)

Augmente la valeur du panier moyen.

| Nom | Coût Base | Bonus Panier | Description |
|-----|-----------|--------------|-------------|
| Gîte Rural | 100€ | +10€ | Hébergement économique |
| Hôtel 3★ | 500€ | +30€ | Hébergement standard |

### 3. Produit (Bonus) — *Phase 2*

Améliore les métriques globales.

| Nom | Coût | Effet | Description |
|-----|------|-------|-------------|
| UX Refonte | 500€ | +0.05% conversion | Meilleure interface |

---

## 📊 Dashboard (MVP)

### Métriques Affichées

| Métrique | Description |
|----------|-------------|
| 💰 **Argent** | Solde actuel (en €, centimes stockés) |
| 👥 **Visiteurs totaux** | Compteur cumulé depuis le début |
| 📈 **Visiteurs/sec** | Production passive actuelle |
| 🛒 **Ventes totales** | Nombre de ventes déclenchées |
| 🎯 **Prochain achat** | Progress bar vers prochaine vente |

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
│    │         🛒 124 ventes (prochaine: 67/100)       │      │
│    │                                                 │      │
│    └─────────────────────────────────────────────────┘      │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  MARKETING                    │  PARTENAIRES                │
│  ─────────────────────────    │  ─────────────────────────  │
│  📢 Flyers (x3)      15€      │  🏠 Gîte Rural (x2)  132€   │
│  🔍 SEO Basic (x1)   58€      │  🏨 Hôtel 3★ (x0)    500€   │
│  📱 Google Ads (x0)  200€     │  🏝️ Resort 4★ (x0)  2000€  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 Technical Mapping

### Config YAML → Game Mechanics

```yaml
# config/game/formulas.yaml
formulas:
  cost_growth_rate: 1.15          # Coefficient multiplicateur des prix
  visitors_per_click: 1           # Visiteurs gagnés par clic
  sale_trigger_threshold: 100     # Visiteurs nécessaires pour 1 vente
  base_conversion_rate: 0.001     # 0.1% de conversion
  base_commission_rate: 0.10      # 10% de commission
  base_cart_value: 5000           # 50€ en centimes
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

  partners:
    - id: gite_rural
      name: "Gîte Rural"
      base_cost: 10000             # 100€ en centimes
      cart_bonus: 1000             # +10€ au panier moyen
      icon: "🏠"
    # ...
```

### State Structure

```typescript
interface GameState {
  money: number;              // En centimes (int)
  totalVisitors: number;      // Compteur cumulé
  visitorsTowardsSale: number; // Compteur vers prochaine vente
  totalSales: number;         // Ventes totales
  
  buildings: {
    [buildingId: string]: {
      owned: number;          // Quantité possédée
    }
  };
  
  stats: {
    visitorsPerSecond: number; // Calculé à partir des buildings
    cartValue: number;         // Calculé à partir des partenaires
  };
}
```

---

## 🚀 Roadmap

### Phase 1 - MVP ✅
- [ ] Click to earn visitors
- [ ] Passive visitor generation (Marketing)
- [ ] Sales trigger system
- [ ] Partner system (cart value)
- [ ] Basic UI with Svelte 5
- [ ] Config-driven (YAML → PHP → JSON → Svelte)

### Phase 2 - Product Service
- [ ] Product upgrades (conversion, commission bonuses)
- [ ] Achievements system
- [ ] Offline earnings calculation

### Phase 3 - Persistence & Social
- [ ] User authentication
- [ ] Server-side save (PostgreSQL)
- [ ] Leaderboards (Mercure real-time)

### Phase 4 - Polish
- [ ] Animations & sounds
- [ ] Prestige/Reset system
- [ ] Events saisonniers
