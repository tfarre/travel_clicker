<?php

declare(strict_types=1);

/**
 * UserInterface Layer - Controllers & API Endpoints
 *
 * This layer contains:
 * - HTTP Controllers
 * - API Endpoints
 * - Request/Response handling
 * - Input Validation
 *
 * Rules:
 * - Thin controllers only (HTTP glue)
 * - Deserialize request → dispatch Command → return JSON response
 * - NEVER use EntityManager directly
 */
