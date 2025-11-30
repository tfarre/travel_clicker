/**
 * GameState - Global reactive state for the game
 *
 * This class manages the game state using Svelte 5's $state rune.
 * It implements the Optimistic UI pattern where actions update immediately
 * and sync with the server in the background.
 *
 * All monetary values are stored in CENTIMES (1€ = 100 centimes).
 *
 * Revenue Model: "Entonnoir de Vente" (Sales Funnel)
 * 1. Visitors → Buyers (conversion rate)
 * 2. Buyers distributed among Verticals by market share (attractivity / total)
 * 3. Each vertical's price increases with level
 * 4. Total revenue = commission on sum of all vertical sales
 */

/**
 * @typedef {Object} BuildingState
 * @property {number} owned - Number of buildings owned
 */

/**
 * @typedef {Object} VerticalState
 * @property {number} level - Current level (0 = locked, 1+ = unlocked)
 */

/**
 * @typedef {Object} VerticalRevenue
 * @property {string} id - Vertical ID
 * @property {string} name - Vertical name
 * @property {string} icon - Emoji icon
 * @property {number} marketShare - Percentage of buyers (0-100)
 * @property {number} sales - Number of sales
 * @property {number} revenue - Revenue in centimes
 * @property {number} currentPrice - Current price at level
 */

/**
 * @typedef {Object} GameConfig
 * @property {Object} formulas - Game formulas and coefficients
 * @property {Array} marketing - Marketing buildings
 * @property {Array} verticals - Travel category verticals
 */

export class GameState {
    // ==========================================
    // Core State (using Svelte 5 $state rune)
    // ==========================================

    /** @type {number} Money in centimes */
    money = $state(0);

    /** @type {number} Total visitors accumulated */
    totalVisitors = $state(0);

    /** @type {number} Visitors counting towards next sale batch */
    visitorsTowardsSale = $state(0);

    /** @type {number} Total sales made (across all verticals) */
    totalSales = $state(0);

    /** @type {number} Total revenue earned (before commission) */
    totalRevenue = $state(0);

    /** @type {Object.<string, BuildingState>} Marketing buildings owned by ID */
    buildings = $state({});

    /** @type {Object.<string, VerticalState>} Verticals state by ID (level) */
    verticals = $state({});

    /** @type {GameConfig|null} Game configuration from server */
    config = $state(null);

    /** @type {VerticalRevenue[]|null} Last sale breakdown (for UI display) */
    lastSaleBreakdown = $state(null);

    // ==========================================
    // Derived State (using Svelte 5 $derived)
    // ==========================================

    /** Money formatted as euros (e.g., "12.34€") */
    moneyFormatted = $derived(this.formatMoney(this.money));

    /** Visitors per second from all marketing buildings */
    visitorsPerSecond = $derived(this.calculateVisitorsPerSecond());

    /** Progress towards next sale batch (0-100) */
    saleProgress = $derived(
        this.config
            ? (this.visitorsTowardsSale / this.config.formulas.saleTriggerThreshold) * 100
            : 0
    );

    /** Total attractivity of all unlocked verticals */
    totalAttractivity = $derived(this.calculateTotalAttractivity());

    /** Number of unlocked verticals */
    unlockedVerticalsCount = $derived(this.countUnlockedVerticals());

    /** Array of unlocked verticals with their market share */
    marketDistribution = $derived(this.calculateMarketDistribution());

    /** Expected revenue per sale batch (preview) */
    expectedRevenuePerBatch = $derived(this.calculateExpectedRevenue());

    // ==========================================
    // Initialization
    // ==========================================

    /**
     * Initialize the game state with configuration
     * @param {GameConfig} config - Game configuration from server
     */
    init(config) {
        this.config = config;

        // Initialize marketing building state
        const buildingsState = {};
        for (const building of config.marketing) {
            buildingsState[building.id] = { owned: 0 };
        }
        this.buildings = buildingsState;

        // Initialize verticals state
        const verticalsState = {};
        for (const vertical of config.verticals) {
            // Verticals with unlockCost = 0 start at level 1 (unlocked)
            verticalsState[vertical.id] = {
                level: vertical.unlockCost === 0 ? 1 : 0
            };
        }
        this.verticals = verticalsState;

        // Give starting money (100€ = 10000 centimes)
        this.money = 10000;
    }

    // ==========================================
    // Actions
    // ==========================================

    /**
     * Handle a manual click to generate visitors
     */
    click() {
        if (!this.config) return;

        const visitors = this.config.formulas.visitorsPerClick;
        this.addVisitors(visitors);
    }

    /**
     * Add visitors and check for sale batches
     * @param {number} count - Number of visitors to add
     */
    addVisitors(count) {
        if (!this.config) return;

        this.totalVisitors += count;
        this.visitorsTowardsSale += count;

        // Check if we've reached the sale threshold
        const threshold = this.config.formulas.saleTriggerThreshold;

        while (this.visitorsTowardsSale >= threshold) {
            this.visitorsTowardsSale -= threshold;
            this.processSaleBatch(threshold);
        }
    }

    /**
     * Process a batch of visitors through the sales funnel
     * @param {number} visitors - Number of visitors in this batch
     */
    processSaleBatch(visitors) {
        if (!this.config) return;
        if (this.totalAttractivity === 0) return; // No unlocked verticals

        // Step 1: Convert visitors to buyers
        const buyers = visitors * this.config.formulas.conversionRate;

        // Step 2: Distribute buyers among verticals and calculate revenue
        const breakdown = this.calculateRevenueDistribution(buyers);

        // Step 3: Sum up total revenue and apply commission
        let totalSaleValue = 0;
        let totalSalesCount = 0;

        for (const item of breakdown) {
            totalSaleValue += item.revenue;
            totalSalesCount += item.sales;
        }

        const commission = Math.floor(totalSaleValue * this.config.formulas.baseCommissionRate);

        // Update state
        this.totalSales += totalSalesCount;
        this.totalRevenue += totalSaleValue;
        this.money += commission;
        this.lastSaleBreakdown = breakdown;
    }

    /**
     * Attempt to buy a marketing building
     * @param {string} buildingId - ID of the building to buy
     * @returns {boolean} Whether the purchase was successful
     */
    buyBuilding(buildingId) {
        if (!this.config) return false;

        const building = this.config.marketing.find(b => b.id === buildingId);
        if (!building) return false;

        const cost = this.getBuildingCost(buildingId);

        if (this.money < cost) {
            return false;
        }

        this.money -= cost;
        this.buildings[buildingId].owned += 1;

        return true;
    }

    /**
     * Attempt to unlock or upgrade a vertical
     * @param {string} verticalId - ID of the vertical
     * @returns {boolean} Whether the action was successful
     */
    upgradeVertical(verticalId) {
        if (!this.config) return false;

        const vertical = this.config.verticals.find(v => v.id === verticalId);
        if (!vertical) return false;

        const currentLevel = this.verticals[verticalId]?.level ?? 0;
        const cost = this.getVerticalUpgradeCost(verticalId);

        if (this.money < cost) {
            return false;
        }

        this.money -= cost;
        this.verticals[verticalId].level = currentLevel + 1;

        return true;
    }

    /**
     * Process one game tick (called every tickIntervalMs)
     */
    tick() {
        if (!this.config) return;

        const ticksPerSecond = 1000 / this.config.formulas.tickIntervalMs;
        const visitorsThisTick = this.visitorsPerSecond / ticksPerSecond;

        if (visitorsThisTick > 0) {
            this.addVisitors(visitorsThisTick);
        }
    }

    // ==========================================
    // Revenue Distribution Calculations
    // ==========================================

    /**
     * Calculate revenue distribution among unlocked verticals
     * @param {number} buyers - Number of buyers to distribute
     * @returns {VerticalRevenue[]}
     */
    calculateRevenueDistribution(buyers) {
        if (!this.config) return [];

        const result = [];
        const totalAttr = this.totalAttractivity;

        if (totalAttr === 0) return [];

        for (const vertical of this.config.verticals) {
            const level = this.verticals[vertical.id]?.level ?? 0;

            if (level === 0) continue; // Skip locked verticals

            // Market share = attractivity / total
            const marketShare = vertical.attractivity / totalAttr;

            // Sales = buyers × market share
            const sales = buyers * marketShare;

            // Current price = basePrice × (marginGrowthFactor ^ (level - 1))
            const currentPrice = Math.floor(
                vertical.basePrice * Math.pow(vertical.marginGrowthFactor, level - 1)
            );

            // Revenue = sales × currentPrice
            const revenue = Math.floor(sales * currentPrice);

            result.push({
                id: vertical.id,
                name: vertical.name,
                icon: vertical.icon,
                marketShare: marketShare * 100,
                sales,
                revenue,
                currentPrice
            });
        }

        return result;
    }

    /**
     * Calculate total attractivity of unlocked verticals
     * @returns {number}
     */
    calculateTotalAttractivity() {
        if (!this.config) return 0;

        let total = 0;
        for (const vertical of this.config.verticals) {
            const level = this.verticals[vertical.id]?.level ?? 0;
            if (level > 0) {
                total += vertical.attractivity;
            }
        }
        return total;
    }

    /**
     * Count unlocked verticals
     * @returns {number}
     */
    countUnlockedVerticals() {
        if (!this.config) return 0;

        let count = 0;
        for (const vertical of this.config.verticals) {
            const level = this.verticals[vertical.id]?.level ?? 0;
            if (level > 0) count++;
        }
        return count;
    }

    /**
     * Calculate market distribution for UI display
     * @returns {Array}
     */
    calculateMarketDistribution() {
        if (!this.config) return [];

        const totalAttr = this.totalAttractivity;
        if (totalAttr === 0) return [];

        return this.config.verticals
            .filter(v => (this.verticals[v.id]?.level ?? 0) > 0)
            .map(v => ({
                id: v.id,
                name: v.name,
                icon: v.icon,
                marketShare: (v.attractivity / totalAttr) * 100,
                level: this.verticals[v.id]?.level ?? 0,
                currentPrice: this.getVerticalCurrentPrice(v.id)
            }));
    }

    /**
     * Calculate expected revenue per sale batch (for preview)
     * @returns {number}
     */
    calculateExpectedRevenue() {
        if (!this.config) return 0;

        const buyers = this.config.formulas.saleTriggerThreshold * this.config.formulas.conversionRate;
        const breakdown = this.calculateRevenueDistribution(buyers);

        let total = 0;
        for (const item of breakdown) {
            total += item.revenue;
        }

        return Math.floor(total * this.config.formulas.baseCommissionRate);
    }

    // ==========================================
    // Cost Calculations
    // ==========================================

    /**
     * Calculate total visitors per second from marketing buildings
     * @returns {number}
     */
    calculateVisitorsPerSecond() {
        if (!this.config) return 0;

        let total = 0;
        for (const building of this.config.marketing) {
            const owned = this.buildings[building.id]?.owned ?? 0;
            total += building.production * owned;
        }
        return total;
    }

    /**
     * Get the current cost of a marketing building
     * @param {string} buildingId
     * @returns {number} Cost in centimes
     */
    getBuildingCost(buildingId) {
        if (!this.config) return 0;

        const building = this.config.marketing.find(b => b.id === buildingId);
        if (!building) return 0;

        const owned = this.buildings[buildingId]?.owned ?? 0;
        return Math.floor(building.baseCost * Math.pow(this.config.formulas.costGrowthRate, owned));
    }

    /**
     * Get the upgrade cost for a vertical (unlock or level up)
     * @param {string} verticalId
     * @returns {number} Cost in centimes
     */
    getVerticalUpgradeCost(verticalId) {
        if (!this.config) return 0;

        const vertical = this.config.verticals.find(v => v.id === verticalId);
        if (!vertical) return 0;

        const level = this.verticals[verticalId]?.level ?? 0;

        if (level === 0) {
            // Unlock cost
            return vertical.unlockCost;
        }

        // Upgrade cost: unlockCost × (upgradeGrowthRate ^ level)
        return Math.floor(
            vertical.unlockCost * Math.pow(this.config.formulas.verticalUpgradeGrowthRate, level)
        );
    }

    /**
     * Get the current price of a vertical at its level
     * @param {string} verticalId
     * @returns {number} Price in centimes
     */
    getVerticalCurrentPrice(verticalId) {
        if (!this.config) return 0;

        const vertical = this.config.verticals.find(v => v.id === verticalId);
        if (!vertical) return 0;

        const level = this.verticals[verticalId]?.level ?? 0;
        if (level === 0) return 0;

        return Math.floor(
            vertical.basePrice * Math.pow(vertical.marginGrowthFactor, level - 1)
        );
    }

    /**
     * Check if player can afford a building
     * @param {string} buildingId
     * @returns {boolean}
     */
    canAffordBuilding(buildingId) {
        return this.money >= this.getBuildingCost(buildingId);
    }

    /**
     * Check if player can afford a vertical upgrade
     * @param {string} verticalId
     * @returns {boolean}
     */
    canAffordVertical(verticalId) {
        return this.money >= this.getVerticalUpgradeCost(verticalId);
    }

    /**
     * Check if a vertical is unlocked
     * @param {string} verticalId
     * @returns {boolean}
     */
    isVerticalUnlocked(verticalId) {
        return (this.verticals[verticalId]?.level ?? 0) > 0;
    }

    // ==========================================
    // Helpers
    // ==========================================

    /**
     * Format centimes as euros
     * @param {number} centimes
     * @returns {string}
     */
    formatMoney(centimes) {
        const euros = centimes / 100;
        return euros.toLocaleString('fr-FR', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

// Create singleton instance for global state
export const gameState = new GameState();
